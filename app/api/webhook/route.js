import { NextResponse } from 'next/server';
import crypto from 'crypto';

export async function POST(request) {
  try {
    const notification = await request.json();
    const { order_id, status_code, gross_amount, signature_key, transaction_status } = notification;

    const serverKey = process.env.MIDTRANS_SERVER_KEY;
    const hash = crypto
      .createHash('sha512')
      .update(order_id + status_code + gross_amount + serverKey)
      .digest('hex');

    if (hash !== signature_key) {
      return NextResponse.json({ message: 'Invalid Signature' }, { status: 400 });
    }

    if (transaction_status === 'settlement' || transaction_status === 'capture') {
      const nama = notification.custom_field1;
      const email = notification.custom_field2;
      const organisasi = notification.custom_field3;

      const gasUrl = process.env.GOOGLE_SCRIPT_URL;
      if (gasUrl) {
        await fetch(gasUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ nama, email, organisasi, orderId: order_id }),
          redirect: 'follow',
        });
      }
    }

    return NextResponse.json({ status: 'OK' });
  } catch (error) {
    return NextResponse.json({ message: error.message }, { status: 500 });
  }
}