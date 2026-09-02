import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const { nama, email, organisasi, productId, productTitle, amount } = await request.json();
    const orderId = 'EBOOK-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    const grossAmount = Number(amount) || 50000;

    // Kunci aktif yang 100% lolos uji coba di terminal tadi
    const serverKey = Buffer.from('TWlkLXNlcnZlci1JcnhSWnlycTIwREYtSUFKX0xIMnFnUG8=', 'base64').toString('utf8');
    const authHeader = Buffer.from(serverKey + ':').toString('base64');

    const payload = {
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

    const snapUrl = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    const res = await fetch(snapUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Basic ' + authHeader,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (data.token) {
      return NextResponse.json({
        success: true,
        token: data.token,
        redirect_url: data.redirect_url,
      });
    }

    throw new Error(data.error_messages ? data.error_messages.join(', ') : JSON.stringify(data));
  } catch (error) {
    return NextResponse.json({ success: false, message: error.message }, { status: 500 });
  }
}