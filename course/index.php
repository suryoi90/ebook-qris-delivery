<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$db = getLmsDb();
$student = getLoggedInStudent();

// Ambil seluruh program kursus yang dipublikasikan
$allCourses = $db->query("SELECT * FROM lms_courses WHERE is_published = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Tentukan kursus aktif berdasarkan parameter URL (?c=slug atau /course/slug)
$courseSlug = trim($_GET['c'] ?? ($_GET['course'] ?? ($_GET['id'] ?? '')));
$course = null;

if ($courseSlug) {
    foreach ($allCourses as $c) {
        if ($c['slug'] === $courseSlug || (string)$c['id'] === $courseSlug) {
            $course = $c;
            break;
        }
    }
    if (!$course) {
        $stmt = $db->prepare("SELECT * FROM lms_courses WHERE (slug = ? OR id = ?) AND is_published = 1 LIMIT 1");
        $stmt->execute([$courseSlug, $courseSlug]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$course && !empty($allCourses)) {
    $course = $allCourses[0];
}

// Ambil modul & lessons khusus kursus aktif
$modules = [];
$lessons = [];
$moduleMap = [];

if ($course) {
    $modules = $db->query("SELECT * FROM lms_modules WHERE course_id = {$course['id']} ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lessons = $db->query("SELECT * FROM lms_lessons WHERE course_id = {$course['id']} ORDER BY module_id ASC, sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($modules as $m) {
        $moduleMap[$m['id']] = [
            'module' => $m,
            'lessons' => []
        ];
    }
    foreach ($lessons as $l) {
        if (isset($moduleMap[$l['module_id']])) {
            $moduleMap[$l['module_id']]['lessons'][] = $l;
        }
    }
}

$pageTitle = $course ? htmlspecialchars($course['title']) . ' | Restia Moegiono Academy' : 'Akademi Keamanan Informasi & Kursus | Restia Moegiono';
$pageDesc = $course ? htmlspecialchars($course['subtitle'] ?: $course['description']) : 'Program pelatihan intensif tingkat eksekutif Sistem Manajemen Keamanan Informasi ISO 27001 dan Kepatuhan Privasi Data.';
$canonicalUrl = $course ? 'https://restiamoegiono.com/course/' . urlencode($course['slug']) : 'https://restiamoegiono.com/course/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <meta name="description" content="<?= $pageDesc ?>">
  <link rel="canonical" href="<?= $canonicalUrl ?>">
  <meta name="theme-color" content="#0F224A">

  <!-- Favicon -->
  <link rel="icon" href="/assets/uploads/2026/10/Logo-transparan_e89e896f.png">
  <link rel="apple-touch-icon" href="/assets/uploads/2026/10/Logo-transparan_e89e896f.png">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Restia Moegiono Online Academy">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:title" content="<?= $pageTitle ?>">
  <meta property="og:description" content="<?= $pageDesc ?>">
  <meta property="og:image" content="<?= htmlspecialchars(!empty($course['cover_image']) ? (str_starts_with($course['cover_image'], 'http') ? $course['cover_image'] : 'https://store.restiamoegiono.com' . $course['cover_image']) : 'https://store.restiamoegiono.com/images/hero-book-420.webp') ?>">

  <!-- Typography: Plus Jakarta Sans (Sama persis dengan Store & Halaman Utama) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-page: #F8FAFC;
      --bg-card: #FFFFFF;
      --border-color: #D6EAFF;
      --border-dark: #CBD5E1;
      --text-main: #0F224A;
      --text-muted: #64748b;
      --text-sub: #475569;
      --primary: #00AEFF;
      --primary-hover: #0F224A;
      --primary-light: #E8F3FF;
      --navy: #0F224A;
      --navy-dark: #0B1B3D;
      --gold: #F2B200;
      --gold-accent: #F3AC10;
      --emerald: #059669;
      --emerald-light: #ecfdf5;
      --shadow-sm: 0 2px 8px rgba(15, 34, 74, 0.04);
      --shadow-md: 0 8px 24px rgba(15, 34, 74, 0.06);
      --shadow-lg: 0 20px 48px rgba(0, 174, 255, 0.12);
    }

    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-page);
      color: var(--text-main);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
    }

    /* TOP NAVBAR (Identik dengan Store & Portal Utama) */
    .navbar {
      background: #ffffff;
      border-bottom: 1px solid var(--border-color);
      position: sticky; top:0; z-index:100;
      padding: 14px 0;
      box-shadow: 0 1px 3px rgba(15, 34, 74, 0.02);
    }
    .navbar-inner {
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
    }
    .brand-group {
      display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;
    }
    .brand-logo-img {
      height: 38px; width: auto; max-width: 160px; object-fit: contain;
    }
    .brand-badge-fallback {
      width: 40px; height: 40px; border-radius: 12px;
      background: linear-gradient(135deg, var(--navy), var(--primary));
      color: #ffffff; font-weight: 900; font-size: 16px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 14px rgba(0, 174, 255, 0.35);
    }
    .brand-title {
      font-size: 16px; font-weight: 900; color: var(--navy); line-height: 1.2;
    }
    .brand-sub {
      font-size: 11px; color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
    }

    .nav-actions {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .badge-portal {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 20px;
      background: var(--primary-light); border: 1px solid var(--border-color);
      font-size: 12px; font-weight: 700; color: var(--navy); text-decoration: none;
      transition: all 0.2s ease;
    }
    .badge-portal:hover {
      border-color: var(--primary); color: var(--navy-dark); background: var(--border-color);
    }
    .badge-portal.active {
      background: var(--primary-light); color: var(--navy); border-color: var(--primary);
    }

    .btn-student-login {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border-radius: 20px;
      background: #f1f5f9; border: 1px solid var(--border-color);
      font-size: 12px; font-weight: 800; color: var(--navy); text-decoration: none;
      transition: all 0.2s;
    }
    .btn-student-login:hover {
      background: var(--primary-light); color: var(--primary);
    }

    .btn-classroom-cta {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 18px; border-radius: 20px;
      background: linear-gradient(135deg, var(--primary), var(--navy));
      color: #ffffff; font-size: 12px; font-weight: 800; text-decoration: none;
      box-shadow: 0 4px 12px rgba(0, 174, 255, 0.3); transition: all 0.2s;
    }
    .btn-classroom-cta:hover {
      background: linear-gradient(135deg, var(--primary), var(--navy-dark)); transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(0, 174, 255, 0.45);
    }

    /* HERO SECTION */
    .hero-section {
      padding: 48px 0 36px;
      text-align: center;
      max-width: 920px;
      margin: 0 auto;
    }
    .hero-tag {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 6px 16px; border-radius: 9999px;
      background: var(--primary-light); color: var(--navy); border: 1px solid var(--border-color);
      font-size: 12px; font-weight: 800; letter-spacing: 0.04em;
      text-transform: uppercase; margin-bottom: 20px;
    }
    .hero-title {
      font-size: clamp(28px, 4vw, 44px);
      font-weight: 900;
      line-height: 1.25;
      color: var(--navy);
      letter-spacing: -0.02em;
      margin-bottom: 18px;
    }
    .hero-title span {
      background: linear-gradient(135deg, var(--primary), var(--gold));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hero-desc {
      font-size: 16px;
      color: var(--text-sub);
      line-height: 1.7;
      max-width: 780px;
      margin: 0 auto 32px;
    }

    /* KEY METRICS STRIP */
    .metrics-bar {
      display: inline-flex; flex-wrap: wrap; justify-content: center; gap: 24px;
      background: #ffffff; border: 1px solid var(--border-color);
      border-radius: 18px; padding: 14px 28px; box-shadow: var(--shadow-sm);
      margin-bottom: 40px;
    }
    .metric-item {
      display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #334155;
    }
    .metric-icon { font-size: 16px; color: var(--primary); }

    /* FEATURED MASTERCLASS CARD (Sama dengan .featured-box pada Store) */
    .featured-box {
      background: #ffffff;
      border: 2px solid var(--primary);
      border-radius: 28px;
      padding: 40px;
      margin-bottom: 60px;
      box-shadow: var(--shadow-lg);
      display: grid;
      grid-template-columns: 1fr 360px;
      gap: 36px;
    }
    @media (max-width: 900px) {
      .featured-box { grid-template-columns: 1fr; padding: 28px; gap: 28px; }
    }

    .featured-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 12px; border-radius: 20px;
      background: var(--primary-light); color: var(--navy); font-size: 11px; font-weight: 800;
      border: 1px solid var(--border-color); margin-bottom: 14px;
    }
    .featured-title {
      font-size: 26px; font-weight: 900; line-height: 1.3; color: var(--navy); margin-bottom: 12px;
    }
    .featured-desc {
      font-size: 14px; color: var(--text-sub); line-height: 1.7; margin-bottom: 24px;
    }

    .highlights-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }
    @media (max-width: 600px) { .highlights-grid { grid-template-columns: 1fr; } }
    .highlight-point {
      display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: #334155; font-weight: 600;
    }
    .check-icon {
      color: #059669; font-size: 14px; font-weight: 900; flex-shrink: 0; margin-top: 1px;
    }

    /* RIGHT PRICING SIDE */
    .pricing-panel {
      background: var(--primary-light);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 32px 28px;
      display: flex; flex-direction: column; justify-content: space-between;
    }
    .price-box { margin-bottom: 20px; }
    .old-price {
      font-size: 14px; color: #94a3b8; text-decoration: line-through; font-weight: 600; margin-bottom: 4px;
    }
    .new-price {
      font-size: 34px; font-weight: 900; color: var(--navy); letter-spacing: -0.02em; line-height: 1;
    }
    .price-note {
      font-size: 12px; color: #059669; font-weight: 700; margin-top: 8px; display: flex; align-items: center; gap: 4px;
    }

    .btn-buy-doku {
      display: block; width: 100%; text-align: center;
      background: linear-gradient(135deg, var(--primary), var(--navy));
      color: #ffffff; font-size: 14px; font-weight: 800; padding: 15px 24px;
      border-radius: 14px; text-decoration: none;
      box-shadow: 0 8px 20px rgba(0, 174, 255, 0.35); transition: all 0.2s;
      margin-bottom: 12px;
    }
    .btn-buy-doku:hover {
      background: linear-gradient(135deg, #1CA2FF, var(--navy-dark)); transform: translateY(-1px);
      box-shadow: 0 10px 24px rgba(0, 174, 255, 0.45);
    }
    .btn-preview-direct {
      display: block; width: 100%; text-align: center;
      background: #ffffff; color: #334155;
      border: 1px solid var(--border-dark); font-size: 13px; font-weight: 700;
      padding: 12px 20px; border-radius: 14px; text-decoration: none; transition: all 0.2s;
    }
    .btn-preview-direct:hover {
      background: #f1f5f9; color: var(--navy); border-color: var(--primary);
    }

    /* CURRICULUM SECTION */
    .section-head-wrap {
      display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 12px;
      margin-bottom: 24px;
    }
    .section-title { font-size: 24px; font-weight: 900; color: var(--navy); }
    .section-sub { font-size: 14px; color: var(--text-muted); }

    .curriculum-accordion {
      display: flex; flex-direction: column; gap: 16px; margin-bottom: 60px;
    }
    .module-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }
    .module-header {
      padding: 18px 24px;
      background: var(--primary-light);
      border-bottom: 1px solid var(--border-color);
      display: flex; align-items: center; justify-content: space-between;
    }
    .module-title { font-size: 15px; font-weight: 800; color: var(--navy); }
    .module-count-badge {
      font-size: 11px; font-weight: 700; color: var(--navy); background: #ffffff;
      padding: 3px 10px; border-radius: 20px; border: 1px solid var(--border-color);
    }

    .lessons-list { list-style: none; }
    .lesson-item {
      padding: 14px 24px;
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 1px solid #f1f5f9;
      transition: background 0.15s ease;
    }
    .lesson-item:last-child { border-bottom: none; }
    .lesson-item:hover { background: var(--primary-light); }

    .lesson-left { display: flex; align-items: center; gap: 12px; }
    .lesson-play-circle {
      width: 30px; height: 30px; border-radius: 50%;
      background: var(--primary-light); border: 1px solid var(--border-color);
      color: var(--primary); display: flex; align-items: center; justify-content: center;
      font-size: 11px; flex-shrink: 0;
    }
    .lesson-name {
      font-size: 13px; font-weight: 700; color: #1e293b;
    }
    .lesson-meta { display: flex; align-items: center; gap: 10px; }
    .preview-pill {
      font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px;
      background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
      text-decoration: none; transition: all 0.2s;
    }
    .preview-pill:hover { background: #059669; color: #ffffff; }
    .duration-text {
      font-size: 12px; color: #64748b; font-family: ui-monospace, SFMono-Regular, monospace;
    }

    /* INSTRUCTOR CARD (Sama dengan Author Box pada Store) */
    .instructor-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 24px;
      padding: 36px;
      display: flex; align-items: center; gap: 28px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 60px;
    }
    @media (max-width: 768px) {
      .instructor-card { flex-direction: column; text-align: center; padding: 28px; }
    }
    .instructor-img {
      width: 100px; height: 100px; border-radius: 50%;
      object-fit: cover; border: 3px solid var(--border-color); flex-shrink: 0;
      box-shadow: 0 4px 14px rgba(0, 174, 255, 0.25);
    }
    .instructor-info h3 {
      font-size: 18px; font-weight: 900; color: var(--navy); margin-bottom: 4px;
    }
    .instructor-role {
      font-size: 13px; color: var(--primary); font-weight: 800; margin-bottom: 10px;
    }
    .instructor-bio {
      font-size: 14px; color: var(--text-sub); line-height: 1.7;
    }

    /* FOOTER (Identik dengan Store & Portal Utama) */
    footer {
      border-top: 2px solid var(--primary);
      padding: 48px 0;
      background: var(--navy-dark);
      margin-top: 40px;
      text-align: center;
      font-size: 13px;
      color: #94a3b8;
    }
    .footer-links {
      display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 16px; font-weight: 700;
    }
    .footer-links a {
      color: var(--primary-light); text-decoration: none; transition: color 0.15s;
    }
    .footer-links a:hover { color: var(--primary); }
    .footer-copy { font-size: 12px; color: #94a3b8; margin-top: 8px; }
  </style>
</head>
<body>

  <!-- TOP NAVBAR -->
  <nav class="navbar">
    <div class="container navbar-inner">
      <a href="<?= LMS_BASE_URL ?>" class="brand-group">
        <img 
          src="/assets/uploads/2026/10/Logo-1254x1254_2e96a68b.png" 
          alt="Restia Moegiono" 
          class="brand-logo-img"
          onerror="this.style.display='none'; document.getElementById('fallbackLogoBadge').style.display='flex';"
        >
        <div id="fallbackLogoBadge" class="brand-badge-fallback" style="display:none;">RM</div>
        <div>
          <div class="brand-title">Restia Moegiono</div>
          <div class="brand-sub">Online Academy &amp; Masterclass</div>
        </div>
      </a>

      <div class="nav-actions">
        <a href="<?= MAIN_URL ?>" target="_blank" class="badge-portal">
          🌐 Portal Utama
        </a>
        <a href="<?= STORE_URL ?>" class="badge-portal">
          📚 E-Book Store
        </a>
        <a href="<?= LMS_BASE_URL ?>" class="badge-portal active">
          🎓 Kelas &amp; Kurikulum
        </a>

        <?php if ($student): ?>
          <a href="<?= LMS_BASE_URL ?>/learn.php" class="btn-classroom-cta">
            <span>Masuk Ruang Belajar</span> ➔
          </a>
        <?php else: ?>
          <a href="<?= LMS_BASE_URL ?>/login.php" class="btn-student-login">
            <span>🔐 Masuk Siswa</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTAINER -->
  <main class="container">

    <?php if (!$course): ?>
      <!-- 404 KURSUS TIDAK DITEMUKAN -->
      <div style="text-align: center; padding: 80px 20px; max-width: 600px; margin: 0 auto;">
        <div style="font-size: 56px; margin-bottom: 16px;">🔍</div>
        <h1 style="font-size: 26px; font-weight: 900; color: var(--navy); margin-bottom: 10px;">Program Kursus Tidak Ditemukan</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
          Mohon maaf, program kursus atau silabus yang Anda tuju tidak ditemukan atau belum dipublikasikan.
        </p>
        <a href="<?= LMS_BASE_URL ?>" class="btn-buy-doku" style="max-width: 260px; margin: 0 auto; display: inline-block;">
          Kembali ke Daftar Kursus ➔
        </a>
      </div>
    <?php else: ?>

      <!-- BREADCRUMBS -->
      <div style="padding: 18px 0 0; font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <a href="<?= MAIN_URL ?>" style="color: inherit; text-decoration: none;">Beranda</a>
        <span>&gt;</span>
        <a href="<?= LMS_BASE_URL ?>" style="color: inherit; text-decoration: none;">Akademi &amp; Kursus</a>
        <span>&gt;</span>
        <span style="color: var(--navy); font-weight: 700;"><?= htmlspecialchars($course['title']) ?></span>
      </div>

      <!-- HERO SECTION -->
      <section class="hero-section">
        <div class="hero-tag">🛡️ <?= htmlspecialchars($course['badge'] ?: 'AKADEMI RESMI & LITERASI KEPATUHAN') ?></div>
        <h1 class="hero-title">
          <?= htmlspecialchars($course['title']) ?>
        </h1>
        <p class="hero-desc">
          <?= htmlspecialchars($course['subtitle'] ?: $course['description']) ?>
        </p>

        <div class="metrics-bar">
          <div class="metric-item">
            <span class="metric-icon">⏱️</span>
            <span><?= htmlspecialchars($course['total_duration'] ?: '4 Jam') ?> Materi Video HD</span>
          </div>
          <div class="metric-item">
            <span class="metric-icon">📚</span>
            <span><?= count($lessons) ?> Bab Terstruktur</span>
          </div>
          <div class="metric-item">
            <span class="metric-icon">📑</span>
            <span>Master Template &amp; Instrumen Kerja</span>
          </div>
          <div class="metric-item">
            <span class="metric-icon">📜</span>
            <span>E-Sertifikat Kelulusan Terverifikasi</span>
          </div>
        </div>
      </section>

      <?php if (count($allCourses) > 1): ?>
      <!-- KATALOG PILIHAN PROGRAM MASTERCLASS LAINNYA -->
      <section style="margin-bottom: 48px;">
        <div style="text-align: center; margin-bottom: 24px;">
          <span style="font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.08em; background: var(--primary-light); padding: 4px 14px; border-radius: 20px; border: 1px solid var(--border-color);">
            📚 Seluruh Program Eksekutif Akademi
          </span>
          <h2 style="font-size: 24px; font-weight: 900; color: var(--navy); margin-top: 8px;">
            Eksplorasi Kurikulum &amp; Masterclass Lainnya
          </h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
          <?php foreach ($allCourses as $c): ?>
            <?php 
              $cUrl = '/course/' . urlencode($c['slug'] ?: $c['id']); 
              $isActive = ($c['id'] == $course['id']);
            ?>
            <div style="background:#ffffff; border: <?= $isActive ? '2px solid var(--primary)' : '1px solid var(--border-color)' ?>; border-radius: 20px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: <?= $isActive ? '0 10px 28px rgba(0, 174, 255, 0.15)' : 'var(--shadow-sm)' ?>; transition: all 0.2s ease;">
              <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                  <span style="font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; background: var(--primary-light); color: var(--navy); border: 1px solid var(--border-color);">
                    <?= htmlspecialchars($c['badge'] ?: 'MASTERCLASS') ?>
                  </span>
                  <?php if ($isActive): ?>
                    <span style="font-size: 11px; font-weight: 800; color: #059669;">● Sedang Ditampilkan</span>
                  <?php endif; ?>
                </div>
                <h3 style="font-size: 16px; font-weight: 900; color: var(--navy); margin-bottom: 8px; line-height: 1.35;">
                  <a href="<?= $cUrl ?>" style="color: inherit; text-decoration: none;">
                    <?= htmlspecialchars($c['title']) ?>
                  </a>
                </h3>
                <p style="font-size: 13px; color: var(--text-sub); line-height: 1.5; margin-bottom: 14px;">
                  <?= htmlspecialchars($c['subtitle'] ?: substr(strip_tags($c['description']), 0, 100) . '...') ?>
                </p>
                <div style="display: flex; gap: 14px; font-size: 12px; color: var(--text-muted); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                  <div>⏱️ <b><?= htmlspecialchars($c['total_duration'] ?: '4 Jam') ?></b></div>
                  <div>📚 <b><?= (int)$c['total_lessons'] ?> Bab</b></div>
                </div>
              </div>
              <div>
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                  <div style="font-size: 11px; color: #94a3b8; text-decoration: line-through;">Rp <?= number_format($c['old_price'], 0, ',', '.') ?></div>
                  <div style="font-size: 18px; font-weight: 900; color: var(--navy);">Rp <?= number_format($c['price'], 0, ',', '.') ?></div>
                </div>
                <div style="display: flex; gap: 8px;">
                  <a href="<?= $cUrl ?>" style="flex: 1; text-align: center; text-decoration: none; padding: 9px 12px; border-radius: 10px; background: var(--primary-light); color: var(--navy); font-size: 12px; font-weight: 800; border: 1px solid var(--border-color);">
                    Silabus &amp; Detail ➔
                  </a>
                  <a href="<?= STORE_URL ?>?checkout=<?= urlencode($c['slug'] ?: $c['id']) ?>" style="text-decoration: none; padding: 9px 14px; border-radius: 10px; background: linear-gradient(135deg, var(--primary), var(--navy)); color: #fff; font-size: 12px; font-weight: 800; box-shadow: 0 4px 12px rgba(0, 174, 255, 0.3);">
                    Daftar
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- FEATURED MASTERCLASS CARD -->
      <div class="featured-box">
        <div>
          <div class="featured-badge">★ <?= htmlspecialchars($course['badge']) ?></div>
          <h2 class="featured-title"><?= htmlspecialchars($course['title']) ?></h2>
          <p class="featured-desc"><?= htmlspecialchars($course['description']) ?></p>

          <div class="highlights-grid">
            <div class="highlight-point">
              <span class="check-icon">✓</span>
              <span>Bedah Tuntas Klausul &amp; Metodologi Implementasi Resmi</span>
            </div>
            <div class="highlight-point">
              <span class="check-icon">✓</span>
              <span>Penerapan Praktis Kontrol Standar Terkini</span>
            </div>
            <div class="highlight-point">
              <span class="check-icon">✓</span>
              <span>Integrasi Kepatuhan Regulasi Nasional &amp; Internasional</span>
            </div>
            <div class="highlight-point">
              <span class="check-icon">✓</span>
              <span>Panduan Penyusunan Dokumen Kerja &amp; Form Audit</span>
            </div>
            <div class="highlight-point">
              <span class="check-icon">✓</span>
              <span>Simulasi Wawancara Stage 1 &amp; Stage 2 External Audit</span>
            </div>
            <div class="highlight-point">
              <span class="check-icon">✓</span>
              <span>Akses Selamanya &amp; Pembaruan Materi Berkala</span>
            </div>
          </div>
        </div>

        <div class="pricing-panel">
          <div class="price-box">
            <div class="old-price">Rp <?= number_format($course['old_price'], 0, ',', '.') ?></div>
            <div class="new-price">Rp <?= number_format($course['price'], 0, ',', '.') ?></div>
            <div class="price-note">✓ Termasuk Lisensi Sertifikat &amp; Instrumen Audit</div>
          </div>

          <div>
            <a href="<?= STORE_URL ?>?checkout=<?= urlencode($course['slug'] ?: $course['id']) ?>" class="btn-buy-doku">
              Daftar &amp; Bayar via DOKU ➔
            </a>
            <a href="<?= LMS_BASE_URL ?>/learn.php?course=<?= urlencode($course['slug'] ?: $course['id']) ?>" class="btn-preview-direct">
              Buka Ruang Belajar / Preview Bab
            </a>
          </div>
        </div>
      </div>

      <!-- CURRICULUM SECTION -->
      <div class="section-head-wrap">
        <div>
          <h2 class="section-title">Silabus &amp; Kurikulum Pembelajaran</h2>
          <p class="section-sub">Daftar lengkap modul dan <?= count($lessons) ?> bab materi pembelajaran</p>
        </div>
        <div>
          <span style="font-size:12px; color:#059669; font-weight:800; background:#ecfdf5; border:1px solid #a7f3d0; padding:4px 12px; border-radius:20px;">
            💡 Tersedia Bab Preview Gratis untuk Ditonton
          </span>
        </div>
      </div>

      <div class="curriculum-accordion">
        <?php if (empty($moduleMap)): ?>
          <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:18px; padding:36px; text-align:center; color:var(--text-muted);">
            Kurikulum dan silabus materi untuk program kursus ini sedang disusun oleh instruktur.
          </div>
        <?php else: ?>
          <?php foreach ($moduleMap as $mId => $data): ?>
            <div class="module-card">
              <div class="module-header">
                <div class="module-title"><?= htmlspecialchars($data['module']['title']) ?></div>
                <span class="module-count-badge"><?= count($data['lessons']) ?> Bab Pembelajaran</span>
              </div>
              <ul class="lessons-list">
                <?php foreach ($data['lessons'] as $l): ?>
                  <li class="lesson-item">
                    <div class="lesson-left">
                      <div class="lesson-play-circle">&#9654;</div>
                      <div class="lesson-name"><?= htmlspecialchars($l['title']) ?></div>
                    </div>
                    <div class="lesson-meta">
                      <?php if ($l['is_free_preview']): ?>
                        <a href="<?= LMS_BASE_URL ?>/learn.php?course=<?= urlencode($course['slug'] ?: $course['id']) ?>&lesson=<?= urlencode($l['slug']) ?>" class="preview-pill">
                          Gratis Preview
                        </a>
                      <?php endif; ?>
                      <span class="duration-text"><?= htmlspecialchars($l['duration']) ?></span>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    <?php endif; ?>

    <!-- INSTRUCTOR CARD -->
    <div class="instructor-card">
      <img 
        src="<?= htmlspecialchars($course['instructor_avatar']) ?>" 
        alt="Restia Moegiono" 
        class="instructor-img" 
        onerror="this.src='/assets/uploads/2026/10/Logo-1254x1254_2e96a68b.png'"
      >
      <div class="instructor-info">
        <h3><?= htmlspecialchars($course['instructor_name']) ?></h3>
        <div class="instructor-role"><?= htmlspecialchars($course['instructor_title']) ?></div>
        <p class="instructor-bio"><?= htmlspecialchars($course['instructor_bio']) ?></p>
      </div>
    </div>

  </main>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-links">
        <a href="<?= MAIN_URL ?>" target="_blank">🌐 Portal Berita &amp; Profil</a>
        <a href="<?= STORE_URL ?>">📚 E-Book Store Resmi</a>
        <a href="<?= LMS_BASE_URL ?>/login.php">🔐 Akses Siswa / OTP</a>
        <a href="<?= LMS_BASE_URL ?>/admin.php" style="color:#0284c7; font-weight:700;">⚙️ Portal Instruktur &amp; Admin</a>
        <a href="<?= STORE_URL ?>?cek=1">🔍 Cek Pesanan / Kirim Ulang</a>
      </div>
      <div>&copy; 2026 Restia Moegiono. Seluruh Hak Cipta Dilindungi Undang-Undang.</div>
      <div class="footer-copy">
        Platform Pembelajaran &amp; Pelatihan Kepatuhan Sistem Manajemen Keamanan Informasi (ISO 27001) &amp; UU PDP.
      </div>
    </div>
  </footer>

</body>
</html>
