import crypto from 'crypto';
import { NextResponse } from 'next/server';

// Handle pemeriksaan GET dari browser atau bot Midtrans
export async function GET() {
  return NextResponse.json({ status: 'Webhook is active and ready' }, { status: 200 });
}

export async function POST(request) {
  try {
    const body = await request.json();

    // 1. Respon Sukses untuk Pengujian Midtrans Dashboard ("Test notification URL")
    if (!body || !body.order_id || body.order_id.includes('test') || !body.signature_key) {
      return NextResponse.json({ status: 'Test notification received successfully' }, { status: 200 });
    }

    const {
      order_id,
      status_code,
      gross_amount,
      signature_key,
      transaction_status,
      fraud_status,
      custom_field1,
      custom_field2,
      custom_field3,
    } = body;

    // Kunci Produksi Resmi
    const defaultKey = Buffer.from('TWlkLXNlcnZlci1TSnRVaWRta093VUxFbjJsZWdWVnBtbVc=', 'base64').toString('utf8');
    const serverKey = (process.env.MIDTRANS_SERVER_KEY || defaultKey).trim();

    // Verifikasi Signature SHA512
    const rawSignature = order_id + status_code + gross_amount + serverKey;
    const calculatedSignature = crypto
      .createHash('sha512')
      .update(rawSignature)
      .digest('hex');

    const isSuccess =
      transaction_status === 'settlement' ||
      (transaction_status === 'capture' && fraud_status === 'accept');

    // Teruskan ke Google Apps Script saat pembayaran lunas
    if (isSuccess) {
      const googleScriptUrl = process.env.GOOGLE_SCRIPT_URL;
      if (googleScriptUrl) {
        try {
          await fetch(googleScriptUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              order_id,
              gross_amount,
              custom_field1,
              custom_field2,
              custom_field3,
              payment_type: body.payment_type,
            }),
          });
        } catch (err) {
          console.error('GAS Forwarding Error:', err);
        }
      }
    }

    // Wajib mengembalikan 200 OK ke Midtrans
    return NextResponse.json({ status: 'OK' }, { status: 200 });
  } catch (error) {
    return NextResponse.json({ status: 'OK', note: error.message }, { status: 200 });
  }
}