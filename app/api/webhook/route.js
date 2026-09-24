import { NextResponse } from 'next/server';
import { createHash, createHmac, timingSafeEqual } from 'node:crypto';

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
    return NextResponse.json({ status: 'invalid signature' }, { status: 401 });
  }

  let body;
  try { body = JSON.parse(rawBody); }
  catch { return NextResponse.json({ status: 'invalid body' }, { status: 400 }); }

  // DOKU Checkout may send FAILED while a customer selects another payment method.
  // Only SUCCESS can trigger delivery; Apps Script also checks the stored invoice/amount.
  if (body.transaction?.status !== 'SUCCESS') return NextResponse.json({ status: 'accepted' });
  const invoice = body.order?.invoice_number;
  const amount = Number(body.order?.amount);
  if (!invoice || !Number.isFinite(amount) || amount <= 0) {
    return NextResponse.json({ status: 'missing transaction data' }, { status: 400 });
  }

  const gasUrl = process.env.GOOGLE_SCRIPT_URL;
  const sharedSecret = process.env.GAS_SHARED_SECRET;
  if (!gasUrl || !sharedSecret) {
    console.error('Google Apps Script integration is not configured');
    return NextResponse.json({ status: 'delivery integration unavailable' }, { status: 503 });
  }

  try {
    const response = await fetch(gasUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'deliver_paid_order', shared_secret: sharedSecret,
        invoice_number: String(invoice), amount,
        transaction_status: String(body.transaction.status),
        transaction_id: String(body.transaction?.transaction_id || body.transaction?.original_request_id || ''),
      }),
      cache: 'no-store', redirect: 'follow',
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || result.status !== 'success') {
      console.error('Apps Script did not accept paid order', { invoice, status: response.status });
      return NextResponse.json({ status: 'delivery pending' }, { status: 503 });
    }
    return NextResponse.json({ status: 'OK' });
  } catch (error) {
    console.error('Apps Script forwarding failed:', error.message);
    return NextResponse.json({ status: 'delivery pending' }, { status: 503 });
  }
}
