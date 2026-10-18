<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$db = getLmsDb();
$student = getLoggedInStudent();

// Ambil course berdasarkan parameter URL (slug atau ID), fallback ke kursus aktif pertama
$courseParam = trim($_GET['course'] ?? ($_GET['c'] ?? ($_GET['id'] ?? '')));
$course = null;
if ($courseParam) {
    $stmt = $db->prepare("SELECT * FROM lms_courses WHERE slug = ? OR id = ? LIMIT 1");
    $stmt->execute([$courseParam, $courseParam]);
    $course = $stmt->fetch();
}
if (!$course) {
    $course = $db->query("SELECT * FROM lms_courses WHERE is_published = 1 ORDER BY id ASC LIMIT 1")->fetch();
}
if (!$course) {
    $course = $db->query("SELECT * FROM lms_courses ORDER BY id ASC LIMIT 1")->fetch();
}
if (!$course) {
    die("Kursus belum tersedia.");
}


// Ambil seluruh modul & lessons
$modules = $db->query("SELECT * FROM lms_modules WHERE course_id = {$course['id']} ORDER BY sort_order ASC")->fetchAll();
$lessons = $db->query("SELECT * FROM lms_lessons WHERE course_id = {$course['id']} ORDER BY module_id ASC, sort_order ASC")->fetchAll();

// Tentukan bab aktif
$activeSlug = trim($_GET['lesson'] ?? '');
$activeLesson = null;

if ($activeSlug) {
    foreach ($lessons as $l) {
        if ($l['slug'] === $activeSlug) {
            $activeLesson = $l;
            break;
        }
    }
}
if (!$activeLesson && !empty($lessons)) {
    $activeLesson = $lessons[0];
}

