<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$db = StoreDB::get();
$products = $db->query("SELECT * FROM `store_products` WHERE `is_active` = 1 ORDER BY `price` DESC")->fetchAll();

$activePromo = null;
try {
    $promoStmt = $db->query("SELECT * FROM `store_coupons` 
        WHERE `is_active` = 1 
          AND (expires_at IS NULL OR expires_at >= CURDATE()) 
          AND (usage_limit = 0 OR used_count < usage_limit) 
        ORDER BY `id` DESC LIMIT 1");
    $activePromo = $promoStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $activePromo = null;
}

$settings = [];
try {
    $settingsStmt = $db->query("SELECT `setting_key`, `setting_value` FROM `store_settings`");
    $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $settings = [];
}

$heroImage = !empty($settings['hero_image']) ? $settings['hero_image'] : '/images/hero-book-420.webp';
$heroTag = !empty($settings['hero_tag']) ? $settings['hero_tag'] : '🛡️ LITERASI RESMI & KEPATUHAN ENTERPRISE';
$heroTitle = !empty($settings['hero_title']) ? $settings['hero_title'] : 'Pustaka Keamanan Siber & <span>Privasi Data Digital</span>';
$heroDesc = !empty($settings['hero_desc']) ? $settings['hero_desc'] : 'Dapatkan panduan implementasi <b>ISO 27001:2022</b>, kepatuhan <b>UU PDP No. 27/2022</b>, dan instrumen audit siap pakai karya <b>Restia Moegiono</b>. Berkas PDF berlisensi resmi atas nama Anda terkirim otomatis setelah pembayaran melalui <b>DOKU (QRIS & Virtual Account)</b> terkonfirmasi.';

function renderSafeHtml($html) {
    if (empty($html)) return '';
    return strip_tags($html, '<p><br><strong><b><em><i><u><s><ul><ol><li><h2><h3><h4><h5><h6><blockquote><span><a>');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restia Moegiono — Pustaka &amp; Instrumen Kerja Keamanan Siber</title>
  <meta name="description" content="Toko pustaka resmi Restia Moegiono. Dapatkan buku panduan implementasi ISO 27001:2022, kepatuhan UU PDP, serta instrumen audit siap pakai dengan watermark lisensi resmi.">
  <meta name="author" content="Restia Moegiono">
  <link rel="canonical" href="https://store.restiamoegiono.com/">
  <meta name="theme-color" content="#0F224A">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Restia Moegiono Official Store">
  <meta property="og:url" content="https://store.restiamoegiono.com/">
  <meta property="og:title" content="Restia Moegiono — Pustaka &amp; Instrumen Kerja Keamanan Siber">
  <meta property="og:description" content="Buku panduan ISO 27001:2022 dan kepatuhan UU PDP karya Restia Moegiono. Pembayaran aman via DOKU QRIS & Virtual Account.">
  <meta property="og:image" content="<?= htmlspecialchars(strpos($heroImage, 'http') === 0 ? $heroImage : 'https://store.restiamoegiono.com' . $heroImage) ?>">

  <!-- Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- Typography with font-display: swap for Instant Rendering -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">

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
      --color-text-sub: #475569;

      /* Fluid Typography (CSS clamp): Scales smoothly from 320px mobile to 4K TV */
      --font-hero-title: clamp(1.85rem, 3.8vw + 0.6rem, 3.25rem);
      --font-section-title: clamp(1.35rem, 2.4vw + 0.4rem, 2.15rem);
      --font-card-title: clamp(1.025rem, 1.1vw + 0.65rem, 1.25rem);
      --font-body: clamp(0.925rem, 0.35vw + 0.8rem, 1.05rem);
      --font-small: clamp(0.775rem, 0.2vw + 0.725rem, 0.875rem);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html {
      text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: var(--color-bg-page);
      color: var(--color-text-main);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      overflow-x: hidden;
      touch-action: manipulation;
      -webkit-tap-highlight-color: transparent;
      word-break: break-word;
      overflow-wrap: break-word;
    }
    a { color: inherit; text-decoration: none; }
    button { font-family: inherit; }

    .container {
      width: 100%;
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 clamp(16px, 3.5vw, 32px);
    }

    /* Navbar */
    .navbar {
      position: sticky; top: 0; z-index: 100;
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--color-blue-border);
      box-shadow: 0 2px 10px rgba(15, 34, 74, 0.03);
    }
    .navbar-inner {
      display: flex; align-items: center; justify-content: space-between;
      height: 72px; gap: 12px;
    }
    .brand-group { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .brand-logo {
      width: 44px; height: 44px; border-radius: 12px;
      background: linear-gradient(135deg, var(--color-navy), var(--color-cyan));
      color: #ffffff; display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 18px; letter-spacing: -0.5px;
      box-shadow: 0 4px 14px rgba(0, 174, 255, 0.35); flex-shrink: 0;
    }
    .brand-title { font-weight: 800; font-size: 16px; color: var(--color-navy); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .brand-sub { font-size: 11px; color: var(--color-text-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .nav-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .badge-portal {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 20px;
      background: var(--color-blue-soft); border: 1px solid var(--color-blue-border);
      font-size: 12px; font-weight: 700; color: var(--color-navy);
      transition: all 0.15s ease; white-space: nowrap;
    }
    .badge-portal:hover { background: var(--color-blue-border); color: var(--color-navy-dark); }

    .badge-doku {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: 20px;
      background: #FFF9E6; border: 1px solid #FDE68A;
      font-size: 12px; font-weight: 700; color: #B45309; white-space: nowrap;
    }
    .pulse-dot {
      width: 7px; height: 7px; border-radius: 50%; background: var(--color-gold);
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(242, 178, 0, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(242, 178, 0, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(242, 178, 0, 0); }
    }

    @media (max-width: 768px) {
      .navbar-inner { height: 64px; }
      .brand-logo { width: 38px; height: 38px; font-size: 16px; border-radius: 10px; }
      .brand-title { font-size: 14.5px; }
      .brand-sub { display: none; }
      .badge-portal { padding: 6px 10px; font-size: 11.5px; }
      .hide-on-mobile { display: none !important; }
    }

    /* Promo Banner */
    .promo-banner {
      background: linear-gradient(135deg, #FFF9E6, #FEF3C7);
      border: 1px solid #FDE68A;
      border-radius: 14px;
      padding: 10px 18px;
      margin-top: 18px;
      font-size: 13px;
      color: #92400E;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    /* Hero Section with Fluid Layout */
    .hero {
      padding: clamp(28px, 4vw, 56px) 0 clamp(24px, 3.5vw, 48px);
      display: grid; grid-template-columns: 1.08fr 0.92fr; gap: clamp(24px, 4vw, 48px); align-items: center;
    }
    @media (max-width: 900px) {
      .hero { grid-template-columns: 1fr; gap: 28px; }
      .hero-3d-card { max-width: 480px; margin: 0 auto; width: 100%; }
    }
    .hero-tag {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: 20px;
      background: var(--color-blue-soft); border: 1px solid var(--color-blue-border);
      color: var(--color-navy); font-size: 11.5px; font-weight: 800; margin-bottom: 16px;
    }
    .hero-title {
      font-size: var(--font-hero-title); font-weight: 900;
      color: var(--color-navy); line-height: 1.2; letter-spacing: -0.025em; margin-bottom: 16px;
      text-wrap: balance;
    }
    .hero-title span {
      background: linear-gradient(135deg, var(--color-cyan), var(--color-gold));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hero-desc { font-size: var(--font-body); color: var(--color-text-sub); margin-bottom: 24px; line-height: 1.65; }
    
    .btn-cta {
      display: inline-flex; align-items: center; justify-content: center; gap: 10px;
      padding: 15px 28px; border-radius: 14px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      color: #ffffff; font-weight: 800; font-size: 15px; border: none; cursor: pointer;
      box-shadow: 0 10px 25px rgba(0, 174, 255, 0.35); transition: all 0.2s ease;
      touch-action: manipulation; min-height: 48px;
    }
    .btn-cta:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(0, 174, 255, 0.5); }

    .trust-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: clamp(10px, 2vw, 20px); margin-top: 28px;
      padding-top: 24px; border-top: 1px solid var(--color-blue-border);
    }
    @media (max-width: 480px) {
      .trust-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
      .trust-grid > div:last-child { grid-column: span 2; }
    }
    .trust-val { font-size: clamp(18px, 2.5vw, 22px); font-weight: 900; color: var(--color-cyan); }
    .trust-lbl { font-size: 11.5px; color: var(--color-text-muted); font-weight: 600; margin-top: 2px; }

    .hero-3d-card {
      background: #ffffff; border: 1px solid var(--color-blue-border); border-radius: 28px;
      padding: 12px; box-shadow: 0 24px 50px rgba(15, 34, 74, 0.08);
      position: relative; overflow: hidden;
      width: 100%; max-width: 520px; margin: 0 auto;
    }
    .hero-3d-img {
      width: 100%; height: auto; border-radius: 20px; display: block;
      transition: transform 0.3s ease;
    }
    .hero-3d-card:hover .hero-3d-img { transform: scale(1.02); }

    /* Catalog Grid with Universal Breakpoints */
    .section-title { font-size: var(--font-section-title); font-weight: 900; margin-bottom: 8px; color: var(--color-navy); text-wrap: balance; }
    .section-sub { font-size: var(--font-body); color: var(--color-text-muted); margin-bottom: 28px; }
    .catalog-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 330px), 1fr));
      gap: clamp(16px, 2.5vw, 28px); margin-bottom: 64px;
    }
    @media (max-width: 640px) {
      .catalog-grid { grid-template-columns: 1fr; gap: 16px; }
      .product-card { padding: 18px 16px; border-radius: 18px; }
    }
    @media (min-width: 1920px) {
      .container { max-width: 1380px; }
      .catalog-grid { grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); }
    }
    .product-card {
      background: #ffffff; border: 1px solid var(--color-blue-border); border-radius: 24px;
      padding: 24px; display: flex; flex-direction: column; justify-content: space-between;
      transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(15, 34, 74, 0.03);
    }
    .product-card:hover {
      transform: translateY(-4px); box-shadow: 0 16px 36px rgba(0, 174, 255, 0.12);
      border-color: var(--color-cyan);
    }
    .card-top { display: flex; gap: 16px; margin-bottom: 16px; }
    .card-thumb {
      width: 80px; height: 110px; border-radius: 12px; overflow: hidden;
      background: var(--color-navy); flex-shrink: 0; box-shadow: 0 6px 16px rgba(15, 34, 74, 0.12);
    }
    .card-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .card-meta { flex: 1; }
    .card-badge {
      display: inline-block; padding: 3px 10px; border-radius: 20px;
      background: var(--color-blue-soft); color: var(--color-navy); border: 1px solid var(--color-blue-border);
      font-size: 11px; font-weight: 800; margin-bottom: 6px;
    }
    .card-title { font-size: var(--font-card-title); font-weight: 800; color: var(--color-navy); line-height: 1.35; margin-bottom: 6px; text-wrap: balance; }
    .card-specs { font-size: 12px; color: var(--color-text-muted); font-weight: 600; }
    
    /* Rich Text Description styling */
    .card-desc {
      font-size: 13px; color: var(--color-text-sub); margin-bottom: 20px; line-height: 1.6;
      max-height: 120px; overflow-y: auto; padding-right: 4px;
    }
    .card-desc::-webkit-scrollbar { width: 4px; }
    .card-desc::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .card-desc p { margin-bottom: 6px; }
    .card-desc p:last-child { margin-bottom: 0; }
    .card-desc ul, .card-desc ol { margin: 6px 0 6px 18px; padding-left: 0; }
    .card-desc li { margin-bottom: 3px; }
    .card-desc strong, .card-desc b { color: var(--color-navy); font-weight: 700; }
    .card-desc blockquote {
      border-left: 3px solid var(--color-cyan); padding-left: 8px; margin: 6px 0;
      color: var(--color-navy); background: var(--color-blue-soft); border-radius: 0 6px 6px 0; font-style: italic;
    }

    .card-footer {
      display: flex; align-items: center; justify-content: space-between;
      padding-top: 18px; border-top: 1px solid var(--color-blue-border);
    }
    .card-price-num { font-size: 20px; font-weight: 900; color: var(--color-navy); }
    .btn-buy {
      padding: 10px 18px; border-radius: 12px;
      background: var(--color-cyan); color: #ffffff; font-size: 13px; font-weight: 800;
      border: none; cursor: pointer; transition: all 0.15s ease;
      box-shadow: 0 4px 12px rgba(0, 174, 255, 0.3);
    }
    .btn-buy:hover { background: var(--color-navy); box-shadow: 0 4px 12px rgba(15, 34, 74, 0.3); }

    .btn-detail {
      padding: 10px 14px; border-radius: 12px;
      background: var(--color-blue-soft); color: var(--color-navy); font-size: 13px; font-weight: 700;
      border: 1px solid var(--color-blue-border); cursor: pointer; transition: all 0.15s ease;
    }
    .btn-detail:hover { background: var(--color-blue-border); color: var(--color-navy-dark); }

    .btn-cart-add-card {
      padding: 9px 13px; border-radius: 12px;
      background: var(--color-blue-soft); color: var(--color-cyan); font-size: 12.5px; font-weight: 800;
      border: 1px solid var(--color-blue-border); cursor: pointer; transition: all 0.15s ease;
      display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-cart-add-card:hover {
      background: var(--color-cyan); color: #ffffff; border-color: var(--color-cyan);
      transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 174, 255, 0.3);
    }

    /* Modals */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(11, 27, 61, 0.78);
      backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
      z-index: 1000; display: none; align-items: center; justify-content: center;
      padding: 20px 14px;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
    }
    .modal-card {
      background: #ffffff; border-radius: 24px; max-width: 490px; width: 100%;
      padding: 24px 22px; box-shadow: 0 25px 60px rgba(11, 27, 61, 0.35); position: relative;
      max-height: min(92vh, 760px);
      overflow-y: auto;
      margin: auto;
      border: 1px solid var(--color-blue-border);
    }
    .modal-card::-webkit-scrollbar { width: 6px; }
    .modal-card::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
    .modal-card::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .modal-detail-card {
      background: #ffffff; border-radius: 28px; max-width: 720px; width: 100%;
      max-height: 85vh; display: flex; flex-direction: column;
      box-shadow: 0 25px 60px rgba(11, 27, 61, 0.35); position: relative; overflow: hidden;
      margin: auto;
      border: 1px solid var(--color-blue-border);
    }
    .modal-detail-body { padding: 24px 28px; overflow-y: auto; flex: 1; }
    .modal-detail-footer {
      padding: 16px 28px; border-top: 1px solid var(--color-blue-border); background: var(--color-blue-soft);
      display: flex; justify-content: space-between; align-items: center;
    }

    /* Modal Detail Header Grid with Product Image */
    .modal-detail-header-grid {
      display: flex; gap: 20px; align-items: flex-start;
      margin-bottom: 20px; padding-bottom: 18px; border-bottom: 1px solid var(--color-blue-border);
    }
    .modal-detail-thumb-wrapper { flex-shrink: 0; width: 130px; }
    .modal-detail-thumb {
      width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 12px;
      box-shadow: 0 10px 25px rgba(15, 34, 74, 0.12);
      border: 1px solid var(--color-blue-border); background: var(--color-blue-soft);
      transition: transform 0.2s ease;
    }
    .modal-detail-thumb:hover { transform: scale(1.02); }
    .modal-detail-header-info { flex: 1; min-width: 0; }
    .modal-detail-highlight-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .spec-tag {
      display: inline-flex; align-items: center; font-size: 11px; font-weight: 600;
      padding: 3px 8px; border-radius: 6px; background: var(--color-blue-soft);
      border: 1px solid var(--color-blue-border); color: var(--color-navy);
    }

    .btn-close-modal {
      position: absolute; top: 16px; right: 16px; width: 34px; height: 34px;
      border-radius: 50%; border: none; background: var(--color-blue-soft); color: var(--color-navy);
      font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background 0.15s ease; z-index: 10;
    }
    .btn-close-modal:hover { background: var(--color-blue-border); }
    .modal-title { font-size: 20px; font-weight: 900; margin-bottom: 2px; color: var(--color-navy); }
    .modal-sub { font-size: 12px; color: var(--color-text-muted); margin-bottom: 14px; }
    .selected-summary {
      background: var(--color-blue-soft); border: 1px solid var(--color-blue-border); border-radius: 12px;
      padding: 12px 14px; margin-bottom: 12px;
    }
    .summary-title { font-weight: 800; font-size: 13px; color: var(--color-navy); line-height: 1.35; }
    .summary-price { font-weight: 900; font-size: 18px; color: var(--color-cyan); margin-top: 3px; }

    .form-group { margin-bottom: 10px; }
    .form-label { display: block; font-size: 11px; font-weight: 800; color: var(--color-navy); margin-bottom: 4px; }
    .form-input {
      width: 100%; padding: 10px 14px; border-radius: 10px;
      border: 1px solid var(--color-blue-border); font-size: 13px; font-family: inherit; outline: none;
    }
    .form-input:focus { border-color: var(--color-cyan); box-shadow: 0 0 0 3px rgba(0, 174, 255, 0.2); }

    .watermark-note {
      font-size: 10px; color: #059669; font-weight: 600; margin-top: 3px;
      display: flex; align-items: center; gap: 4px;
    }

    .btn-submit-checkout {
      width: 100%; padding: 13px 18px; border-radius: 12px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      color: #ffffff; font-size: 14px; font-weight: 900; border: none; cursor: pointer;
      box-shadow: 0 8px 20px rgba(0, 174, 255, 0.35); transition: all 0.2s; margin-top: 6px;
    }
    .btn-submit-checkout:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(0, 174, 255, 0.5); }
    .btn-submit-checkout:disabled { opacity: 0.6; cursor: not-allowed; }

    /* Filter Chips */
    .btn-filter-chip {
      padding: 8px 16px;
      border-radius: 20px;
      border: 1px solid var(--color-blue-border);
      background: #f8fafc;
      color: var(--color-text-sub);
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-filter-chip:hover {
      border-color: var(--color-cyan);
      color: var(--color-navy);
      background: var(--color-blue-soft);
    }
    .btn-filter-chip.active {
      background: var(--color-navy);
      color: #ffffff;
      border-color: var(--color-navy);
      box-shadow: 0 4px 12px rgba(15, 34, 74, 0.2);
    }

    /* Universal 4-Column Footer */
    footer {
      border-top: 2px solid var(--color-cyan); padding: 56px 0 32px; background: var(--color-navy-dark);
      margin-top: 80px; color: #94a3b8; font-size: 13px;
    }
    .footer-grid {
      display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 36px;
      margin-bottom: 40px; text-align: left;
    }
    @media (max-width: 800px) {
      .footer-grid { grid-template-columns: 1fr; gap: 28px; }
    }
    .footer-heading {
      color: var(--color-gold) !important; font-size: 13px; font-weight: 800;
      text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 16px;
    }
    .footer-links-col { list-style: none; display: flex; flex-direction: column; gap: 10px; padding: 0; margin: 0; }
    .footer-links-col a { color: var(--color-blue-border); font-weight: 500; text-decoration: none; transition: color 0.15s; }
    .footer-links-col a:hover { color: var(--color-cyan); }
    .footer-bottom-bar {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 24px; display: flex; justify-content: space-between; align-items: center;
      flex-wrap: wrap; gap: 14px; font-size: 12px; color: #94a3b8;
    }

    /* Mobile Touch Optimization & Safari Auto-Zoom Fix */
    @media (max-width: 768px) {
      input, select, textarea {
        font-size: 16px !important;
      }
      .card-thumb {
        width: 72px; height: 98px;
      }
      .card-price-num { font-size: 18px; }
      .btn-buy, .btn-detail, .btn-cart-add-card {
        padding: 9px 12px; font-size: 12px;
      }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="container navbar-inner">
      <div class="brand-group">
        <div class="brand-logo">RM</div>
        <div>
          <div class="brand-title">Restia Moegiono</div>
          <div class="brand-sub">Official Cybersecurity &amp; Privacy Store</div>
        </div>
      </div>
      <div class="nav-actions">
        <button type="button" onclick="CartEngine.open()" class="badge-portal" style="border:none; cursor:pointer; background:linear-gradient(135deg, #0F224A, #00AEFF); color:#ffffff; font-weight:800; gap:8px;">
          🛒 Keranjang
          <span class="nav-cart-counter" style="display:none; background:#ef4444; color:#ffffff; font-size:10px; font-weight:900; padding:1px 6px; border-radius:10px; line-height:1.2;">0</span>
        </button>
        <button type="button" onclick="openOrderCheckModal()" class="badge-portal" style="border:none; cursor:pointer; background:#f1f5f9;">
          🔍 Cek Pesanan
        </button>
        <a href="https://restiamoegiono.com/course/" class="badge-portal hide-on-mobile" style="background:#f0fdf4; color:#166534; border-color:#bbf7d0;">
          🎓 Akademi &amp; Course
        </a>
        <a href="https://restiamoegiono.com" target="_blank" class="badge-portal hide-on-mobile">
          🌐 Portal Utama
        </a>
        <span class="badge-doku hide-on-mobile">
          <span class="pulse-dot"></span>
          DOKU Payment
        </span>
      </div>
    </div>
  </nav>

  <main class="container">
    <!-- PROMO BANNER: HANYA TAMPIL JIKA DIAKTIFKAN OLEH ADMIN -->
    <?php if (!empty($activePromo)): ?>
    <?php
      $promoCode = htmlspecialchars($activePromo['code']);
      $discountFormatted = ($activePromo['discount_type'] === 'PERCENT')
          ? 'diskon <b>' . intval($activePromo['discount_value']) . '%</b>'
          : 'potongan <b>Rp ' . number_format($activePromo['discount_value'], 0, ',', '.') . '</b>';
      
      $scopeLabel = 'seluruh transaksi!';
      if (($activePromo['applicable_to'] ?? '') === 'ESTORE') {
          $scopeLabel = 'pembelian seluruh E-Book Toko!';
      } elseif (($activePromo['applicable_to'] ?? '') === 'LMS') {
          $scopeLabel = 'pendaftaran Kelas Masterclass LMS!';
      }
    ?>
    <div class="promo-banner" id="storePromoBanner">
      <div>
        <span>🎉 <b>Promo Resmi:</b> Gunakan kode voucher <code style="background:#bbf7d0; padding:2px 8px; border-radius:6px; font-weight:800; color:#166534; cursor:pointer;" onclick="quickApplyPromo('<?= $promoCode ?>')" title="Klik untuk terapkan"><?= $promoCode ?></code> untuk <?= $discountFormatted ?> <?= $scopeLabel ?></span>
      </div>
      <button type="button" onclick="quickApplyPromo('<?= $promoCode ?>')" style="background:#166534; color:#fff; border:none; padding:4px 12px; border-radius:8px; font-size:11px; font-weight:800; cursor:pointer;">
        Gunakan Kupon ➔
      </button>
    </div>
    <?php endif; ?>

    <!-- HERO SECTION -->
    <section class="hero">
      <div>
        <div class="hero-tag"><?= htmlspecialchars($heroTag) ?></div>
        <h1 class="hero-title">
          <?= renderSafeHtml($heroTitle) ?>
        </h1>
        <p class="hero-desc">
          <?= renderSafeHtml($heroDesc) ?>
        </p>
        <div>
          <button class="btn-cta" onclick="document.getElementById('katalog').scrollIntoView({behavior: 'smooth'})">
            <span>Pilih Koleksi E-Book</span>
            <span>↓</span>
          </button>
        </div>
        <div class="trust-grid">
          <div>
            <div class="trust-val">100%</div>
            <div class="trust-lbl">Lisensi Resmi Pembeli</div>
          </div>
          <div>
            <div class="trust-val">DRM PDF</div>
            <div class="trust-lbl">Stempel Watermark Personal</div>
          </div>
          <div>
            <div class="trust-val">Otomatis</div>
            <div class="trust-lbl">Kirim Email via SMTP Aman</div>
          </div>
        </div>
      </div>

      <div class="hero-3d-card">
        <img src="<?= htmlspecialchars($heroImage) ?>" alt="Pustaka Keamanan Siber Restia Moegiono" class="hero-3d-img" fetchpriority="high" decoding="async">
      </div>
    </section>


    <!-- KATALOG PRODUK LENGKAP -->
    <section id="katalog" style="margin-bottom: 64px;">
      <div style="text-align:center; max-width:680px; margin:0 auto 28px;">
        <h2 class="section-title">Katalog Pustaka &amp; Dokumen Kerja</h2>
        <p class="section-sub">Pilih materi literasi siber atau panduan kepatuhan regulasi resmi untuk organisasi Anda</p>
      </div>

      <!-- FILTER & PENCARIAN LIVE -->
      <div style="background:#ffffff; border:1px solid var(--color-blue-border); border-radius:18px; padding:18px 20px; margin-bottom:28px; box-shadow:0 4px 16px rgba(15, 34, 74, 0.03); display:flex; gap:16px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
        <div style="display:flex; gap:8px; flex-wrap:wrap;" id="categoryFilterGroup">
          <button type="button" class="btn-filter-chip active" onclick="setCategoryFilter('ALL', this)">Semua Koleksi</button>
          <button type="button" class="btn-filter-chip" onclick="setCategoryFilter('ISO', this)">Implementasi ISO 27001</button>
          <button type="button" class="btn-filter-chip" onclick="setCategoryFilter('PDP', this)">Kepatuhan UU PDP</button>
        </div>
        <div style="flex:1; min-width:240px; max-width:340px;">
          <input type="text" id="catalogSearchInput" placeholder="Cari judul atau kata kunci..." oninput="filterCatalog()" class="form-input" style="padding:8px 14px; font-size:13px; border-radius:10px;">
        </div>
      </div>

      <div class="catalog-grid" id="catalogProductGrid">
        <?php foreach ($products as $p): ?>
          <?php 
            $prodUrl = '/produk/' . urlencode($p['slug'] ?: $p['id']);
            $catNormalized = strtoupper($p['category']);
          ?>
          <div class="product-card" data-category="<?= htmlspecialchars($catNormalized) ?>" data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>">
            <div>
              <div class="card-top">
                <a href="<?= $prodUrl ?>" class="card-thumb" style="display:block;">
                  <img src="<?= htmlspecialchars($p['cover_image'] ?: '/images/book-cover-450.webp') ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy" decoding="async">
                </a>
                <div class="card-meta">
                  <span class="card-badge"><?= htmlspecialchars($p['badge'] ?: $p['category']) ?></span>
                  <h3 class="card-title">
                    <a href="<?= $prodUrl ?>" style="color:inherit; text-decoration:none;">
                      <?= htmlspecialchars($p['title']) ?>
                    </a>
                  </h3>
                  <div class="card-specs"><?= htmlspecialchars($p['version'] ?: 'Edisi 2026') ?> &bull; <?= (int)$p['pages'] ?> Halaman</div>
                </div>
              </div>
              <div class="card-desc"><?= renderSafeHtml($p['description']) ?></div>
            </div>
            <div class="card-footer">
              <div>
                <?php if ($p['old_price'] > $p['price']): ?>
                  <div style="font-size:12px; color:#94a3b8; text-decoration:line-through;">Rp <?= number_format($p['old_price'], 0, ',', '.') ?></div>
                <?php endif; ?>
                <div class="card-price-num">Rp <?= number_format($p['price'], 0, ',', '.') ?></div>
              </div>
              <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                <button type="button" class="btn-cart-add-card" onclick='CartEngine.add(<?= json_encode([
                  "id" => $p["id"],
                  "title" => $p["title"],
                  "price" => (float)$p["price"],
                  "cover" => $p["cover_image"] ?: "/images/book-cover-450.webp",
                  "badge" => $p["badge"] ?: $p["category"]
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Tambahkan ke Keranjang">
                  + 🛒 Keranjang
                </button>
                <button class="btn-buy" onclick="openCheckout('<?= htmlspecialchars($p['id']) ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>', <?= (int)$p['price'] ?>)">
                  Beli Langsung
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <!-- MODAL DETAIL PRODUK & SILABUS -->
  <div class="modal-overlay" id="detailModal">
    <div class="modal-detail-card">
      <button type="button" class="btn-close-modal" onclick="closeDetailModal()">&times;</button>
      <div class="modal-detail-body">
        <div class="modal-detail-header-grid">
          <div class="modal-detail-thumb-wrapper">
            <img id="modalDetailImg" src="" alt="Cover E-Book" class="modal-detail-thumb">
          </div>
          <div class="modal-detail-header-info">
            <span class="card-badge" id="modalDetailBadge" style="margin-bottom:8px;">Kategori</span>
            <h2 id="modalDetailTitle" style="font-size:22px; font-weight:900; line-height:1.3; margin-bottom:8px;">Judul E-Book</h2>
            <div id="modalDetailSpecs" style="font-size:13px; color:#64748b; font-weight:600; margin-bottom:10px;">
              Versi &bull; Halaman
            </div>
            <div class="modal-detail-highlight-tags">
              <span class="spec-tag">⚡ Akses Instan</span>
              <span class="spec-tag">🔒 Watermark Keamanan DRM</span>
              <span class="spec-tag">📱 PDF Semua Perangkat</span>
            </div>
          </div>
        </div>
        <div id="modalDetailContent" style="font-size:14px; line-height:1.7; color:#334155;">
          <!-- WYSIWYG Content Rendered Here -->
        </div>
      </div>
      <div class="modal-detail-footer">
        <div>
          <div id="modalDetailOldPrice" style="font-size:12px; color:#94a3b8; text-decoration:line-through;"></div>
          <div id="modalDetailPrice" style="font-size:24px; font-weight:900; color:#0284c7;"></div>
        </div>
        <button type="button" id="btnModalDetailBuy" class="btn-cta" style="padding:12px 24px;">
          Beli E-Book Ini ➔
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL CEK PESANAN / UNDUH ULANG MANDIRI -->
  <div class="modal-overlay" id="orderCheckModal">
    <div class="modal-card">
      <button type="button" class="btn-close-modal" onclick="closeOrderCheckModal()">&times;</button>
      <h3 class="modal-title">Cek Status &amp; Kirim Ulang Berkas</h3>
      <p class="modal-sub">Masukkan nomor invoice atau alamat email pembeli untuk memeriksa pesanan</p>

      <form id="orderSearchForm" onsubmit="handleOrderSearch(event)" style="margin-bottom:16px;">
        <div class="form-group">
          <label class="form-label">Nomor Invoice atau Alamat Email</label>
          <div style="display:flex; gap:8px;">
            <input type="text" id="inputSearchQuery" required placeholder="Misal: RM-17... atau ahmad@gmail.com" class="form-input">
            <button type="submit" id="btnSearchOrder" class="btn-buy" style="white-space:nowrap;">
              Cari Pesanan
            </button>
          </div>
        </div>
      </form>

      <div id="orderSearchResults" style="max-height:260px; overflow-y:auto; display:none;">
        <!-- Populated by JS -->
      </div>
    </div>
  </div>

  <!-- MODAL CHECKOUT -->
  <div class="modal-overlay" id="checkoutModal">
    <div class="modal-card">
      <button type="button" class="btn-close-modal" onclick="closeCheckout()">&times;</button>
      <h3 class="modal-title">Checkout Dokumen Resmi</h3>
      <p class="modal-sub">Masukkan identitas Anda untuk stempel watermark lisensi personal</p>

      <div class="selected-summary">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
          <div class="summary-title" id="summaryTitle">-</div>
          <div id="summaryOriginalPrice" style="font-size:12px; color:#94a3b8; text-decoration:line-through; display:none;"></div>
        </div>
        <div id="summaryDiscountRow" style="display:none; justify-content:space-between; font-size:12px; color:#166534; font-weight:800; margin-top:4px;">
          <span id="summaryDiscountLabel">Potongan Kupon:</span>
          <span id="summaryDiscountVal">-</span>
        </div>
        <div class="summary-price" id="summaryPrice">-</div>
      </div>

      <form id="checkoutForm" onsubmit="handleCheckoutSubmit(event)">
        <input type="hidden" id="inputProductId" name="product_id">
        <input type="hidden" id="appliedCouponCode" name="coupon_code" value="">

        <!-- Kode Promo / Kupon -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; margin-bottom:10px;">
          <label class="form-label" style="margin-bottom:3px;">Punya Kode Promo / Voucher Diskon?</label>
          <div style="display:flex; gap:8px;">
            <input type="text" id="inputCouponCode" placeholder="<?= !empty($activePromo) ? 'Misal: ' . htmlspecialchars($activePromo['code']) : 'Masukkan kode kupon' ?>" style="text-transform:uppercase; font-weight:700;" class="form-input">
            <button type="button" class="btn-detail" onclick="applyCoupon()" style="white-space:nowrap; padding:9px 12px; font-size:12px;">Terapkan</button>
          </div>
          <?php if (!empty($activePromo)): ?>
          <div style="font-size:11px; color:#166534; margin-top:4px; display:flex; align-items:center; gap:4px;">
            <span>🎉 Promo aktif: <b><?= htmlspecialchars($activePromo['code']) ?></b> (<?= $discountFormatted ?>)</span>
          </div>
          <?php endif; ?>
          <div id="couponFeedback" style="font-size:11px; margin-top:4px; display:none;"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Nama Lengkap (Watermark Dokumen)</label>
          <input type="text" id="inputName" required placeholder="Misal: Ahmad Fauzi, S.Kom" class="form-input">
          <div class="watermark-note">✓ Akan distempel resmi di setiap footer halaman PDF</div>
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Email (Pengiriman Berkas PDF)</label>
          <input type="email" id="inputEmail" required placeholder="nama@instansi.com" class="form-input">
        </div>

        <div class="form-group">
          <label class="form-label">Instansi / Perusahaan (Opsional)</label>
          <input type="text" id="inputOrg" placeholder="Misal: PT Solusi Teknologi" class="form-input">
        </div>

        <div class="form-group">
          <label class="form-label">Nomor WhatsApp (Konfirmasi Transaksi)</label>
          <input type="tel" id="inputWa" placeholder="Misal: 08123456789" class="form-input">
        </div>

        <button type="submit" id="btnSubmitPay" class="btn-submit-checkout">
          <span>Lanjut ke Pembayaran DOKU (QRIS/VA)</span> ➔
        </button>
      </form>
    </div>
  </div>

  <!-- UNIVERSAL FOOTER RESMI -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        <!-- Kolom 1: Profil -->
        <div>
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <div class="brand-logo" style="width:34px; height:34px; font-size:14px;">RM</div>
            <div>
              <div style="font-weight:900; font-size:16px; color:#ffffff;">Restia Moegiono</div>
              <div style="font-size:11px; color:#94a3b8;">Lead Auditor ISO 27001 &amp; Data Protection Consultant</div>
            </div>
          </div>
          <p style="font-size:12px; line-height:1.6; color:#cbd5e1; margin-bottom:14px;">
            Platform resmi distribusi instrumen audit, template SOP, dan literasi keamanan informasi terapan di Indonesia.
          </p>
          <div style="font-size:11px; color:#94a3b8;">
            Bekasi, Jawa Barat, Indonesia.
          </div>
        </div>

        <!-- Kolom 2: Katalog -->
        <div>
          <h4 class="footer-heading">Pustaka E-Book</h4>
          <ul class="footer-links-col">
            <li><a href="/produk/course-iso-27001">Implementasi ISO 27001:2022</a></li>
            <li><a href="/produk/kepatuhan-pdp">Panduan Praktis UU PDP</a></li>
            <li><a href="/produk/ropa-dpia">Kertas Kerja RoPA &amp; DPIA</a></li>
            <li><a href="/produk/lia-tia">Kertas Kerja LIA &amp; TIA</a></li>
          </ul>
        </div>

        <!-- Kolom 3: Layanan & Ekosistem -->
        <div>
          <h4 class="footer-heading">Ekosistem Terpadu</h4>
          <ul class="footer-links-col">
            <li><a href="https://restiamoegiono.com/" target="_blank">Portal Berita &amp; Artikel Resmi</a></li>
            <li><a href="https://restiamoegiono.com/course/" target="_blank">Ruang Belajar &amp; Kursus LMS</a></li>
            <li><a href="https://restiamoegiono.com/profil/alur-pendampingan-konsultasi" target="_blank">Layanan Konsultansi &amp; Audit</a></li>
            <li><a href="/cek-pesanan">Lacak Pesanan / Unduh Ulang</a></li>
          </ul>
        </div>

        <!-- Kolom 4: Legalitas -->
        <div>
          <h4 class="footer-heading">Bantuan &amp; Legal</h4>
          <ul class="footer-links-col">
            <li><a href="/panduan">Panduan Pembayaran QRIS / VA</a></li>
            <li><a href="/lisensi">Kebijakan Lisensi &amp; DRM</a></li>
            <li><a href="/syarat-ketentuan">Syarat &amp; Ketentuan Toko</a></li>
            <li><a href="mailto:order@restiamoegiono.com">Hubungi Layanan Konsumen</a></li>
          </ul>
        </div>
      </div>

      <!-- Footer Bottom -->
      <div class="footer-bottom-bar">
        <div>
          &copy; <?= date('Y') ?> Restia Moegiono. Seluruh Hak Cipta Dilindungi Undang-Undang.
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          <span style="width:7px; height:7px; border-radius:50%; background:#00AEFF; box-shadow:0 0 8px #00AEFF; display:inline-block;"></span>
          <span>Standar Keamanan SMKI ISO/IEC 27001:2022 &amp; Kepatuhan UU PDP</span>
        </div>
      </div>
    </div>
  </footer>

  <script>
    const storeProducts = <?= json_encode(array_values($products), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const activeBannerPromo = <?= !empty($activePromo) ? json_encode($activePromo['code']) : 'null' ?>;
    let preferredPromoCode = null;
    let currentProduct = null;
    let currentOriginalPrice = 0;
    let currentFinalPrice = 0;
    let activeCoupon = null;

    // Filter Kategori & Pencarian Real-Time
    let currentCatFilter = 'ALL';

    function setCategoryFilter(cat, btn) {
      currentCatFilter = cat;
      document.querySelectorAll('#categoryFilterGroup .btn-filter-chip').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      filterCatalog();
    }

    function filterCatalog() {
      const q = (document.getElementById('catalogSearchInput').value || '').toLowerCase().trim();
      document.querySelectorAll('#catalogProductGrid .product-card').forEach(card => {
        const cat = (card.getAttribute('data-category') || '').toUpperCase();
        const title = (card.getAttribute('data-title') || '').toLowerCase();
        const matchCat = (currentCatFilter === 'ALL' || cat.includes(currentCatFilter));
        const matchQ = (q === '' || title.includes(q));
        card.style.display = (matchCat && matchQ) ? 'flex' : 'none';
      });
    }

    function quickApplyPromo(code) {
      preferredPromoCode = code;
      const katalogEl = document.getElementById('katalog');
      if (katalogEl) katalogEl.scrollIntoView({ behavior: 'smooth' });
    }

    function openCheckout(id, title, price) {
      currentProduct = { id, title, price };
      currentOriginalPrice = Number(price);
      currentFinalPrice = Number(price);
      activeCoupon = null;

      document.getElementById('inputProductId').value = id;
      document.getElementById('appliedCouponCode').value = '';
      document.getElementById('couponFeedback').style.display = 'none';

      document.getElementById('summaryTitle').innerText = title;
      document.getElementById('summaryOriginalPrice').style.display = 'none';
      document.getElementById('summaryDiscountRow').style.display = 'none';
      document.getElementById('summaryPrice').innerText = 'Rp ' + currentFinalPrice.toLocaleString('id-ID');

      // Terapkan kupon otomatis jika ada kupon aktif di toko atau yang diklik pembeli
      const codeToApply = preferredPromoCode || activeBannerPromo;
      if (codeToApply) {
        document.getElementById('inputCouponCode').value = codeToApply;
        applyCoupon();
      } else {
        document.getElementById('inputCouponCode').value = '';
      }

      const modalEl = document.getElementById('checkoutModal');
      modalEl.style.display = 'flex';
      modalEl.scrollTop = 0;
      const cardEl = modalEl.querySelector('.modal-card');
      if (cardEl) cardEl.scrollTop = 0;
    }

    function closeCheckout() {
      document.getElementById('checkoutModal').style.display = 'none';
    }

    // Terapkan Kupon Promo
    async function applyCoupon() {
      const code = document.getElementById('inputCouponCode').value.trim();
      const fb = document.getElementById('couponFeedback');
      if (!code) {
        fb.style.display = 'block';
        fb.style.color = '#b91c1c';
        fb.innerText = 'Masukkan kode promo terlebih dahulu.';
        return;
      }

      fb.style.display = 'block';
      fb.style.color = '#0284c7';
      fb.innerText = 'Memeriksa kode promo...';

      try {
        const pid = (currentProduct && currentProduct.id) ? currentProduct.id : '';
        const res = await fetch(`/api/checkout.php?action=check_coupon&code=${encodeURIComponent(code)}&amount=${currentOriginalPrice}&product_id=${encodeURIComponent(pid)}`);
        const data = await res.json();

        if (data.success) {
          activeCoupon = data.code;
          currentFinalPrice = Number(data.final_amount);
          document.getElementById('appliedCouponCode').value = data.code;

          // Update summary visual
          document.getElementById('summaryOriginalPrice').style.display = 'block';
          document.getElementById('summaryOriginalPrice').innerText = 'Rp ' + currentOriginalPrice.toLocaleString('id-ID');

          document.getElementById('summaryDiscountRow').style.display = 'flex';
          document.getElementById('summaryDiscountLabel').innerText = `Kupon ${data.code}:`;
          document.getElementById('summaryDiscountVal').innerText = '-Rp ' + Number(data.discount_amount).toLocaleString('id-ID');

          document.getElementById('summaryPrice').innerText = 'Rp ' + currentFinalPrice.toLocaleString('id-ID');

          fb.style.color = '#166534';
          fb.innerText = `✓ ${data.message} Hemat Rp ${Number(data.discount_amount).toLocaleString('id-ID')}`;
        } else {
          fb.style.color = '#b91c1c';
          fb.innerText = '✕ ' + (data.error || 'Kode promo tidak valid.');
        }
      } catch (err) {
        fb.style.color = '#b91c1c';
        fb.innerText = '✕ Terjadi kesalahan koneksi saat memeriksa kupon.';
      }
    }

    // Modal Detail Produk
    function openDetailModal(id) {
      const p = storeProducts.find(x => x.id === id);
      if (!p) return;

      const imgEl = document.getElementById('modalDetailImg');
      if (imgEl) {
        imgEl.src = p.cover_image || '/assets/book-cover-default.png';
        imgEl.alt = p.title || 'Sampul E-Book';
      }

      document.getElementById('modalDetailBadge').innerText = p.badge || p.category;
      document.getElementById('modalDetailTitle').innerText = p.title;
      document.getElementById('modalDetailSpecs').innerText = `${p.version || 'Edisi 2026'} • ${p.pages || 0} Halaman • Master Berkas PDF`;
      document.getElementById('modalDetailContent').innerHTML = p.description || '<p>Belum ada deskripsi lengkap.</p>';
      
      const oldP = Number(p.old_price);
      const curP = Number(p.price);
      if (oldP > curP) {
        document.getElementById('modalDetailOldPrice').style.display = 'block';
        document.getElementById('modalDetailOldPrice').innerText = 'Rp ' + oldP.toLocaleString('id-ID');
      } else {
        document.getElementById('modalDetailOldPrice').style.display = 'none';
      }
      document.getElementById('modalDetailPrice').innerText = 'Rp ' + curP.toLocaleString('id-ID');

      document.getElementById('btnModalDetailBuy').onclick = function() {
        closeDetailModal();
        openCheckout(p.id, p.title, p.price);
      };

      document.getElementById('detailModal').style.display = 'flex';
    }

    function closeDetailModal() {
      document.getElementById('detailModal').style.display = 'none';
    }

    // Modal Cek Pesanan
    function openOrderCheckModal() {
      document.getElementById('inputSearchQuery').value = '';
      document.getElementById('orderSearchResults').style.display = 'none';
      document.getElementById('orderCheckModal').style.display = 'flex';
    }

    function closeOrderCheckModal() {
      document.getElementById('orderCheckModal').style.display = 'none';
    }

    async function handleOrderSearch(e) {
      e.preventDefault();
      const q = document.getElementById('inputSearchQuery').value.trim();
      const box = document.getElementById('orderSearchResults');
      const btn = document.getElementById('btnSearchOrder');

      btn.disabled = true;
      btn.innerText = 'Mencari...';

      const isEmail = q.includes('@');
      const param = isEmail ? `email=${encodeURIComponent(q)}` : `invoice=${encodeURIComponent(q)}`;

      try {
        const res = await fetch(`/api/order-status.php?${param}`);
        const data = await res.json();
        btn.disabled = false;
        btn.innerText = 'Cari Pesanan';

        if (data.success && data.orders && data.orders.length > 0) {
          box.innerHTML = '';
          data.orders.forEach(o => {
            const item = document.createElement('div');
            item.style.cssText = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; margin-bottom:10px; font-size:13px;';
            item.innerHTML = `
              <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <b>${o.invoice}</b>
                <span class="status-badge ${o.is_paid ? 'status-paid' : 'status-pending'}">${o.status}</span>
              </div>
              <div style="color:#0f172a; font-weight:700; margin-bottom:4px;">${o.product_title}</div>
              <div style="color:#64748b; font-size:11px; margin-bottom:8px;">Tanggal: ${o.created_at} &bull; Total: Rp ${Number(o.amount).toLocaleString('id-ID')}</div>
              ${o.is_paid ? `
                <button type="button" class="btn-detail" onclick="resendEbook('${o.invoice}', '${o.customer_email}')" style="width:100%; text-align:center; padding:8px 12px; font-size:12px; font-weight:800; background:#ecfdf5; color:#047857; border-color:#a7f3d0;">
                  ✉️ Kirim Ulang Berkas PDF ke Email Saya
                </button>
              ` : `
                <div style="font-size:11px; color:#b45309;">Menunggu pembayaran. Berkas dikirim otomatis setelah transaksi selesai.</div>
              `}
            `;
            box.appendChild(item);
          });
          box.style.display = 'block';
        } else {
          box.innerHTML = `<div style="text-align:center; padding:16px; color:#b91c1c; font-size:13px;">${data.error || 'Pesanan tidak ditemukan.'}</div>`;
          box.style.display = 'block';
        }
      } catch (err) {
        btn.disabled = false;
        btn.innerText = 'Cari Pesanan';
        box.innerHTML = '<div style="text-align:center; padding:16px; color:#b91c1c; font-size:13px;">Kesalahan koneksi.</div>';
        box.style.display = 'block';
      }
    }

    async function resendEbook(inv, email) {
      if (!confirm(`Kirim ulang dokumen berlisensi untuk invoice ${inv} ke ${email}?`)) return;
      try {
        const res = await fetch(`/api/order-status.php?action=resend&invoice=${encodeURIComponent(inv)}&email=${encodeURIComponent(email)}`);
        const data = await res.json();
        alert(data.message || (data.success ? 'Dokumen berhasil dikirimkan ulang!' : 'Gagal: ' + data.error));
      } catch (e) {
        alert('Kesalahan jaringan.');
      }
    }

    window.onclick = function(e) {
      if (e.target === document.getElementById('checkoutModal')) closeCheckout();
      if (e.target === document.getElementById('detailModal')) closeDetailModal();
      if (e.target === document.getElementById('orderCheckModal')) closeOrderCheckModal();
    };

    async function handleCheckoutSubmit(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitPay');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span>Menyiapkan Sesi DOKU...</span>';

      const payload = {
        product_id: document.getElementById('inputProductId').value,
        name: document.getElementById('inputName').value.trim(),
        email: document.getElementById('inputEmail').value.trim(),
        org: document.getElementById('inputOrg').value.trim(),
        whatsapp: document.getElementById('inputWa').value.trim(),
        coupon_code: document.getElementById('appliedCouponCode').value.trim()
      };

      try {
        const res = await fetch('/api/checkout.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success && data.payment_url) {
          localStorage.setItem('rm_last_invoice', data.invoice);
          window.location.href = data.payment_url;
        } else {
          alert('Gagal memproses checkout: ' + (data.error || 'Terjadi kesalahan sistem.'));
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      } catch (err) {
        console.error('Checkout error:', err);
        alert('Terjadi kesalahan koneksi saat memproses tagihan.');
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    }

    // Auto-open checkout jika ada parameter ?checkout=... di URL
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const checkoutParam = urlParams.get('checkout');
      if (checkoutParam) {
        const targetProd = storeProducts.find(p => p.id === checkoutParam);
        if (targetProd) {
          openCheckout(targetProd.id, targetProd.title, targetProd.price);
        }
      }
    } catch(e) {}
  </script>

  <!-- UNIVERSAL CART DRAWER -->
  <?php include __DIR__ . '/cart-drawer.php'; ?>
</body>
</html>
