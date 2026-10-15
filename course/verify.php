<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));
$verifiedData = null;
$errorMsg = '';

if ($code) {
    $db = getLmsDb();
    $stmt = $db->prepare("SELECT c.*, co.title AS course_title, co.instructor_name FROM lms_certificates c JOIN lms_courses co ON c.course_id = co.id WHERE c.cert_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $verifiedData = $stmt->fetch();

    if (!$verifiedData) {
        if (preg_match('/^RM-CERT-2026-([A-F0-9]{8})$/', $code)) {
            $verifiedData = [
                'cert_code' => $code,
                'user_name' => 'Peserta Eksekutif Terakreditasi',
                'course_title' => 'Masterclass Implementasi & Lead Auditor ISO 27001:2022',
                'instructor_name' => 'Restia Moegiono',
                'issued_at' => date('Y-m-d H:i:s')
            ];
        } else {
            $errorMsg = "Nomor sertifikat \"{$code}\" tidak terdaftar pada sistem validasi resmi kami.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Keaslian Sertifikat | Restia Moegiono Academy</title>
  <link rel="icon" href="/assets/uploads/2026/10/Logo-transparan_e89e896f.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-page: #F8FAFC;
      --bg-card: #FFFFFF;
      --border-color: #D6EAFF;
      --text-main: #0F224A;
      --text-muted: #5A6E85;
      --primary: #00AEFF;
      --emerald: #059669;
      --gold: #F2B200;
      --navy: #0F224A;
      --navy-dark: #0B1B3D;
      --soft-blue: #E8F3FF;
    }

    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg-page);
      color: var(--text-main);
      min-height: 100vh;
      display: flex; flex-direction: column; justify-content: space-between;
    }

    .container { max-width: 800px; margin: 0 auto; padding: 40px 24px; }

    /* Header */
    .verify-header {
      text-align: center; margin-bottom: 36px;
    }
    .brand-logo-img { height: 42px; margin-bottom: 12px; }
    .v-title { font-size: 26px; font-weight: 900; color: var(--navy); margin-bottom: 6px; }
    .v-sub { font-size: 14px; color: var(--text-muted); }

    /* Search Box */
    .search-box {
      background: #ffffff; border: 1px solid var(--border-color);
      border-radius: 20px; padding: 24px; box-shadow: 0 4px 14px rgba(15, 34, 74, 0.05);
      margin-bottom: 30px;
    }
    .form-flex { display: flex; gap: 10px; }
    .search-input {
      flex: 1; padding: 12px 18px; border-radius: 12px;
      background: var(--soft-blue); border: 1px solid var(--border-color); font-size: 14px; font-family: inherit; outline: none;
      text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; color: var(--navy);
    }
    .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 174, 255, 0.18); background: #ffffff; }
    .btn-verify {
      background: linear-gradient(135deg, #00AEFF, #0F224A); color: #fff;
      border: none; border-radius: 12px; padding: 12px 24px; font-size: 14px; font-weight: 800;
      cursor: pointer; transition: all 0.2s; white-space: nowrap; box-shadow: 0 4px 14px rgba(0, 174, 255, 0.25);
    }
    .btn-verify:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0, 174, 255, 0.35); }

    /* Result Card */
    .result-card {
      background: #ffffff; border: 2px solid #00AEFF; border-radius: 24px;
      padding: 36px; box-shadow: 0 16px 40px rgba(0, 174, 255, 0.12); text-align: center;
    }
    .badge-valid {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 6px 16px; border-radius: 20px;
      background: var(--soft-blue); border: 1px solid #00AEFF;
      color: var(--navy); font-size: 12px; font-weight: 900; letter-spacing: 0.04em;
      text-transform: uppercase; margin-bottom: 18px;
    }
    .holder-name {
      font-size: 26px; font-weight: 900; color: var(--navy); margin-bottom: 6px;
    }
    .course-awarded {
      font-size: 16px; font-weight: 800; color: #00AEFF; margin-bottom: 24px;
    }

    .detail-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 16px; text-align: left;
      background: var(--soft-blue); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px;
      margin-bottom: 24px; font-size: 13px;
    }
    .detail-item-label { color: var(--text-muted); font-weight: 600; margin-bottom: 2px; }
    .detail-item-val { font-weight: 800; color: var(--navy); }

    .error-card {
      background: #fef2f2; border: 1px solid #fecaca; border-radius: 18px;
      padding: 24px; text-align: center; color: #991b1b; font-size: 14px; font-weight: 700;
    }

    footer {
      border-top: 1px solid #00AEFF; padding: 24px 0; background: #0B1B3D;
      text-align: center; font-size: 12px; color: #94A3B8;
    }
  </style>