// Ambil data progres siswa jika login
$completedLessonIds = [];
if ($student) {
    $stmt = $db->prepare("SELECT lesson_id FROM lms_progress WHERE user_email = ? AND course_id = ? AND is_completed = 1");
    $stmt->execute([$student['email'], $course['id']]);
    $completedLessonIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$totalLessons = count($lessons);
$completedCount = count($completedLessonIds);
$progressPercent = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

// Cari previous & next lesson
$prevLesson = null;
$nextLesson = null;
foreach ($lessons as $idx => $l) {
    if ($l['id'] === $activeLesson['id']) {
        if (isset($lessons[$idx - 1])) $prevLesson = $lessons[$idx - 1];
        if (isset($lessons[$idx + 1])) $nextLesson = $lessons[$idx + 1];
        break;
    }
}

$isActiveCompleted = in_array($activeLesson['id'], $completedLessonIds);
$canAccess = ($student !== null) || (!empty($activeLesson['is_free_preview']));

// Kuis questions count
$quizQuestionsCount = (int)$db->query("SELECT COUNT(*) FROM lms_quiz_questions WHERE course_id = {$course['id']}")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($activeLesson['title']) ?> | <?= htmlspecialchars($course['title']) ?></title>
  <link rel="icon" href="/assets/uploads/2026/10/Logo-transparan_e89e896f.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-page: #F8FAFC;
      --bg-card: #FFFFFF;
      --border-color: #D6EAFF;
      --border-dark: #B9DCFF;
      --text-main: #0F224A;
      --text-muted: #5A6E85;
      --text-sub: #334155;
      --primary: #00AEFF;
      --primary-hover: #0096DE;
      --gold: #F2B200;
      --navy: #0F224A;
      --navy-dark: #0B1B3D;
      --soft-blue: #E8F3FF;
      --emerald: #059669;
      --shadow-sm: 0 2px 8px rgba(15, 34, 74, 0.04);
      --shadow-md: 0 8px 24px rgba(15, 34, 74, 0.08);
    }

    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-page);
      color: var(--text-main);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }

    /* Top Bar */
    .classroom-header {
      background: #ffffff; border-bottom: 1px solid var(--border-color);
      padding: 12px 24px; position: sticky; top:0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
      box-shadow: 0 1px 3px rgba(15, 34, 74, 0.03);
    }
    .header-left { display: flex; align-items: center; gap: 14px; }
    .back-btn {
      color: var(--navy); text-decoration: none; font-size: 13px; font-weight: 700;
      display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;
      border-radius: 20px; background: var(--soft-blue); border: 1px solid var(--border-color);
      transition: all 0.2s;
    }
    .back-btn:hover { background: #D6EAFF; color: var(--primary); }
    .course-title-crumb {
      font-size: 14px; font-weight: 800; color: var(--navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 480px;
    }
    @media (max-width: 768px) { .course-title-crumb { display: none; } }

    .header-right { display: flex; align-items: center; gap: 14px; }
    .progress-box {
      display: flex; align-items: center; gap: 10px; font-size: 12px; font-weight: 700; color: var(--text-muted);
    }
    .progress-track {
      width: 120px; height: 7px; background: var(--soft-blue); border-radius: 9999px; overflow: hidden; border: 1px solid var(--border-color);
    }
    .progress-fill {
      height: 100%; background: linear-gradient(90deg, #00AEFF, #F2B200);
      width: <?= $progressPercent ?>%; transition: width 0.4s ease;
    }
    .user-pill {
      display: flex; align-items: center; gap: 8px; padding: 4px 12px 4px 6px;
      background: var(--soft-blue); border: 1px solid var(--border-color);
      border-radius: 9999px; font-size: 12px; font-weight: 700; color: var(--navy);
    }
    .user-avatar {
      width: 24px; height: 24px; border-radius: 50%;
      background: linear-gradient(135deg, #00AEFF, #0F224A);
      display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: #fff;
    }
    .btn-logout {
      background: transparent; border: none; color: #94a3b8; font-size: 12px;
      cursor: pointer; padding: 4px 6px; border-radius: 6px; font-weight: 700;
    }
    .btn-logout:hover { color: #ef4444; }

    /* Classroom Layout Grid */
    .classroom-layout {
      display: grid; grid-template-columns: 1fr 360px; min-height: calc(100vh - 61px);
      transition: grid-template-columns 0.3s ease;
    }
    .classroom-layout.theater-active {
      grid-template-columns: 1fr;
    }
    .classroom-layout.theater-active .classroom-sidebar {
      display: none;
    }
    @media (max-width: 1024px) {
      .classroom-layout { grid-template-columns: 1fr; }
    }

    /* Video & Content Area */
    .stage-section {
      padding: 24px 32px 60px; max-width: 1060px; margin: 0 auto; width: 100%;
    }
    @media (max-width: 768px) { .stage-section { padding: 16px; } }

    /* Video Player Container */
    .video-viewport {
      position: relative; width: 100%; padding-bottom: 56.25%; /* 16:9 */
      background: #000; border-radius: 20px; overflow: hidden;
      box-shadow: 0 16px 36px rgba(15, 34, 74, 0.12); margin-bottom: 24px;
      border: 1px solid var(--border-color);
    }
    .video-viewport iframe, .video-viewport video {
      position: absolute; top:0; left:0; width:100%; height:100%; border:0;
    }

    /* Locked Overlay */
    .locked-overlay {
      position: absolute; top:0; left:0; width:100%; height:100%;
      background: rgba(11, 27, 61, 0.94); backdrop-filter: blur(8px);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 30px; text-align: center; color: #fff;
    }
    .locked-icon { font-size: 42px; margin-bottom: 12px; }
    .locked-title { font-size: 20px; font-weight: 900; color: #fff; margin-bottom: 8px; }
    .locked-desc { font-size: 13px; color: #D6EAFF; max-width: 440px; margin-bottom: 20px; }
    .btn-unlock-cta {
      background: linear-gradient(135deg, #00AEFF, #0F224A); color: #fff;
      font-size: 14px; font-weight: 800; padding: 12px 28px; border-radius: 9999px;
      text-decoration: none; box-shadow: 0 6px 20px rgba(0, 174, 255, 0.4);
    }

    /* Stage Controls Bar */
    .stage-header-bar {
      display: flex; align-items: flex-start; justify-content: space-between;
      flex-wrap: wrap; gap: 16px; margin-bottom: 24px; padding-bottom: 20px;
      border-bottom: 1px solid var(--border-color);
    }
    .lesson-meta-crumb { font-size: 12px; color: var(--primary); font-weight: 800; margin-bottom: 4px; }
    .lesson-main-title { font-size: 22px; font-weight: 900; color: var(--navy); line-height: 1.3; }

    .stage-actions-right {
      display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .btn-complete-toggle {
      padding: 9px 18px; border-radius: 12px; font-size: 13px; font-weight: 800;
      cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
      border: none; transition: all 0.2s;
    }
    .btn-complete-toggle.is-done {
      background: var(--soft-blue); color: var(--navy); border: 1px solid var(--primary);
    }
    .btn-complete-toggle.is-done:hover {
      background: #fef2f2; color: #991b1b; border-color: #fecaca;
    }
    .btn-complete-toggle.not-done {
      background: linear-gradient(135deg, #00AEFF, #0F224A); color: #fff;
      box-shadow: 0 4px 14px rgba(0, 174, 255, 0.3);
    }
    .btn-complete-toggle.not-done:hover {
      background: linear-gradient(135deg, #0096DE, #0B1B3D); transform: translateY(-1px);
    }

    .btn-theater-mode {
      padding: 9px 14px; border-radius: 12px; font-size: 12px; font-weight: 800;
      background: #ffffff; border: 1px solid var(--border-color); color: var(--navy);
      cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
    }
    .btn-theater-mode:hover { background: var(--soft-blue); color: var(--primary); border-color: var(--primary); }

    .nav-arrow-btn {
      width: 36px; height: 36px; border-radius: 10px;
      background: #ffffff; border: 1px solid var(--border-color);
      color: var(--navy); font-size: 14px; display: flex; align-items: center; justify-content: center;
      text-decoration: none; transition: all 0.2s;
    }
    .nav-arrow-btn:hover { background: var(--soft-blue); color: var(--primary); border-color: var(--primary); }
    .nav-arrow-btn.disabled { opacity: 0.3; pointer-events: none; }

    /* Content Tabs */
    .content-tabs-nav {
      display: flex; gap: 4px; border-bottom: 1px solid var(--border-color); margin-bottom: 24px;
      overflow-x: auto; white-space: nowrap; padding-bottom: 2px;
    }
    .tab-btn {
      padding: 10px 16px; background: transparent; border: none;
      color: var(--text-muted); font-size: 13px; font-weight: 800; cursor: pointer;
      border-bottom: 2px solid transparent; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px;
    }
    .tab-btn:hover { color: var(--navy); }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    .notes-card {
      background: #ffffff; border: 1px solid var(--border-color); border-radius: 20px;
      padding: 28px; box-shadow: var(--shadow-sm);
    }
    .lesson-rich-body {
      color: #334155; font-size: 14px; line-height: 1.8;
    }
    .lesson-rich-body h4 { color: var(--navy); font-size: 16px; margin: 20px 0 8px; font-weight: 800; }
    .lesson-rich-body ul { padding-left: 20px; margin-bottom: 16px; }
    .lesson-rich-body li { margin-bottom: 6px; }

    /* Personal Notes Notepad */
    .notepad-textarea {
      width: 100%; min-height: 220px; padding: 18px; border-radius: 14px;
      border: 1px solid var(--border-color); background: var(--soft-blue); font-family: inherit; font-size: 14px;
      line-height: 1.7; color: var(--navy); outline: none; transition: all 0.2s; resize: vertical;
    }
    .notepad-textarea:focus {
      background: #ffffff; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 174, 255, 0.18);
    }
    .notepad-footer {
      display: flex; align-items: center; justify-content: space-between; margin-top: 12px;
    }

    /* Quiz Assessment */
    .quiz-question-box {
      background: var(--soft-blue); border: 1px solid var(--border-color); border-radius: 16px;
      padding: 20px 24px; margin-bottom: 18px;
    }
    .quiz-q-title {
      font-size: 15px; font-weight: 800; color: var(--navy); margin-bottom: 14px;
    }
    .quiz-options-list { display: flex; flex-direction: column; gap: 8px; }
    .quiz-opt-label {
      display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 10px;
      background: #ffffff; border: 1px solid var(--border-color); cursor: pointer;
      font-size: 13px; font-weight: 600; color: var(--navy); transition: all 0.15s;
    }
    .quiz-opt-label:hover { background: #F8FAFC; border-color: var(--primary); }
    .quiz-opt-label.selected { background: var(--soft-blue); border-color: var(--primary); color: var(--navy); font-weight: 800; }

    /* Discussions Q&A */
    .discussion-card {
      border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 16px; background: #ffffff;
    }
    .disc-q-title { font-size: 15px; font-weight: 800; color: var(--navy); margin-bottom: 6px; }
    .disc-meta { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
    .disc-body { font-size: 13px; color: #334155; line-height: 1.6; margin-bottom: 14px; }
    .disc-reply-box {
      background: var(--soft-blue); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 18px; margin-top: 10px;
    }
    .disc-reply-sender { font-size: 12px; font-weight: 800; color: var(--navy); margin-bottom: 4px; }
    .disc-reply-text { font-size: 13px; color: #1e293b; line-height: 1.6; }

    /* Resource Vault Table */
    .vault-table {
      width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px;
    }
    .vault-table th {
      text-align: left; padding: 12px 16px; background: var(--soft-blue); border-bottom: 1px solid var(--border-color); font-weight: 800; color: var(--navy);
    }
    .vault-table td {
      padding: 14px 16px; border-bottom: 1px solid var(--border-color); vertical-align: middle;
    }
    .vault-table tr:hover { background: var(--soft-blue); }

    /* Sidebar Drawer */
    .classroom-sidebar {
      background: #ffffff; border-left: 1px solid var(--border-color);
      overflow-y: auto; height: calc(100vh - 61px); position: sticky; top: 61px;
    }
    .sidebar-head {
      padding: 18px 20px; border-bottom: 1px solid var(--border-color);
      display: flex; align-items: center; justify-content: space-between; background: var(--soft-blue);
    }
    .sidebar-title { font-size: 14px; font-weight: 900; color: var(--navy); }
    .sidebar-badge {
      font-size: 11px; font-weight: 800; color: var(--navy); background: #ffffff;
      padding: 3px 10px; border-radius: 20px; border: 1px solid var(--border-color);
    }

    .curriculum-module-group {
      border-bottom: 1px solid var(--border-color);
    }
    .module-group-head {
      padding: 12px 20px; background: #F8FAFC; border-bottom: 1px solid var(--border-color);
      font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;
    }
    .sidebar-lesson-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 13px 20px; border-bottom: 1px solid var(--border-color);
      text-decoration: none; color: var(--navy); transition: all 0.15s;
    }
    .sidebar-lesson-item:hover { background: var(--soft-blue); color: var(--primary); }
    .sidebar-lesson-item.active {
      background: var(--soft-blue); color: var(--navy); border-left: 4px solid var(--primary); font-weight: 800;
    }
    .s-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .status-check-circle {
      width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 900; flex-shrink: 0;
    }
    .status-check-circle.completed {
      background: var(--primary); color: #fff;
    }
    .status-check-circle.incomplete {
      border: 1px solid var(--border-dark); color: transparent; background: #fff;
    }
    .status-check-circle.locked {
      color: #94a3b8; font-size: 11px;
    }
    .s-title {
      font-size: 12px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .s-duration {
      font-size: 11px; color: #94a3b8; font-family: ui-monospace, SFMono-Regular, monospace; flex-shrink: 0; margin-left: 8px;
    }

    /* Certificate Banner if 100% */
    .cert-unlocked-banner {
      background: var(--soft-blue); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px;
      text-align: center; margin-bottom: 24px; box-shadow: var(--shadow-sm);
    }
    .btn-view-cert {
      display: inline-block; padding: 10px 24px; border-radius: 9999px;
      background: linear-gradient(135deg, #00AEFF, #0F224A); color: #fff; font-weight: 800; font-size: 13px;
      text-decoration: none; box-shadow: 0 4px 14px rgba(0, 174, 255, 0.3); margin-top: 10px;
    }

    /* Mobile & Tablet Curriculum Drawer & Auto-Sizing */
    @media (max-width: 1024px) {
      .classroom-layout { grid-template-columns: 1fr; }
      .classroom-sidebar {
        position: fixed;
        top: 0; right: 0; bottom: 0;
        width: 100%; max-width: 360px;
        z-index: 10001;
        box-shadow: -10px 0 30px rgba(15, 34, 74, 0.25);
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        height: 100vh;
      }
      .classroom-sidebar.mobile-open {
        transform: translateX(0);
      }
      .sidebar-backdrop {
        position: fixed; inset: 0; background: rgba(15, 34, 74, 0.6);
        z-index: 10000; opacity: 0; pointer-events: none;
        transition: opacity 0.3s ease;
      }
      .sidebar-backdrop.active {
        opacity: 1; pointer-events: auto;
      }
    }
    .btn-mobile-curriculum {
      padding: 9px 14px; border-radius: 12px; font-size: 12.5px; font-weight: 800;
      background: #ffffff; border: 1px solid var(--border-color); color: var(--navy);
      cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
      transition: all 0.2s; box-shadow: var(--shadow-sm);
    }
    .btn-mobile-curriculum:hover {
      background: var(--soft-blue); border-color: var(--primary); color: var(--primary);
    }
    .btn-close-sidebar {
      background: rgba(15, 34, 74, 0.08); border: none; color: var(--navy);
      width: 32px; height: 32px; border-radius: 50%; font-size: 20px; line-height: 1;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    @media (min-width: 1025px) {
      .hide-on-desktop { display: none !important; }
      .sidebar-backdrop { display: none !important; }
    }
    @media (max-width: 768px) {
      .btn-theater-mode { display: none !important; }
      .hide-on-mobile { display: none !important; }
    }
    @media (max-width: 640px) {
      .classroom-header { padding: 10px 14px; }
      .progress-box { display: none; }
      .header-left { gap: 8px; }
      .back-btn { padding: 5px 10px; font-size: 11.5px; }
      .stage-section { padding: 14px 12px 40px; }
      .lesson-main-title { font-size: 18px; line-height: 1.35; }
      .stage-actions-right { gap: 6px; }
      .btn-complete-toggle { padding: 8px 12px; font-size: 11.5px; }
      .content-tabs-nav { gap: 4px; padding-bottom: 6px; }
      .tab-btn { padding: 8px 12px; font-size: 12px; }
      .notes-card { padding: 18px 14px; border-radius: 16px; }
      .notepad-textarea { font-size: 16px !important; }
      .quiz-question-box { padding: 14px 16px; border-radius: 12px; }
      .discussion-card { padding: 14px; }
      .vault-table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    }
  </style>
</head>
<body>

  <!-- CLASSROOM HEADER -->
  <header class="classroom-header">
    <div class="header-left">
      <a href="<?= LMS_BASE_URL ?>/<?= urlencode($course['slug']) ?>" class="back-btn" title="Kembali ke Halaman Kursus">
        <span>&larr;</span>
        <span>Halaman Kursus</span>
      </a>
      <div class="course-title-crumb">
        <?= htmlspecialchars($course['title']) ?>
      </div>
      <a href="https://store.restiamoegiono.com/admin/?tab=course" target="_blank" class="back-btn" style="background:var(--soft-blue); color:var(--navy); border-color:var(--border-color); font-size:11px; padding:5px 10px;" title="Buka Panel Editor Materi Bab di Admin">
        <span>✏️ Edit Materi (Admin)</span>
      </a>
    </div>

    <div class="header-right">
      <div class="progress-box">
        <div class="progress-track">
          <div id="progressFillBar" class="progress-fill" style="width:<?= $progressPercent ?>%;"></div>
        </div>
        <span id="progressTextLabel"><?= $progressPercent ?>% (<?= $completedCount ?>/<?= $totalLessons ?> Selesai)</span>
      </div>

      <?php if ($student): ?>
        <div class="user-pill" title="<?= htmlspecialchars($student['email']) ?>">
          <div class="user-avatar"><?= strtoupper(substr($student['name'], 0, 1)) ?></div>
          <span><?= htmlspecialchars($student['name']) ?><?= $student['is_demo'] ? ' (Demo)' : '' ?></span>
          <button type="button" class="btn-logout" onclick="lmsLogout()" title="Keluar Akun">Keluar</button>
        </div>
      <?php else: ?>
        <a href="<?= LMS_BASE_URL ?>/login.php" class="back-btn" style="background:linear-gradient(135deg, #00AEFF, #0F224A); color:#fff; border-color:transparent; font-weight:800;">
          Masuk Akun Siswa
        </a>
      <?php endif; ?>
    </div>
  </header>

  <!-- CLASSROOM TWO-COLUMN LAYOUT -->
  <div id="classroomLayoutGrid" class="classroom-layout">

    <!-- MAIN STAGE (VIDEO + TABS) -->
    <main class="stage-section">

      <!-- CERTIFICATE UNLOCKED BANNER (IF 100%) -->
      <div id="certBanner" class="cert-unlocked-banner" style="<?= $progressPercent >= 100 ? '' : 'display:none;' ?>">
        <div style="font-size:24px; margin-bottom:4px;">🎓</div>
        <h3 style="font-size:16px; font-weight:900; color:#065f46;">Selamat! Anda Telah Menyelesaikan 100% Materi Pelatihan</h3>
        <p style="font-size:12px; color:#047857; margin-top:2px;">E-Sertifikat Kelulusan resmi atas nama Anda telah siap diterbitkan.</p>
        <a href="<?= LMS_BASE_URL ?>/certificate.php?course=<?= urlencode($course['slug']) ?>" class="btn-view-cert" target="_blank">
          Lihat &amp; Unduh Sertifikat Kelulusan &rarr;
        </a>
      </div>

      <!-- VIDEO VIEWPORT -->
      <div class="video-viewport">
        <?php if ($canAccess): ?>
          <?php if ($activeLesson['video_type'] === 'youtube'): ?>
            <iframe 
              src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($activeLesson['video_id']) ?>?rel=0&modestbranding=1" 
              title="<?= htmlspecialchars($activeLesson['title']) ?>" 
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
              allowfullscreen>
            </iframe>
          <?php else: ?>
            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8; font-size:14px;">
              Memutar video materi beresolusi tinggi...
            </div>
          <?php endif; ?>
        <?php else: ?>
          <!-- LOCKED OVERLAY FOR PREMIUM LESSON -->
          <div class="locked-overlay">
            <div class="locked-icon">🔒</div>
            <h3 class="locked-title">Bab Materi Terproteksi</h3>
            <p class="locked-desc">
              Bab ini merupakan bagian dari kurikulum intensif <b><?= htmlspecialchars($course['title']) ?></b>. Masuk menggunakan email terdaftar Anda atau lakukan pendaftaran kursus untuk membuka seluruh materi.
            </p>
            <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
              <a href="<?= LMS_BASE_URL ?>/login.php" class="btn-unlock-cta" style="background:#ffffff; color:#0f172a;">
                Masuk Email Terdaftar
              </a>
              <a href="<?= STORE_URL ?>?checkout=course-iso-27001" class="btn-unlock-cta">
                Daftar &amp; Buka Akses Sekarang
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- STAGE HEADER BAR -->
      <div class="stage-header-bar">
        <div>
          <div class="lesson-meta-crumb">
            Bab Pelatihan &bull; Durasi <?= htmlspecialchars($activeLesson['duration']) ?>
            <?= $activeLesson['is_free_preview'] ? ' &bull; Preview Gratis' : '' ?>
          </div>
          <h1 class="lesson-main-title"><?= htmlspecialchars($activeLesson['title']) ?></h1>
        </div>

        <div class="stage-actions-right">
          <button type="button" class="btn-mobile-curriculum hide-on-desktop" onclick="toggleMobileCurriculum()" title="Buka Daftar Materi">
            <span>📚</span>
            <span>Daftar Bab (<?= $totalLessons ?>)</span>
          </button>

          <button type="button" class="btn-theater-mode" onclick="toggleTheaterMode()" title="Sembunyikan sidebar untuk layar lebar">
            <span id="theaterIcon">🖥️</span>
            <span id="theaterText">Mode Fokus</span>
          </button>

          <?php if ($student): ?>
            <button 
              type="button" 
              id="btnCompleteToggle" 
              class="btn-complete-toggle <?= $isActiveCompleted ? 'is-done' : 'not-done' ?>"
              onclick="toggleLessonComplete(<?= $activeLesson['id'] ?>)">
              <span id="completeIcon"><?= $isActiveCompleted ? '✓' : '○' ?></span>
              <span id="completeText"><?= $isActiveCompleted ? 'Selesai Ditonton' : 'Tandai Selesai' ?></span>
            </button>
          <?php endif; ?>

          <?php if ($prevLesson): ?>
            <a href="<?= LMS_BASE_URL ?>/learn.php?course=<?= urlencode($course['slug']) ?>&lesson=<?= urlencode($prevLesson['slug']) ?>" class="nav-arrow-btn" title="Bab Sebelumnya: <?= htmlspecialchars($prevLesson['title']) ?>">
              &larr;
            </a>
          <?php else: ?>
            <span class="nav-arrow-btn disabled">&larr;</span>
          <?php endif; ?>

          <?php if ($nextLesson): ?>
            <a href="<?= LMS_BASE_URL ?>/learn.php?course=<?= urlencode($course['slug']) ?>&lesson=<?= urlencode($nextLesson['slug']) ?>" class="nav-arrow-btn" title="Bab Selanjutnya: <?= htmlspecialchars($nextLesson['title']) ?>">
              &rarr;
            </a>
          <?php else: ?>
            <span class="nav-arrow-btn disabled">&rarr;</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- CONTENT TABS -->
      <div class="content-tabs-nav">
        <button type="button" class="tab-btn active" onclick="switchTab('notes', this)">📖 Catatan Kuliah</button>
        <button type="button" class="tab-btn" onclick="switchTab('notepad', this)">📝 Catatan Saya</button>
        <button type="button" class="tab-btn" onclick="switchTab('quiz', this)">❓ Evaluasi Kuis (<?= $quizQuestionsCount ?> Soal)</button>
        <button type="button" class="tab-btn" onclick="switchTab('discussion', this)">💬 Tanya Jawab (Q&amp;A)</button>
        <button type="button" class="tab-btn" onclick="switchTab('vault', this)">📂 Resource Vault</button>
        <button type="button" class="tab-btn" onclick="switchTab('about', this)">Profil Instruktur</button>
      </div>

      <!-- TAB 1: NOTES -->
      <div id="tab-notes" class="tab-pane active">
        <div class="notes-card">
          <div class="lesson-rich-body">
            <?= $activeLesson['content_html'] ?: '<p>Tidak ada catatan tambahan untuk bab ini. Silakan simak video pembelajaran di atas secara seksama.</p>' ?>
          </div>
        </div>
      </div>

      <!-- TAB 2: NOTEPAD (CATATAN PRIBADI SISWA) -->
      <div id="tab-notepad" class="tab-pane">
        <div class="notes-card">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div>
              <h4 style="font-size:16px; font-weight:800; color:#0f172a;">Catatan Belajar Pribadi Anda</h4>
              <p style="font-size:12px; color:#64748b;">Tersimpan otomatis khusus untuk akun Anda pada bab ini.</p>
            </div>
            <span id="noteSaveStatus" style="font-size:12px; color:#059669; font-weight:700;">✓ Siap</span>
          </div>
          <textarea 
            id="personalNoteArea" 
            class="notepad-textarea" 
            placeholder="Ketik catatan dan rangkuman penting Anda untuk bab ini... Catatan akan otomatis tersimpan." 
            oninput="handleNoteTyping(<?= $activeLesson['id'] ?>)"
          ></textarea>
          <div class="notepad-footer">
            <span style="font-size:11px; color:#94a3b8;">💡 Catatan dapat dicetak sebagai bahan review audit Anda.</span>
            <button type="button" class="btn-theater-mode" onclick="window.print()">
              🖨️ Cetak Catatan Saya
            </button>
          </div>
        </div>
      </div>

      <!-- TAB 3: QUIZ ASSESSMENT -->
      <div id="tab-quiz" class="tab-pane">
        <div class="notes-card">
          <div style="margin-bottom:20px; border-bottom:1px solid #e2e8f0; padding-bottom:14px;">
            <h4 style="font-size:17px; font-weight:900; color:#0f172a; margin-bottom:4px;">Kuis Evaluasi Pemahaman Mandiri ISO 27001:2022</h4>
            <p style="font-size:13px; color:#64748b;">Uji pemahaman klausul PDCA dan Annex A. Nilai kelulusan (passing grade): <b>80%</b>.</p>
          </div>

          <div id="quizResultAlert" style="display:none; padding:16px 20px; border-radius:14px; margin-bottom:20px;"></div>

          <form id="quizForm" onsubmit="submitQuizAssessment(event)">
            <div id="quizQuestionsContainer">
              <div style="text-align:center; padding:30px; color:#64748b;">Memuat soal evaluasi...</div>
            </div>
            <div style="margin-top:20px;">
              <button type="submit" id="btnSubmitQuiz" class="btn-complete-toggle not-done" style="width:100%; justify-content:center; padding:14px;">
                Kirim &amp; Nilai Jawaban Kuis ➔
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- TAB 4: DISCUSSIONS (Q&A) -->
      <div id="tab-discussion" class="tab-pane">
        <div class="notes-card">
          <!-- Form Ajukan Pertanyaan -->
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:20px; margin-bottom:24px;">
            <h4 style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:10px;">Ajukan Pertanyaan ke Instruktur (Restia Moegiono)</h4>
            <form onsubmit="handlePostDiscussion(event)">
              <div style="margin-bottom:10px;">
                <input type="text" id="discInputTitle" placeholder="Judul / Pokok Pertanyaan (contoh: Klausul 6.1.2 Scope Remote)" class="search-input" style="width:100%; text-transform:none; font-weight:normal;" required>
              </div>
              <div style="margin-bottom:12px;">
                <textarea id="discInputText" rows="3" placeholder="Uraikan pertanyaan spesifik atau studi kasus di perusahaan Anda..." class="notepad-textarea" style="min-height:90px;" required></textarea>
              </div>
              <button type="submit" class="btn-complete-toggle not-done">
                <span>Kirim Pertanyaan ➔</span>
              </button>
            </form>
          </div>

          <!-- Daftar Pertanyaan Terjawab -->
          <h4 style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:14px;">Pertanyaan &amp; Diskusi Siswa</h4>
          <div id="discussionsContainer">
            <div style="text-align:center; padding:20px; color:#64748b;">Memuat daftar diskusi...</div>
          </div>
        </div>
      </div>

      <!-- TAB 5: RESOURCE VAULT -->
      <div id="tab-vault" class="tab-pane">
        <div class="notes-card">
          <div style="margin-bottom:16px;">
            <h4 style="font-size:16px; font-weight:800; color:#0f172a;">Pusat Unduhan Instrumen Kerja &amp; Toolkit Audit (Resource Vault)</h4>
            <p style="font-size:13px; color:#64748b;">Seluruh berkas master instrumen ISO 27001:2022 dan UU PDP yang siap diunduh dan diedit.</p>
          </div>

          <table class="vault-table">
            <thead>
              <tr>
                <th>Nama Dokumen / Instrumen</th>
                <th>Format &amp; Ukuran</th>
                <th style="text-align:right;">Aksi Unduh</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div style="font-weight:800; color:#0f172a;">Master Statement of Applicability (SoA) 93 Kontrol 2022</div>
                  <div style="font-size:11px; color:#64748b;">Dokumen wajib klausul 6.1.3 dengan justifikasi inklusi &amp; eksklusi.</div>
                </td>
                <td><span style="font-weight:700; color:#0284c7;">Excel (.xlsx)</span> &bull; 2.4 MB</td>
                <td style="text-align:right;">
                  <button type="button" class="btn-download-attach" onclick="alert('Berkas Master SoA berhasil disiapkan untuk siswa terdaftar.')">Unduh Berkas</button>
                </td>
              </tr>
              <tr>
                <td>
                  <div style="font-weight:800; color:#0f172a;">Formulir Information Security Risk Assessment &amp; Treatment Register</div>
                  <div style="font-size:11px; color:#64748b;">Matriks matriks risiko, kalkulasi dampak &amp; probabilitas, serta treatment plan.</div>
                </td>
                <td><span style="font-weight:700; color:#0284c7;">Excel (.xlsx)</span> &bull; 1.8 MB</td>
                <td style="text-align:right;">
                  <button type="button" class="btn-download-attach" onclick="alert('Berkas Risk Register berhasil disiapkan untuk siswa terdaftar.')">Unduh Berkas</button>
                </td>
              </tr>
              <tr>
                <td>
                  <div style="font-weight:800; color:#0f172a;">Template Kebijakan Keamanan Informasi (Information Security Policy)</div>
                  <div style="font-size:11px; color:#64748b;">SOP tata kelola dokumen kepatuhan manajemen klausul 5.2.</div>
                </td>
                <td><span style="font-weight:700; color:#2563eb;">Word (.docx)</span> &bull; 850 KB</td>
                <td style="text-align:right;">
                  <button type="button" class="btn-download-attach" onclick="alert('Berkas Template SOP berhasil disiapkan untuk siswa terdaftar.')">Unduh Berkas</button>
                </td>
              </tr>
              <tr>
                <td>
                  <div style="font-weight:800; color:#0f172a;">Checklist Kesiapan Kepatuhan UU Pelindungan Data Pribadi (UU PDP)</div>
                  <div style="font-size:11px; color:#64748b;">Matriks kepatuhan klausul data pribadi harmonisasi dengan UU No. 27/2022.</div>
                </td>
                <td><span style="font-weight:700; color:#059669;">Excel (.xlsx)</span> &bull; 1.5 MB</td>
                <td style="text-align:right;">
                  <button type="button" class="btn-download-attach" onclick="alert('Berkas Checklist UU PDP berhasil disiapkan untuk siswa terdaftar.')">Unduh Berkas</button>
                </td>
              </tr>
              <tr>
                <td>
                  <div style="font-weight:800; color:#0f172a;">Checklist Audit Sertifikasi Stage 1 &amp; Stage 2 External Audit</div>
                  <div style="font-size:11px; color:#64748b;">Panduan verifikasi auditor badan sertifikasi internasional (BSI, TÜV, Sucofindo).</div>
                </td>
                <td><span style="font-weight:700; color:#dc2626;">PDF (.pdf)</span> &bull; 920 KB</td>
                <td style="text-align:right;">
                  <button type="button" class="btn-download-attach" onclick="alert('Berkas Checklist Audit berhasil disiapkan untuk siswa terdaftar.')">Unduh Berkas</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 6: ABOUT INSTRUCTOR -->
      <div id="tab-about" class="tab-pane">
        <div class="notes-card">
          <div class="lesson-rich-body">
            <h4 style="margin-top:0;"><?= htmlspecialchars($course['instructor_name']) ?></h4>
            <p style="color:#0284c7; font-weight:800; font-size:13px; margin-bottom:10px;">
              <?= htmlspecialchars($course['instructor_title']) ?>
            </p>
            <p><?= htmlspecialchars($course['instructor_bio']) ?></p>
          </div>
        </div>
      </div>

    </main>

    <!-- SIDEBAR BACKDROP FOR MOBILE -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleMobileCurriculum()"></div>

    <!-- SIDEBAR DRAWER (SYLLABUS) -->
    <aside id="classroomSidebar" class="classroom-sidebar">
      <div class="sidebar-head">
        <div>
          <div class="sidebar-title">Kurikulum Pembelajaran</div>
          <div class="sidebar-badge" style="margin-top:2px;"><?= $completedCount ?>/<?= $totalLessons ?> Bab Selesai</div>
        </div>
        <button type="button" class="btn-close-sidebar hide-on-desktop" onclick="toggleMobileCurriculum()" title="Tutup Silabus" aria-label="Tutup">&times;</button>
      </div>

      <?php foreach ($modules as $m): ?>
        <div class="curriculum-module-group">
          <div class="module-group-head"><?= htmlspecialchars($m['title']) ?></div>
          <div>
            <?php foreach ($lessons as $l): ?>
              <?php if ($l['module_id'] == $m['id']): ?>
                <?php 
                  $isLCompleted = in_array($l['id'], $completedLessonIds);
                  $isLActive = ($l['id'] === $activeLesson['id']);
                  $isLLocked = (!$student && !$l['is_free_preview']);
                ?>
                <a 
                  href="<?= LMS_BASE_URL ?>/learn.php?course=<?= urlencode($course['slug']) ?>&lesson=<?= urlencode($l['slug']) ?>" 
                  class="sidebar-lesson-item <?= $isLActive ? 'active' : '' ?>">
                  <div class="s-left">
                    <?php if ($isLCompleted): ?>
                      <div class="status-check-circle completed" id="circle-<?= $l['id'] ?>">✓</div>
                    <?php elseif ($isLLocked): ?>
                      <div class="status-check-circle locked">&#128274;</div>
                    <?php else: ?>
                      <div class="status-check-circle incomplete" id="circle-<?= $l['id'] ?>"></div>
                    <?php endif; ?>
                    <span class="s-title" title="<?= htmlspecialchars($l['title']) ?>">
                      <?= htmlspecialchars($l['title']) ?>
                    </span>
                  </div>
                  <span class="s-duration"><?= htmlspecialchars($l['duration']) ?></span>
                </a>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </aside>

  </div>

  <script>
    const currentLessonId = <?= $activeLesson['id'] ?>;
    const currentCourseId = <?= $course['id'] ?>;
    let noteDebounceTimer = null;

    // Toggle Theater / Focus Mode
    function toggleTheaterMode() {
      const grid = document.getElementById('classroomLayoutGrid');
      const isTheater = grid.classList.toggle('theater-active');
      const text = document.getElementById('theaterText');
      if (text) text.innerText = isTheater ? 'Tutup Fokus' : 'Mode Fokus';
    }

    // Switch Content Tabs
    function switchTab(tabKey, btn) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const pane = document.getElementById('tab-' + tabKey);
      if (pane) pane.classList.add('active');

      if (tabKey === 'notepad') loadPersonalNote(currentLessonId);
      if (tabKey === 'quiz') loadQuizData();
      if (tabKey === 'discussion') loadDiscussionsData();
    }

    // Load & Save Personal Note
    async function loadPersonalNote(lessonId) {
      try {
        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php?action=get_note&lesson_id=' + lessonId);
        const res = await resp.json();
        if (res.success) {
          const area = document.getElementById('personalNoteArea');
          if (area) area.value = res.note_text || '';
        }
      } catch (e) {}
    }

    function handleNoteTyping(lessonId) {
      const status = document.getElementById('noteSaveStatus');
      if (status) {
        status.innerText = '● Menyimpan...';
        status.style.color = '#f59e0b';
      }
      clearTimeout(noteDebounceTimer);
      noteDebounceTimer = setTimeout(async () => {
        const area = document.getElementById('personalNoteArea');
        const text = area ? area.value : '';
        try {
          const formData = new FormData();
          formData.append('action', 'save_note');
          formData.append('lesson_id', lessonId);
          formData.append('course_id', currentCourseId);
          formData.append('note_text', text);

          const resp = await fetch('<?= LMS_BASE_URL ?>/api.php', { method:'POST', body:formData });
          const res = await resp.json();
          if (res.success && status) {
            status.innerText = '✓ Tersimpan (' + res.time + ')';
            status.style.color = '#059669';
          }
        } catch (e) {
          if (status) status.innerText = 'Gagal menyimpan';
        }
      }, 800);
    }

    // Load Quiz Questions
    async function loadQuizData() {
      const container = document.getElementById('quizQuestionsContainer');
      try {
        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php?action=get_quiz&course_id=' + currentCourseId);
        const res = await resp.json();

        if (res.success && res.questions) {
          if (res.last_attempt) {
            const att = res.last_attempt;
            const alertBox = document.getElementById('quizResultAlert');
            if (alertBox) {
              alertBox.style.display = 'block';
              alertBox.style.background = att.is_passed ? '#ecfdf5' : '#fffbeb';
              alertBox.style.border = att.is_passed ? '1px solid #a7f3d0' : '1px solid #fde68a';
              alertBox.style.color = att.is_passed ? '#065f46' : '#92400e';
              alertBox.innerHTML = `
                <div style="font-weight:900; font-size:14px; margin-bottom:2px;">
                  ${att.is_passed ? '🎉 ANDA TELAH LULUS KUIS EVALUASI!' : '⚠️ Evaluasi Terakhir Belum Lulus'}
                </div>
                <div style="font-size:12px;">Skor: <b>${att.score} / ${att.total_questions} Benar</b> (${Math.round((att.score/att.total_questions)*100)}%). ${att.is_passed ? 'Memenuhi syarat kelulusan sertifikat.' : 'Silakan pelajari kembali materi dan kirim ulang jawaban.'}</div>
              `;
            }
          }

          let html = '';
          res.questions.forEach((q, idx) => {
            html += `
              <div class="quiz-question-box">
                <div class="quiz-q-title">${idx + 1}. ${q.question_text}</div>
                <div class="quiz-options-list">
                  <label class="quiz-opt-label" onclick="selectQuizOpt(this)">
                    <input type="radio" name="q_${q.id}" value="A" required>
                    <span><b>A.</b> ${q.option_a}</span>
                  </label>
                  <label class="quiz-opt-label" onclick="selectQuizOpt(this)">
                    <input type="radio" name="q_${q.id}" value="B">
                    <span><b>B.</b> ${q.option_b}</span>
                  </label>
                  <label class="quiz-opt-label" onclick="selectQuizOpt(this)">
                    <input type="radio" name="q_${q.id}" value="C">
                    <span><b>C.</b> ${q.option_c}</span>
                  </label>
                  <label class="quiz-opt-label" onclick="selectQuizOpt(this)">
                    <input type="radio" name="q_${q.id}" value="D">
                    <span><b>D.</b> ${q.option_d}</span>
                  </label>
                </div>
                <div id="q_explain_${q.id}" style="display:none; margin-top:12px; font-size:12px; background:#f0f9ff; border:1px solid #bae6fd; padding:10px 14px; border-radius:10px; color:#0369a1;"></div>
              </div>
            `;
          });
          container.innerHTML = html;
        }
      } catch (e) {
        container.innerHTML = '<div style="color:#ef4444; padding:20px;">Gagal memuat kuis.</div>';
      }
    }

    function selectQuizOpt(label) {
      const parent = label.parentElement;
      parent.querySelectorAll('.quiz-opt-label').forEach(l => l.classList.remove('selected'));
      label.classList.add('selected');
    }

    async function submitQuizAssessment(e) {
      e.preventDefault();
      const form = document.getElementById('quizForm');
      const btn = document.getElementById('btnSubmitQuiz');
      btn.disabled = true;
      btn.innerText = 'Menilai Jawaban...';

      const formData = new FormData(form);
      const answers = {};
      for (let [k, v] of formData.entries()) {
        if (k.startsWith('q_')) {
          answers[k.replace('q_', '')] = v;
        }
      }

      try {
        const postData = new FormData();
        postData.append('action', 'submit_quiz');
        postData.append('course_id', currentCourseId);
        postData.append('answers', JSON.stringify(answers));

        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php', { method:'POST', body:postData });
        const res = await resp.json();

        if (res.success) {
          const alertBox = document.getElementById('quizResultAlert');
          alertBox.style.display = 'block';
          alertBox.style.background = res.is_passed ? '#ecfdf5' : '#fef2f2';
          alertBox.style.border = res.is_passed ? '1px solid #a7f3d0' : '1px solid #fecaca';
          alertBox.style.color = res.is_passed ? '#065f46' : '#991b1b';
          alertBox.innerHTML = `
            <div style="font-weight:900; font-size:15px; margin-bottom:4px;">
              ${res.is_passed ? '🎉 SELAMAT! ANDA LULUS EVALUASI PEMAHAMAN' : '⚠️ HASIL: BELUM MEMENUHI PASSING GRADE (80%)'}
            </div>
            <div style="font-size:13px;">Skor Anda: <b>${res.score} dari ${res.total} Soal Benar</b> (${res.percentage}%). ${res.is_passed ? 'Kredensial kelulusan sertifikat telah tervalidasi.' : 'Silakan tinjau pembahasan di bawah ini dan ulangi kembali.'}</div>
          `;

          // Tampilkan Pembahasan per soal
          if (res.review) {
            for (let [qId, rev] of Object.entries(res.review)) {
              const expEl = document.getElementById('q_explain_' + qId);
              if (expEl) {
                expEl.style.display = 'block';
                expEl.innerHTML = `
                  <div style="font-weight:800; margin-bottom:2px; color:${rev.is_correct ? '#059669' : '#dc2626'};">
                    ${rev.is_correct ? '✓ Jawaban Anda Benar (Pilihan ' + rev.correct + ')' : '✗ Jawaban Salah. Kunci Benar: ' + rev.correct}
                  </div>
                  <div>${rev.explanation || ''}</div>
                `;
              }
            }
          }
          alertBox.scrollIntoView({ behavior:'smooth' });
        } else {
          alert(res.error || 'Gagal memproses penilaian kuis.');
        }
      } catch (err) {
        alert('Terjadi kesalahan jaringan.');
      } finally {
        btn.disabled = false;
        btn.innerText = 'Kirim & Nilai Jawaban Kuis \u2794';
      }
    }

    // Load Discussions
    async function loadDiscussionsData() {
      const container = document.getElementById('discussionsContainer');
      try {
        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php?action=get_discussions&lesson_id=' + currentLessonId + '&course_id=' + currentCourseId);
        const res = await resp.json();

        if (res.success && res.discussions) {
          if (res.discussions.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">Belum ada pertanyaan pada bab ini. Jadilah yang pertama bertanya!</div>';
            return;
          }

          let html = '';
          res.discussions.forEach(d => {
            let repliesHtml = '';
            if (d.replies && d.replies.length > 0) {
              d.replies.forEach(r => {
                repliesHtml += `
                  <div class="disc-reply-box">
                    <div class="disc-reply-sender">🛡️ ${r.sender_name} (${r.sender_role})</div>
                    <div class="disc-reply-text">${r.reply_text}</div>
                  </div>
                `;
              });
            }

            html += `
              <div class="discussion-card">
                <div class="disc-q-title">${d.question_title}</div>
                <div class="disc-meta">Ditanyakan oleh <b>${d.user_name}</b> &bull; ${d.created_at}</div>
                <div class="disc-body">${d.question_text}</div>
                ${repliesHtml}
              </div>
            `;
          });
          container.innerHTML = html;
        }
      } catch (e) {
        container.innerHTML = '<div style="color:#ef4444; padding:20px;">Gagal memuat diskusi.</div>';
      }
    }

    async function handlePostDiscussion(e) {
      e.preventDefault();
      const title = document.getElementById('discInputTitle').value.trim();
      const text = document.getElementById('discInputText').value.trim();

      try {
        const formData = new FormData();
        formData.append('action', 'post_discussion');
        formData.append('course_id', currentCourseId);
        formData.append('lesson_id', currentLessonId);
        formData.append('title', title);
        formData.append('text', text);

        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php', { method:'POST', body:formData });
        const res = await resp.json();

        if (res.success) {
          alert('Pertanyaan Anda berhasil dikirim ke instruktur.');
          document.getElementById('discInputTitle').value = '';
          document.getElementById('discInputText').value = '';
          loadDiscussionsData();
        } else {
          alert(res.error || 'Gagal mengirim pertanyaan.');
        }
      } catch (err) {
        alert('Terjadi kesalahan jaringan.');
      }
    }

    // Toggle Lesson Completion
    async function toggleLessonComplete(lessonId) {
      const btn = document.getElementById('btnCompleteToggle');
      const icon = document.getElementById('completeIcon');
      const text = document.getElementById('completeText');

      try {
        const formData = new FormData();
        formData.append('action', 'toggle_complete');
        formData.append('lesson_id', lessonId);
        formData.append('course_id', currentCourseId);

        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php', { method:'POST', body:formData });
        const res = await resp.json();

        if (res.success) {
          if (res.is_completed) {
            btn.className = 'btn-complete-toggle is-done';
            icon.innerText = '✓';
            text.innerText = 'Selesai Ditonton';
            const c = document.getElementById('circle-' + lessonId);
            if (c) { c.className = 'status-check-circle completed'; c.innerText = '✓'; }
          } else {
            btn.className = 'btn-complete-toggle not-done';
            icon.innerText = '○';
            text.innerText = 'Tandai Selesai';
            const c = document.getElementById('circle-' + lessonId);
            if (c) { c.className = 'status-check-circle incomplete'; c.innerText = ''; }
          }

          // Update Progress Bar
          const pBar = document.getElementById('progressFillBar');
          const pLabel = document.getElementById('progressTextLabel');
          if (pBar) pBar.style.width = res.percentage + '%';
          if (pLabel) pLabel.innerText = res.percentage + '% (' + res.completed_count + '/' + res.total_count + ' Selesai)';

          // Tampilkan Banner Sertifikat jika 100%
          const certBanner = document.getElementById('certBanner');
          if (certBanner) {
            certBanner.style.display = (res.percentage >= 100) ? 'block' : 'none';
          }
        } else {
          alert(res.error || 'Gagal memperbarui progres.');
        }
      } catch (e) {
        alert('Terjadi kesalahan jaringan.');
      }
    }

    async function lmsLogout() {
      if (!confirm('Apakah Anda ingin keluar dari ruang belajar?')) return;
      await fetch('<?= LMS_BASE_URL ?>/api.php?action=logout');
      window.location.href = '<?= LMS_BASE_URL ?>';
    }

    function toggleMobileCurriculum() {
      const sb = document.getElementById('classroomSidebar');
      const bd = document.getElementById('sidebarBackdrop');
      if (sb) sb.classList.toggle('mobile-open');
      if (bd) bd.classList.toggle('active');
    }

    function toggleTheaterMode() {
      const grid = document.getElementById('classroomLayoutGrid');
      if (!grid) return;
      grid.classList.toggle('theater-active');
      const isTheater = grid.classList.contains('theater-active');
      const icon = document.getElementById('theaterIcon');
      const text = document.getElementById('theaterText');
      if (icon) icon.innerText = isTheater ? '🗔' : '🖥️';
      if (text) text.innerText = isTheater ? 'Buka Sidebar' : 'Mode Fokus';
    }
  </script>

</body>
</html>
