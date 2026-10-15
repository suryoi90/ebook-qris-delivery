'use client';
import { useState } from 'react';

const FEATURED_BOOKS = [
  {
    id: 'iso-27001-main',
    title: 'Buku Pegangan Implementasi ISO 27001:2022',
    subtitle: 'Panduan Sistematis Membangun, Mendokumentasikan, dan Mengaudit SMKI Secara Efektif',
    category: 'E-Book Utama',
    badge: '🔥 Paling Populer',
    price: 175000,
    originalPrice: 250000,
    highlights: ['Standar ISO 27001:2022 Terbaru', 'Format PDF Berlisensi Resmi', 'Watermark & Enkripsi AES-256']
  },
  {
    id: 'kepatuhan-pdp',
    title: 'Panduan Praktis Kepatuhan UU PDP',
    subtitle: 'Menerjemahkan Regulasi Pelindungan Data Pribadi ke Langkah Nyata Implementasi di Organisasi',
    category: 'E-Book Utama',
    badge: '⚖️ Regulasi UU PDP',
    price: 150000,
    originalPrice: 225000,
    highlights: ['Kepatuhan UU No. 27/2022', 'Pedoman Hak Subjek Data', 'Prosedur Tanggap Pelanggaran Data']
  },
  {
    id: '11-kontrol-v2',
    title: '[Versi 2.0] Buku Pegangan Implementasi 11 Kontrol Baru Annex A ISO 27001 2022',
    subtitle: 'Panduan Praktis & Komprehensif Penerapan Kontrol Baru Annex A',
    category: 'E-Book Utama',
    badge: '⭐ Versi 2.0 Terupdate',
    price: 150000,
    originalPrice: 225000,
    highlights: ['Threat Intelligence (A.5.7)', 'Cloud Services (A.5.23)', 'Data Masking & Leakage Prevention']
  },
  {
    id: 'ropa-dpia',
    title: 'RoPA dan DPIA Dalam Implementasi UU PDP',
    subtitle: 'Catat, Nilai, Kendalikan Risiko pada Pemrosesan Data Pribadi',
    category: 'Instrumen Praktis',
    badge: '📑 Audit & Penilaian',
    price: 50000,
    originalPrice: 125000,
    highlights: ['Format Record of Processing Activities', 'Metodologi DPIA Praktis', 'Mitigasi Risiko Privasi']
  },
  {
    id: 'lia-tia',
    title: 'LIA dan TIA Dalam Implementasi UU PDP',
    subtitle: 'Penilaian Kepentingan yang Sah dan Transfer Data Pribadi',
    category: 'Instrumen Praktis',
    badge: '🔍 Analisis Hukum',
    price: 50000,
    originalPrice: 125000,
    highlights: ['Legitimate Interests Assessment', 'Transfer Impact Assessment', 'Contoh Kasus Riil']
  }
];

const PAID_SERVICES = [
  { id: 'chk-web-word', title: 'Checklist Keamanan Web (versi Word)', desc: 'Template dokumen checklist penilaian kerentanan web siap pakai dan dapat diedit.', price: 100000, icon: '📝', badge: 'Tools & Checklist' },
  { id: 'jasa-konsultasi', title: 'Jasa Konsultasi Keamanan Siber', desc: 'Sesi konsultasi privat arsitektur keamanan, kepatuhan audit, dan tata kelola siber.', price: 500000, icon: '💼', badge: 'Layanan Profesional' },
  { id: 'workshop-11-kontrol', title: 'Workshop 11 Kontrol Baru Annex A ISO 27001 (SekolahSiber)', desc: 'Rekaman materi workshop komprehensif implementasi kontrol baru bersama praktisi.', price: 250000, icon: '🎓', badge: 'Workshop Eksklusif' },
  { id: 'kursus-smartphone', title: 'Kursus Proteksi Smartphone di SekolahSiber', desc: 'Panduan komprehensif mengamankan perangkat mobile dari penyadapan dan malware.', price: 99000, icon: '📱', badge: 'Kursus Mandiri' },
  { id: 'traktiran-minres', title: 'Traktiran Secangkir Kopi untuk Minres', desc: 'Dukungan apresiasi atas konten edukasi literasi keamanan siber yang dipublikasikan.', price: 25000, icon: '☕', badge: 'Dukungan Kreator' },
];

