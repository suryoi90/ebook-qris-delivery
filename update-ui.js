const fs = require('fs');

const pageCode = `'use client';
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
    <div style={{ minHeight: '100vh', backgroundColor: '#f1f5f9', fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', padding: '30px 16px' }}>
      <div style={{ maxWidth: '520px', margin: '0 auto' }}>
        
        {/* Header Profil Kreator (Ala Lynk.id) */}
        <div style={{ textAlign: 'center', marginBottom: '24px' }}>
          <div style={{ width: '84px', height: '84px', borderRadius: '50%', backgroundColor: '#0f172a', color: '#ffffff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '28px', fontWeight: 'bold', margin: '0 auto 12px', border: '3px solid #ffffff', boxShadow: '0 4px 14px rgba(0,0,0,0.1)' }}>
            RM
          </div>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px' }}>
            <h1 style={{ fontSize: '22px', fontWeight: '800', color: '#0f172a', margin: 0 }}>Restia Moegiono</h1>
            <span style={{ backgroundColor: '#0284c7', color: '#ffffff', fontSize: '11px', padding: '2px 6px', borderRadius: '12px', fontWeight: 'bold' }}>✓</span>
          </div>
          <p style={{ fontSize: '13px', color: '#64748b', marginTop: '4px', marginBottom: 0 }}>Data Protection & Privacy Practitioner</p>
        </div>

        {/* Kartu Produk E-Book Premium */}
        <div style={{ backgroundColor: '#ffffff', borderRadius: '20px', overflow: 'hidden', boxShadow: '0 8px 24px rgba(0,0,0,0.06)', border: '1px solid #e2e8f0', marginBottom: '20px' }}>
          
          {/* Banner Header */}
          <div style={{ background: 'linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%)', padding: '28px 24px', color: '#ffffff', textAlign: 'center' }}>
            <span style={{ backgroundColor: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(4px)', fontSize: '11px', padding: '4px 10px', borderRadius: '20px', fontWeight: 'bold', letterSpacing: '0.5px' }}>
              OFFICIAL E-BOOK
            </span>
            <h2 style={{ fontSize: '20px', fontWeight: '800', margin: '12px 0 6px', lineHeight: 1.3 }}>
              RoPA & DPIA dalam Implementasi UU PDP
            </h2>
            <p style={{ fontSize: '13px', color: '#93c5fd', margin: 0 }}>
              Buku Pegangan Praktis Penyusunan & Kepatuhan Perlindungan Data Pribadi
            </p>
          </div>

          <div style={{ padding: '24px' }}>
            
            {/* Keunggulan E-Book */}
            <div style={{ marginBottom: '20px' }}>
              <div style={{ fontSize: '13px', fontWeight: 'bold', color: '#334155', marginBottom: '8px' }}>Apa yang Anda Dapatkan:</div>
              <ul style={{ margin: 0, paddingLeft: '18px', fontSize: '13px', color: '#475569', lineHeight: 1.6 }}>
                <li>Panduan komprehensif Record of Processing Activities (RoPA)</li>
                <li>Framework & Metodologi Data Protection Impact Assessment (DPIA)</li>
                <li>Format & template siap pakai untuk implementasi organisasi</li>
                <li>Watermark personalisasi otomatis (Nama, Email & Organisasi)</li>
              </ul>
            </div>

            {/* Harga */}
            <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', backgroundColor: '#f8fafc', padding: '12px 16px', borderRadius: '12px', border: '1px solid #e2e8f0', marginBottom: '20px' }}>
              <div>
                <span style={{ fontSize: '12px', color: '#64748b' }}>Harga Resmi</span>
                <div style={{ fontSize: '22px', fontWeight: '900', color: '#0f172a' }}>Rp 150.000</div>
              </div>
              <span style={{ backgroundColor: '#dcfce7', color: '#15803d', fontSize: '12px', padding: '4px 10px', borderRadius: '12px', fontWeight: 'bold' }}>
                Instant Access
              </span>
            </div>

            {!qris ? (
              /* Form Pembelian */
              <form onSubmit={handleCheckout}>
                <div style={{ marginBottom: '14px' }}>
                  <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#1e293b', marginBottom: '4px' }}>Nama Lengkap:</label>
                  <input required style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '14px' }}
                    placeholder="Contoh: Budi Santoso"
                    value={form.nama} onChange={(e) => setForm({ ...form, nama: e.target.value })} />
                </div>

                <div style={{ marginBottom: '14px' }}>
                  <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#1e293b', marginBottom: '4px' }}>Alamat Email (Pengiriman E-Book):</label>
                  <input required type="email" style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '14px' }}
                    placeholder="nama@email.com"
                    value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
                </div>

                <div style={{ marginBottom: '20px' }}>
                  <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#1e293b', marginBottom: '4px' }}>Organisasi / Perusahaan:</label>
                  <input required style={{ width: '100%', padding: '10px 14px', borderRadius: '10px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '14px' }}
                    placeholder="Contoh: PT Data Aman Indonesia"
                    value={form.organisasi} onChange={(e) => setForm({ ...form, organisasi: e.target.value })} />
                </div>

                <button type="submit" disabled={loading} style={{ width: '100%', padding: '14px', backgroundColor: '#0284c7', color: '#ffffff', border: 'none', borderRadius: '12px', fontSize: '16px', fontWeight: '800', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', boxShadow: '0 4px 12px rgba(2,132,199,0.3)' }}>
                  {loading ? 'Membuat QRIS...' : 'Beli Sekarang via QRIS (Rp 150.000)'}
                </button>
              </form>
            ) : (
              /* Tampilan QRIS Ala Lynk.id */
              <div style={{ textAlign: 'center' }}>
                <div style={{ backgroundColor: '#eff6ff', border: '1px solid #bfdbfe', borderRadius: '12px', padding: '10px', marginBottom: '16px' }}>
                  <div style={{ fontSize: '14px', fontWeight: 'bold', color: '#1e40af' }}>Scan QRIS untuk Menyelesaikan Pembelian</div>
                  <div style={{ fontSize: '12px', color: '#3b82f6', marginTop: '2px' }}>Total: <b>Rp 150.000</b></div>
                </div>

                <div style={{ backgroundColor: '#ffffff', border: '2px dashed #cbd5e1', borderRadius: '16px', padding: '16px', display: 'inline-block', marginBottom: '16px' }}>
                  <img src={qris.qrUrl} alt="QRIS Code" style={{ width: '250px', height: '250px', display: 'block' }} />
                </div>

                <div style={{ fontSize: '12px', color: '#64748b', marginBottom: '16px' }}>
                  Mendukung semua pembayaran: <b>BCA, Mandiri, BRI, BNI, GoPay, DANA, OVO, ShopeePay, LinkAja</b>.
                </div>

                <div style={{ backgroundColor: '#f0fdf4', border: '1px solid #bbf7d0', padding: '12px 14px', borderRadius: '12px', fontSize: '13px', color: '#166534', textAlign: 'left' }}>
                  ⚡ <b>Otomatis & Real-Time:</b> E-Book terpersonalisasi khusus atas nama <b>{form.nama}</b> ({form.organisasi}) akan langsung terkirim otomatis ke email <b>{form.email}</b> dalam 1 menit setelah Anda bayar.
                </div>
              </div>
            )}

          </div>
        </div>

        {/* Footer */}
        <div style={{ textAlign: 'center', fontSize: '12px', color: '#94a3b8' }}>
          Hak Cipta © {new Date().getFullYear()} Restia Moegiono. All Rights Reserved.
        </div>

      </div>
    </div>
  );
}`;

fs.writeFileSync('app/page.js', pageCode.trim(), 'utf8');
console.log('✅ Halaman Lynk.id style siap di-deploy!');