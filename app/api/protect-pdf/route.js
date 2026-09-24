import { NextResponse } from 'next/server';
import { randomBytes, timingSafeEqual } from 'node:crypto';
import { PDFDocument, StandardFonts, rgb } from '@cantoo/pdf-lib';

export const runtime = 'nodejs';
export const maxDuration = 60;

function matchesSecret(a, b) {
  const left = Buffer.from(String(a || ''));
  const right = Buffer.from(String(b || ''));
  return left.length === right.length && left.length > 0 && timingSafeEqual(left, right);
}

export async function POST(request) {
  const configuredSecret = process.env.PDF_PROTECT_SECRET;
  if (!configuredSecret || !matchesSecret(request.headers.get('x-pdf-protect-secret'), configuredSecret)) {
    return NextResponse.json({ message: 'Unauthorized' }, { status: 401 });
  }
  const contentLength = Number(request.headers.get('content-length') || 0);
  if (contentLength > 3400000) return NextResponse.json({ message: 'PDF melebihi batas ukuran 2,4 MB.' }, { status: 413 });

  try {
    const input = await request.json();
    const encoded = String(input.pdf_base64 || '');
    const password = String(input.password || '');
    if (!encoded || encoded.length > 3200000 || password.length < 10 || password.length > 100) {
      return NextResponse.json({ message: 'Data PDF atau password tidak valid.' }, { status: 400 });
    }
    const source = Buffer.from(encoded, 'base64');
    if (source.length < 8 || source.subarray(0, 5).toString('ascii') !== '%PDF-') {
      return NextResponse.json({ message: 'Berkas yang diterima bukan PDF.' }, { status: 400 });
    }

    const pdf = await PDFDocument.load(source);
    const font = await pdf.embedFont(StandardFonts.Helvetica);
    const text = 'Lisensi: ' + String(input.name || '').slice(0, 80) + ' | ' + String(input.email || '').slice(0, 120) + ' | ID: ' + String(input.license_id || '').slice(0, 24);
    for (const page of pdf.getPages()) {
      const { width } = page.getSize();
      page.drawText(text, {
        x: 24, y: 18, size: 7, font, color: rgb(0.35, 0.4, 0.48), opacity: 0.8,
        maxWidth: Math.max(100, width - 48),
      });
    }
    pdf.encrypt({
      userPassword: password,
      ownerPassword: randomBytes(32).toString('base64url'),
      permissions: { printing: 'lowResolution', copying: false, modifying: false, annotating: false, fillingForms: false, contentAccessibility: true, documentAssembly: false },
    });
    const result = Buffer.from(await pdf.save());
    return NextResponse.json({ pdf_base64: result.toString('base64') }, { headers: { 'Cache-Control': 'no-store' } });
  } catch (error) {
    console.error('PDF protection failed:', error.message);
    return NextResponse.json({ message: 'PDF gagal diberi watermark/password.' }, { status: 422 });
  }
}
