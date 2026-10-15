import { NextResponse } from 'next/server';
import fs from 'node:fs';
import path from 'node:path';
import { isUserAdmin } from '../../../../lib/auth.js';
import { PRODUCTS } from '../../../../lib/products.js';

export const runtime = 'nodejs';

const EBOOKS_DIR = path.join(process.cwd(), 'storage', 'ebooks');

function ensureDir() {
  if (!fs.existsSync(EBOOKS_DIR)) {
    fs.mkdirSync(EBOOKS_DIR, { recursive: true });
  }
}

export async function GET() {
  if (!(await isUserAdmin())) {
    return NextResponse.json({ success: false, message: 'Unauthorized' }, { status: 401 });
  }

  ensureDir();
  const list = Object.entries(PRODUCTS).map(([id, product]) => {
    const filePath = path.join(EBOOKS_DIR, `${id}.pdf`);
    const exists = fs.existsSync(filePath);
    let sizeBytes = 0;
    let modifiedAt = null;

    if (exists) {
      const stat = fs.statSync(filePath);
      sizeBytes = stat.size;
      modifiedAt = stat.mtime;
    }

    return {
      id,
      title: product.title,
      amount: product.amount,
      hasFile: exists,
      sizeBytes,
      sizeMb: exists ? (sizeBytes / (1024 * 1024)).toFixed(2) : '0',
      modifiedAt
    };
  });

  return NextResponse.json({ success: true, ebooks: list });
}

export async function POST(request) {
  if (!(await isUserAdmin())) {
    return NextResponse.json({ success: false, message: 'Unauthorized' }, { status: 401 });
  }

  try {
    ensureDir();
    const formData = await request.formData();
    const productId = String(formData.get('productId') || '');
    const file = formData.get('file');

    if (!productId || !PRODUCTS[productId]) {
      return NextResponse.json({ success: false, message: 'Produk tidak valid.' }, { status: 400 });
    }

    if (!file || typeof file.arrayBuffer !== 'function') {
      return NextResponse.json({ success: false, message: 'File PDF belum dipilih.' }, { status: 400 });
    }

    const buffer = Buffer.from(await file.arrayBuffer());
    if (buffer.length < 8 || buffer.subarray(0, 5).toString('ascii') !== '%PDF-') {
      return NextResponse.json({ success: false, message: 'Format berkas harus berupa dokumen PDF yang valid.' }, { status: 400 });
    }

    const destination = path.join(EBOOKS_DIR, `${productId}.pdf`);
    fs.writeFileSync(destination, buffer);

    return NextResponse.json({
      success: true,
      message: `File master e-book untuk "${PRODUCTS[productId].title}" berhasil di-upload (${(buffer.length / (1024 * 1024)).toFixed(2)} MB).`
    });
  } catch (error) {
    console.error('Upload ebook error:', error);
    return NextResponse.json({ success: false, message: error.message || 'Gagal menyimpan file PDF.' }, { status: 500 });
  }
}
