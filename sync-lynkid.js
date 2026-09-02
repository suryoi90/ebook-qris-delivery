const https = require('https');
const fs = require('fs');

function getPage(url) {
  return new Promise((resolve, reject) => {
    https.get(url, { headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' } }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });
}

async function run() {
  console.log('⏳ Mengambil data dan asset gambar dari https://lynk.id/restiapriw...');
  let avatarUrl = '';
  let coverUrl = '';
  let title = 'E-Book RoPA dan DPIA dalam Implementasi UU PDP';
  let price = '150.000';
  let desc = 'Buku Pegangan ini karya Restia Moegiono';

  try {
    const html = await getPage('https://lynk.id/restiapriw');
    
    // Ekstraksi data JSON dari __NEXT_DATA__ Lynk.id
    const match = html.match(/<script id="__NEXT_DATA__" type="application\/json">([\s\S]*?)<\/script>/);
    if (match) {
      const json = JSON.parse(match[1]);
      const pageProps = json.props?.pageProps;
      const user = pageProps?.user || pageProps?.creator;
      const products = pageProps?.products || [];

      if (user) {
        avatarUrl = user.avatar || user.photo_url || user.profile_picture || '';
      }

      if (products.length > 0) {
        const p = products[0];
        title = p.title || p.name || title;
        price = p.price ? Number(p.price).toLocaleString('id-ID') : price;
        coverUrl = p.cover || p.image || p.thumbnail || p.image_url || '';
        desc = p.description || p.short_description || desc;
      }
    }

    // Fallback jika avatar/cover ada di tag img / meta og
    if (!avatarUrl) {
      const ogImg = html.match(/<meta property="og:image" content="([^"]+)"/);
      if (ogImg) avatarUrl = ogImg[1];
    }
  } catch (err) {
    console.log('Catatan: Menggunakan asset cadangan resolusi tinggi.');
  }

  // Jika avatar belum ada, gunakan foto profil default
  if (!avatarUrl) {
    avatarUrl = 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300';
  }

  const newPageCode = `'use client';
import { useState } from 'react';

export default function Home() {
  const [form, setForm] = useState({ nama: '', email: '', organisasi: '' });
  const [qris, setQris] = useState(null);
  const [loading, setLoading] = useState(false);
  const [faqOpen, setFaqOpen] = useState(null);

  const toggleFaq = (index) => {
    setFaqOpen(faqOpen === index ? null : index);
  };

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

  const faqs = [
    {
      q: "Bagaimana format E-Book yang diterima?",
      a: "E-Book dikirimkan dalam format PDF High Resolution yang dilengkapi watermark nama, email, dan organisasi pembeli di bagian footer."
    },
    {
      q: "Kapan E-Book dikirim setelah pembayaran?",
      a: "Sistem kami mengirimkan file PDF E-Book secara otomatis ke alamat email Anda dalam 1 menit setelah transaksi QRIS terverifikasi."
    },
    {
      q: "Metode pembayaran apa saja yang didukung?",
      a: "Bisa menggunakan semua aplikasi mobile banking (BCA, Mandiri, BRI, BNI, Permata, dll) dan dompet digital (DANA, GoPay, OVO, ShopeePay, LinkAja)."
    }
  ];

  return (
    <div style={{ minHeight: '100vh', backgroundColor: '#f8fafc', fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', color: '#0f172a' }}>
      
      {/* Background Banner */}
      <div style={{ height: '150px', background: 'linear-gradient(135deg, #090d16 0%, #1e293b 50%, #0369a1 100%)', width: '100%' }}></div>

      <main style={{ maxWidth: '580px', margin: '-75px auto 40px', padding: '0 16px' }}>
        
        {/* Kartu Profil Kreator Lynk.id */}
        <div style={{ backgroundColor: '#ffffff', borderRadius: '24px', padding: '24px', textAlign: 'center', boxShadow: '0 10px 30px -5px rgba(0,0,0,0.06)', border: '1px solid #e2e8f0', marginBottom: '20px' }}>
          
          {/* Avatar Profile */}
          <div style={{ position: 'relative', width: '96px', height: '96px', margin: '-65px auto 12px' }}>
            <img 
              src="${avatarUrl}" 
              alt="Restia Moegiono"
              onError={(e) => {
                e.target.onerror = null; 
                e.target.src = 'https://ui-avatars.com/api/?name=Restia+Moegiono&background=0f172a&color=fff&size=128';
              }}
              style={{ width: '96px', height: '96px', borderRadius: '50%', objectFit: 'cover', border: '4px solid #ffffff', boxShadow: '0 8px 16px rgba(0,0,0,0.12)' }} 
            />
          </div>

          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px' }}>
            <h1 style={{ fontSize: '22px', fontWeight: '800', color: '#0f172a', margin: 0 }}>Restia Moegiono</h1>
            <span style={{ backgroundColor: '#0284c7', color: '#ffffff', fontSize: '11px', width: '18px', height: '18px', borderRadius: '50%', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontWeight: 'bold' }}>✓</span>
          </div>
          
          <p style={{ fontSize: '13px', color: '#64748b', margin: '4px 0 16px', fontWeight: '500' }}>
            Data Protection & Privacy Practitioner | @restiapriw
          </p>

          <div style={{ display: 'flex', justifyContent: 'center', gap: '10px', flexWrap: 'wrap' }}>
            <span style={{ backgroundColor: '#f1f5f9', color: '#334155', fontSize: '12px', padding: '6px 14px', borderRadius: '20px', fontWeight: '600', border: '1px solid #e2e8f0' }}>
              🔒 UU PDP Specialist
            </span>
            <span style={{ backgroundColor: '#f1f5f9', color: '#334155', fontSize: '12px', padding: '6px 14px', borderRadius: '20px', fontWeight: '600', border: '1px solid #e2e8f0' }}>
              📚 Official Store
            </span>
          </div>
        </div>

        {/* Kartu Produk Utama E-Book */}
        <div style={{ backgroundColor: '#ffffff', borderRadius: '24px', overflow: 'hidden', boxShadow: '0 10px 30px -5px rgba(0,0,0,0.06)', border: '1px solid #e2e8f0', marginBottom: '20px' }}>
          
          {/* Cover Header */}
          <div style={{ background: 'linear-gradient(135deg, #090d16 0%, #1e3a8a 100%)', padding: '36px 24px', color: '#ffffff', textAlign: 'center' }}>
            <div style={{ display: 'inline-block', backgroundColor: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(8px)', fontSize: '11px', padding: '4px 12px', borderRadius: '20px', fontWeight: '700', letterSpacing: '0.8px', marginBottom: '12px' }}>
              OFFICIAL E-BOOK
            </div>
            <h2 style={{ fontSize: '22px', fontWeight: '800', margin: '0 0 8px', lineHeight: 1.3 }}>
              RoPA dan DPIA dalam Implementasi UU PDP
            </h2>
            <p style={{ fontSize: '13px', color: '#93c5fd', margin: 0, lineHeight: 1.5 }}>
              Buku Pegangan Praktis Kepatuhan & Tata Kelola Pelindungan Data Pribadi
            </p>
          </div>

          <div style={{ padding: '24px' }}>
            
            {/* Detail & Manfaat Pembahasan */}
            <div style={{ marginBottom: '24px' }}>
              <h3 style={{ fontSize: '14px', fontWeight: '700', color: '#1e293b', marginBottom: '12px' }}>
                Materi yang Anda Dapatkan:
              </h3>
              <div style={{ display: 'grid', gap: '10px' }}>
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', fontSize: '13px', color: '#334155' }}>
                  <span style={{ color: '#0284c7', fontWeight: 'bold' }}>✓</span>
                  <span><b>Record of Processing Activities (RoPA):</b> Kerangka kerja, tahapan inventarisasi, dan format pencatatan pemrosesan data pribadi.</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', fontSize: '13px', color: '#334155' }}>
                  <span style={{ color: '#0284c7', fontWeight: 'bold' }}>✓</span>
                  <span><b>Data Protection Impact Assessment (DPIA):</b> Metodologi penilaian dampak tinggi, matriks risiko, dan mitigasi kepatuhan.</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', fontSize: '13px', color: '#334155' }}>
                  <span style={{ color: '#0284c7', fontWeight: 'bold' }}>✓</span>
                  <span><b>Kepatuhan UU No. 27 Tahun 2022:</b> Panduan implementasi nyata bagi DPO, Tim Legal, & IT Security.</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', fontSize: '13px', color: '#334155' }}>
                  <span style={{ color: '#0284c7', fontWeight: 'bold' }}>✓</span>
                  <span><b>Watermark Terpersonalisasi:</b> Dilengkapi identitas resmi pembeli untuk menjaga keaslian salinan.</span>
                </div>
              </div>
            </div>

            {/* Kotak Harga */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', backgroundColor: '#f8fafc', padding: '16px 20px', borderRadius: '16px', border: '1px solid #e2e8f0', marginBottom: '24px' }}>
              <div>
                <span style={{ fontSize: '12px', color: '#64748b', fontWeight: '600' }}>Harga Resmi</span>
                <div style={{ fontSize: '24px', fontWeight: '900', color: '#0f172a' }}>Rp 150.000</div>
              </div>
              <div style={{ textAlign: 'right' }}>
                <span style={{ backgroundColor: '#dcfce7', color: '#15803d', fontSize: '12px', padding: '4px 12px', borderRadius: '12px', fontWeight: '700', display: 'inline-block', marginBottom: '2px' }}>
                  ⚡ Auto Deliver
                </span>
                <div style={{ fontSize: '11px', color: '#64748b' }}>Akses PDF Langsung</div>
              </div>
            </div>

            {/* Form & QRIS */}
            {!qris ? (
              <form onSubmit={handleCheckout}>
                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#1e293b', marginBottom: '6px' }}>Nama Lengkap:</label>
                  <input required style={{ width: '100%', padding: '12px 14px', borderRadius: '12px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '14px' }}
                    placeholder="Contoh: Budi Santoso"
                    value={form.nama} onChange={(e) => setForm({ ...form, nama: e.target.value })} />
                </div>

                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#1e293b', marginBottom: '6px' }}>Alamat Email (Pengiriman E-Book):</label>
                  <input required type="email" style={{ width: '100%', padding: '12px 14px', borderRadius: '12px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '14px' }}
                    placeholder="nama@email.com"
                    value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
                </div>

                <div style={{ marginBottom: '24px' }}>
                  <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#1e293b', marginBottom: '6px' }}>Organisasi / Perusahaan:</label>
                  <input required style={{ width: '100%', padding: '12px 14px', borderRadius: '12px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '14px' }}
                    placeholder="Contoh: PT Data Aman Indonesia"
                    value={form.organisasi} onChange={(e) => setForm({ ...form, organisasi: e.target.value })} />
                </div>

                <button type="submit" disabled={loading} style={{ width: '100%', padding: '16px', backgroundColor: '#0284c7', color: '#ffffff', border: 'none', borderRadius: '14px', fontSize: '16px', fontWeight: '800', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', boxShadow: '0 4px 14px rgba(2,132,199,0.35)' }}>
                  {loading ? 'Membuat QRIS...' : 'Beli Sekarang via QRIS (Rp 150.000)'}
                </button>
              </form>
            ) : (
              /* Tampilan QRIS Dinamis */
              <div style={{ textAlign: 'center' }}>
                <div style={{ backgroundColor: '#eff6ff', border: '1px solid #bfdbfe', borderRadius: '14px', padding: '12px', marginBottom: '20px' }}>
                  <div style={{ fontSize: '15px', fontWeight: '800', color: '#1e40af' }}>Scan QRIS untuk Menyelesaikan Pembayaran</div>
                  <div style={{ fontSize: '13px', color: '#3b82f6', marginTop: '2px' }}>Total: <b>Rp 150.000</b></div>
                </div>

                <div style={{ backgroundColor: '#ffffff', border: '2px dashed #94a3b8', borderRadius: '20px', padding: '20px', display: 'inline-block', marginBottom: '16px', boxShadow: '0 4px 12px rgba(0,0,0,0.05)' }}>
                  <img src={qris.qrUrl} alt="QRIS Code" style={{ width: '260px', height: '260px', display: 'block' }} />
                </div>

                <div style={{ fontSize: '12px', color: '#64748b', marginBottom: '20px', lineHeight: 1.5 }}>
                  Mendukung: <b>BCA, Mandiri, BRI, BNI, DANA, GoPay, OVO, ShopeePay, LinkAja</b>.
                </div>

                <div style={{ backgroundColor: '#f0fdf4', border: '1px solid #bbf7d0', padding: '14px 16px', borderRadius: '14px', fontSize: '13px', color: '#166534', textAlign: 'left', lineHeight: 1.5 }}>
                  ⚡ <b>Pengiriman Instan:</b> E-Book terpersonalisasi khusus atas nama <b>{form.nama}</b> ({form.organisasi}) akan langsung terkirim otomatis ke email <b>{form.email}</b> dalam waktu 1 menit setelah Anda membayar.
                </div>
              </div>
            )}

          </div>
        </div>

        {/* Section FAQ */}
        <div style={{ backgroundColor: '#ffffff', borderRadius: '24px', padding: '24px', border: '1px solid #e2e8f0', boxShadow: '0 10px 30px -5px rgba(0,0,0,0.06)', marginBottom: '30px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: '800', margin: '0 0 16px', color: '#0f172a' }}>
            Pertanyaan yang Sering Diajukan (FAQ)
          </h3>
          <div style={{ display: 'grid', gap: '12px' }}>
            {faqs.map((faq, idx) => (
              <div key={idx} style={{ border: '1px solid #e2e8f0', borderRadius: '14px', overflow: 'hidden' }}>
                <button onClick={() => toggleFaq(idx)} style={{ width: '100%', textAlign: 'left', padding: '14px 16px', background: faqOpen === idx ? '#f8fafc' : '#ffffff', border: 'none', fontSize: '13px', fontWeight: '700', color: '#1e293b', cursor: 'pointer', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span>{faq.q}</span>
                  <span style={{ fontSize: '16px', color: '#64748b' }}>{faqOpen === idx ? '−' : '+'}</span>
                </button>
                {faqOpen === idx && (
                  <div style={{ padding: '12px 16px', fontSize: '13px', color: '#475569', backgroundColor: '#ffffff', borderTop: '1px solid #f1f5f9', lineHeight: 1.6 }}>
                    {faq.a}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Footer */}
        <footer style={{ textAlign: 'center', padding: '20px 0', fontSize: '12px', color: '#94a3b8' }}>
          <p style={{ margin: '0 0 4px' }}>Hak Cipta © {new Date().getFullYear()} <b>Restia Moegiono</b>.</p>
          <p style={{ margin: 0 }}>E-Book RoPA & DPIA dalam Implementasi UU PDP. All Rights Reserved.</p>
        </footer>

      </main>
    </div>
  );
}
`;

  fs.writeFileSync('app/page.js', newPageCode.trim(), 'utf8');
  console.log('✅ Sinkronisasi asset dan halaman berhasil dibuat!');
}

run();