const FREE_ITEMS = [
  { title: 'Free Ebook Buku Pegangan Tanggap Insiden', desc: 'Panduan SOP respons cepat ketika terjadi insiden serangan siber pada organisasi.', icon: '📘' },
  { title: 'Free Ebook Panduan Secure SDLC', desc: 'Prinsip integrasi keamanan dalam seluruh siklus pengembangan perangkat lunak.', icon: '📘' },
  { title: 'Free Ebook Modus Operandi Phishing', desc: 'Bedah teknik rekayasa sosial mutakhir dan cara pencegahannya bagi karyawan.', icon: '📘' },
  { title: 'Free Ebook Template Tanggap Insiden Siber', desc: 'Template formulir pelaporan dan eskalasi penanganan insiden keamanan informasi.', icon: '📘' },
  { title: 'Free Excel Penilaian Mandiri 18 Kontrol CIS pada UU PDP', desc: 'Lembar kerja audit kesiapan kontrol teknis selaras undang-undang PDP.', icon: '📊' },
  { title: 'Free Checklist Keamanan Web (Non-Editable)', desc: 'Rangkuman checklist keamanan aplikasi web untuk developer dan tim IT.', icon: '📑' },
  { title: 'Free Kebijakan Anti-Phishing Perusahaan', desc: 'Contoh draft kebijakan formal perlindungan terhadap serangan social engineering.', icon: '📑' },
  { title: 'Free Checklist Keamanan dan Privasi untuk Personal', desc: 'Langkah praktis mengamankan akun pribadi, email, dan perangkat gadget sehari-hari.', icon: '📑' },
  { title: 'Free Checklist Audit Singkat ISO 27001:2022', desc: 'Instrumen pra-audit untuk mengevaluasi kelengkapan dokumen SMKI.', icon: '📑' },
];

function formatRupiah(number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
}

