import { NextResponse } from 'next/server';
import crypto from 'crypto';

export async function POST(request) {
  try {
    const { nama, email, organisasi, productId, productTitle, amount } = await request.json();
    const invoiceNumber = 'INV-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    const grossAmount = Number(amount) || 50000;

    const clientId = Buffer.from('QlJOLTAyMzctMTc4OTQ3MDQ0OTA0OQ==', 'base64').toString('utf8');
    const secretKey = Buffer.from('U0stTTdFZWlRZ2tYaGhCSjN5ZXdwbFo=', 'base64').toString('utf8');

    const cleanProductName = (productTitle || 'E-Book')
      .replace(/[\[\]\(\)]/g, '')
      .substring(0, 45);

    const payload = {
      order: {
        invoice_number: invoiceNumber,
        amount: grossAmount,
        line_items: [
          {
            name: cleanProductName,
            price: grossAmount,
            quantity: 1,
          }
        ]
      },
      payment: {
        payment_due_date: 60,
      },
      customer: {
        name: (nama || 'Pembeli').substring(0, 50),
        email: email,
      },
      additional_info: {
        organisasi: organisasi || '-',
        product_title: productTitle || 'E-Book',
        nama: nama || 'Pembeli',
        email: email
      }
    };

    const payloadStr = JSON.stringify(payload);
    const digest = crypto.createHash('sha256').update(payloadStr, 'utf8').digest('base64');
    const requestId = 'REQ-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    const requestTimestamp = new Date().toISOString().slice(0, 19) + 'Z';
    const targetPath = '/checkout/v1/payment';

    const signatureComponent =
      'Client-Id:' + clientId + '\n' +
      'Request-Id:' + requestId + '\n' +
      'Request-Timestamp:' + requestTimestamp + '\n' +
      'Request-Target:' + targetPath + '\n' +
      'Digest:' + digest;

    const signature = 'HMACSHA256=' + crypto.createHmac('sha256', secretKey).update(signatureComponent).digest('base64');

    const dokuRes = await fetch('https://api.doku.com' + targetPath, {
      method: 'POST',
      headers: {
        'Client-Id': clientId,
        'Request-Id': requestId,
        'Request-Timestamp': requestTimestamp,
        'Signature': signature,
        'Content-Type': 'application/json',
      },
      body: payloadStr,
    });

    const data = await dokuRes.json();
    const paymentUrl = data.response?.payment?.url;

    if (paymentUrl) {
      return NextResponse.json({
        success: true,
        paymentUrl: paymentUrl,
        invoiceNumber: invoiceNumber,
      });
    }

    throw new Error(data.error?.message || JSON.stringify(data));
  } catch (error) {
    return NextResponse.json({ success: false, message: error.message }, { status: 500 });
  }
}