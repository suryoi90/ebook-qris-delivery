'use client';
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
}
