import { createHmac } from 'node:crypto';
import { cookies } from 'next/headers';

const COOKIE_NAME = 'rm_admin_session';

function getSecret() {
  return process.env.ADMIN_PASSWORD || 'AdminMoegiono2026!';
}

export function generateToken() {
  const secret = getSecret();
  const timestamp = Date.now();
  const signature = createHmac('sha256', secret).update(String(timestamp)).digest('hex');
  return `${timestamp}.${signature}`;
}

export function verifyToken(token) {
  if (!token || typeof token !== 'string') return false;
  const [timestamp, signature] = token.split('.');
  if (!timestamp || !signature) return false;
  const age = Date.now() - Number(timestamp);
  // Valid for 7 days
  if (age < 0 || age > 7 * 24 * 60 * 60 * 1000) return false;

  const secret = getSecret();
  const expected = createHmac('sha256', secret).update(String(timestamp)).digest('hex');
  return signature === expected;
}

export async function isUserAdmin() {
  try {
    const cookieStore = await cookies();
    const token = cookieStore.get(COOKIE_NAME)?.value;
    return verifyToken(token);
  } catch (err) {
    return false;
  }
}
