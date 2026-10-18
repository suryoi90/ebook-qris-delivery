<?php
require_once __DIR__ . '/config.php';
$student = getLoggedInStudent();

if ($student) {
    header('Location: ' . LMS_BASE_URL . '/learn.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk Ruang Belajar | Restia Moegiono Academy</title>
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
      --primary-hover: #0096DE;
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
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 24px;
    }

    .login-container {
      width: 100%; max-width: 440px;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 28px;
      padding: 40px 36px;
      box-shadow: 0 20px 48px rgba(15, 34, 74, 0.08);
    }

    .brand-center {
      text-align: center; margin-bottom: 28px;
    }
    .brand-logo-img {
      height: 44px; width: auto; max-width: 180px; object-fit: contain; margin-bottom: 12px;
    }
    .login-title { font-size: 22px; font-weight: 900; color: var(--navy); margin-bottom: 6px; }
    .login-sub { font-size: 13px; color: var(--text-muted); line-height: 1.5; }

    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 12px; font-weight: 800; color: var(--navy); margin-bottom: 6px; }
    .form-input {
      width: 100%; padding: 12px 16px; border-radius: 12px;
      background: var(--soft-blue); border: 1px solid var(--border-color);
      color: var(--navy); font-size: 14px; outline: none; font-family: inherit; transition: all 0.2s;
    }
    .form-input:focus {
      background: #ffffff; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 174, 255, 0.18);
    }

    .btn-submit {
      width: 100%; padding: 14px 20px; border-radius: 12px;
      background: linear-gradient(135deg, #00AEFF 0%, #0F224A 100%);
      color: #fff; font-size: 14px; font-weight: 800; border: none;
      cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 16px rgba(0, 174, 255, 0.25);
    }
    .btn-submit:hover {
      background: linear-gradient(135deg, #0096DE 0%, #0B1B3D 100%); transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(0, 174, 255, 0.35);
    }

    .divider-or {
      display: flex; align-items: center; text-align: center; margin: 24px 0; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;
    }
    .divider-or::before, .divider-or::after {
      content: ''; flex: 1; border-bottom: 1px solid var(--border-color);
    }
    .divider-or:not(:empty)::before { margin-right: 12px; }
    .divider-or:not(:empty)::after { margin-left: 12px; }

    .btn-demo-mode {
      width: 100%; padding: 12px 20px; border-radius: 12px;
      background: #f0fdf4; border: 1px solid #bbf7d0;
      color: #166534; font-size: 13px; font-weight: 800; cursor: pointer; transition: all 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-demo-mode:hover {
      background: #dcfce7; border-color: #86efac; transform: translateY(-1px);
    }

    .alert-box {
      padding: 10px 14px; border-radius: 10px; font-size: 12px; margin-bottom: 16px; display: none;
    }
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .alert-success { background: var(--soft-blue); border: 1px solid var(--border-color); color: #0369a1; }

    .bottom-back-link {
      text-align: center; margin-top: 24px; font-size: 12px; color: var(--text-muted);
    }
    .bottom-back-link a { color: var(--primary); text-decoration: none; font-weight: 700; }
    .bottom-back-link a:hover { text-decoration: underline; }

    /* Mobile Auto-Sizing */
    @media (max-width: 480px) {
      body { padding: 16px 12px; }
      .login-container {
        padding: 28px 18px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(15, 34, 74, 0.06);
      }
      .brand-logo-img { height: 38px; margin-bottom: 8px; }
      .login-title { font-size: 20px; }
      .login-sub { font-size: 12.5px; }
      .form-input {
        font-size: 16px !important; /* Prevents iOS Safari auto-zoom */
        padding: 12px 14px;
      }
      .btn-submit { padding: 13px 18px; font-size: 14px; }
      .btn-demo-mode { padding: 12px 16px; font-size: 12.5px; }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="brand-center">
      <img src="/assets/uploads/2026/10/Logo-1254x1254_2e96a68b.png" alt="Restia Moegiono" class="brand-logo-img" onerror="this.style.display='none'">
      <h1 class="login-title">Akses Ruang Belajar</h1>
      <p class="login-sub">Masukkan email terdaftar saat pembelian di toko</p>
    </div>

    <div id="alertBox" class="alert-box"></div>

    <!-- STEP 1: INPUT EMAIL -->
    <form id="stepEmailForm" onsubmit="handleRequestOtp(event)">
      <div class="form-group">
        <label class="form-label" for="inputEmail">Alamat Email Siswa</label>
        <input type="email" id="inputEmail" class="form-input" placeholder="contoh: nama@perusahaan.com" required>
      </div>
      <button type="submit" id="btnRequestOtp" class="btn-submit">
        Kirim Kode Verifikasi OTP ➔
      </button>
    </form>

    <!-- STEP 2: INPUT OTP -->
    <form id="stepOtpForm" style="display:none;" onsubmit="handleVerifyOtp(event)">
      <div class="form-group">
        <label class="form-label" for="inputOtp">Masukkan 6 Digit Kode OTP</label>
        <input type="text" id="inputOtp" class="form-input" placeholder="6 digit angka" maxlength="6" style="letter-spacing:4px; font-size:18px; text-align:center; font-weight:800;" required>
      </div>
      <button type="submit" id="btnVerifyOtp" class="btn-submit">
        Verifikasi &amp; Masuk Kelas ➔
      </button>
      <div style="text-align:center; margin-top:12px;">
        <button type="button" onclick="resetToEmailStep()" style="background:none; border:none; color:#64748b; font-size:12px; cursor:pointer; font-weight:700;">
          &larr; Ubah Alamat Email
        </button>
      </div>
    </form>

    <div class="divider-or">Atau Uji Coba Langsung</div>

    <!-- ONE-CLICK DEMO ACCESS -->
    <button type="button" class="btn-demo-mode" onclick="handleDemoLogin()">
      <span>✨ Coba Masuk Mode Demo Siswa</span>
    </button>

    <div class="bottom-back-link" style="display:flex; justify-content:center; align-items:center; gap:10px; font-size:12px;">
      <a href="<?= LMS_BASE_URL ?>">&larr; Beranda Kursus</a>
      <span style="color:#cbd5e1;">•</span>
      <a href="<?= LMS_BASE_URL ?>/admin.php" style="color:#0284c7; font-weight:700;">🔐 Portal Admin &amp; Instruktur ➔</a>
    </div>
  </div>

  <script>
    let currentEmail = '';

    function showAlert(msg, isError = true) {
      const el = document.getElementById('alertBox');
      el.innerText = msg;
      el.className = 'alert-box ' + (isError ? 'alert-error' : 'alert-success');
      el.style.display = 'block';
    }

    async function handleRequestOtp(e) {
      e.preventDefault();
      const email = document.getElementById('inputEmail').value.trim();
      const btn = document.getElementById('btnRequestOtp');
      btn.disabled = true;
      btn.innerText = 'Mengirim kode OTP...';

      try {
        const formData = new FormData();
        formData.append('action', 'request_otp');
        formData.append('email', email);

        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php', { method:'POST', body:formData });
        const res = await resp.json();

        if (res.success) {
          currentEmail = email;
          showAlert(res.message, false);
          document.getElementById('stepEmailForm').style.display = 'none';
          document.getElementById('stepOtpForm').style.display = 'block';
          if (res.hint_otp) {
            document.getElementById('inputOtp').value = res.hint_otp;
          }
        } else {
          showAlert(res.error || 'Gagal mengirim OTP.', true);
        }
      } catch (err) {
        showAlert('Terjadi gangguan koneksi.', true);
      } finally {
        btn.disabled = false;
        btn.innerText = 'Kirim Kode Verifikasi OTP \u2794';
      }
    }

    async function handleVerifyOtp(e) {
      e.preventDefault();
      const otp = document.getElementById('inputOtp').value.trim();
      const btn = document.getElementById('btnVerifyOtp');
      btn.disabled = true;
      btn.innerText = 'Memverifikasi...';

      try {
        const formData = new FormData();
        formData.append('action', 'verify_otp');
        formData.append('email', currentEmail);
        formData.append('otp', otp);

        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php', { method:'POST', body:formData });
        const res = await resp.json();

        if (res.success) {
          showAlert(res.message, false);
          window.location.href = res.redirect || 'learn.php';
        } else {
          showAlert(res.error || 'Kode OTP tidak cocok.', true);
        }
      } catch (err) {
        showAlert('Terjadi kesalahan jaringan.', true);
      } finally {
        btn.disabled = false;
        btn.innerText = 'Verifikasi & Masuk Kelas \u2794';
      }
    }

    function resetToEmailStep() {
      document.getElementById('stepOtpForm').style.display = 'none';
      document.getElementById('stepEmailForm').style.display = 'block';
      document.getElementById('alertBox').style.display = 'none';
    }

    async function handleDemoLogin() {
      try {
        const resp = await fetch('<?= LMS_BASE_URL ?>/api.php?action=demo_login');
        const res = await resp.json();
        if (res.success) {
          window.location.href = res.redirect || 'learn.php';
        }
      } catch(e) {
        alert('Gagal memulai sesi demo.');
      }
    }
  </script>

</body>
</html>
