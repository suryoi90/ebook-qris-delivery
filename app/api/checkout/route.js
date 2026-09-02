import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const { nama, email, organisasi, productId, productTitle, amount } = await request.json();
    const orderId = 'EBOOK-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    const grossAmount = Number(amount) || 50000;

    const cleanProductName = (productTitle || 'E-Book')
      .replace(/[\[\]\(\)]/g, '')
      .substring(0, 45);

    const serverKey = Buffer.from('TWlkLXNlcnZlci1TSnRUaWRta093VUxFbjJsZWdWVnBtbVc=', 'base64').toString('utf8');
    const authHeader = Buffer.from(serverKey + ':').toString('base64');

    const payload = {
      transaction_details: {
        order_id: orderId,
        gross_amount: grossAmount,
      },
      customer_details: {
        first_name: (nama || 'Pembeli').substring(0, 50),
        email: email,
      },
      item_details: [
        {
          id: (productId || 'ebook-1').substring(0, 50),
          price: grossAmount,
          quantity: 1,
          name: cleanProductName,
        }
      ],
      custom_field1: nama,
      custom_field2: email,
      custom_field3: organisasi || '-',
    };

    const res = await fetch('https://app.midtrans.com/snap/v1/transactions', {
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