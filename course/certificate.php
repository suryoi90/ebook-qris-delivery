<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$student = getLoggedInStudent();
$db = getLmsDb();

$studentName = $student['name'] ?? 'Peserta Eksekutif';
$studentEmail = $student['email'] ?? 'demo@restiamoegiono.com';

// Ambil course berdasarkan parameter URL (slug atau ID), fallback ke kursus pertama
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

// Generate kode sertifikat deterministik
$hash = strtoupper(substr(md5($studentEmail . ($course['slug'] ?? 'course-masterclass')), 0, 8));
$certCode = "RM-CERT-2026-{$hash}";
$issueDate = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sertifikat Kelulusan - <?= htmlspecialchars($studentName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #0B1B3D;
      min-height: 100vh;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 30px 16px;
    }

    .action-bar {
      margin-bottom: 24px; display: flex; gap: 12px;
    }
    .btn-action {
      background: #0F224A; color: #fff; border: 1px solid rgba(0, 174, 255, 0.3);
      padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700;
      cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
      transition: all 0.2s;
    }
    .btn-action:hover { background: #1a366f; }
    .btn-action.primary { background: #00AEFF; border-color: #00AEFF; color: #0B1B3D; font-weight: 800; }
    .btn-action.primary:hover { background: #1CA2FF; color: #0B1B3D; }

    /* Certificate Box (Landscape A4-ish ratio) */
    .cert-frame {
      width: 100%; max-width: 960px; aspect-ratio: 1.414 / 1;
      background: #ffffff; color: #0F224A; border-radius: 12px;
      padding: 40px; position: relative; box-shadow: 0 24px 60px rgba(0,0,0,0.6);
      border: 8px solid #0F224A; outline: 3px solid #F2B200;
      display: flex; flex-direction: column; justify-content: space-between; text-align: center;
    }

    .cert-corner-decor {
      position: absolute; width: 40px; height: 40px; border: 3px solid #F2B200;
    }
    .c-tl { top: 12px; left: 12px; border-right: none; border-bottom: none; }
    .c-tr { top: 12px; right: 12px; border-left: none; border-bottom: none; }
    .c-bl { bottom: 12px; left: 12px; border-right: none; border-top: none; }
    .c-br { bottom: 12px; right: 12px; border-left: none; border-top: none; }

    .cert-header h4 {
      font-size: 13px; font-weight: 800; letter-spacing: 0.25em; text-transform: uppercase; color: #00AEFF; margin-bottom: 6px;
    }
    .cert-header h1 {
      font-family: 'Cinzel', serif; font-size: 32px; font-weight: 900; letter-spacing: 0.05em; color: #0F224A; margin-bottom: 8px;
    }
    .cert-sub {
      font-size: 13px; color: #5A6E85; font-weight: 600;
    }

    .cert-body { margin: 20px 0; }
    .cert-award-to { font-size: 13px; color: #5A6E85; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.1em; }
    .cert-recipient-name {
      font-size: 32px; font-weight: 900; color: #0F224A; border-bottom: 2px solid #0F224A;
      display: inline-block; padding: 0 24px 6px; margin-bottom: 16px; letter-spacing: -0.01em;
    }
    .cert-desc {
      font-size: 14px; color: #0F224A; line-height: 1.7; max-width: 680px; margin: 0 auto;
    }

    .cert-footer {
      display: flex; align-items: flex-end; justify-content: space-between; padding: 0 30px;
    }
    .sig-block { text-align: center; }
    .sig-line {
      width: 200px; height: 1px; background: #0F224A; margin-bottom: 6px;
    }
    .sig-name { font-size: 14px; font-weight: 900; color: #0F224A; }
    .sig-role { font-size: 11px; color: #5A6E85; font-weight: 600; }

    .cert-seal {
      width: 76px; height: 76px; border-radius: 50%;
      background: radial-gradient(circle, #F3AC10, #F2B200);
      border: 3px solid #0F224A; box-shadow: 0 4px 12px rgba(15, 34, 74, 0.2);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      color: #0F224A; font-weight: 900; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em;
    }

    .cert-code-box {
      text-align: right; font-size: 11px; color: #5A6E85; font-family: ui-monospace, monospace;
    }

    @media print {
      body { background: #fff; padding: 0; }
      .action-bar { display: none; }
      .cert-frame { box-shadow: none; max-width: 100%; border-width: 6px; }
    }
  </style>
</head>
<body>

  <div class="action-bar">
    <a href="<?= LMS_BASE_URL ?>/learn.php?course=<?= urlencode($course['slug']) ?>" class="btn-action">
      &larr; Kembali ke Ruang Belajar
    </a>
    <button type="button" class="btn-action primary" onclick="window.print()">
      🖨️ Cetak / Simpan PDF
    </button>
  </div>

  <div class="cert-frame">
    <div class="cert-corner-decor c-tl"></div>
    <div class="cert-corner-decor c-tr"></div>
    <div class="cert-corner-decor c-bl"></div>
    <div class="cert-corner-decor c-br"></div>

    <div class="cert-header">
      <h4>RESTIA MOEGIONO ONLINE ACADEMY</h4>
      <h1>CERTIFICATE OF ACHIEVEMENT</h1>
      <p class="cert-sub">Sertifikat Kelulusan Pelatihan Manajemen Keamanan Informasi &amp; Kepatuhan Regulasi</p>
    </div>

    <div class="cert-body">
      <div class="cert-award-to">Diberikan secara resmi kepada:</div>
      <div class="cert-recipient-name"><?= htmlspecialchars($studentName) ?></div>
      <p class="cert-desc">
        Atas dedikasi dan keberhasilan menyelesaikan seluruh silabus kurikulum eksekutif:<br>
        <b style="color:#00AEFF; font-size:16px;"><?= htmlspecialchars($course['title']) ?></b><br>
        Mencakup klausul standar implementasi, pengujian kontrol keamanan, tata kelola dokumen audit, dan harmonisasi kepatuhan regulasi data.
      </p>
    </div>

    <div class="cert-footer">
      <div class="cert-code-box" style="text-align:left;">
        <div>Tanggal Terbit: <b><?= $issueDate ?></b></div>
        <div>Verifikasi ID: <a href="<?= LMS_BASE_URL ?>/verify.php?code=<?= urlencode($certCode) ?>" target="_blank" style="color:#00AEFF; text-decoration:underline; font-weight:800;"><?= $certCode ?></a></div>
        <div style="font-size:9px; color:#5A6E85; margin-top:2px;">Klik untuk verifikasi publik resmi</div>
      </div>

      <div class="cert-seal">
        <span>★ VERIFIED ★</span>
        <span style="font-size:8px;">ACADEMY</span>
        <span>2026</span>
      </div>

      <div class="sig-block">
        <div style="font-family:'Cinzel', serif; font-size:18px; font-weight:900; color:#0F224A; margin-bottom:4px;">
          <?= htmlspecialchars($course['instructor_name']) ?>
        </div>
        <div class="sig-line"></div>
        <div class="sig-name"><?= htmlspecialchars($course['instructor_name']) ?></div>
        <div class="sig-role"><?= htmlspecialchars($course['instructor_title']) ?></div>
      </div>
    </div>
  </div>

</body>
</html>
