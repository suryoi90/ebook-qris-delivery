import { NextResponse } from 'next/server';
import { createHash, createHmac, timingSafeEqual } from 'node:crypto';
import { getOrder, updateOrderStatus } from '../../../lib/db.js';
import { fulfillOrder } from '../../../lib/fulfillment.js';

export const runtime = 'nodejs';

function safeEqualText(left, right) {
  const a = Buffer.from(String(left || ''));
  const b = Buffer.from(String(right || ''));
  return a.length === b.length && timingSafeEqual(a, b);
}

function verifyDoku(rawBody, headers, requestTarget) {
  const clientId = process.env.DOKU_CLIENT_ID;
  const secretKey = process.env.DOKU_SECRET_KEY;
  const gotClient = headers.get('client-id');
  const requestId = headers.get('request-id');
  const timestamp = headers.get('request-timestamp');
  const gotSignature = headers.get('signature');
  if (!clientId || !secretKey || !gotClient || !requestId || !timestamp || !gotSignature) return false;
  if (!safeEqualText(gotClient, clientId)) return false;
  const digest = createHash('sha256').update(rawBody, 'utf8').digest('base64');
  const component = `Client-Id:${gotClient}\nRequest-Id:${requestId}\nRequest-Timestamp:${timestamp}\nRequest-Target:${requestTarget}\nDigest:${digest}`;
  const expected = `HMACSHA256=${createHmac('sha256', secretKey).update(component, 'utf8').digest('base64')}`;
  return safeEqualText(gotSignature, expected);
}

export async function GET() {
  return NextResponse.json({ status: 'DOKU notification endpoint ready' });
}

export async function POST(request) {
  const rawBody = await request.text();
  const requestTarget = new URL(request.url).pathname;

  if (!verifyDoku(rawBody, request.headers, requestTarget)) {
    console.warn('DOKU webhook signature mismatch');
    return NextResponse.json({ status: 'invalid signature' }, { status: 401 });
  }

  let body;
  try { body = JSON.parse(rawBody); }
  catch { return NextResponse.json({ status: 'invalid body' }, { status: 400 }); }

  // Only SUCCESS can trigger delivery
  if (body.transaction?.status !== 'SUCCESS') {
    return NextResponse.json({ status: 'accepted' });
  }

  const invoice = body.order?.invoice_number;
  const amount = Number(body.order?.amount);
  if (!invoice || !Number.isFinite(amount) || amount <= 0) {
    return NextResponse.json({ status: 'missing transaction data' }, { status: 400 });
  }

  const order = getOrder(invoice);
  if (!order) {
    console.error('Order not found for invoice:', invoice);
    return NextResponse.json({ status: 'order not found' }, { status: 404 });
  }

  const transactionId = String(body.transaction?.transaction_id || body.transaction?.original_request_id || '');

  // Record payment
  updateOrderStatus(invoice, {
    status: 'PAID',
    transaction_id: transactionId,
    paid_at: new Date().toISOString()
  });

  try {
    // Deliver personalized watermarked & password-protected ebook via SMTP
    await fulfillOrder(invoice);
    console.log(`Successfully fulfilled and emailed order: ${invoice}`);
    return NextResponse.json({ status: 'OK' });
  } catch (error) {
    console.error('Direct fulfillment error:', error);
    return NextResponse.json({ status: 'delivery pending', error: error.message }, { status: 500 });
  }
}
