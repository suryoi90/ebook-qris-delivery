import { NextResponse } from 'next/server';

export async function GET() {
  return NextResponse.json({ status: 'DOKU Webhook Active & Ready' }, { status: 200 });
}

export async function POST(request) {
  try {
    const body = await request.json();

    // Notifikasi DOKU Berhasil (Status SUCCESS)
    const isSuccess = body.transaction?.status === 'SUCCESS' || body.order?.status === 'SUCCESS';
    
    if (isSuccess) {
      const googleScriptUrl = process.env.GOOGLE_SCRIPT_URL;
      const additionalInfo = body.additional_info || {};

      if (googleScriptUrl) {
        try {
          await fetch(googleScriptUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              order_id: body.order?.invoice_number || 'DOKU-' + Date.now(),
              gross_amount: body.order?.amount,
              custom_field1: additionalInfo.nama || body.customer?.name || 'Pembeli',
              custom_field2: additionalInfo.email || body.customer?.email,
              custom_field3: additionalInfo.organisasi || '-',
              product_title: additionalInfo.product_title || 'E-Book',
              payment_type: body.channel?.id || 'DOKU',
            }),
          });
        } catch (err) {
          console.error('GAS Forwarding Error:', err);
        }
      }
    }

    return NextResponse.json({ status: 'OK' }, { status: 200 });
  } catch (error) {
    return NextResponse.json({ status: 'OK', note: error.message }, { status: 200 });
  }
}