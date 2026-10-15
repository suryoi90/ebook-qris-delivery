import { NextResponse } from 'next/server';
import { cookies } from 'next/headers';
import { generateToken, verifyToken } from '../../../../lib/auth.js';

export const runtime = 'nodejs';

export async function POST(request) {
  try {
    const { password } = await request.json();
    const correctPassword = process.env.ADMIN_PASSWORD || 'AdminMoegiono2026!';

    if (!password || password !== correctPassword) {
      return NextResponse.json({ success: false, message: 'Password salah.' }, { status: 401 });
    }

    const token = generateToken();
    const cookieStore = await cookies();
    cookieStore.set('rm_admin_session', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      path: '/',
      maxAge: 7 * 24 * 60 * 60
    });

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ success: false, message: error.message }, { status: 500 });
  }
}

export async function DELETE() {
  const cookieStore = await cookies();
  cookieStore.delete('rm_admin_session');
  return NextResponse.json({ success: true });
}

export async function GET() {
  const cookieStore = await cookies();
  const token = cookieStore.get('rm_admin_session')?.value;
  const authenticated = verifyToken(token);
  return NextResponse.json({ authenticated });
}
