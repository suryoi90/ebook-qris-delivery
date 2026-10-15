import { NextResponse } from 'next/server';
import { isUserAdmin } from '../../../../lib/auth.js';
import { getOrders, getStats, getOrder, updateOrderStatus } from '../../../../lib/db.js';
import { fulfillOrder } from '../../../../lib/fulfillment.js';

export const runtime = 'nodejs';

export async function GET() {
  if (!(await isUserAdmin())) {
    return NextResponse.json({ success: false, message: 'Unauthorized' }, { status: 401 });
  }

  const orders = getOrders();
  const stats = getStats();
  return NextResponse.json({ success: true, orders, stats });
}

export async function POST(request) {
  if (!(await isUserAdmin())) {
    return NextResponse.json({ success: false, message: 'Unauthorized' }, { status: 401 });
  }

  try {
    const body = await request.json();
    const { action, invoice_number } = body;

    if (!invoice_number) {
      return NextResponse.json({ success: false, message: 'Nomor invoice dibutuhkan.' }, { status: 400 });
    }

    const order = getOrder(invoice_number);
    if (!order) {
      return NextResponse.json({ success: false, message: 'Pesanan tidak ditemukan.' }, { status: 404 });
    }

    if (action === 'resend') {
      await fulfillOrder(invoice_number);
      return NextResponse.json({ success: true, message: `E-Book berhasil dikirim ulang ke ${order.customer_email}.` });
    }

    if (action === 'mark_paid') {
      updateOrderStatus(invoice_number, {
        status: 'PAID',
        paid_at: new Date().toISOString(),
        manual_override: true
      });
      await fulfillOrder(invoice_number);
      return NextResponse.json({ success: true, message: `Pesanan ${invoice_number} ditandai lunas dan ebook berhasil dikirim.` });
    }

    return NextResponse.json({ success: false, message: 'Action tidak dikenal.' }, { status: 400 });
  } catch (error) {
    console.error('Admin order action error:', error);
    return NextResponse.json({ success: false, message: error.message || 'Gagal memproses aksi.' }, { status: 500 });
  }
}
