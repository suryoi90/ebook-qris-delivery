import { NextResponse } from 'next/server';

export function middleware(request) {
  const response = NextResponse.next();
  response.headers.set('Content-Security-Policy', "default-src 'self'; base-uri 'self'; script-src 'self' 'unsafe-inline' https://app.midtrans.com https://app.sandbox.midtrans.com; connect-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com https://api.midtrans.com https://api.sandbox.midtrans.com; frame-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob: https://app.midtrans.com https://app.sandbox.midtrans.com https://images.unsplash.com https://ui-avatars.com; font-src 'self' data:; style-src 'self' 'unsafe-inline'; object-src 'none'; upgrade-insecure-requests;");
  response.headers.set('X-Content-Type-Options', 'nosniff');
  response.headers.set('X-Frame-Options', 'SAMEORIGIN');
  response.headers.set('X-XSS-Protection', '1; mode=block');
  response.headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');
  response.headers.set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
  response.headers.set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), browsing-topics=()');
  response.headers.set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
  response.headers.set('Cross-Origin-Resource-Policy', 'cross-origin');
  return response;
}

export const config = {
  matcher: '/:path*',
};