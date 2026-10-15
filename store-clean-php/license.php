<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kebijakan Lisensi &amp; DRM Watermark — Toko Resmi Restia Moegiono</title>
  <meta name="description" content="Ketentuan lisensi penggunaan dokumen e-book resmi Restia Moegiono, perlindungan hak cipta, dan mekanisme penanda air (watermark) personal.">
  <link rel="canonical" href="https://store.restiamoegiono.com/lisensi">
  <meta name="theme-color" content="#0F224A">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --color-navy: #0F224A;
      --color-navy-dark: #0B1B3D;
      --color-gold: #F2B200;
      --color-cyan: #00AEFF;
      --color-blue-soft: #E8F3FF;
      --color-blue-border: #D6EAFF;
      --color-bg-page: #F8FAFC;
      --color-text-main: #0F224A;
      --color-text-muted: #64748b;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--color-bg-page); color: var(--color-text-main); line-height: 1.8; }
    a { color: inherit; text-decoration: none; }
    .container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
    .navbar { position: sticky; top: 0; z-index: 100; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); border-bottom: 1px solid var(--color-blue-border); }
    .navbar-inner { display: flex; align-items: center; justify-content: space-between; height: 72px; }
    .brand-group { display: flex; align-items: center; gap: 12px; }
    .brand-logo { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, var(--color-cyan), var(--color-navy)); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; }
    .brand-title { font-size: 16px; font-weight: 800; color: var(--color-navy); line-height: 1.2; }
    .brand-sub { font-size: 11px; color: var(--color-text-muted); font-weight: 600; }
    .badge-portal { font-size: 12px; font-weight: 700; color: var(--color-navy); background: var(--color-blue-soft); padding: 7px 14px; border-radius: 10px; border: 1px solid var(--color-blue-border); }

    .content-card {
      background: #ffffff; border: 1px solid var(--color-blue-border); border-radius: 24px;
      padding: 40px; margin: 48px 0 64px; box-shadow: 0 10px 30px rgba(15, 34, 74, 0.04);
    }
    @media (max-width: 600px) { .content-card { padding: 24px 20px; margin: 24px 0; } }

    h2 { font-size: 18px; font-weight: 800; color: var(--color-navy); margin: 24px 0 8px; }
    p { margin-bottom: 14px; font-size: 14px; }
    ul { margin-left: 20px; margin-bottom: 16px; font-size: 14px; }
    li { margin-bottom: 6px; }

    footer { border-top: 2px solid var(--color-cyan); padding: 48px 0 28px; background: var(--color-navy-dark); color: #94a3b8; font-size: 13px; text-align: center; }
    .footer-links { display: flex; justify-content: center; gap: 20px; margin-bottom: 16px; font-weight: 700; flex-wrap: wrap; }
    .footer-links a { color: var(--color-blue-soft); }
    .footer-links a:hover { color: var(--color-cyan); }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="container navbar-inner">
      <a href="/" class="brand-group">
        <div class="brand-logo">RM</div>
        <div>
          <div class="brand-title">Restia Moegiono</div>
          <div class="brand-sub">Official Cybersecurity &amp; Privacy Store</div>
        </div>
      </a>
      <div>
        <a href="/" class="badge-portal">📚 Kembali ke Toko</a>
      </div>
    </div>
  </nav>

  <main class="container">
    <div class="content-card">
      <span style="font-size:11px; font-weight:800; color:var(--color-cyan); text-transform:uppercase; letter-spacing:0.05em;">Ketentuan Hak Cipta &amp; Lisensi</span>
      <h1 style="font-size:28px; font-weight:900; color:var(--color-navy); margin:6px 0 20px; letter-spacing:-0.02em;">
        Kebijakan Lisensi Dokumen &amp; Perlindungan DRM Watermark
      </h1>

      <div style="background:var(--color-blue-soft); border-left:4px solid var(--color-cyan); padding:16px 20px; border-radius:0 12px 12px 0; margin-bottom:28px;">
        <h4 style="font-size:14px; font-weight:800; color:var(--color-navy); margin-bottom:4px;">Pernyataan Integritas Intelektual</h4>
        <p style="font-size:13px; color:var(--color-text-main); margin:0;">
          Seluruh berkas e-book, instrumen audit, template SOP, dan silabus yang didistribusikan melalui <b>store.restiamoegiono.com</b> merupakan karya orisinal <b>Restia Moegiono</b> yang dilindungi Undang-Undang Republik Indonesia Nomor 28 Tahun 2014 tentang Hak Cipta.
        </p>
      </div>

      <h2>1. Ruang Lingkup Lisensi Penggunaan</h2>
      <p>
        Setiap pembelian satu unit e-book memberikan lisensi penggunaan non-eksklusif dan tidak dapat dipindahtangankan kepada individu atau institusi pembeli yang terdaftar untuk keperluan:
      </p>
      <ul>
        <li>Pembelajaran mandiri, referensi kerja profesional, dan penyusunan instrumen tata kelola internal organisasi pembeli.</li>
        <li>Penyelarasan kebijakan keamanan informasi dan kepatuhan perlindungan data di lingkungan internal instansi Anda.</li>
        <li>Penyimpanan salinan digital pada perangkat pribadi (Laptop, Tablet, Smartphone, atau Penyimpanan Awan Pribadi) milik pembeli sah.</li>
      </ul>

      <h2>2. Batasan &amp; Larangan Penggunaan</h2>
      <p>
        Demi menjaga integritas profesi dan menghormati hak cipta intelektual, pembeli dilarang keras untuk:
      </p>
      <ul>
        <li>Mengunggah, mempublikasikan, atau menyebarkan berkas PDF secara terbuka di internet, media sosial, grup perpesanan publik, atau situs berbagi dokumen (seperti Scribd, SlideShare, GitHub, dsb).</li>
        <li>Menjual kembali, menyewakan, atau mengomersialisasikan berkas e-book maupun materi turunannya tanpa izin tertulis resmi dari Restia Moegiono.</li>
        <li>Menghapus, memodifikasi, mengaburkan, atau merekayasa penanda air (*watermark*) lisensi yang tercantum pada dokumen PDF.</li>
      </ul>

      <h2>3. Mekanisme Penanda Air (Dynamic DRM Watermark)</h2>
      <p>
        Setiap berkas PDF yang diterbitkan oleh sistem kami melalui proses penyematan penanda air kriptografis secara otomatis pada bagian footer setiap halaman. Watermark ini memuat:
      </p>
      <ul>
        <li><b>Nama Lengkap Pembeli Sah</b></li>
        <li><b>Alamat Email Resmi Terdaftar</b></li>
        <li><b>Nomor Transaksi Invoice Unik</b></li>
      </ul>
      <p>
        Penyematan ini bertujuan sebagai bukti keabsahan kepemilikan lisensi bagi pembeli, sekaligus alat pelacak digital apabila terjadi kebocoran atau pembajakan dokumen secara ilegal.
      </p>

      <h2>4. Pembaruan Materi &amp; Revisi Regulasi</h2>
      <p>
        Apabila terjadi pembaruan pedoman teknis atau penyesuaian regulasi nasional (seperti aturan turunan UU PDP atau revisi klausul ISO), pembeli sah yang terdaftar berhak mendapatkan pemberitahuan dan salinan edisi revisi secara gratis yang dikirimkan ke email terdaftar.
      </p>
    </div>
  </main>

  <footer>
    <div class="container">
      <div class="footer-links">
        <a href="/">Katalog E-Book</a>
        <a href="/cek-pesanan">Cek Pesanan</a>
        <a href="/panduan">Panduan Pembayaran</a>
        <a href="/syarat-ketentuan">Syarat &amp; Ketentuan</a>
        <a href="https://restiamoegiono.com" target="_blank">Portal Utama</a>
      </div>
      <div>&copy; <?= date('Y') ?> Restia Moegiono. Seluruh Hak Cipta Dilindungi Undang-Undang.</div>
    </div>
  </footer>
</body>
</html>
