import { NextResponse } from 'next/server';
import { isUserAdmin } from '../../../../lib/auth.js';
import { testSmtp } from '../../../../lib/fulfillment.js';

export const runtime = 'nodejs';

export async function POST(request) {
  if (!(await isUserAdmin())) {
    return NextResponse.json({ success: false, message: 'Unauthorized' }, { status: 401 });
  }

  try {
    const { email } = await request.json();
    if (!email || !email.includes('@')) {
      return NextResponse.json({ success: false, message: 'Alamat email tujuan tidak valid.' }, { status: 400 });
    }

    const info = await testSmtp(email);
    return NextResponse.json({
      success: true,
      message: `Email tes berhasil dikirim ke ${email}!`,
      info: { messageId: info.messageId, response: info.response }
    });
  } catch (error) {
    console.error('SMTP test failed:', error);
    return NextResponse.json({
      success: false,
      message: `Gagal mengirim email: ${error.message}. Periksa pengaturan SMTP di file .env server Anda.`
    }, { status: 500 });
  }
}
