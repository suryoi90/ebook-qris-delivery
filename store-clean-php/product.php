<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$db = StoreDB::get();

// Dapatkan identifier dari parameter id atau slug
$queryId = trim($_GET['id'] ?? $_GET['slug'] ?? '');
if (empty($queryId)) {
    // Redirect ke beranda jika tanpa parameter
    header('Location: /');
    exit;
}

$stmt = $db->prepare("SELECT * FROM `store_products` WHERE (`slug` = :q1 OR `id` = :q2) AND `is_active` = 1 LIMIT 1");
$stmt->execute(['q1' => $queryId, 'q2' => $queryId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
}

// Ambil kupon aktif jika ada
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

// Ambil produk terkait (3 produk lain)
$relatedProducts = [];
if ($product) {
    try {
        $stmtRel = $db->prepare("SELECT * FROM `store_products` WHERE `id` != :id AND `is_active` = 1 ORDER BY `order_index` ASC, `price` DESC LIMIT 3");
        $stmtRel->execute(['id' => $product['id']]);
        $relatedProducts = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $relatedProducts = [];
    }
}

function renderSafeHtml($html) {
    if (empty($html)) return '';
    return strip_tags($html, '<p><br><strong><b><em><i><u><s><ul><ol><li><h2><h3><h4><h5><h6><blockquote><span><a><div>');
}

$pageTitle = $product 
    ? htmlspecialchars($product['title']) . " — E-Book Resmi Restia Moegiono"
    : "Produk Tidak Ditemukan — Pustaka Restia Moegiono";

$pageDesc = $product 
    ? (!empty($product['subtitle']) ? htmlspecialchars($product['subtitle']) : str_limit(strip_tags($product['description'] ?? ''), 160))
    : "Buku panduan implementasi ISO 27001 dan kepatuhan UU PDP karya Restia Moegiono.";

$coverUrl = $product && !empty($product['cover_image'])
    ? (strpos($product['cover_image'], 'http') === 0 ? $product['cover_image'] : 'https://store.restiamoegiono.com' . $product['cover_image'])
    : 'https://store.restiamoegiono.com/images/book-cover-450.webp';

$canonicalUrl = $product 
    ? 'https://store.restiamoegiono.com/produk/' . urlencode($product['slug'] ?: $product['id'])
    : 'https://store.restiamoegiono.com/';

function str_limit($value, $limit = 100, $end = '...') {
    if (mb_strwidth($value, 'UTF-8') <= $limit) {
        return $value;
    }
    return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <meta name="description" content="<?= $pageDesc ?>">
  <meta name="author" content="Restia Moegiono">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="theme-color" content="#0F224A">

  <!-- Open Graph / Meta Sosial -->
  <meta property="og:type" content="book">
  <meta property="og:site_name" content="Restia Moegiono Official Store">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= $pageTitle ?>">
  <meta property="og:description" content="<?= $pageDesc ?>">
  <meta property="og:image" content="<?= htmlspecialchars($coverUrl) ?>">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $pageTitle ?>">
  <meta name="twitter:description" content="<?= $pageDesc ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($coverUrl) ?>">

  <!-- Fonts Google -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- Fonts Google with font-display: swap -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <?php if ($product): ?>
  <!-- JSON-LD Structured Data Schema.org untuk Google Search Rich Snippets -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Book",
    "name": <?= json_encode($product['title']) ?>,
    "headline": <?= json_encode($product['subtitle'] ?: $product['title']) ?>,
    "description": <?= json_encode(strip_tags($product['description'] ?? '')) ?>,
    "image": <?= json_encode($coverUrl) ?>,
    "url": <?= json_encode($canonicalUrl) ?>,
    "inLanguage": "id",
    "bookFormat": "https://schema.org/EBook",
    "numberOfPages": <?= (int)($product['pages'] ?? 100) ?>,
    "author": {
      "@type": "Person",
      "name": "Restia Moegiono",
      "jobTitle": "Lead Auditor ISO 27001 & Data Protection Consultant",
      "url": "https://restiamoegiono.com"
    },
    "publisher": {
      "@type": "Organization",
      "name": "Restia Moegiono Publishing",
      "logo": {
        "@type": "ImageObject",
        "url": "https://restiamoegiono.com/assets/images/logo.png"
      }
    },
    "offers": {
      "@type": "Offer",
      "price": "<?= (float)$product['price'] ?>",
      "priceCurrency": "IDR",
      "availability": "https://schema.org/InStock",
      "url": <?= json_encode($canonicalUrl) ?>,
      "seller": {
        "@type": "Person",
        "name": "Restia Moegiono"
      }
    },
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": "5.0",
      "reviewCount": "148"
    }
  }
  </script>
  <?php endif; ?>

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
      --font-product-title: clamp(1.4rem, 2.6vw + 0.4rem, 2.25rem);
      --font-section-title: clamp(1.2rem, 1.8vw + 0.3rem, 1.6rem);
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

    /* Sticky Navbar */
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
      width: 42px; height: 42px; border-radius: 12px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      display: flex; align-items: center; justify-content: center;
      color: #ffffff; font-weight: 900; font-size: 18px;
      box-shadow: 0 4px 12px rgba(0, 174, 255, 0.35); flex-shrink: 0;
    }
    .brand-title { font-size: 16px; font-weight: 800; color: var(--color-navy); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .brand-sub { font-size: 11px; color: var(--color-text-muted); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .nav-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .badge-portal {
      font-size: 12px; font-weight: 700; color: var(--color-navy);
      background: var(--color-blue-soft); padding: 7px 14px; border-radius: 10px;
      border: 1px solid var(--color-blue-border); display: inline-flex; align-items: center; gap: 6px;
      transition: all 0.15s ease; white-space: nowrap;
    }
    .badge-portal:hover { background: var(--color-blue-border); }

    .badge-doku {
      font-size: 11px; font-weight: 800; color: #166534;
      background: #f0fdf4; padding: 6px 12px; border-radius: 20px;
      border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
    }
    .pulse-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: #22c55e; box-shadow: 0 0 8px #22c55e;
      animation: pulse-kf 1.5s infinite;
    }
    @keyframes pulse-kf { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(0.85); } }

    @media (max-width: 768px) {
      .navbar-inner { height: 64px; }
      .brand-logo { width: 38px; height: 38px; font-size: 16px; border-radius: 10px; }
      .brand-title { font-size: 14.5px; }
      .brand-sub { display: none; }
      .badge-portal { padding: 6px 10px; font-size: 11.5px; }
      .hide-on-mobile { display: none !important; }
      input, select, textarea { font-size: 16px !important; }
    }

    @media (min-width: 1920px) {
      .container { max-width: 1380px; }
    }

    /* Breadcrumbs */
    .breadcrumb-nav {
      padding: 20px 0 10px;
      font-size: 13px; font-weight: 600; color: var(--color-text-muted);
      display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .breadcrumb-nav a { color: var(--color-navy); transition: color 0.15s; }
    .breadcrumb-nav a:hover { color: var(--color-cyan); }
    .breadcrumb-sep { color: #cbd5e1; font-weight: 400; }
    .breadcrumb-current { color: var(--color-text-muted); font-weight: 500; }

    /* Product Hero Grid */
    .product-hero-card {
      background: #ffffff;
      border: 1px solid var(--color-blue-border);
      border-radius: 24px;
      padding: 36px 32px;
      margin-bottom: 36px;
      box-shadow: 0 10px 30px rgba(15, 34, 74, 0.04);
      display: grid;
      grid-template-columns: 360px 1fr;
      gap: 40px;
      align-items: start;
    }
    @media (max-width: 900px) {
      .product-hero-card { grid-template-columns: 1fr; padding: 24px 20px; gap: 28px; }
      .cover-showcase-box { position: static; }
      .cta-button-group { flex-direction: column; }
      .btn-buy-primary, .btn-syllabus-secondary { width: 100%; min-width: 0; }
    }
    @media (max-width: 480px) {
      .product-hero-card { padding: 18px 14px; border-radius: 18px; gap: 20px; }
      .cover-wrapper-3d { max-width: 250px; }
      .product-title-h1 { font-size: clamp(20px, 5.5vw, 24px); }
      .price-main-val { font-size: 26px; }
      .price-action-box { padding: 16px 14px; border-radius: 14px; }
      .details-tab-container { padding: 16px 12px; border-radius: 16px; }
      .tab-pill-btn { padding: 8px 12px; font-size: 12px; }
    }

    /* Left: Cover Showcase */
    .cover-showcase-box {
      text-align: center;
      position: sticky;
      top: 96px;
    }
    .cover-wrapper-3d {
      width: 100%;
      max-width: 320px;
      margin: 0 auto 20px;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 20px 45px rgba(15, 34, 74, 0.22);
      border: 2px solid var(--color-blue-border);
      background: var(--color-navy);
      position: relative;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .cover-wrapper-3d:hover {
      transform: translateY(-4px) scale(1.01);
      box-shadow: 0 25px 55px rgba(0, 174, 255, 0.3);
      border-color: var(--color-cyan);
    }
    .cover-img-main {
      width: 100%;
      aspect-ratio: 3/4;
      object-fit: cover;
      display: block;
    }
    .cover-badge-float {
      position: absolute;
      top: 14px; right: 14px;
      background: var(--color-gold);
      color: var(--color-navy-dark);
      font-size: 11px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 5px 12px;
      border-radius: 20px;
      box-shadow: 0 4px 12px rgba(242, 178, 0, 0.4);
    }

    .rating-badge-bar {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--color-blue-soft);
      border: 1px solid var(--color-blue-border);
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      color: var(--color-navy);
      margin-bottom: 14px;
    }
    .rating-stars { color: var(--color-gold); letter-spacing: 2px; }

    .trust-badges-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
      text-align: left;
      margin-top: 16px;
    }
    .trust-badge-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      color: var(--color-navy);
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 8px 12px;
      font-weight: 600;
    }

    /* Right: Product Info & Buy CTA */
    .product-info-col {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .category-chip-row {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .chip-category {
      background: var(--color-blue-soft);
      color: var(--color-navy);
      border: 1px solid var(--color-blue-border);
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 4px 10px;
      border-radius: 8px;
    }
    .chip-version {
      background: #f1f5f9;
      color: var(--color-text-muted);
      border: 1px solid #e2e8f0;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 8px;
    }

    .product-title-h1 {
      font-size: var(--font-product-title);
      font-weight: 900;
      color: var(--color-navy);
      line-height: 1.25;
      letter-spacing: -0.02em;
      text-wrap: balance;
    }

    .product-subtitle-tag {
      font-size: 16px;
      font-weight: 600;
      color: var(--color-text-sub);
      line-height: 1.5;
    }

    /* Price Box */
    .price-action-box {
      background: linear-gradient(135deg, #f8fafc 0%, var(--color-blue-soft) 100%);
      border: 1px solid var(--color-blue-border);
      border-radius: 18px;
      padding: 20px 24px;
      margin: 8px 0;
    }
    .price-flex-row {
      display: flex;
      align-items: baseline;
      gap: 14px;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }
    .price-main-val {
      font-size: 32px;
      font-weight: 900;
      color: var(--color-navy);
      letter-spacing: -0.02em;
    }
    .price-old-val {
      font-size: 16px;
      color: #94a3b8;
      text-decoration: line-through;
      font-weight: 600;
    }
    .discount-pill {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
      font-size: 12px;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 20px;
    }

    .cta-button-group {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .btn-buy-primary {
      flex: 1;
      min-width: 220px;
      padding: 15px 28px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      color: #ffffff;
      font-size: 15px;
      font-weight: 900;
      border: none;
      cursor: pointer;
      box-shadow: 0 8px 24px rgba(0, 174, 255, 0.4);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.2s ease;
    }
    .btn-buy-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(0, 174, 255, 0.55);
    }
    .btn-syllabus-secondary {
      padding: 15px 22px;
      border-radius: 14px;
      background: #ffffff;
      color: var(--color-navy);
      font-size: 14px;
      font-weight: 800;
      border: 1px solid var(--color-blue-border);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.15s ease;
    }
    .btn-syllabus-secondary:hover {
      background: var(--color-blue-soft);
      border-color: var(--color-cyan);
    }

    /* Social Share Bar */
    .share-bar-row {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      font-weight: 700;
      color: var(--color-text-muted);
      margin-top: 8px;
      flex-wrap: wrap;
    }
    .btn-share-icon {
      padding: 6px 12px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      background: #ffffff;
      color: var(--color-navy);
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s;
    }
    .btn-share-icon:hover {
      border-color: var(--color-cyan);
      color: var(--color-cyan);
    }

    /* In-Depth Content Tabs */
    .details-tab-container {
      background: #ffffff;
      border: 1px solid var(--color-blue-border);
      border-radius: 24px;
      padding: 32px;
      margin-bottom: 48px;
      box-shadow: 0 10px 30px rgba(15, 34, 74, 0.04);
    }
    @media (max-width: 600px) {
      .details-tab-container { padding: 20px 16px; }
    }

    .tab-pills-nav {
      display: flex;
      gap: 8px;
      border-bottom: 2px solid var(--color-blue-border);
      padding-bottom: 12px;
      margin-bottom: 24px;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .tab-pills-nav::-webkit-scrollbar { display: none; }
    .tab-pill-btn {
      padding: 10px 18px;
      border-radius: 12px;
      border: none;
      background: transparent;
      color: var(--color-text-muted);
      font-size: 13px;
      font-weight: 800;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.15s ease;
    }
    .tab-pill-btn.active {
      background: var(--color-navy);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(15, 34, 74, 0.2);
    }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; animation: fadeIn 0.25s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

    .rich-prose-content {
      font-size: 15px;
      line-height: 1.8;
      color: var(--color-text-main);
    }
    .rich-prose-content h2, .rich-prose-content h3 {
      color: var(--color-navy);
      font-weight: 800;
      margin: 24px 0 12px;
    }
    .rich-prose-content p { margin-bottom: 14px; }
    .rich-prose-content ul, .rich-prose-content ol {
      margin-left: 20px;
      margin-bottom: 16px;
    }
    .rich-prose-content li { margin-bottom: 6px; }
    .rich-prose-content blockquote {
      border-left: 4px solid var(--color-cyan);
      background: var(--color-blue-soft);
      padding: 14px 18px;
      border-radius: 0 12px 12px 0;
      margin: 18px 0;
      font-style: italic;
    }

    /* Specs Table */
    .specs-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
    }
    .specs-table tr { border-bottom: 1px solid #f1f5f9; }
    .specs-table tr:last-child { border-bottom: none; }
    .specs-table td {
      padding: 14px 12px;
      font-size: 14px;
    }
    .specs-td-label {
      width: 35%;
      font-weight: 700;
      color: var(--color-text-muted);
    }
    .specs-td-val {
      font-weight: 800;
      color: var(--color-navy);
    }

    /* Related Products Section */
    .related-section {
      margin-bottom: 64px;
    }
    .related-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 24px;
      margin-top: 20px;
    }
    .related-card {
      background: #ffffff;
      border: 1px solid var(--color-blue-border);
      border-radius: 18px;
      padding: 18px;
      display: flex;
      gap: 16px;
      align-items: center;
      transition: all 0.2s ease;
    }
    .related-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 24px rgba(15, 34, 74, 0.08);
      border-color: var(--color-cyan);
    }
    .related-thumb {
      width: 72px;
      aspect-ratio: 3/4;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      flex-shrink: 0;
    }
    .related-info { flex: 1; min-width: 0; }
    .related-title {
      font-size: 14px; font-weight: 800; color: var(--color-navy);
      line-height: 1.35; margin-bottom: 6px;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .related-price { font-size: 15px; font-weight: 900; color: var(--color-cyan); }

    /* Modal Checkout */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(11, 27, 61, 0.78);
      backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
      z-index: 1000; display: none; align-items: center; justify-content: center;
      padding: 20px 14px; overflow-y: auto;
    }
    .modal-card {
      background: #ffffff; border-radius: 24px; max-width: 490px; width: 100%;
      padding: 24px 22px; box-shadow: 0 25px 60px rgba(11, 27, 61, 0.35); position: relative;
      max-height: min(92vh, 760px); overflow-y: auto; margin: auto;
      border: 1px solid var(--color-blue-border);
    }
    .btn-close-modal {
      position: absolute; top: 16px; right: 16px; width: 34px; height: 34px;
      border-radius: 50%; border: none; background: var(--color-blue-soft); color: var(--color-navy);
      font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
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
    .watermark-note { font-size: 10px; color: #059669; font-weight: 600; margin-top: 3px; }
    .btn-submit-checkout {
      width: 100%; padding: 13px 18px; border-radius: 12px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      color: #ffffff; font-size: 14px; font-weight: 900; border: none; cursor: pointer;
      box-shadow: 0 8px 20px rgba(0, 174, 255, 0.35); transition: all 0.2s; margin-top: 6px;
    }

    /* Universal Footer */
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
    .footer-links-col { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer-links-col a { color: var(--color-blue-border); font-weight: 500; transition: color 0.15s; }
    .footer-links-col a:hover { color: var(--color-cyan); }
    .footer-bottom-bar {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 24px; display: flex; justify-content: space-between; align-items: center;
      flex-wrap: wrap; gap: 14px; font-size: 12px; color: #94a3b8;
    }
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
        <button type="button" onclick="CartEngine.open()" class="badge-portal" style="border:none; cursor:pointer; background:linear-gradient(135deg, #0F224A, #00AEFF); color:#ffffff; font-weight:800; gap:8px;">
          🛒 Keranjang
          <span class="nav-cart-counter" style="display:none; background:#ef4444; color:#ffffff; font-size:10px; font-weight:900; padding:1px 6px; border-radius:10px; line-height:1.2;">0</span>
        </button>
        <a href="/" class="badge-portal">
          📚 Katalog
        </a>
        <a href="/cek-pesanan" class="badge-portal hide-on-mobile">
          🔍 Cek Pesanan
        </a>
        <a href="https://restiamoegiono.com/course/" class="badge-portal hide-on-mobile" style="background:#f0fdf4; color:#166534; border-color:#bbf7d0;">
          🎓 Ruang Belajar LMS
        </a>
        <a href="https://restiamoegiono.com" target="_blank" class="badge-portal hide-on-mobile">
          🌐 Portal Utama
        </a>
        <span class="badge-doku hide-on-mobile">
          <span class="pulse-dot"></span>
          DOKU Live
        </span>
      </div>
    </div>
  </nav>

  <main class="container">
    <?php if (!$product): ?>
      <!-- 404 HALAMAN TIDAK DITEMUKAN -->
      <div style="text-align:center; padding:90px 20px; max-width:640px; margin:0 auto;">
        <div style="font-size:64px; margin-bottom:12px;">🔍</div>
        <h1 style="font-size:28px; font-weight:900; color:var(--color-navy); margin-bottom:10px;">E-Book Tidak Ditemukan</h1>
        <p style="color:var(--color-text-muted); font-size:15px; margin-bottom:28px;">
          Mohon maaf, e-book atau dokumen kerja yang Anda tuju tidak tersedia atau telah diperbarui dengan edisi terbaru.
        </p>
        <div style="display:flex; justify-content:center; gap:12px;">
          <a href="/" class="btn-buy-primary" style="text-decoration:none; max-width:240px;">
            Kembali ke Katalog Toko ➔
          </a>
        </div>
      </div>
    <?php else: ?>
      <!-- BREADCRUMB -->
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <a href="/">Beranda Toko</a>
        <span class="breadcrumb-sep">&gt;</span>
        <a href="/#katalog"><?= htmlspecialchars($product['category']) ?></a>
        <span class="breadcrumb-sep">&gt;</span>
        <span class="breadcrumb-current"><?= htmlspecialchars($product['title']) ?></span>
      </nav>

      <!-- PROMO BANNER (JIKA ADA KUPON AKTIF) -->
      <?php if (!empty($activePromo)): ?>
      <?php
        $promoCode = htmlspecialchars($activePromo['code']);
        $discountFormatted = ($activePromo['discount_type'] === 'PERCENT')
            ? 'diskon <b>' . intval($activePromo['discount_value']) . '%</b>'
            : 'potongan <b>Rp ' . number_format($activePromo['discount_value'], 0, ',', '.') . '</b>';
      ?>
      <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:14px; padding:12px 18px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; font-size:13px; color:#065f46;">
        <div>
          🎉 <b>Voucher Spesial:</b> Gunakan kode kupon <code style="background:#bbf7d0; padding:2px 8px; border-radius:6px; font-weight:800; cursor:pointer;" onclick="openCheckoutWithPromo('<?= $promoCode ?>')"><?= $promoCode ?></code> untuk <?= $discountFormatted ?>!
        </div>
        <button type="button" onclick="openCheckoutWithPromo('<?= $promoCode ?>')" style="background:#166534; color:#fff; border:none; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:800; cursor:pointer;">
          Beli dengan Kupon ➔
        </button>
      </div>
      <?php endif; ?>

      <!-- PRODUCT SHOWCASE HERO CARD -->
      <section class="product-hero-card">
        <!-- Kolom Kiri: 3D Cover & Jaminan -->
        <div class="cover-showcase-box">
          <div class="cover-wrapper-3d">
            <img src="<?= htmlspecialchars($coverUrl) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="cover-img-main" fetchpriority="high" decoding="async">
            <?php if (!empty($product['badge'])): ?>
              <span class="cover-badge-float"><?= htmlspecialchars($product['badge']) ?></span>
            <?php endif; ?>
          </div>

          <div class="rating-badge-bar">
            <span class="rating-stars">★★★★★</span>
            <span>5.0 (140+ Ulasan Pembeli)</span>
          </div>

          <div class="trust-badges-list">
            <div class="trust-badge-item">
              <span style="font-size:16px;">⚡</span>
              <span>Pengiriman Otomatis &lt; 1 Jam via Email</span>
            </div>
            <div class="trust-badge-item">
              <span style="font-size:16px;">🔒</span>
              <span>Watermark Lisensi Resmi (Nama &amp; Email)</span>
            </div>
            <div class="trust-badge-item">
              <span style="font-size:16px;">📱</span>
              <span>Format PDF Universal Semua Perangkat</span>
            </div>
            <div class="trust-badge-item">
              <span style="font-size:16px;">🔄</span>
              <span>Pembaruan Materi Regulasi Gratis</span>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Detail & Pembelian -->
        <div class="product-info-col">
          <div class="category-chip-row">
            <span class="chip-category"><?= htmlspecialchars($product['category']) ?></span>
            <span class="chip-version"><?= htmlspecialchars($product['version'] ?: 'Edisi 2026') ?></span>
            <span class="chip-version">📄 <?= (int)$product['pages'] ?> Halaman</span>
            <span class="chip-version">🇮🇩 <?= htmlspecialchars($product['language'] ?: 'Bahasa Indonesia') ?></span>
          </div>

          <h1 class="product-title-h1"><?= htmlspecialchars($product['title']) ?></h1>

          <?php if (!empty($product['subtitle'])): ?>
            <p class="product-subtitle-tag"><?= htmlspecialchars($product['subtitle']) ?></p>
          <?php endif; ?>

          <!-- Box Harga & CTA -->
          <div class="price-action-box">
            <div class="price-flex-row">
              <span class="price-main-val">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
              <?php if ($product['old_price'] > $product['price']): ?>
                <span class="price-old-val">Rp <?= number_format($product['old_price'], 0, ',', '.') ?></span>
                <?php $pct = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>
                <span class="discount-pill">Hemat <?= $pct ?>%</span>
              <?php endif; ?>
            </div>

            <div class="cta-button-group">
              <button type="button" class="btn-buy-primary" onclick="openCheckout('<?= htmlspecialchars($product['id']) ?>', '<?= htmlspecialchars(addslashes($product['title'])) ?>', <?= (int)$product['price'] ?>)">
                <span>⚡ Beli Langsung</span>
                <span>➔</span>
              </button>
              <button type="button" class="btn-cart-add-product" style="padding:15px 20px; border-radius:14px; background:var(--color-blue-soft); color:var(--color-cyan); font-weight:800; font-size:14px; border:2px solid var(--color-cyan); cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all 0.15s ease;" onclick='CartEngine.add(<?= json_encode([
                "id" => $product["id"],
                "title" => $product["title"],
                "price" => (float)$product["price"],
                "cover" => $product["cover_image"] ?: "/images/book-cover-450.webp",
                "badge" => $product["badge"] ?: $product["category"]
              ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                <span>+ 🛒 Tambah ke Keranjang</span>
              </button>
              <button type="button" class="btn-syllabus-secondary" onclick="scrollToTabs()">
                <span>📑 Silabus</span>
              </button>
            </div>

            <div style="font-size:11px; color:var(--color-text-muted); margin-top:12px; display:flex; align-items:center; gap:6px;">
              <span>💳</span>
              <span>Didukung DOKU: QRIS (Semua Bank / E-Wallet) &amp; Virtual Account (BCA, Mandiri, BRI, BNI).</span>
            </div>
          </div>

          <!-- Ringkasan Singkat Dari Deskripsi -->
          <div style="font-size:14px; line-height:1.7; color:var(--color-text-sub); border-left:3px solid var(--color-cyan); padding-left:14px; background:var(--color-blue-soft); border-radius:0 10px 10px 0; padding:12px 16px;">
            <strong>Catatan Kurikulum Resmi:</strong> E-book ini disusun langsung oleh <b>Restia Moegiono</b> (Lead Auditor ISO 27001 &amp; Data Protection Consultant) untuk memberikan langkah terstruktur yang langsung dapat dieksekusi di tempat kerja.
          </div>

          <!-- Share Bar -->
          <div class="share-bar-row">
            <span>Bagikan:</span>
            <button type="button" class="btn-share-icon" onclick="shareWhatsApp()">
              💬 WhatsApp
            </button>
            <button type="button" class="btn-share-icon" onclick="shareLinkedIn()">
              🔗 LinkedIn
            </button>
            <button type="button" class="btn-share-icon" onclick="copyProductLink()">
              📋 Salin Tautan
            </button>
          </div>
        </div>
      </section>

      <!-- IN-DEPTH DETAILS TABS CONTAINER -->
      <section class="details-tab-container" id="inDepthSection">
        <div class="tab-pills-nav" role="tablist">
          <button type="button" class="tab-pill-btn active" onclick="switchTab('tabOverview', this)">
            📖 Ikhtisar &amp; Deskripsi
          </button>
          <button type="button" class="tab-pill-btn" onclick="switchTab('tabSyllabus', this)">
            📑 Daftar Isi &amp; Silabus Bab
          </button>
          <button type="button" class="tab-pill-btn" onclick="switchTab('tabAudience', this)">
            🎯 Target Pembaca
          </button>
          <button type="button" class="tab-pill-btn" onclick="switchTab('tabBenefits', this)">
            ✨ Yang Anda Dapatkan
          </button>
          <button type="button" class="tab-pill-btn" onclick="switchTab('tabSpecs', this)">
            ⚙️ Spesifikasi Teknis
          </button>
          <button type="button" class="tab-pill-btn" onclick="switchTab('tabFaq', this)">
            ❓ FAQ &amp; Bantuan
          </button>
        </div>

        <!-- PANE 1: IKHTISAR -->
        <div class="tab-pane active" id="tabOverview">
          <div class="rich-prose-content">
            <?= renderSafeHtml($product['description']) ?>
          </div>
        </div>

        <!-- PANE 2: DAFTAR ISI & SILABUS -->
        <div class="tab-pane" id="tabSyllabus">
          <div class="rich-prose-content">
            <h2 style="font-size:20px; font-weight:800; color:var(--color-navy); margin-bottom:14px;">
              Silabus &amp; Bab Pembahasan Lengkap
            </h2>
            <?php if (!empty($product['table_of_contents'])): ?>
              <?= renderSafeHtml($product['table_of_contents']) ?>
            <?php else: ?>
              <p>Daftar isi dan pembahasan disajikan secara berurutan mencakup metodologi tata kelola, analisis gap, instrumen checklist kerja, hingga panduan simulasi audit internal.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- PANE 3: TARGET PEMBACA -->
        <div class="tab-pane" id="tabAudience">
          <div class="rich-prose-content">
            <h2 style="font-size:20px; font-weight:800; color:var(--color-navy); margin-bottom:14px;">
              Siapa yang Wajib Memiliki E-Book Ini?
            </h2>
            <?php if (!empty($product['target_audience'])): ?>
              <?= renderSafeHtml($product['target_audience']) ?>
            <?php else: ?>
              <ul>
                <li>Chief Information Security Officer (CISO) &amp; Manajer Keamanan Informasi</li>
                <li>Data Protection Officer (DPO) &amp; Compliance Officer</li>
                <li>Tim IT Operasional, Infrastructure, &amp; Cloud Engineer</li>
                <li>Auditor Internal, Konsultan Tata Kelola TI, &amp; Praktisi Keamanan Siber</li>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- PANE 4: YANG ANDA DAPATKAN -->
        <div class="tab-pane" id="tabBenefits">
          <div class="rich-prose-content">
            <h2 style="font-size:20px; font-weight:800; color:var(--color-navy); margin-bottom:14px;">
              Keunggulan &amp; Nilai Tambah Dokumen
            </h2>
            <?php if (!empty($product['benefits'])): ?>
              <?= renderSafeHtml($product['benefits']) ?>
            <?php else: ?>
              <ul>
                <li>Panduan aplikatif dengan bahasa lugas dan mudah dipahami lintas divisi</li>
                <li>Dilengkapi template matriks kerja siap pakai untuk percepatan kepatuhan</li>
                <li>Stempel watermark personal nama dan email pembeli sebagai bukti kepemilikan lisensi resmi</li>
                <li>Akses instan unduh seketika setelah pembayaran terverifikasi</li>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- PANE 5: SPESIFIKASI TEKNIS -->
        <div class="tab-pane" id="tabSpecs">
          <h2 style="font-size:20px; font-weight:800; color:var(--color-navy); margin-bottom:14px;">
            Spesifikasi Teknis Dokumen Digital
          </h2>
          <table class="specs-table">
            <tr>
              <td class="specs-td-label">Judul Lengkap</td>
              <td class="specs-td-val"><?= htmlspecialchars($product['title']) ?></td>
            </tr>
            <tr>
              <td class="specs-td-label">Penulis &amp; Penyusun</td>
              <td class="specs-td-val">Restia Moegiono — Lead Auditor ISO 27001 &amp; Data Protection Consultant</td>
            </tr>
            <tr>
              <td class="specs-td-label">Penerbit Resmi</td>
              <td class="specs-td-val">Restia Moegiono Publishing (Jakarta / Bekasi)</td>
            </tr>
            <tr>
              <td class="specs-td-label">Format Berkas</td>
              <td class="specs-td-val"><?= htmlspecialchars($product['file_format'] ?: 'PDF Digital (Universal Semua Perangkat)') ?></td>
            </tr>
            <tr>
              <td class="specs-td-label">Jumlah Halaman</td>
              <td class="specs-td-val"><?= (int)$product['pages'] ?> Halaman Lengkap</td>
            </tr>
            <tr>
              <td class="specs-td-label">Versi / Edisi</td>
              <td class="specs-td-val"><?= htmlspecialchars($product['edition'] ?: ($product['version'] ?: 'Edisi 2026 Revisi')) ?></td>
            </tr>
            <tr>
              <td class="specs-td-label">Bahasa Pengantar</td>
              <td class="specs-td-val"><?= htmlspecialchars($product['language'] ?: 'Bahasa Indonesia') ?></td>
            </tr>
            <tr>
              <td class="specs-td-label">Perkiraan Ukuran File</td>
              <td class="specs-td-val"><?= htmlspecialchars($product['file_size'] ?: '15-25 MB') ?></td>
            </tr>
            <tr>
              <td class="specs-td-label">Perlindungan Hak Cipta</td>
              <td class="specs-td-val">Dynamic Cryptographic Watermarking (Stempel Nama &amp; Email Pembeli di Setiap Halaman)</td>
            </tr>
          </table>
        </div>

        <!-- PANE 6: FAQ -->
        <div class="tab-pane" id="tabFaq">
          <div class="rich-prose-content">
            <h2 style="font-size:20px; font-weight:800; color:var(--color-navy); margin-bottom:14px;">
              Pertanyaan yang Sering Diajukan (FAQ)
            </h2>
            <h3>1. Kapan dan bagaimana saya menerima berkas e-book setelah bayar?</h3>
            <p>Pengiriman dilakukan secara instan dan otomatis oleh server. Seketika setelah sistem DOKU memverifikasi transaksi Anda (QRIS atau Virtual Account), server akan membubuhkan watermark personal dan mengirimkan tautan berkas resmi langsung ke alamat email yang Anda masukkan saat checkout.</p>

            <h3>2. Apa yang harus saya lakukan jika email berkas tidak kunjung masuk?</h3>
            <p>Pertama, periksa folder <i>Spam</i> atau <i>Promotions</i> di email Anda. Jika masih belum ditemukan, Anda dapat memanfaatkan fitur <b>Cek Pesanan Mandiri</b> di menu atas situs ini dengan memasukkan alamat email atau nomor invoice untuk mengunduh ulang secara instan.</p>

            <h3>3. Apakah materi ini kompatibel jika dibuka di HP atau Tablet?</h3>
            <p>Sangat kompatibel. Berkas berformat PDF standar internasional yang dapat dibuka dengan lancar di Adobe Acrobat, Apple Books, Google Drive PDF Viewer, browser, maupun aplikasi PDF reader lainnya di Android, iOS, Windows, dan MacOS.</p>

            <h3>4. Apakah saya boleh membagikan berkas e-book ini kepada kolega?</h3>
            <p>Pembelian e-book memberikan lisensi penggunaan personal atau keperluan internal organisasi Anda. Dokumen dilengkapi dengan penanda air (watermark) dinamis yang memuat nama dan email pembeli guna mencegah pembajakan dan redistribusi ilegal di ruang publik sesuai UU Hak Cipta No. 28 Tahun 2014.</p>
          </div>
        </div>
      </section>

      <!-- RELATED PRODUCTS SECTION -->
      <?php if (!empty($relatedProducts)): ?>
      <section class="related-section">
        <h2 style="font-size:22px; font-weight:900; color:var(--color-navy);">Koleksi Pustaka &amp; Dokumen Terkait</h2>
        <p style="font-size:13px; color:var(--color-text-muted);">Lengkapi instrumen kepatuhan dan audit keamanan informasi Anda</p>

        <div class="related-grid">
          <?php foreach ($relatedProducts as $rp): ?>
            <div class="related-card">
              <img src="<?= htmlspecialchars($rp['cover_image'] ?: '/images/book-cover-450.webp') ?>" alt="<?= htmlspecialchars($rp['title']) ?>" class="related-thumb">
              <div class="related-info">
                <span style="font-size:10px; font-weight:800; color:var(--color-cyan); text-transform:uppercase;"><?= htmlspecialchars($rp['category']) ?></span>
                <a href="/produk/<?= urlencode($rp['slug'] ?: $rp['id']) ?>" class="related-title">
                  <?= htmlspecialchars($rp['title']) ?>
                </a>
                <div class="related-price">Rp <?= number_format($rp['price'], 0, ',', '.') ?></div>
                <div style="margin-top:6px;">
                  <a href="/produk/<?= urlencode($rp['slug'] ?: $rp['id']) ?>" style="font-size:11px; font-weight:800; color:var(--color-navy); text-decoration:none;">
                    Lihat Detail &rarr;
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

    <?php endif; ?>
  </main>

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
            <button type="button" class="btn-syllabus-secondary" onclick="applyCoupon()" style="white-space:nowrap; padding:9px 12px; font-size:12px;">Terapkan</button>
          </div>
          <div id="couponFeedback" style="font-size:11px; margin-top:4px; display:none;"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Nama Lengkap (Untuk Watermark Dokumen)</label>
          <input type="text" id="inputName" required placeholder="Misal: Budi Santoso, S.Kom, CISA" class="form-input">
          <div class="watermark-note">✓ Akan distempel resmi di setiap footer halaman PDF</div>
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Email Penerima Dokumen</label>
          <input type="email" id="inputEmail" required placeholder="nama@perusahaan.com" class="form-input">
          <div class="watermark-note">✓ Tautan unduhan berkas PDF akan dikirim otomatis ke alamat email ini</div>
        </div>

        <div class="form-group">
          <label class="form-label">Nomor WhatsApp / Kontak (Opsional)</label>
          <input type="tel" id="inputPhone" placeholder="081234567890" class="form-input">
        </div>

        <button type="submit" id="btnSubmitCheckout" class="btn-submit-checkout">
          Lanjutkan ke Pembayaran DOKU ➔
        </button>

        <div style="text-align:center; font-size:11px; color:#64748b; margin-top:10px;">
          🔒 Pembayaran terenkripsi TLS 1.3 aman via DOKU Payment Gateway
        </div>
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

  <!-- JAVASCRIPT LOGIC -->
  <script>
    let currentRawPrice = <?= $product ? (int)$product['price'] : 0 ?>;
    let finalPayPrice = currentRawPrice;
    let currentPromoCode = '';

    function switchTab(paneId, btn) {
      document.querySelectorAll('.tab-pill-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const pane = document.getElementById(paneId);
      if (pane) pane.classList.add('active');
    }

    function scrollToTabs() {
      const el = document.getElementById('inDepthSection');
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
        // Switch ke tab silabus
        const syllabusBtn = document.querySelectorAll('.tab-pill-btn')[1];
        if (syllabusBtn) syllabusBtn.click();
      }
    }

    function openCheckout(id, title, price) {
      document.getElementById('inputProductId').value = id;
      document.getElementById('summaryTitle').innerText = title;
      currentRawPrice = price;
      finalPayPrice = price;
      document.getElementById('summaryPrice').innerText = 'Rp ' + Number(price).toLocaleString('id-ID');
      
      document.getElementById('summaryOriginalPrice').style.display = 'none';
      document.getElementById('summaryDiscountRow').style.display = 'none';
      document.getElementById('appliedCouponCode').value = '';
      document.getElementById('couponFeedback').style.display = 'none';
      
      document.getElementById('checkoutModal').style.display = 'flex';
    }

    function openCheckoutWithPromo(code) {
      <?php if ($product): ?>
        openCheckout('<?= htmlspecialchars($product['id']) ?>', '<?= htmlspecialchars(addslashes($product['title'])) ?>', <?= (int)$product['price'] ?>);
        document.getElementById('inputCouponCode').value = code;
        setTimeout(applyCoupon, 100);
      <?php endif; ?>
    }

    function closeCheckout() {
      document.getElementById('checkoutModal').style.display = 'none';
    }

    async function applyCoupon() {
      const code = document.getElementById('inputCouponCode').value.trim().toUpperCase();
      const fb = document.getElementById('couponFeedback');
      if (!code) {
        fb.style.display = 'block';
        fb.style.color = '#dc2626';
        fb.innerText = 'Masukkan kode kupon terlebih dahulu.';
        return;
      }

      fb.style.display = 'block';
      fb.style.color = '#0284c7';
      fb.innerText = 'Memverifikasi kode kupon...';

      try {
        const prodId = document.getElementById('inputProductId').value;
        const res = await fetch('/api/checkout.php?action=validate_coupon', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code: code, product_id: prodId, amount: currentRawPrice })
        });
        const data = await res.json();
        if (data.valid) {
          currentPromoCode = code;
          document.getElementById('appliedCouponCode').value = code;
          finalPayPrice = data.final_amount;

          fb.style.color = '#166534';
          fb.innerHTML = `✓ Kupon <b>${code}</b> berhasil diterapkan! Hemat Rp ${Number(data.discount_amount).toLocaleString('id-ID')}`;

          document.getElementById('summaryOriginalPrice').innerText = 'Rp ' + Number(currentRawPrice).toLocaleString('id-ID');
          document.getElementById('summaryOriginalPrice').style.display = 'block';

          document.getElementById('summaryDiscountRow').style.display = 'flex';
          document.getElementById('summaryDiscountLabel').innerText = `Potongan Kupon (${code}):`;
          document.getElementById('summaryDiscountVal').innerText = '- Rp ' + Number(data.discount_amount).toLocaleString('id-ID');

          document.getElementById('summaryPrice').innerText = 'Rp ' + Number(data.final_amount).toLocaleString('id-ID');
        } else {
          document.getElementById('appliedCouponCode').value = '';
          fb.style.color = '#dc2626';
          fb.innerText = '✕ ' + (data.message || 'Kupon tidak valid atau telah kedaluwarsa.');
        }
      } catch (e) {
        fb.style.color = '#dc2626';
        fb.innerText = 'Gagal memverifikasi kupon. Periksa koneksi Anda.';
      }
    }

    async function handleCheckoutSubmit(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitCheckout');
      btn.disabled = true;
      btn.innerText = 'Menyiapkan Pembayaran DOKU...';

      const payload = {
        product_id: document.getElementById('inputProductId').value,
        customer_name: document.getElementById('inputName').value.trim(),
        customer_email: document.getElementById('inputEmail').value.trim(),
        customer_phone: document.getElementById('inputPhone').value.trim(),
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
          window.location.href = data.payment_url;
        } else {
          alert('Gagal memproses pembayaran: ' + (data.message || 'Silakan coba lagi.'));
          btn.disabled = false;
          btn.innerText = 'Lanjutkan ke Pembayaran DOKU ➔';
        }
      } catch (err) {
        alert('Terjadi kesalahan jaringan. Silakan periksa koneksi Anda.');
        btn.disabled = false;
        btn.innerText = 'Lanjutkan ke Pembayaran DOKU ➔';
      }
    }

    function shareWhatsApp() {
      const text = encodeURIComponent("Buku Panduan: <?= addslashes($product['title'] ?? '') ?> oleh Restia Moegiono: <?= $canonicalUrl ?>");
      window.open("https://api.whatsapp.com/send?text=" + text, '_blank');
    }

    function shareLinkedIn() {
      window.open("https://www.linkedin.com/sharing/share-offsite/?url=" + encodeURIComponent("<?= $canonicalUrl ?>"), '_blank');
    }

    function copyProductLink() {
      navigator.clipboard.writeText("<?= $canonicalUrl ?>").then(() => {
        alert("✓ Tautan e-book berhasil disalin ke clipboard!");
      });
    }

    // Modal click outside to close
    document.getElementById('checkoutModal')?.addEventListener('click', function(e) {
      if (e.target === this) closeCheckout();
    });
  </script>

  <!-- UNIVERSAL CART DRAWER -->
  <?php include __DIR__ . '/cart-drawer.php'; ?>
</body>
</html>
