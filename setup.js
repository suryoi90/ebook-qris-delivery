const fs = require('fs');
const path = require('path');

function write(f, c) {
  const dir = path.dirname(f);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(f, c.trim(), 'utf8');
}

write('package.json', JSON.stringify({
  "name": "ebook-qris-delivery",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start"
  },
  "dependencies": {
    "next": "^14.2.5",
    "react": "^18.3.1",
    "react-dom": "^18.3.1"
  }
}, null, 2));

write('next.config.mjs', '/** @type {import("next").NextConfig} */\nconst nextConfig = {};\nexport default nextConfig;\n');
write('.gitignore', 'node_modules\n.next\n.env*.local\n.vercel\nsetup.js\n');

write('app/layout.js', \export const metadata = {
  title: 'E-Book RoPA dan DPIA - UU PDP',
  description: 'Beli E-Book RoPA dan DPIA UU PDP dengan QRIS Otomatis',
};

export default function RootLayout({ children }) {
  return (
    <html lang="id">
      <body style={{ margin: 0, padding: 0, backgroundColor: '#f8fafc', color: '#1e293b' }}>
        {children}
      </body>
    </html>
  );
}\);

write('app/api/checkout/route.js', \import { NextResponse } from 'next/server';

export async function POST(request) {
  try {
    const { nama, email, organisasi } = await request.json();
    const orderId = "EBOOK-" + Date.now() + "-" + Math.floor(Math.random() * 1000);
    const grossAmount = 150000;

    const serverKey = process.env.MIDTRANS_SERVER_KEY;
    const authHeader = Buffer.from(serverKey + ':').toString('base64');

    const midtransRes = await fetch('https://api.midtrans.com/v2/charge', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Basic ' + authHeader,
      },
      body: JSON.stringify({
        payment_type: 'qris',
        transaction_details: {
          order_id: orderId,
          gross_amount: grossAmount,
        },
        customer_details: {
          first_name: nama,
          email: email,
        },
        custom_field1: nama,
        custom_field2: email,
        custom_field3: organisasi || '-',
      }),
    });

    const data = await midtransRes.json();
    if (data.status_code && data.status_code !== '201') {
      throw new Error(data.status_message || 'Gagal membuat QRIS di Midtrans');
    }

    const qrUrl = data.actions?.find((a) => a.name === 'generate-qr-code')?.url;

    return NextResponse.json({
      success: true,
      orderId,
      grossAmount,
      qrUrl,
    });
  } catch (error) {
    return NextResponse.json({ success: false, message: error.message }, { status: 500 });
  }
}\);

write('app/api/webhook/route.js', \import { NextResponse } from 'next/server';
import crypto from 'crypto';

export async function POST(request) {
  try {
    const notification = await request.json();
    const { order_id, status_code, gross_amount, signature_key, transaction_status } = notification;

    const serverKey = process.env.MIDTRANS_SERVER_KEY;
    const hash = crypto
      .createHash('sha512')
      .update(order_id + status_code + gross_amount + serverKey)
      .digest('hex');

    if (hash !== signature_key) {
      return NextResponse.json({ message: 'Invalid Signature' }, { status: 400 });
    }

    if (transaction_status === 'settlement' || transaction_status === 'capture') {
      const nama = notification.custom_field1;
      const email = notification.custom_field2;
      const organisasi = notification.custom_field3;

      const gasUrl = process.env.GOOGLE_SCRIPT_URL;
      if (gasUrl) {
        await fetch(gasUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ nama, email, organisasi, orderId: order_id }),
          redirect: 'follow',
        });
      }
    }

    return NextResponse.json({ status: 'OK' });
  } catch (error) {
    return NextResponse.json({ message: error.message }, { status: 500 });
  }
}\);

