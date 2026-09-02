'use client';
import { useState, useEffect } from 'react';

const FEATURED_BOOKS = [
  {
    id: 'iso-27001-main',
    title: 'Buku Pegangan Implementasi ISO 27001:2022',
    subtitle: 'Panduan Sistematis Membangun, Mendokumentasikan, dan Mengaudit SMKI Secara Efektif',
    coverTitle: 'BUKU PEGANGAN IMPLEMENTASI ISO 27001:2022',
    coverTheme: '#0284c7',
    price: 175000,
    originalPrice: 250000,
  },
  {
    id: 'kepatuhan-pdp',
    title: 'Panduan Praktis Kepatuhan UU PDP',
    subtitle: 'Menerjemahkan Regulasi Pelindungan Data Pribadi ke Langkah Nyata Implementasi di Organisasi',
    coverTitle: 'PANDUAN PRAKTIS KEPATUHAN UNDANG-UNDANG PELINDUNGAN DATA PRIBADI',
    coverTheme: '#1e40af',
    price: 150000,
    originalPrice: 225000,
  },
  {
    id: 'ropa-dpia',
    title: 'RoPA dan DPIA Dalam Implementasi UU PDP',
    subtitle: 'Catat, Nilai, Kendalikan Risiko pada Pemrosesan Data Pribadi',
    coverTitle: 'RoPA & DPIA DALAM IMPLEMENTASI UNDANG-UNDANG PELINDUNGAN DATA PRIBADI',
    coverTheme: '#475569',
    price: 50000,
    originalPrice: 125000,
  },
  {
    id: 'lia-tia',
    title: 'LIA dan TIA Dalam Implementasi UU PDP',
    subtitle: 'Catat, Nilai, Kendalikan Risiko pada Pemrosesan Data Pribadi',
    coverTitle: 'LIA & TIA DALAM IMPLEMENTASI UNDANG-UNDANG PELINDUNGAN DATA PRIBADI',
    coverTheme: '#0369a1',
    price: 50000,
    originalPrice: 125000,
  },
  {
    id: '11-kontrol-v2',
    title: '[Versi 2.0] Buku Pegangan Implementasi 11 Kontrol Baru Annex A ISO 27001 2022',
    subtitle: 'Panduan Praktis & Komprehensif Penerapan Kontrol Baru Annex A',
    coverTitle: 'BUKU PEGANGAN IMPLEMENTASI 11 KONTROL BARU ANNEX A ISO 27001:2022 VERSI 2.0',
    coverTheme: '#0f172a',
    price: 150000,
    originalPrice: 225000,
  }
];

const PAID_SERVICES = [
  { id: 'chk-web-word', title: 'Checklist Keamanan Web (versi Word)', price: 100000, icon: '📝' },
  { id: 'jasa-konsultasi', title: 'Jasa Konsultasi Keamanan Siber', price: 500000, icon: '💼' },
  { id: 'traktiran-minres', title: 'Traktiran untuk Minres', price: 25000, icon: '☕' },
  { id: 'workshop-11-kontrol', title: 'Workshop Implementasi 11 Kontrol Baru Annex A ISO 27001:2022 (SekolahSiber)', price: 250000, icon: '🎓' },
  { id: 'kursus-smartphone', title: 'Kursus Proteksi Smartphone di sekolahsiber.com', price: 99000, icon: '📱' },
];

const FREE_ITEMS = [
  { title: 'Free Ebook Buku Pegangan Tanggap Insiden', icon: '📘' },
  { title: 'Free Ebook Panduan Secure SDLC', icon: '📘' },
  { title: 'Free Ebook Modus Operandi Phishing', icon: '📘' },
  { title: 'Free Ebook Template Tanggap Insiden Siber', icon: '📘' },
  { title: 'Free Excel Instrumen Penilaian Mandiri 18 Kontrol CIS pada UU PDP', icon: '📊' },
  { title: 'Free Checklist Keamanan Web (Non-Editable)', icon: '📑' },
  { title: 'Free Kebijakan Anti-Phishing', icon: '📑' },
  { title: 'Free Checklist Keamanan dan Privasi untuk Personal', icon: '📑' },
  { title: 'Free Checklist ISO 27001:2022', icon: '📑' },
];

