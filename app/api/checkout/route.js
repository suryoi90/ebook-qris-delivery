import { NextResponse } from 'next/server';
import { randomUUID } from 'node:crypto';
import { PRODUCTS, validateCustomer } from '../../../lib/products.js';

export const runtime = 'nodejs';

function requiredEnv(name) {
  const value = process.env[name];
  if (!value) throw new Error(`Konfigurasi server ${name} belum tersedia.`);
  return value;
}

async function callGoogleScript(data) {
  const url = requiredEnv('GOOGLE_SCRIPT_URL');
  const secret = requiredEnv('GAS_SHARED_SECRET');
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...data, shared_secret: secret }),
    cache: 'no-store',
    redirect: 'follow',
  });
  if (!response.ok) throw new Error('Google Apps Script tidak dapat dihubungi.');
  const result = await response.json();
  if (result.status !== 'success') throw new Error('Google Apps Script menolak pencatatan pesanan.');
  return result;
}

export async function POST(request) {
  try {
    const input = await request.json();
    const product = PRODUCTS[String(input.productId || '')];
    if (!product) return NextResponse.json({ success: false, message: 'Produk tidak ditemukan.' }, { status: 400 });
    const customer = validateCustomer(input);
    const invoiceNumber = `EB-${Date.now()}-${randomUUID().slice(0, 8).toUpperCase()}`;
    const clientId = requiredEnv('DOKU_CLIENT_ID');
    const secretKey = requiredEnv('DOKU_SECRET_KEY');
    const baseUrl = (process.env.DOKU_BASE_URL || 'https://api-sandbox.doku.com').replace(/\/$/, '');
    const appUrl = requiredEnv('APP_BASE_URL').replace(/\/$/, '');
    const notifyUrl = `${appUrl}/api/webhook`;
    const callbackUrl = `${appUrl}/payment/result?invoice=${encodeURIComponent(invoiceNumber)}`;

    // Store trusted purchase details before the customer can pay. The webhook later
    // supplies only a signed invoice/status; Apps Script looks up these stored details.
    await callGoogleScript({
      action: 'register_order', invoice_number: invoiceNumber, amount: product.amount,
      customer_name: customer.name, customer_email: customer.email,
      organization: customer.organization, product_id: input.productId,
      product_title: product.title,
    });

    const payload = {
      order: {
        invoice_number: invoiceNumber,
        amount: product.amount,
        callback_url: callbackUrl,
        callback_url_result: callbackUrl,
        auto_redirect: true,
        line_items: [{ name: product.title.replace(/[\[\]()]/g, '').slice(0, 45), price: product.amount, quantity: 1 }],
      },
      payment: { payment_due_date: 60 },
      customer: { name: customer.name.slice(0, 50), email: customer.email },
      additional_info: { product_id: input.productId, override_notification_url: notifyUrl },
    };
    const body = JSON.stringify(payload);
    const requestId = randomUUID();
    const timestamp = new Date().toISOString().replace(/\.\d{3}Z$/, 'Z');
    const target = '/checkout/v1/payment';
    const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(body));
    const digestBase64 = Buffer.from(digest).toString('base64');
    const component = `Client-Id:${clientId}\nRequest-Id:${requestId}\nRequest-Timestamp:${timestamp}\nRequest-Target:${target}\nDigest:${digestBase64}`;
    const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(secretKey), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
    const signed = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(component));
    const signature = `HMACSHA256=${Buffer.from(signed).toString('base64')}`;

    const dokuResponse = await fetch(`${baseUrl}${target}`, {
      method: 'POST',
      headers: { 'Client-Id': clientId, 'Request-Id': requestId, 'Request-Timestamp': timestamp, Signature: signature, 'Content-Type': 'application/json' },
      body,
      cache: 'no-store',
    });
    const result = await dokuResponse.json().catch(() => ({}));
    const paymentUrl = result.response?.payment?.url;
    if (!dokuResponse.ok || !paymentUrl) {
      console.error('DOKU checkout creation failed', { status: dokuResponse.status, invoiceNumber });
      return NextResponse.json({ success: false, message: 'DOKU gagal membuat pembayaran. Silakan coba lagi.' }, { status: 502 });
    }
    return NextResponse.json({ success: true, paymentUrl, invoiceNumber });
  } catch (error) {
    console.error('Checkout error:', error.message);
    return NextResponse.json({ success: false, message: error.message || 'Checkout gagal.' }, { status: 500 });
  }
}
