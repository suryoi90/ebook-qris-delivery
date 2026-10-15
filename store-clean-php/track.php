<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$db = StoreDB::get();
$searchQuery = trim($_GET['q'] ?? '');
$orders = [];
$searched = false;

if (!empty($searchQuery)) {
    $searched = true;
    try {
        $stmt = $db->prepare("SELECT o.*, p.title as prod_title, p.cover_image, p.version, p.pages, p.slug as prod_slug 
            FROM `store_orders` o
            LEFT JOIN `store_products` p ON o.product_id = p.id
            WHERE o.invoice_number = :q OR o.customer_email = :q
            ORDER BY o.created_at DESC LIMIT 20");
        $stmt->execute(['q' => $searchQuery]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $orders = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cek Pesanan &amp; Unduh Ulang Mandiri — Toko Resmi Restia Moegiono</title>
  <meta name="description" content="Lacak status pesanan e-book, unduh ulang berkas PDF berlisensi resmi, atau minta pengiriman ulang ke alamat email Anda.">
  <link rel="canonical" href="https://store.restiamoegiono.com/cek-pesanan">
  <meta name="theme-color" content="#0F224A">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --color-navy: #0F224A;
      --color-navy-dark: #0B1B3D;
      --color-gold: #F2B200;
      --color-gold-accent: #F3AC10;
      --color-cyan: #00AEFF;
      --color-cyan-electric: #1CA2FF;
      --color-blue-soft: #E8F3FF;
      --color-blue-border: #D6EAFF;
      --color-bg-page: #F8FAFC;
      --color-bg-card: #FFFFFF;
      --color-text-main: #0F224A;
      --color-text-muted: #64748b;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      background-color: var(--color-bg-page);
      color: var(--color-text-main);
      line-height: 1.6;
    }
    a { color: inherit; text-decoration: none; }
    button { font-family: inherit; }

    .container { max-width: 1180px; margin: 0 auto; padding: 0 20px; }

    .navbar {
      position: sticky; top: 0; z-index: 100;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--color-blue-border);
    }
    .navbar-inner {
      display: flex; align-items: center; justify-content: space-between;
      height: 72px; gap: 16px;
    }
    .brand-group { display: flex; align-items: center; gap: 12px; }
    .brand-logo {
      width: 42px; height: 42px; border-radius: 12px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      display: flex; align-items: center; justify-content: center;
      color: #ffffff; font-weight: 900; font-size: 18px;
    }
    .brand-title { font-size: 16px; font-weight: 800; color: var(--color-navy); line-height: 1.2; }
    .brand-sub { font-size: 11px; color: var(--color-text-muted); font-weight: 600; }

    .nav-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .badge-portal {
      font-size: 12px; font-weight: 700; color: var(--color-navy);
      background: var(--color-blue-soft); padding: 7px 14px; border-radius: 10px;
      border: 1px solid var(--color-blue-border);
    }

    /* Page Content */
    .track-hero {
      text-align: center;
      max-width: 680px;
      margin: 48px auto 32px;
    }
    .track-title {
      font-size: 30px; font-weight: 900; color: var(--color-navy);
      letter-spacing: -0.02em; margin-bottom: 8px;
    }
    .track-subtitle {
      font-size: 15px; color: var(--color-text-muted); line-height: 1.6;
    }

    .search-box-card {
      background: #ffffff;
      border: 1px solid var(--color-blue-border);
      border-radius: 20px;
      padding: 24px;
      max-width: 640px;
      margin: 0 auto 40px;
      box-shadow: 0 10px 30px rgba(15, 34, 74, 0.04);
    }
    .search-input-group {
      display: flex;
      gap: 10px;
    }
    .search-input {
      flex: 1;
      padding: 14px 18px;
      border-radius: 12px;
      border: 1px solid var(--color-blue-border);
      font-size: 14px;
      outline: none;
      font-family: inherit;
    }
    .search-input:focus {
      border-color: var(--color-cyan);
      box-shadow: 0 0 0 3px rgba(0, 174, 255, 0.2);
    }
    .btn-search {
      padding: 14px 24px;
      border-radius: 12px;
      background: var(--color-navy);
      color: #ffffff;
      font-size: 14px;
      font-weight: 800;
      border: none;
      cursor: pointer;
      transition: background 0.15s;
    }
    .btn-search:hover { background: var(--color-cyan); }

    /* Order Results */
    .results-container {
      max-width: 800px;
      margin: 0 auto 64px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .order-card-item {
      background: #ffffff;
      border: 1px solid var(--color-blue-border);
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 4px 16px rgba(15, 34, 74, 0.03);
      display: flex;
      gap: 20px;
      align-items: center;
      flex-wrap: wrap;
    }
    .order-cover-thumb {
      width: 70px;
      aspect-ratio: 3/4;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid var(--color-blue-border);
      flex-shrink: 0;
    }
    .order-meta-info { flex: 1; min-width: 240px; }
    .invoice-tag {
      font-size: 11px;
      font-weight: 800;
      color: var(--color-navy);
      background: var(--color-blue-soft);
      padding: 3px 8px;
      border-radius: 6px;
      display: inline-block;
      margin-bottom: 6px;
    }
    .order-prod-title {
      font-size: 16px;
      font-weight: 800;
      color: var(--color-navy);
      margin-bottom: 4px;
    }
    .order-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 20px;
    }
    .status-paid { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

    .btn-action-download {
      padding: 10px 18px;
      border-radius: 10px;
      background: var(--color-cyan);
      color: #ffffff;
      font-size: 13px;
      font-weight: 800;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s;
    }
    .btn-action-download:hover { background: var(--color-navy); }

    /* Footer */
    footer {
      border-top: 2px solid var(--color-cyan); padding: 48px 0 28px; background: var(--color-navy-dark);
      color: #94a3b8; font-size: 13px; text-align: center;
    }
    .footer-links { display: flex; justify-content: center; gap: 20px; margin-bottom: 16px; font-weight: 700; flex-wrap: wrap; }
    .footer-links a { color: var(--color-blue-soft); }
    .footer-links a:hover { color: var(--color-cyan); }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="container navbar-inner">
      <a href="/" class="brand-group">
        <div class="brand-logo">RM</div>
        <div>
          <div class="brand-title">Restia Moegiono</div>
          <div class="brand-sub">Official Cybersecurity &amp; Privacy Store</div>
        </div>
      </a>
      <div class="nav-actions">
        <a href="/" class="badge-portal">
          📚 Katalog E-Book
        </a>
        <a href="https://restiamoegiono.com/course/" class="badge-portal" style="background:#f0fdf4; color:#166534; border-color:#bbf7d0;">
          🎓 Ruang Belajar LMS
        </a>
        <a href="https://restiamoegiono.com" target="_blank" class="badge-portal">
          🌐 Portal Utama
        </a>
      </div>
    </div>
  </nav>

  <main class="container">
    <section class="track-hero">
      <div style="font-size:42px; margin-bottom:8px;">🔍</div>
      <h1 class="track-title">Cek Status Pesanan &amp; Unduh Ulang</h1>
      <p class="track-subtitle">
        Masukkan nomor invoice (contoh: <code>RM-17...</code>) atau alamat email yang Anda gunakan saat pembelian untuk mengakses dokumen resmi Anda kembali.
      </p>
    </section>

    <!-- FORM PENCARIAN -->
    <div class="search-box-card">
      <form action="/cek-pesanan" method="GET">
        <div class="search-input-group">
          <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Nomor Invoice atau Alamat Email..." required class="search-input">
          <button type="submit" class="btn-search">
            Cari Pesanan
          </button>
        </div>
      </form>
    </div>

    <!-- HASIL PENCARIAN -->
    <?php if ($searched): ?>
      <div class="results-container">
        <?php if (empty($orders)): ?>
          <div style="text-align:center; padding:48px 20px; background:#ffffff; border:1px solid var(--color-blue-border); border-radius:18px;">
            <div style="font-size:36px; margin-bottom:8px;">📭</div>
            <h3 style="font-size:18px; font-weight:800; color:var(--color-navy); margin-bottom:6px;">Pesanan Tidak Ditemukan</h3>
            <p style="font-size:13px; color:var(--color-text-muted); max-width:440px; margin:0 auto;">
              Pastikan Anda memasukkan nomor invoice atau alamat email yang persis sama dengan saat melakukan pembayaran.
            </p>
          </div>
        <?php else: ?>
          <h2 style="font-size:18px; font-weight:800; color:var(--color-navy); margin-bottom:4px;">
            Ditemukan <?= count($orders) ?> Riwayat Pesanan:
          </h2>
          <?php foreach ($orders as $ord): ?>
            <?php 
              $isPaid = ($ord['payment_status'] === 'PAID');
              $cover = !empty($ord['cover_image']) ? $ord['cover_image'] : '/images/book-cover-450.webp';
            ?>
            <div class="order-card-item">
              <img src="<?= htmlspecialchars($cover) ?>" alt="Cover" class="order-cover-thumb">
              <div class="order-meta-info">
                <span class="invoice-tag"><?= htmlspecialchars($ord['invoice_number']) ?></span>
                <div class="order-prod-title"><?= htmlspecialchars($ord['prod_title'] ?: $ord['product_id']) ?></div>
                <div style="font-size:12px; color:var(--color-text-muted); margin-bottom:6px;">
                  Penerima: <b><?= htmlspecialchars($ord['customer_name']) ?></b> (<?= htmlspecialchars($ord['customer_email']) ?>)
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                  <span class="order-status-badge <?= $isPaid ? 'status-paid' : 'status-pending' ?>">
                    <?= $isPaid ? '✓ Pembayaran LUNAS' : '⏳ Menunggu Pembayaran' ?>
                  </span>
                  <span style="font-size:12px; color:var(--color-text-muted);">
                    Tanggal: <?= date('d M Y, H:i', strtotime($ord['created_at'])) ?> WIB
                  </span>
                </div>
                <?php if ($isPaid && !empty($ord['pdf_password'])): ?>
                  <div style="margin-top:8px; display:inline-flex; align-items:center; gap:8px; background:#f0fdf4; border:1px dashed #059669; padding:5px 12px; border-radius:8px;">
                    <span style="font-size:11px; font-weight:800; color:#047857;">🔐 Password PDF:</span>
                    <code style="font-size:15px; font-weight:900; color:#065f46; letter-spacing:3px; font-family:'SF Mono',Consolas,monospace;"><?= htmlspecialchars($ord['pdf_password']) ?></code>
                  </div>
                <?php endif; ?>
              </div>
              <div>
                <?php if ($isPaid): ?>
                  <button type="button" class="btn-action-download" onclick="requestResendEmail('<?= htmlspecialchars($ord['invoice_number']) ?>')">
                    📩 Kirim Ulang Email
                  </button>
                <?php else: ?>
                  <span style="font-size:12px; color:#b45309; font-weight:700;">Menunggu verifikasi DOKU</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-links">
        <a href="/">Katalog E-Book</a>
        <a href="/panduan">Panduan Pembayaran</a>
        <a href="/lisensi">Kebijakan Lisensi DRM</a>
        <a href="/syarat-ketentuan">Syarat &amp; Ketentuan</a>
        <a href="https://restiamoegiono.com" target="_blank">Portal Utama</a>
      </div>
      <div>
        &copy; <?= date('Y') ?> Restia Moegiono. Seluruh Hak Cipta Dilindungi Undang-Undang.
      </div>
    </div>
  </footer>

  <script>
    async function requestResendEmail(inv) {
      if (!confirm(`Kirim ulang berkas PDF untuk invoice ${inv} ke alamat email terdaftar?`)) return;
      try {
        const res = await fetch('/api/order-status.php?action=resend&invoice=' + encodeURIComponent(inv), {
          method: 'POST'
        });
        const data = await res.json();
        if (data.success) {
          alert('✓ ' + (data.message || 'Berkas e-book resmi berhasil dikirimkan ulang ke alamat email Anda! Silakan periksa inbox atau folder spam.'));
        } else {
          alert('Gagal: ' + (data.error || data.message || 'Silakan hubungi dukungan order@restiamoegiono.com'));
        }
      } catch (e) {
        alert('Terjadi kesalahan jaringan.');
      }
    }
  </script>
</body>
</html>