write('app/page.js', \'use client';
import { useState } from 'react';

export default function Home() {
  const [form, setForm] = useState({ nama: '', email: '', organisasi: '' });
  const [qris, setQris] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleCheckout = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const res = await fetch('/api/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      const data = await res.json();
      setLoading(false);
      if (data.success) {
        setQris(data);
      } else {
        alert('Gagal membuat QRIS: ' + data.message);
      }
    } catch (err) {
      setLoading(false);
      alert('Terjadi kesalahan jaringan.');
    }
  };

  return (
    <main style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '20px' }}>
      <div style={{ maxWidth: 460, width: '100%', backgroundColor: '#ffffff', padding: 28, borderRadius: 16, boxShadow: '0 4px 12px rgba(0,0,0,0.06)', border: '1px solid #e2e8f0' }}>
        <h2 style={{ margin: '0 0 6px', fontSize: 22, color: '#0f172a' }}>E-Book RoPA & DPIA</h2>
        <p style={{ margin: '0 0 16px', color: '#64748b', fontSize: 14 }}>Panduan Implementasi UU Pelindungan Data Pribadi</p>
        
        <div style={{ backgroundColor: '#f1f5f9', padding: '12px 16px', borderRadius: 8, marginBottom: 20 }}>
          <span style={{ fontSize: 13, color: '#475569' }}>Total Pembayaran:</span>
          <div style={{ fontSize: 20, fontWeight: 'bold', color: '#0f172a' }}>Rp 150.000</div>
        </div>

        {!qris ? (
          <form onSubmit={handleCheckout}>
            <div style={{ marginBottom: 14 }}>
              <label style={{ display: 'block', fontSize: 13, fontWeight: '600', marginBottom: 6 }}>Nama Lengkap:</label>
              <input required style={{ width: '100%', padding: '10px 12px', borderRadius: 8, border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: 14 }}
                placeholder="Contoh: Budi Santoso"
                value={form.nama} onChange={(e) => setForm({ ...form, nama: e.target.value })} />
            </div>

            <div style={{ marginBottom: 14 }}>
              <label style={{ display: 'block', fontSize: 13, fontWeight: '600', marginBottom: 6 }}>Alamat Email (Pengiriman E-Book):</label>
              <input required type="email" style={{ width: '100%', padding: '10px 12px', borderRadius: 8, border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: 14 }}
                placeholder="nama@email.com"
                value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            </div>

            <div style={{ marginBottom: 20 }}>
              <label style={{ display: 'block', fontSize: 13, fontWeight: '600', marginBottom: 6 }}>Organisasi / Instansi:</label>
              <input required style={{ width: '100%', padding: '10px 12px', borderRadius: 8, border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: 14 }}
                placeholder="PT Nama Perusahaan"
                value={form.organisasi} onChange={(e) => setForm({ ...form, organisasi: e.target.value })} />
            </div>

            <button type="submit" disabled={loading} style={{ width: '100%', padding: '12px', backgroundColor: '#0284c7', color: '#fff', border: 'none', borderRadius: 8, fontSize: 15, fontWeight: 'bold', cursor: 'pointer' }}>
              {loading ? 'Membuat QRIS...' : 'Bayar Sekarang via QRIS'}
            </button>
          </form>
        ) : (
          <div style={{ textAlign: 'center' }}>
            <h3 style={{ margin: '0 0 6px', fontSize: 18 }}>Scan QRIS untuk Membayar</h3>
            <p style={{ fontSize: 13, color: '#64748b', margin: 0 }}>Gunakan BCA Mobile, Mandiri Livin, GoPay, OVO, Dana, ShopeePay</p>
            <img src={qris.qrUrl} alt="QRIS Code" style={{ width: 240, height: 240, margin: '20px auto', display: 'block', border: '1px solid #eee', borderRadius: 8 }} />
            <div style={{ backgroundColor: '#f0fdf4', border: '1px solid #bbf7d0', padding: 12, borderRadius: 8, fontSize: 13, color: '#166534' }}>
              ⚡ <b>Pengiriman Otomatis:</b> Setelah transfer berhasil, E-Book PDF ber-watermark akan langsung dikirim ke email <b>{form.email}</b>.
            </div>
          </div>
        )}
      </div>
    </main>
  );
}\);

console.log('✅ SEMUA FILE SUDAH BERHASIL DIBUAT DENGAN BERSIH!');