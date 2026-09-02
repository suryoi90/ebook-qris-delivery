import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const { nama, email, organisasi, productId, productTitle, amount } = await request.json();
    const orderId = 'EBOOK-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    const grossAmount = Number(amount) || 50000;

    const serverKey = (process.env.MIDTRANS_SERVER_KEY || '').trim();
    const authHeader = Buffer.from(serverKey + ':').toString('base64');

    const payload = {
      payment_type: 'qris',
      transaction_details: {
        order_id: orderId,
        gross_amount: grossAmount,
      },
      customer_details: {
        first_name: nama,
        email: email,
      },
      item_details: [
        {
          id: productId || 'ebook-1',
          price: grossAmount,
          quantity: 1,
          name: (productTitle || 'E-Book').substring(0, 50),
        }
      ],
      custom_field1: nama,
      custom_field2: email,
      custom_field3: organisasi || '-',
    };

    const endpoints = [
      'https://api.sandbox.midtrans.com/v2/charge',
      'https://api.midtrans.com/v2/charge'
    ];

    let lastError = null;
    for (const url of endpoints) {
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Basic ' + authHeader,
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        
        if (data.status_code === '201') {
          const qrUrl = data.actions?.find((a) => a.name === 'generate-qr-code')?.url;
          return NextResponse.json({
            success: true,
            orderId,
            grossAmount,
            qrUrl,
          });
        }
        lastError = data.status_message || JSON.stringify(data);
      } catch (err) {
        lastError = err.message;
      }
    }

    throw new Error(lastError || 'Gagal memproses QRIS ke Midtrans');
  } catch (error) {
    return NextResponse.json({ success: false, message: error.message }, { status: 500 });
  }
}