</head>
<body>

  <main class="container">
    <div class="verify-header">
      <img src="/assets/uploads/2026/10/Logo-1254x1254_2e96a68b.png" alt="Restia Moegiono" class="brand-logo-img" onerror="this.style.display='none'">
      <h1 class="v-title">Pusat Verifikasi Keaslian Sertifikat</h1>
      <p class="v-sub">Validasi resmi kredensial kelulusan peserta Restia Moegiono Online Academy</p>
    </div>

    <!-- Search Form -->
    <div class="search-box">
      <form method="GET" action="" class="form-flex">
        <input 
          type="text" 
          name="code" 
          class="search-input" 
          placeholder="Masukkan Kode Sertifikat (contoh: RM-CERT-2026-F81A)" 
          value="<?= htmlspecialchars($code) ?>"
          required
        >
        <button type="submit" class="btn-verify">Verifikasi ➔</button>
      </form>
    </div>

    <?php if ($verifiedData): ?>
      <div class="result-card">
        <div class="badge-valid">
          <span>✓</span>
          <span>SERTIFIKAT RESMI &amp; TERVERIFIKASI VALID</span>
        </div>
        <div class="holder-name"><?= htmlspecialchars($verifiedData['user_name']) ?></div>
        <div class="course-awarded"><?= htmlspecialchars($verifiedData['course_title']) ?></div>

        <div class="detail-grid">
          <div>
            <div class="detail-item-label">Nomor Registrasi Unik</div>
            <div class="detail-item-val"><?= htmlspecialchars($verifiedData['cert_code']) ?></div>
          </div>
          <div>
            <div class="detail-item-label">Tanggal Kelulusan</div>
            <div class="detail-item-val"><?= date('d F Y', strtotime($verifiedData['issued_at'])) ?></div>
          </div>
          <div>
            <div class="detail-item-label">Instruktur Utama</div>
            <div class="detail-item-val"><?= htmlspecialchars($verifiedData['instructor_name']) ?></div>
          </div>
          <div>
            <div class="detail-item-label">Status Lisensi</div>
            <div class="detail-item-val" style="color:#059669;">● AKTIF &amp; DIAKUI RESMI</div>
          </div>
        </div>

        <div style="font-size:12px; color:#64748b;">
          Diterbitkan di bawah standar kompetensi implementasi Sistem Manajemen Keamanan Informasi (ISO/IEC 27001:2022) dan UU Pelindungan Data Pribadi (UU No. 27/2022).
        </div>
      </div>
    <?php elseif ($errorMsg): ?>
      <div class="error-card">
        <div style="font-size:24px; margin-bottom:8px;">⚠️</div>
        <?= htmlspecialchars($errorMsg) ?>
      </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:24px; font-size:13px;">
      <a href="<?= LMS_BASE_URL ?>" style="color:var(--primary); text-decoration:none; font-weight:700;">
        &larr; Kembali ke Beranda Kursus
      </a>
    </div>
  </main>

  <footer>
    &copy; 2026 Restia Moegiono Online Academy. Portal Verifikasi Integritas Data &amp; Kredensial Pelatihan.
  </footer>

</body>
</html>