export default function Home() {
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [form, setForm] = useState({ nama: '', email: '', organisasi: '' });
  const [loading, setLoading] = useState(false);
  const [freeModal, setFreeModal] = useState(null);

  useEffect(() => {
    const clientKey = Buffer.from('TWlkLWNsaWVudC10M05ieHU2bGdfdTY4VHVz', 'base64').toString('utf8');
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', clientKey);
    script.async = true;
    document.body.appendChild(script);

    return () => {
      try { document.body.removeChild(script); } catch (e) {}
    };
  }, []);

  const openCheckout = (item) => {
    setSelectedProduct(item);
  };

  const handlePay = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const res = await fetch('/api/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...form,
          productId: selectedProduct.id,
          productTitle: selectedProduct.title,
          amount: selectedProduct.price,
        }),
      });

      const data = await res.json();
      setLoading(false);

      if (data.token) {
        if (typeof window !== 'undefined' && window.snap) {
          window.snap.pay(data.token, {
            onSuccess: function(result) {
              alert('Pembayaran Sukses! File E-Book ber-watermark otomatis dikirim ke email Anda.');
              setSelectedProduct(null);
            },
            onPending: function(result) {
              alert('Menunggu pembayaran diselesaikan...');
            },
            onError: function(result) {
              alert('Pembayaran gagal atau dibatalkan.');
            },
            onClose: function() {
              console.log('Pop-up ditutup.');
            }
          });
        } else if (data.redirect_url) {
          window.location.href = data.redirect_url;
        }
      } else {
        alert('Gagal membuat pembayaran: ' + (data.message || JSON.stringify(data)));
      }
    } catch (err) {
      setLoading(false);
      alert('Terjadi kesalahan jaringan.');
    }
  };

  return (
    <div style={{ minHeight: '100vh', backgroundColor: '#060b13', color: '#ffffff', fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', padding: '40px 16px' }}>
      <div style={{ maxWidth: '480px', margin: '0 auto' }}>

        {/* HEADER PROFIL */}
        <div style={{ textAlign: 'center', marginBottom: '32px' }}>
          <div style={{ width: '92px', height: '92px', borderRadius: '50%', backgroundColor: '#ffffff', margin: '0 auto 14px', overflow: 'hidden', border: '2px solid rgba(255,255,255,0.2)', boxShadow: '0 4px 16px rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <svg width="84" height="84" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="50" cy="50" r="48" fill="#F8FAFC"/>
              <path d="M50 14C34 14 28 26 28 42C28 58 32 76 34 84C36 86 64 86 66 84C68 76 72 58 72 42C72 26 66 14 50 14Z" fill="#94A3B8"/>
              <ellipse cx="50" cy="46" rx="15" ry="18" fill="#FDE68A"/>
              <circle cx="44" cy="44" r="5" stroke="#475569" strokeWidth="1.5" fill="none"/>
              <circle cx="56" cy="44" r="5" stroke="#475569" strokeWidth="1.5" fill="none"/>
              <line x1="49" y1="44" x2="51" y2="44" stroke="#475569" strokeWidth="1.5"/>
              <path d="M24 92C26 80 34 76 42 78L50 86L58 78C66 76 74 80 76 92H24Z" fill="#1E293B"/>
            </svg>
          </div>

          <div style={{ fontSize: '15px', fontWeight: '700', color: '#ffffff', marginBottom: '6px' }}>
            @restiapriw
          </div>

          <p style={{ fontSize: '13px', color: '#cbd5e1', lineHeight: '1.5', margin: '0 auto 16px', maxWidth: '420px', fontWeight: '400' }}>
            Restia Moegiono, S.ST &#123;ISO 27001 CLI CLA|QRMO|ECSA|CHFI|CEH&#125; | Praktisi Keamanan Siber | Pegiat Literasi Digital
          </p>

          <a href="https://instagram.com/restiapriw" target="_blank" rel="noopener noreferrer" style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: '38px', height: '38px', borderRadius: '50%', border: '1.5px solid rgba(255,255,255,0.4)', color: '#ffffff', textDecoration: 'none' }}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
              <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
            </svg>
          </a>
        </div>

        {/* 5 E-BOOK UTAMA */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '24px', marginBottom: '32px' }}>
          {FEATURED_BOOKS.map((p) => (
            <div key={p.id} style={{ backgroundColor: '#ffffff', borderRadius: '18px', overflow: 'hidden', color: '#0f172a', boxShadow: '0 8px 24px rgba(0,0,0,0.3)' }}>
              
              <div style={{ padding: '24px 20px 16px', display: 'flex', justifyContent: 'center', backgroundColor: '#ffffff' }}>
                <div style={{ width: '100%', maxWidth: '340px', borderRadius: '24px', border: '7px solid #1e293b', backgroundColor: p.coverTheme, overflow: 'hidden', boxShadow: '0 12px 28px rgba(0,0,0,0.25)' }}>
                  
                  <div style={{ width: '40px', height: '4px', backgroundColor: '#475569', borderRadius: '2px', margin: '6px auto 12px' }}></div>

                  <div style={{ backgroundColor: '#ffffff', margin: '0 8px 8px', borderRadius: '14px', padding: '20px 14px', textAlign: 'center', minHeight: '260px', display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
                    <div>
                      <div style={{ fontSize: '13px', fontWeight: '900', color: p.coverTheme, lineHeight: 1.3, textTransform: 'uppercase', marginBottom: '10px' }}>
                        {p.coverTitle}
                      </div>

                      <div style={{ margin: '14px auto', width: '56px', height: '56px', borderRadius: '14px', backgroundColor: '#f0f9ff', display: 'flex', alignItems: 'center', justifyContent: 'center', border: '1px solid #bae6fd' }}>
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke={p.coverTheme} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                          <rect x="9" y="11" width="6" height="5" rx="1"/>
                          <path d="M10 11V9a2 2 0 0 1 4 0v2"/>
                        </svg>
                      </div>
                    </div>

                    <div>
                      <p style={{ fontSize: '10px', color: '#475569', margin: '0 0 8px', fontWeight: '600', lineHeight: 1.3 }}>
                        {p.subtitle}
                      </p>
                      <div style={{ fontSize: '11px', fontWeight: '800', color: '#0f172a' }}>
                        By Restia Moegiono
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <div style={{ margin: '0 20px 14px', backgroundColor: '#f8fafc', border: '1.5px solid #e2e8f0', borderRadius: '14px', padding: '12px 14px', display: 'flex', alignItems: 'center', gap: '14px' }}>
                <div style={{ width: '44px', height: '44px', borderRadius: '8px', backgroundColor: '#0284c7', color: '#ffffff', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', flexShrink: 0, fontWeight: 'bold', fontSize: '9px', lineHeight: 1.1 }}>
                  <span>E-BOOK</span>
                  <span style={{ fontSize: '16px' }}>📄</span>
                </div>
                <div style={{ fontSize: '11px', color: '#0f172a', fontWeight: '700', lineHeight: 1.5 }}>
                  <div>• Ebook berformat PDF.</div>
                  <div>• Ebook menggunakan watermark (nama, email, organisasi).</div>
                  <div>• Dikirim melalui email otomatis 1-2 menit.</div>
                </div>
              </div>

              <div style={{ padding: '0 20px 20px' }}>
                <h2 style={{ fontSize: '14px', fontWeight: '700', color: '#334155', margin: '0 0 8px' }}>
                  {p.title}
                </h2>

                <div style={{ display: 'flex', alignItems: 'baseline', gap: '8px', marginBottom: '14px' }}>
                  <span style={{ fontSize: '18px', fontWeight: '900', color: '#0f172a' }}>
                    IDR {p.price.toLocaleString('id-ID')}
                  </span>
                  <span style={{ fontSize: '13px', color: '#94a3b8', textDecoration: 'line-through' }}>
                    IDR {p.originalPrice.toLocaleString('id-ID')}
                  </span>
                </div>

                <button 
                  onClick={() => openCheckout(p)}
                  style={{ width: '100%', padding: '12px', backgroundColor: '#0284c7', color: '#ffffff', border: 'none', borderRadius: '12px', fontSize: '14px', fontWeight: '800', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', boxShadow: '0 4px 12px rgba(2,132,199,0.3)' }}
                >
                  Beli Sekarang (Pilih Pembayaran)
                </button>
              </div>

            </div>
          ))}
        </div>

        {/* LAYANAN & PRODUK DIGITAL */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', marginBottom: '32px' }}>
          {PAID_SERVICES.map((item) => (
            <div 
              key={item.id}
              onClick={() => openCheckout(item)}
              style={{ backgroundColor: '#ffffff', borderRadius: '14px', padding: '14px 18px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', color: '#0f172a', cursor: 'pointer', boxShadow: '0 4px 12px rgba(0,0,0,0.2)', border: '1px solid rgba(255,255,255,0.1)' }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
                <div style={{ width: '40px', height: '40px', borderRadius: '10px', backgroundColor: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '20px' }}>
                  {item.icon}
                </div>
                <div style={{ fontSize: '13px', fontWeight: '700', color: '#1e293b' }}>
                  {item.title}
                </div>
              </div>

              {item.price && (
                <div style={{ backgroundColor: '#f43f5e', color: '#ffffff', fontSize: '11px', fontWeight: '800', padding: '4px 8px', borderRadius: '8px', flexShrink: 0, textAlign: 'center', lineHeight: 1.2 }}>
                  <div>IDR</div>
                  <div>{item.price.toLocaleString('id-ID')}</div>
                </div>
              )}
            </div>
          ))}
        </div>

        {/* KOLEKSI FREE */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', marginBottom: '40px' }}>
          {FREE_ITEMS.map((item, idx) => (
            <div 
              key={idx}
              onClick={() => setFreeModal(item.title)}
              style={{ backgroundColor: '#ffffff', borderRadius: '14px', padding: '16px 18px', display: 'flex', alignItems: 'center', gap: '14px', color: '#0f172a', cursor: 'pointer', boxShadow: '0 4px 12px rgba(0,0,0,0.2)' }}
            >
              <div style={{ width: '36px', height: '36px', borderRadius: '8px', backgroundColor: '#f8fafc', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '18px', flexShrink: 0 }}>
                {item.icon}
              </div>
              <div style={{ fontSize: '13px', fontWeight: '700', color: '#1e293b', lineHeight: 1.4 }}>
                {item.title}
              </div>
            </div>
          ))}
        </div>

        {/* MODAL CHECKOUT */}
        {selectedProduct && (
          <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.85)', backdropFilter: 'blur(6px)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '16px', zIndex: 9999 }}>
            <div style={{ backgroundColor: '#ffffff', borderRadius: '24px', maxWidth: '440px', width: '100%', padding: '24px', color: '#0f172a', position: 'relative' }}>
              
              <button 
                onClick={() => setSelectedProduct(null)} 
                style={{ position: 'absolute', top: '16px', right: '16px', width: '32px', height: '32px', borderRadius: '50%', backgroundColor: '#f1f5f9', border: 'none', fontSize: '16px', fontWeight: 'bold', cursor: 'pointer', color: '#64748b' }}
              >
                ✕
              </button>

              <div style={{ fontSize: '11px', fontWeight: '700', color: '#0284c7', textTransform: 'uppercase', marginBottom: '4px' }}>
                Konfirmasi Data Pembeli
              </div>
              <h3 style={{ fontSize: '15px', fontWeight: '800', margin: '0 0 4px', color: '#0f172a', paddingRight: '24px', lineHeight: 1.3 }}>
                {selectedProduct.title}
              </h3>
              <div style={{ fontSize: '18px', fontWeight: '900', color: '#0284c7', marginBottom: '18px' }}>
                IDR {selectedProduct.price.toLocaleString('id-ID')}
              </div>

              <form onSubmit={handlePay}>
                <div style={{ marginBottom: '12px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>Nama Lengkap:</label>
                  <input required style={{ width: '100%', padding: '10px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '13px' }}
                    placeholder="Contoh: Budi Santoso"
                    value={form.nama} onChange={(e) => setForm({ ...form, nama: e.target.value })} />
                </div>

                <div style={{ marginBottom: '12px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>Alamat Email (Pengiriman E-Book):</label>
                  <input required type="email" style={{ width: '100%', padding: '10px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '13px' }}
                    placeholder="nama@email.com"
                    value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
                </div>

                <div style={{ marginBottom: '18px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>Organisasi / Perusahaan:</label>
                  <input required style={{ width: '100%', padding: '10px 12px', borderRadius: '10px', border: '1px solid #cbd5e1', boxSizing: 'border-box', fontSize: '13px' }}
                    placeholder="Contoh: PT Data Aman Indonesia"
                    value={form.organisasi} onChange={(e) => setForm({ ...form, organisasi: e.target.value })} />
                </div>

                <button type="submit" disabled={loading} style={{ width: '100%', padding: '14px', backgroundColor: '#0284c7', color: '#ffffff', border: 'none', borderRadius: '12px', fontSize: '15px', fontWeight: '800', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px' }}>
                  {loading ? 'Membuka Pilihan Pembayaran...' : 'Lanjut ke Pembayaran (Pilih Bank / E-Wallet)'}
                </button>
              </form>

            </div>
          </div>
        )}

        {/* MODAL FREE */}
        {freeModal && (
          <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.85)', backdropFilter: 'blur(6px)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '16px', zIndex: 9999 }}>
            <div style={{ backgroundColor: '#ffffff', borderRadius: '20px', maxWidth: '400px', width: '100%', padding: '24px', color: '#0f172a', textAlign: 'center' }}>
              <div style={{ fontSize: '32px', marginBottom: '10px' }}>🎁</div>
              <h3 style={{ fontSize: '16px', fontWeight: '800', margin: '0 0 8px', color: '#0f172a' }}>{freeModal}</h3>
              <p style={{ fontSize: '13px', color: '#64748b', marginBottom: '20px' }}>
                File ini tersedia gratis sebagai materi literasi keamanan & privasi data.
              </p>
              <button 
                onClick={() => setFreeModal(null)}
                style={{ padding: '12px 24px', backgroundColor: '#0284c7', color: '#ffffff', border: 'none', borderRadius: '10px', fontSize: '14px', fontWeight: 'bold', cursor: 'pointer' }}
              >
                Tutup
              </button>
            </div>
          </div>
        )}

        {/* FOOTER */}
        <footer style={{ textAlign: 'center', padding: '24px 0', fontSize: '12px', color: '#64748b' }}>
          <div>Hak Cipta © {new Date().getFullYear()} <b>Restia Moegiono</b>.</div>
          <div style={{ marginTop: '2px' }}>All Rights Reserved.</div>
        </footer>

      </div>
    </div>
  );
}