export default function Home() {
  const [activeFilter, setActiveFilter] = useState('ALL');
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [form, setForm] = useState({ nama: '', email: '', organisasi: '' });
  const [loading, setLoading] = useState(false);
  const [freeModal, setFreeModal] = useState(null);

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

      if (data.paymentUrl) {
        window.location.href = data.paymentUrl;
      } else {
        alert('Gagal membuat pembayaran DOKU: ' + (data.message || JSON.stringify(data)));
      }
    } catch (err) {
      setLoading(false);
      alert('Terjadi kesalahan jaringan.');
    }
  };

  return (
    <div style={{ minHeight: '100vh', backgroundColor: '#f8fafc', color: '#0f172a', fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' }}>
      
      {/* SUBTLE LIGHT BACKGROUND GLOW */}
      <div style={{ position: 'fixed', top: 0, left: '50%', transform: 'translateX(-50%)', width: '100%', maxWidth: '1200px', height: '400px', background: 'radial-gradient(circle at 50% 0%, rgba(2, 132, 199, 0.08) 0%, rgba(248, 250, 252, 0) 70%)', pointerEvents: 'none', zIndex: 0 }} />

      {/* NAVBAR */}
      <nav style={{ position: 'sticky', top: 0, zIndex: 50, backdropFilter: 'blur(16px)', WebkitBackdropFilter: 'blur(16px)', backgroundColor: 'rgba(255, 255, 255, 0.88)', borderBottom: '1px solid #e2e8f0', boxShadow: '0 2px 10px rgba(0,0,0,0.03)' }}>
        <div style={{ maxWidth: '1160px', margin: '0 auto', padding: '14px 20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{ width: '42px', height: '42px', borderRadius: '12px', background: 'linear-gradient(135deg, #0284c7, #2563eb)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: '900', color: '#ffffff', fontSize: '18px', boxShadow: '0 4px 12px rgba(2,132,199,0.3)' }}>
              RM
            </div>
            <div>
              <div style={{ fontSize: '16px', fontWeight: '800', letterSpacing: '-0.3px', color: '#0f172a' }}>Restia Moegiono</div>
              <div style={{ fontSize: '11px', color: '#0284c7', fontWeight: '700', letterSpacing: '0.2px' }}>Official Cybersecurity & Privacy Store</div>
            </div>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <span style={{ fontSize: '12px', color: '#047857', background: '#ecfdf5', padding: '6px 14px', borderRadius: '20px', border: '1px solid #a7f3d0', display: 'inline-flex', alignItems: 'center', gap: '6px', fontWeight: '600' }}>
              <span style={{ width: '8px', height: '8px', borderRadius: '50%', backgroundColor: '#10b981', display: 'inline-block' }} />
              QRIS & Bank Auto Delivery
            </span>
          </div>
        </div>
      </nav>

      {/* MAIN CONTAINER */}
      <main style={{ maxWidth: '1160px', margin: '0 auto', padding: '40px 20px 80px', position: 'relative', zIndex: 1 }}>
        
        {/* HERO SECTION */}
        <section style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '40px', alignItems: 'center', marginBottom: '60px', padding: '20px 0' }}>
          
          <div>
            <div style={{ display: 'inline-flex', alignItems: 'center', gap: '8px', padding: '6px 14px', borderRadius: '30px', background: '#e0f2fe', border: '1px solid #bae6fd', color: '#0369a1', fontSize: '12px', fontWeight: '800', marginBottom: '20px' }}>
              🛡️ KEPATUHAN STANDAR ENTERPRISE & UU PDP
            </div>

            <h1 style={{ fontSize: 'clamp(28px, 4.5vw, 44px)', fontWeight: '900', lineHeight: '1.2', letterSpacing: '-0.8px', margin: '0 0 18px', color: '#0f172a' }}>
              Literasi Keamanan Siber & Privasi Data untuk <span style={{ background: 'linear-gradient(135deg, #0284c7 0%, #2563eb 100%)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>Organisasi Modern</span>
            </h1>

            <p style={{ fontSize: '15px', lineHeight: '1.7', color: '#475569', margin: '0 0 28px', maxWidth: '520px' }}>
              Dapatkan panduan implementasi <b>ISO 27001:2022</b>, kepatuhan <b>UU PDP</b>, dan instrumen audit siap pakai karya <b>Restia Moegiono</b>. Berkas PDF berlisensi resmi atas nama Anda terkirim otomatis secara instan setelah pembayaran terkonfirmasi.
            </p>

            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '14px', alignItems: 'center' }}>
              <button 
                onClick={() => {
                  const el = document.getElementById('catalog-section');
                  if (el) el.scrollIntoView({ behavior: 'smooth' });
                }}
                style={{ padding: '14px 28px', borderRadius: '12px', background: 'linear-gradient(135deg, #0284c7, #2563eb)', color: '#ffffff', border: 'none', fontSize: '14px', fontWeight: '800', cursor: 'pointer', boxShadow: '0 8px 20px rgba(2,132,199,0.3)', display: 'inline-flex', alignItems: 'center', gap: '8px', transition: 'all 0.2s' }}
              >
                <span>Lihat Katalog Buku & Tools</span>
                <span>↓</span>
              </button>

              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '12px', color: '#64748b' }}>
                <span>🔒 Terproteksi Password</span>
                <span>•</span>
                <span>⚡ Pengiriman Email Instan</span>
              </div>
            </div>

            {/* TRUST HIGHLIGHTS */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '12px', marginTop: '36px', paddingTop: '24px', borderTop: '1px solid #e2e8f0' }}>
              <div>
                <div style={{ fontSize: '20px', fontWeight: '900', color: '#0284c7' }}>100%</div>
                <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px', fontWeight: '600' }}>Lisensi Resmi Pembeli</div>
              </div>
              <div>
                <div style={{ fontSize: '20px', fontWeight: '900', color: '#0284c7' }}>AES-256</div>
                <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px', fontWeight: '600' }}>Enkripsi Dokumen PDF</div>
              </div>
              <div>
                <div style={{ fontSize: '20px', fontWeight: '900', color: '#0284c7' }}>QRIS & Bank</div>
                <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px', fontWeight: '600' }}>Pembayaran Otomatis</div>
              </div>
            </div>
          </div>

          {/* 3D HERO VISUAL CARD IN BRIGHT FRAME */}
          <div style={{ position: 'relative', display: 'flex', justifyContent: 'center' }}>
            <div style={{ position: 'relative', width: '100%', maxWidth: '420px', aspectRatio: '1/1', borderRadius: '28px', overflow: 'hidden', border: '1px solid #cbd5e1', boxShadow: '0 20px 45px rgba(2, 132, 199, 0.12), 0 4px 16px rgba(0,0,0,0.06)', background: '#0b1528' }}>
              <img 
                src="/images/hero-shield-3d.jpg" 
                alt="Cybersecurity Shield 3D Asset" 
                style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }}
              />
              <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, padding: '20px', background: 'linear-gradient(to top, rgba(7, 13, 24, 0.95), rgba(7, 13, 24, 0.3), transparent)', backdropFilter: 'blur(4px)' }}>
                <div style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '4px 10px', borderRadius: '12px', background: 'rgba(2, 132, 199, 0.25)', border: '1px solid rgba(56, 189, 248, 0.4)', color: '#38bdf8', fontSize: '11px', fontWeight: '800', marginBottom: '6px' }}>
                  🔐 SISTEM PENGAMANAN DIGITAL
                </div>
                <div style={{ fontSize: '13px', color: '#f8fafc', fontWeight: '600' }}>
                  Setiap pembelian otomatis disematkan watermark nama, email, dan kode unik lisensi Anda.
                </div>
              </div>
            </div>
          </div>

        </section>

        {/* FLAGSHIP SPOTLIGHT (BUKU TERLARIS ISO 27001) IN BRIGHT LUXURY CARD */}
        <section style={{ marginBottom: '60px' }}>
          <div style={{ background: '#ffffff', border: '1px solid #cbd5e1', borderRadius: '24px', padding: '36px', position: 'relative', overflow: 'hidden', boxShadow: '0 16px 40px rgba(0, 0, 0, 0.05), 0 2px 8px rgba(0, 0, 0, 0.03)' }}>
            
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '36px', alignItems: 'center' }}>
              
              {/* 3D BOOK COVER */}
              <div style={{ display: 'flex', justifyContent: 'center' }}>
                <div style={{ width: '100%', maxWidth: '280px', aspectRatio: '1/1', borderRadius: '20px', overflow: 'hidden', border: '1px solid #cbd5e1', boxShadow: '0 12px 30px rgba(0,0,0,0.12)' }}>
                  <img 
                    src="/images/ebook-3d.jpg" 
                    alt="Cyber Defense E-Book 3D" 
                    style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }}
                  />
                </div>
              </div>

              {/* BOOK DETAILS */}
              <div>
                <div style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '5px 12px', borderRadius: '20px', background: '#fef3c7', border: '1px solid #fde68a', color: '#b45309', fontSize: '11px', fontWeight: '800', marginBottom: '12px' }}>
                  👑 FLAGSHIP HANDBOOK • PALING BANYAK DIBACA
                </div>

                <h2 style={{ fontSize: '24px', fontWeight: '900', color: '#0f172a', margin: '0 0 10px', lineHeight: '1.25' }}>
                  Buku Pegangan Implementasi ISO 27001:2022
                </h2>

                <p style={{ fontSize: '14px', color: '#475569', lineHeight: '1.65', margin: '0 0 20px' }}>
                  Panduan sistematis membangun, mendokumentasikan, dan mengaudit Sistem Manajemen Keamanan Informasi (SMKI) secara efektif untuk organisasi dari berbagai skala industri.
                </p>

                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', marginBottom: '24px' }}>
                  {['Standar Terbaru 2022', 'Klausul 4 s/d 10 & Annex A', 'Format Audit & Dokumen', 'Lisensi Langsung Atas Nama Anda'].map((item, idx) => (
                    <span key={idx} style={{ fontSize: '12px', background: '#f1f5f9', border: '1px solid #e2e8f0', padding: '5px 12px', borderRadius: '8px', color: '#334155', fontWeight: '600' }}>
                      <span style={{ color: '#0284c7', marginRight: '4px' }}>✓</span> {item}
                    </span>
                  ))}
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: '20px', flexWrap: 'wrap' }}>
                  <div>
                    <div style={{ fontSize: '12px', color: '#94a3b8', textDecoration: 'line-through', fontWeight: '600' }}>{formatRupiah(250000)}</div>
                    <div style={{ fontSize: '26px', fontWeight: '900', color: '#0284c7' }}>{formatRupiah(175000)}</div>
                  </div>

                  <button 
                    onClick={() => openCheckout(FEATURED_BOOKS[0])}
                    style={{ padding: '14px 26px', borderRadius: '12px', background: 'linear-gradient(135deg, #0284c7, #2563eb)', color: '#ffffff', border: 'none', fontWeight: '800', fontSize: '14px', cursor: 'pointer', boxShadow: '0 6px 18px rgba(2,132,199,0.35)', transition: 'all 0.2s' }}
                  >
                    Beli E-Book Sekarang ➔
                  </button>
                </div>
              </div>

            </div>

          </div>
        </section>

        {/* CATALOG SECTION */}
        <section id="catalog-section" style={{ marginBottom: '32px' }}>
          
          <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: '16px', marginBottom: '28px' }}>
            <div>
              <h2 style={{ fontSize: '22px', fontWeight: '900', margin: '0 0 6px', color: '#0f172a' }}>Katalog Produk & Layanan</h2>
              <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>Pilih materi literasi siber, checklist audit, atau konsultasi resmi</p>
            </div>

            {/* FILTER BUTTONS */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
              {[
                { id: 'ALL', label: 'Semua Koleksi' },
                { id: 'EBOOK', label: '📚 E-Book Utama' },
                { id: 'SERVICE', label: '💼 Tools & Layanan' },
                { id: 'FREE', label: '🎁 Materi Gratis' },
              ].map(f => (
                <button
                  key={f.id}
                  onClick={() => setActiveFilter(f.id)}
                  style={{
                    padding: '8px 16px',
                    borderRadius: '10px',
                    fontSize: '12px',
                    fontWeight: activeFilter === f.id ? '800' : '600',
                    background: activeFilter === f.id ? '#0284c7' : '#ffffff',
                    color: activeFilter === f.id ? '#ffffff' : '#64748b',
                    border: activeFilter === f.id ? '1px solid #0284c7' : '1px solid #cbd5e1',
                    boxShadow: activeFilter === f.id ? '0 4px 10px rgba(2,132,199,0.25)' : 'none',
                    cursor: 'pointer',
                    transition: 'all 0.2s'
                  }}
                >
                  {f.label}
                </button>
              ))}
            </div>
          </div>

          {/* E-BOOK GRID */}
          {(activeFilter === 'ALL' || activeFilter === 'EBOOK') && (
            <div style={{ marginBottom: '44px' }}>
              <div style={{ fontSize: '13px', fontWeight: '800', color: '#0284c7', letterSpacing: '0.5px', textTransform: 'uppercase', marginBottom: '16px' }}>
                Buku Pegangan Utama (PDF Berlisensi Resmi)
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '22px' }}>
                {FEATURED_BOOKS.map((book) => (
                  <div key={book.id} style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '20px', padding: '26px', display: 'flex', flexDirection: 'column', justifyContent: 'space-between', boxShadow: '0 6px 18px rgba(0,0,0,0.03)', transition: 'transform 0.2s, box-shadow 0.2s' }}>
                    <div>
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '12px' }}>
                        <span style={{ fontSize: '11px', color: '#0369a1', background: '#e0f2fe', padding: '4px 10px', borderRadius: '6px', fontWeight: '700' }}>
                          {book.category}
                        </span>
                        <span style={{ fontSize: '11px', color: '#b45309', background: '#fef3c7', padding: '4px 10px', borderRadius: '6px', fontWeight: '700' }}>
                          {book.badge}
                        </span>
                      </div>

                      <h3 style={{ fontSize: '17px', fontWeight: '800', color: '#0f172a', margin: '0 0 8px', lineHeight: '1.35' }}>
                        {book.title}
                      </h3>

                      <p style={{ fontSize: '13px', color: '#475569', lineHeight: '1.6', margin: '0 0 16px' }}>
                        {book.subtitle}
                      </p>

                      <div style={{ display: 'flex', flexDirection: 'column', gap: '6px', marginBottom: '22px' }}>
                        {book.highlights.map((h, i) => (
                          <div key={i} style={{ fontSize: '12px', color: '#334155', display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <span style={{ color: '#0284c7', fontWeight: 'bold' }}>✓</span> {h}
                          </div>
                        ))}
                      </div>
                    </div>

                    <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: '16px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <div>
                        {book.originalPrice && (
                          <div style={{ fontSize: '11px', color: '#94a3b8', textDecoration: 'line-through', fontWeight: '600' }}>{formatRupiah(book.originalPrice)}</div>
                        )}
                        <div style={{ fontSize: '20px', fontWeight: '900', color: '#0284c7' }}>{formatRupiah(book.price)}</div>
                      </div>

                      <button
                        onClick={() => openCheckout(book)}
                        style={{ padding: '10px 18px', borderRadius: '10px', background: '#0284c7', color: '#ffffff', border: 'none', fontSize: '13px', fontWeight: '800', cursor: 'pointer', transition: 'all 0.2s', boxShadow: '0 4px 12px rgba(2,132,199,0.25)' }}
                      >
                        Beli E-Book
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* PAID SERVICES & TOOLS */}
          {(activeFilter === 'ALL' || activeFilter === 'SERVICE') && (
            <div style={{ marginBottom: '44px' }}>
              <div style={{ fontSize: '13px', fontWeight: '800', color: '#0284c7', letterSpacing: '0.5px', textTransform: 'uppercase', marginBottom: '16px' }}>
                Tools, Checklist & Layanan Tambahan
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '18px' }}>
                {PAID_SERVICES.map((srv) => (
                  <div key={srv.id} style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderRadius: '18px', padding: '22px', display: 'flex', flexDirection: 'column', justifyContent: 'space-between', boxShadow: '0 4px 14px rgba(0,0,0,0.03)' }}>
                    <div>
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '10px' }}>
                        <span style={{ fontSize: '26px' }}>{srv.icon}</span>
                        <span style={{ fontSize: '11px', color: '#64748b', background: '#f1f5f9', padding: '4px 10px', borderRadius: '6px', fontWeight: '600' }}>
                          {srv.badge}
                        </span>
                      </div>
                      <h4 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>{srv.title}</h4>
                      <p style={{ fontSize: '13px', color: '#475569', margin: '0 0 16px', lineHeight: '1.5' }}>{srv.desc}</p>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderTop: '1px solid #f1f5f9', paddingTop: '14px' }}>
                      <div style={{ fontSize: '17px', fontWeight: '900', color: '#0284c7' }}>{formatRupiah(srv.price)}</div>
                      <button
                        onClick={() => openCheckout(srv)}
                        style={{ padding: '8px 16px', borderRadius: '8px', background: '#f1f5f9', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '12px', fontWeight: '700', cursor: 'pointer' }}
                      >
                        Pilih ➔
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* FREE ITEMS */}
          {(activeFilter === 'ALL' || activeFilter === 'FREE') && (
            <div style={{ marginBottom: '40px' }}>
              <div style={{ fontSize: '13px', fontWeight: '800', color: '#059669', letterSpacing: '0.5px', textTransform: 'uppercase', marginBottom: '16px' }}>
                🎁 Koleksi Materi Edukasi & Template Gratis
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: '16px' }}>
                {FREE_ITEMS.map((free, idx) => (
                  <div key={idx} style={{ background: '#f8fafc', border: '1px dashed #cbd5e1', borderRadius: '16px', padding: '18px', display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
                    <div>
                      <div style={{ fontSize: '22px', marginBottom: '8px' }}>{free.icon}</div>
                      <h5 style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', margin: '0 0 6px' }}>{free.title}</h5>
                      <p style={{ fontSize: '12px', color: '#64748b', margin: 0, lineHeight: '1.5' }}>{free.desc}</p>
                    </div>
                    <button
                      onClick={() => setFreeModal(free)}
                      style={{ marginTop: '14px', padding: '8px 14px', borderRadius: '8px', background: '#ecfdf5', border: '1px solid #a7f3d0', color: '#047857', fontSize: '12px', fontWeight: '700', cursor: 'pointer', textAlign: 'center' }}
                    >
                      Akses Gratis
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}

        </section>

        {/* CHECKOUT MODAL IN CLEAN LIGHT MODE */}
        {selectedProduct && (
          <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(15, 23, 42, 0.65)', backdropFilter: 'blur(8px)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '16px', zIndex: 9999 }}>
            <div style={{ background: '#ffffff', border: '1px solid #cbd5e1', borderRadius: '24px', maxWidth: '440px', width: '100%', padding: '32px 28px', color: '#0f172a', position: 'relative', boxShadow: '0 24px 60px rgba(0,0,0,0.2)' }}>
              
              <button 
                onClick={() => setSelectedProduct(null)} 
                style={{ position: 'absolute', top: '18px', right: '18px', width: '32px', height: '32px', borderRadius: '50%', background: '#f1f5f9', border: '1px solid #e2e8f0', color: '#64748b', fontSize: '14px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
              >
                ✕
              </button>

              <div style={{ textAlign: 'center', marginBottom: '20px' }}>
                <div style={{ fontSize: '36px', marginBottom: '8px' }}>💳</div>
                <h3 style={{ fontSize: '19px', fontWeight: '900', margin: '0 0 6px', color: '#0f172a' }}>Formulir Pembelian</h3>
                <p style={{ fontSize: '12px', color: '#64748b', margin: 0 }}>Lisensi resmi e-book akan dicetak atas data Anda</p>
              </div>

              <div style={{ background: '#f0f9ff', border: '1px solid #bae6fd', borderRadius: '14px', padding: '16px', marginBottom: '20px' }}>
                <div style={{ fontSize: '11px', color: '#0369a1', fontWeight: '700' }}>Item Yang Dipilih:</div>
                <div style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>{selectedProduct.title}</div>
                <div style={{ fontSize: '20px', fontWeight: '900', color: '#0284c7', marginTop: '6px' }}>{formatRupiah(selectedProduct.price)}</div>
              </div>

              <form onSubmit={handlePay}>
                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>Nama Lengkap (Tercetak di Lisensi PDF):</label>
                  <input 
                    required 
                    type="text" 
                    placeholder="Contoh: Budi Santoso, S.Kom"
                    value={form.nama} 
                    onChange={(e) => setForm({ ...form, nama: e.target.value })} 
                    style={{ width: '100%', padding: '12px 14px', borderRadius: '10px', background: '#f8fafc', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '13px', boxSizing: 'border-box', outline: 'none' }}
                  />
                </div>

                <div style={{ marginBottom: '16px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>Alamat Email (Pengiriman E-Book Langsung):</label>
                  <input 
                    required 
                    type="email" 
                    placeholder="nama@perusahaan.com"
                    value={form.email} 
                    onChange={(e) => setForm({ ...form, email: e.target.value })} 
                    style={{ width: '100%', padding: '12px 14px', borderRadius: '10px', background: '#f8fafc', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '13px', boxSizing: 'border-box', outline: 'none' }}
                  />
                </div>

                <div style={{ marginBottom: '22px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>Nama Organisasi / Perusahaan / Instansi:</label>
                  <input 
                    required 
                    type="text" 
                    placeholder="Contoh: PT Keamanan Data Nusantara"
                    value={form.organisasi} 
                    onChange={(e) => setForm({ ...form, organisasi: e.target.value })} 
                    style={{ width: '100%', padding: '12px 14px', borderRadius: '10px', background: '#f8fafc', border: '1px solid #cbd5e1', color: '#0f172a', fontSize: '13px', boxSizing: 'border-box', outline: 'none' }}
                  />
                </div>

                <button 
                  type="submit" 
                  disabled={loading} 
                  style={{ width: '100%', padding: '14px', background: 'linear-gradient(135deg, #0284c7, #2563eb)', color: '#ffffff', border: 'none', borderRadius: '12px', fontSize: '14px', fontWeight: '800', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', boxShadow: '0 6px 18px rgba(2,132,199,0.35)', opacity: loading ? 0.7 : 1 }}
                >
                  {loading ? 'Menyiapkan Pembayaran DOKU...' : 'Lanjut ke Pembayaran (Pilih QRIS / Bank)'}
                </button>
              </form>

              <div style={{ textAlign: 'center', marginTop: '16px', fontSize: '11px', color: '#64748b' }}>
                🔒 Pembayaran resmi diverifikasi via DOKU Payment Gateway
              </div>
            </div>
          </div>
        )}

        {/* FREE MODAL IN LIGHT MODE */}
        {freeModal && (
          <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(15, 23, 42, 0.65)', backdropFilter: 'blur(8px)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '16px', zIndex: 9999 }}>
            <div style={{ background: '#ffffff', border: '1px solid #a7f3d0', borderRadius: '24px', maxWidth: '400px', width: '100%', padding: '32px 28px', color: '#0f172a', textAlign: 'center', boxShadow: '0 24px 60px rgba(0,0,0,0.2)' }}>
              <div style={{ fontSize: '40px', marginBottom: '12px' }}>🎁</div>
              <h3 style={{ fontSize: '18px', fontWeight: '900', margin: '0 0 10px', color: '#0f172a' }}>{freeModal.title}</h3>
              <p style={{ fontSize: '13px', color: '#475569', lineHeight: '1.6', marginBottom: '24px' }}>
                {freeModal.desc}
              </p>
              <div style={{ background: '#ecfdf5', padding: '14px', borderRadius: '12px', fontSize: '12px', color: '#065f46', marginBottom: '22px', fontWeight: '500' }}>
                Materi ini didistribusikan gratis oleh Restia Moegiono sebagai bagian dari program literasi keamanan informasi nasional.
              </div>
              <button 
                onClick={() => setFreeModal(null)}
                style={{ padding: '10px 26px', backgroundColor: '#0284c7', color: '#ffffff', border: 'none', borderRadius: '10px', fontSize: '13px', fontWeight: '700', cursor: 'pointer' }}
              >
                Tutup
              </button>
            </div>
          </div>
        )}

        {/* FOOTER */}
        <footer style={{ marginTop: '80px', borderTop: '1px solid #e2e8f0', paddingTop: '32px', textAlign: 'center', fontSize: '12px', color: '#64748b' }}>
          <div>Hak Cipta © {new Date().getFullYear()} <b>Restia Moegiono</b>. All Rights Reserved.</div>
          <div style={{ marginTop: '6px' }}>Official Portal: <a href="https://store.restiamoegiono.com" style={{ color: '#0284c7', textDecoration: 'none', fontWeight: '600' }}>store.restiamoegiono.com</a></div>
          <div style={{ marginTop: '16px' }}>
            <a href="/admin" style={{ color: '#64748b', textDecoration: 'none', fontSize: '11px', padding: '6px 14px', borderRadius: '8px', background: '#ffffff', border: '1px solid #cbd5e1', fontWeight: '600', display: 'inline-block' }}>
              🔒 Area Pengelola Toko (Admin Portal)
            </a>
          </div>
        </footer>

      </main>
    </div>
  );
}
