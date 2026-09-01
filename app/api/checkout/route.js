import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const { nama, email, organisasi } = await request.json();
    const orderId = 'EBOOK-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    const grossAmount = 150000;

    const serverKey = process.env.MIDTRANS_SERVER_KEY;
    const authHeader = Buffer.from(serverKey + ':').toString('base64');

    const midtransRes = await fetch('https://api.midtrans.com/v2/charge', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Basic ' + authHeader,
      },
      body: JSON.stringify({
        payment_type: 'qris',
        transaction_details: {
          order_id: orderId,
          gross_amount: grossAmount,
        },
        customer_details: {
          first_name: nama,
          email: email,
        },
        custom_field1: nama,
        custom_field2: email,
        custom_field3: organisasi || '-',
      }),
    });

    const data = await midtransRes.json();
    if (data.status_code && data.status_code !== '201') {
      throw new Error(data.status_message || 'Gagal membuat QRIS di Midtrans');
    }

    const qrUrl = data.actions?.find((a) => a.name === 'generate-qr-code')?.url;

    return NextResponse.json({
      success: true,
      orderId,
      grossAmount,
      qrUrl,
    });
  } catch (error) {
    return NextResponse.json({ success: false, message: error.message }, { status: 500 });
  }
}