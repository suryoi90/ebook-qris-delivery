<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panduan Pembayaran &amp; Pengunduhan — Toko Resmi Restia Moegiono</title>
  <meta name="description" content="Tata cara pembayaran e-book melalui QRIS dan Virtual Account DOKU, serta panduan pengunduhan dan pembukaan dokumen PDF berlisensi resmi.">
  <link rel="canonical" href="https://store.restiamoegiono.com/panduan">
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
    body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: var(--color-bg-page); color: var(--color-text-main); line-height: 1.7; }
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

    .step-box {
      background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px;
      padding: 20px; margin-bottom: 20px; display: flex; gap: 18px; align-items: flex-start;
    }
    .step-num {
      width: 36px; height: 36px; border-radius: 50%; background: var(--color-navy); color: #fff;
      display: flex; align-items: center; justify-content: center; font-weight: 900; flex-shrink: 0;
    }

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
      <span style="font-size:11px; font-weight:800; color:var(--color-cyan); text-transform:uppercase; letter-spacing:0.05em;">Panduan Layanan</span>
      <h1 style="font-size:28px; font-weight:900; color:var(--color-navy); margin:6px 0 20px; letter-spacing:-0.02em;">
        Panduan Pembayaran &amp; Pengunduhan E-Book
      </h1>

      <p style="color:var(--color-text-muted); margin-bottom:28px;">
        Berikut adalah panduan langkah demi langkah untuk menyelesaikan transaksi e-book secara otomatis, aman, dan instan menggunakan payment gateway resmi DOKU.
      </p>

      <div class="step-box">
        <div class="step-num">1</div>
        <div>
          <h3 style="font-size:16px; font-weight:800; color:var(--color-navy); margin-bottom:4px;">Pilih E-Book &amp; Buka Form Checkout</h3>
          <p style="font-size:14px; color:var(--color-text-main);">
            Pilih judul e-book yang Anda butuhkan dari halaman katalog atau halaman detail produk, lalu klik tombol <b>"Beli E-Book Sekarang"</b>.
          </p>
        </div>
      </div>

      <div class="step-box">
        <div class="step-num">2</div>
        <div>
          <h3 style="font-size:16px; font-weight:800; color:var(--color-navy); margin-bottom:4px;">Isi Nama &amp; Email Penerima</h3>
          <p style="font-size:14px; color:var(--color-text-main);">
            Masukkan <b>Nama Lengkap</b> dan <b>Alamat Email Aktif</b> Anda. Nama dan email ini akan digunakan secara sah untuk mencetak penanda air (*watermark*) lisensi personal pada setiap halaman berkas PDF yang Anda beli.
          </p>
        </div>
      </div>

      <div class="step-box">
        <div class="step-num">3</div>
        <div>
          <h3 style="font-size:16px; font-weight:800; color:var(--color-navy); margin-bottom:4px;">Pilih Metode Pembayaran DOKU</h3>
          <p style="font-size:14px; color:var(--color-text-main);">
            Anda akan diarahkan ke gerbang pembayaran aman DOKU. Tersedia metode:
          </p>
          <ul style="font-size:13px; margin:8px 0 0 20px;">
            <li><b>QRIS:</b> Scan menggunakan BCA Mobile, Livin' Mandiri, BRImo, BNI Mobile, GoPay, OVO, Dana, ShopeePay, LinkAja, atau aplikasi perbankan lainnya.</li>
            <li><b>Virtual Account:</b> Transfer ke nomor rekening virtual unik yang disediakan sistem.</li>
          </ul>
        </div>
      </div>

      <div class="step-box">
        <div class="step-num">4</div>
        <div>
          <h3 style="font-size:16px; font-weight:800; color:var(--color-navy); margin-bottom:4px;">Pengiriman Dokumen Otomatis</h3>
          <p style="font-size:14px; color:var(--color-text-main);">
            Dalam waktu &lt; 1 jam setelah pembayaran Anda berhasil diverifikasi, sistem akan membubuhkan stempel watermark kriptografis dan mengirimkan tautan unduhan langsung ke email Anda. Anda juga akan dialihkan ke halaman konfirmasi yang memuat tombol unduh instan.
          </p>
        </div>
      </div>

      <div style="background:var(--color-blue-soft); border-left:4px solid var(--color-cyan); padding:16px 20px; border-radius:0 12px 12px 0; margin-top:32px;">
        <h4 style="font-size:14px; font-weight:800; color:var(--color-navy); margin-bottom:4px;">💡 Butuh Bantuan Unduh Ulang?</h4>
        <p style="font-size:13px; color:var(--color-text-main); margin:0;">
          Jika email Anda tidak kunjung tiba atau terhapus, gunakan menu <a href="/cek-pesanan" style="color:var(--color-cyan); font-weight:700;">Cek Pesanan &amp; Unduh Ulang Mandiri</a> dengan memasukkan invoice atau email Anda.
        </p>
      </div>
    </div>
  </main>

  <footer>
    <div class="container">
      <div class="footer-links">
        <a href="/">Katalog E-Book</a>
        <a href="/cek-pesanan">Cek Pesanan</a>
        <a href="/lisensi">Kebijakan Lisensi</a>
        <a href="/syarat-ketentuan">Syarat &amp; Ketentuan</a>
        <a href="https://restiamoegiono.com" target="_blank">Portal Utama</a>
      </div>
      <div>&copy; <?= date('Y') ?> Restia Moegiono. Seluruh Hak Cipta Dilindungi Undang-Undang.</div>
    </div>
  </footer>
</body>
</html>
