<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../services/MailService.php';
session_start();

$errorMsg = '';
$successMsg = '';

// 1. Endpoint AJAX: Kirim Kode OTP ke Email
if (isset($_GET['action']) && $_GET['action'] === 'request_otp') {
    header('Content-Type: application/json');
    try {
        $lastSent = $_SESSION['store_admin_otp_time'] ?? 0;
        if (time() - $lastSent < 30) {
            $wait = 30 - (time() - $lastSent);
            throw new Exception("Mohon tunggu {$wait} detik sebelum meminta kode OTP baru.");
        }

        $otp = (string)random_int(100000, 999999);
        $_SESSION['store_admin_otp'] = $otp;
        $_SESSION['store_admin_otp_time'] = time();
        $_SESSION['store_admin_otp_expires'] = time() + 600; // 10 menit

        MailService::sendAdminOtp(ADMIN_EMAIL, $otp);

        echo json_encode([
            'success' => true,
            'message' => 'Kode OTP 6-digit berhasil dikirim ke email terdaftar. Silakan periksa inbox atau spam email Anda.'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 2. Submit Verifikasi OTP
if (isset($_POST['login_otp_submit'])) {
    $enteredOtp = trim($_POST['otp_code'] ?? '');
    $savedOtp = $_SESSION['store_admin_otp'] ?? '';
    $otpExpiry = $_SESSION['store_admin_otp_expires'] ?? 0;

    if (empty($enteredOtp)) {
        $errorMsg = 'Silakan masukkan 6 digit kode OTP.';
    } elseif (empty($savedOtp) || time() > $otpExpiry) {
        $errorMsg = 'Kode OTP telah kadaluarsa atau belum diminta. Silakan minta kode baru.';
    } elseif ($enteredOtp !== $savedOtp) {
        $errorMsg = 'Kode OTP tidak sesuai. Pastikan 6 digit yang Anda masukkan benar.';
    } else {
        unset($_SESSION['store_admin_otp']);
        unset($_SESSION['store_admin_otp_expires']);
        $_SESSION['store_admin_auth'] = ADMIN_PASSWORD;
        header('Location: /admin/');
        exit;
    }
}

// 3. Submit Kata Sandi
if (isset($_POST['login_submit'])) {
    $pwd = trim($_POST['password'] ?? '');
    if ($pwd === ADMIN_PASSWORD) {
        $_SESSION['store_admin_auth'] = ADMIN_PASSWORD;
    } else {
        $errorMsg = 'Kata sandi salah. Silakan coba kembali.';
    }
}

// 4. Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['store_admin_auth']);
    unset($_SESSION['store_admin_otp']);
    unset($_SESSION['store_admin_otp_expires']);
    session_destroy();
    header('Location: /admin/');
    exit;
}

$isLoggedIn = ($_SESSION['store_admin_auth'] ?? '') === ADMIN_PASSWORD;
$hasActiveOtp = !empty($_SESSION['store_admin_otp']) && time() < ($_SESSION['store_admin_otp_expires'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Command Vault &amp; Admin Briefcase — Restia Moegiono</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Quill Rich Text Theme CSS -->
  <link rel="stylesheet" href="/assets/vendor/quill/quill.snow.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css">
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
      background-color: var(--color-bg-page); color: var(--color-text-main); line-height: 1.5;
    }
    .container { max-width: 1140px; margin: 0 auto; padding: 0 20px; }

    /* Login Page */
    .login-wrapper {
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .login-card {
      background: #ffffff; border: 1px solid var(--color-blue-border); border-radius: 28px;
      padding: 40px 36px; max-width: 440px; width: 100%;
      box-shadow: 0 20px 50px rgba(15, 34, 74, 0.08); text-align: center;
    }
    .vault-avatar {
      width: 90px; height: 90px; border-radius: 24px; overflow: hidden;
      margin: 0 auto 16px; border: 2px solid var(--color-blue-border); box-shadow: 0 8px 24px rgba(0, 174, 255, 0.25);
    }
    .vault-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .login-tag {
      display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px;
      border-radius: 20px; background: var(--color-blue-soft); border: 1px solid var(--color-blue-border);
      color: var(--color-navy); font-size: 11px; font-weight: 800; margin-bottom: 12px;
    }
    .login-title { font-size: 22px; font-weight: 900; margin-bottom: 6px; color: var(--color-navy); }
    .login-sub { font-size: 13px; color: #64748b; margin-bottom: 24px; }
    .login-input {
      width: 100%; padding: 14px 18px; border-radius: 12px;
      border: 1px solid var(--color-blue-border); font-size: 14px; margin-bottom: 14px;
      outline: none; font-family: inherit;
    }
    .login-input:focus { border-color: var(--color-cyan); box-shadow: 0 0 0 3px rgba(0, 174, 255, 0.2); }
    .login-btn {
      width: 100%; padding: 14px; border-radius: 12px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      color: #ffffff; font-weight: 800; font-size: 14px; border: none; cursor: pointer;
      box-shadow: 0 8px 20px rgba(0, 174, 255, 0.35); transition: transform 0.15s ease;
    }
    .login-btn:hover { transform: translateY(-1px); }
    .login-mode-tabs {
      display: flex; gap: 6px; background: var(--color-blue-soft); padding: 4px; border-radius: 14px; margin-bottom: 20px;
    }
    .login-tab {
      flex: 1; padding: 10px 12px; border: none; border-radius: 10px; font-size: 12px; font-weight: 700;
      color: #64748b; background: transparent; cursor: pointer; transition: all 0.2s ease;
    }
    .login-tab.active {
      background: #ffffff; color: var(--color-navy); box-shadow: 0 2px 8px rgba(0, 174, 255, 0.15);
    }

    /* Dashboard Header */
    .dash-header {
      background: #ffffff; border-bottom: 1px solid var(--color-blue-border); padding: 18px 0;
      box-shadow: 0 2px 10px rgba(15, 34, 74, 0.03); margin-bottom: 32px;
    }
    .dash-header-inner { display: flex; justify-content: space-between; align-items: center; }
    .dash-brand { display: flex; align-items: center; gap: 12px; }
    .btn-logout {
      padding: 8px 16px; border-radius: 10px; border: 1px solid #fecdd3;
      background: #fff1f2; color: #e11d48; font-size: 12px; font-weight: 700; cursor: pointer;
      text-decoration: none;
    }

    /* Compact KPI Strip */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 14px;
    }
    .kpi-card {
      background: #ffffff;
      border: 1px solid var(--color-blue-border);
      border-radius: 14px;
      padding: 12px 16px;
      box-shadow: 0 2px 6px rgba(15, 34, 74, 0.03);
    }
    .kpi-label {
      font-size: 10px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 3px;
    }
    .kpi-val {
      font-size: 18px;
      font-weight: 900;
      color: var(--color-cyan);
      line-height: 1.2;
    }
    .kpi-sub {
      font-size: 10px;
      font-weight: 600;
      color: #10b981;
      margin-top: 2px;
    }
    @media (max-width: 900px) {
      .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 500px) {
      .kpi-grid { grid-template-columns: 1fr; }
    }

    /* Compact & Simple Navigation Chips (Ringkas & Tanpa Slider) */
    .nav-chips-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 18px;
    }
    .nav-chip {
      padding: 8px 14px;
      border-radius: 10px;
      border: 1px solid var(--color-blue-border);
      background: #ffffff;
      color: #475569;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      transition: all 0.15s ease;
      user-select: none;
      box-shadow: 0 1px 3px rgba(15, 34, 74, 0.02);
    }
    .nav-chip:hover {
      background: var(--color-blue-soft);
      border-color: var(--color-cyan);
      color: var(--color-navy);
    }
    .nav-chip.active {
      background: var(--color-blue-soft);
      color: var(--color-navy);
      border-color: var(--color-cyan);
      box-shadow: 0 2px 8px rgba(0, 174, 255, 0.2);
    }
    .nav-chip-icon {
      font-size: 14px;
    }

    /* Fallback compatibility */
    .cue-cards-grid, .cue-card, .tab-bar, .tab-btn { display: none; }

    /* Tables & Card Styles (Tanpa Slider Horizontal) */
    .table-card {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px;
      overflow: hidden; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04); margin-bottom: 24px;
    }
    table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; font-size: 13px; table-layout: auto; }
    th {
      background: #f8fafc; padding: 12px 16px; font-weight: 800; color: #475569;
      border-bottom: 1px solid #e2e8f0; font-size: 11px; text-transform: uppercase;
      letter-spacing: 0.04em; white-space: nowrap;
    }
    td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
    .order-row-item { transition: background 0.15s ease; }
    .order-row-item:hover { background: #f8fafc; }

    /* Orders Table Header & Toolbar */
    .orders-table-header {
      padding: 14px 18px; border-bottom: 1px solid #e2e8f0;
      display: flex; flex-direction: column; gap: 12px;
    }
    .orders-title-wrap {
      display: flex; flex-direction: column; gap: 4px;
    }
    .orders-title {
      font-size: 17px; font-weight: 900; color: #0f172a; letter-spacing: -0.01em;
    }
    .orders-subtitle {
      font-size: 12px; color: #64748b;
    }
    .orders-counter-badge {
      display: inline-flex; align-items: center; font-size: 11px; font-weight: 800;
      padding: 2px 8px; border-radius: 9999px; background: #e0f2fe; color: #0369a1;
      border: 1px solid #bae6fd;
    }
    .orders-toolbar {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 12px;
    }
    .orders-search-box {
      display: flex; align-items: center; position: relative;
      flex: 1; min-width: 240px; max-width: 380px;
    }
    .orders-search-icon {
      position: absolute; left: 14px; font-size: 13px; color: #94a3b8; pointer-events: none;
    }
    .orders-search-input {
      width: 100%; padding: 9px 36px 9px 36px; border-radius: 10px;
      border: 1px solid #cbd5e1; font-size: 12px; background: #f8fafc;
      outline: none; font-family: inherit; transition: all 0.2s ease;
    }
    .orders-search-input:focus {
      background: #ffffff; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .clear-search-btn {
      position: absolute; right: 10px; width: 20px; height: 20px; border-radius: 50%;
      border: none; background: #cbd5e1; color: #ffffff; font-size: 12px; font-weight: 700;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .clear-search-btn:hover { background: #94a3b8; }

    .orders-filter-pills {
      display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 10px; gap: 3px;
    }
    .filter-pill {
      border: none; background: transparent; padding: 6px 14px; border-radius: 8px;
      font-size: 11px; font-weight: 700; color: #64748b; cursor: pointer; transition: all 0.15s ease;
    }
    .filter-pill:hover { color: #0f172a; }
    .filter-pill.active {
      background: #ffffff; color: #0284c7; font-weight: 800;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .orders-actions-group {
      display: flex; align-items: center; gap: 8px; margin-left: auto;
    }
    .btn-export-csv {
      padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 800;
      border: 1px solid #a7f3d0; background: #ecfdf5; color: #047857; cursor: pointer;
      display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;
    }
    .btn-export-csv:hover { background: #d1fae5; border-color: #6ee7b7; transform: translateY(-1px); }
    .btn-refresh-data {
      padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 800;
      border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;
      display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;
    }
    .btn-refresh-data:hover { background: #f8fafc; border-color: #94a3b8; }

    /* Orders Table Cells */
    .inv-wrap { display: flex; flex-direction: column; gap: 3px; }
    .invoice-pill-btn {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 12px; font-weight: 700; background: #f1f5f9; color: #0f172a;
      border: 1px solid #cbd5e1; border-radius: 8px; padding: 4px 8px;
      display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
      white-space: nowrap !important; transition: all 0.15s ease;
      width: fit-content;
    }
    .invoice-pill-btn:hover {
      background: #e0f2fe; border-color: #bae6fd; color: #0369a1;
    }
    .copy-hint-icon { font-size: 10px; opacity: 0.6; }
    .inv-date-sub {
      font-size: 11px; color: #64748b; font-weight: 500; white-space: nowrap;
    }

    .customer-avatar-flex {
      display: flex; align-items: center; gap: 10px;
    }
    .customer-avatar-circle {
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, #0284c7, #6366f1);
      color: #ffffff; font-size: 11px; font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
    }
    .customer-name-text {
      font-size: 13px; font-weight: 700; color: #0f172a; white-space: nowrap;
    }
    .customer-info-col {
      display: flex;
      flex-direction: column;
      gap: 1px;
      min-width: 0;
    }
    .customer-email-link {
      color: #64748b;
      font-size: 11px;
      text-decoration: none;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .customer-email-link:hover {
      color: #0284c7;
      text-decoration: underline;
    }
    .coupon-sub-text {
      font-size: 10px;
      font-weight: 700;
      color: #166534;
      margin-top: 2px;
    }
    .status-stack {
      display: flex;
      flex-direction: column;
      gap: 3px;
      align-items: flex-start;
    }
    .delivery-sub-status {
      font-size: 10px;
      font-weight: 700;
    }
    .delivery-sub-status.sent {
      color: #059669;
    }
    .delivery-sub-status.unsent {
      color: #94a3b8;
    }

    @keyframes pulseDot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.85); }
    }
    .pulse-indicator-dot {
      width: 7px; height: 7px; border-radius: 50%; display: inline-block;
      animation: pulseDot 2s infinite ease-in-out;
    }
    .pulse-indicator-dot.green { background: #10b981; }
    .pulse-indicator-dot.amber { background: #f59e0b; }

    .status-pill-modern {
      display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px;
      border-radius: 9999px; font-size: 11px; font-weight: 800; letter-spacing: 0.03em;
      white-space: nowrap;
    }
    .status-pill-modern.paid {
      background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
    }
    .status-pill-modern.pending {
      background: #fffbeb; color: #b45309; border: 1px solid #fde68a;
    }
    .status-pill-modern.test-paid {
      background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff;
    }

    .pulse-indicator-dot.purple { background: #9333ea; }

    .tag-type-badge {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 10px; font-weight: 800; padding: 2px 7px;
      border-radius: 6px; letter-spacing: 0.02em; white-space: nowrap;
    }
    .tag-type-badge.tag-test {
      background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe;
    }
    .tag-type-badge.tag-live {
      background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
    }

    .delivery-pill-modern {
      display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px;
      border-radius: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.03em;
      white-space: nowrap;
    }
    .delivery-pill-modern.sent {
      background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
    }
    .delivery-check { font-size: 11px; font-weight: 900; }
    .delivery-pill-modern.unsent {
      background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;
    }

    .btn-fulfill-pill {
      display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;
      border-radius: 9999px; font-size: 11px; font-weight: 800;
      background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1;
      cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 1px 2px rgba(0,0,0,0.04); white-space: nowrap;
    }
    .btn-fulfill-pill:hover {
      background: #0284c7; color: #ffffff; border-color: #0284c7;
      transform: translateY(-1px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    }
    .btn-fulfill-icon { font-size: 12px; }

    /* Toast Notification */
    .toast-notice {
      position: fixed; bottom: 24px; right: 24px; background: #0f172a;
      color: #ffffff; padding: 12px 20px; border-radius: 12px;
      font-size: 13px; font-weight: 700; box-shadow: 0 10px 30px rgba(0,0,0,0.25);
      z-index: 99999; display: flex; align-items: center; gap: 8px;
      transition: all 0.3s cubic-bezier(0.4,0,0.2,1); opacity: 0;
      pointer-events: none; transform: translateY(10px);
    }
    .toast-notice.show {
      opacity: 1; pointer-events: auto; transform: translateY(0);
    }

    .status-badge {
      display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;
    }
    .status-paid { background: #ecfdf5; color: #047857; }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-inactive { background: #fee2e2; color: #b91c1c; }

    /* Grids & Cards */
    .briefcase-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
    .briefcase-item {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px;
      padding: 22px; box-shadow: 0 4px 14px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;
    }
    .item-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; gap: 12px; }
    .item-title { font-weight: 800; font-size: 15px; color: #0f172a; line-height: 1.4; }
    .item-badge { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; }
    .badge-ready { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

    .btn-action-sm {
      padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 800;
      border: 1px solid #cbd5e1; background: #f8fafc; cursor: pointer; transition: all 0.15s;
      text-decoration: none; color: inherit; display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    }
    .btn-action-sm:hover { background: #e2e8f0; }

    /* Product Grid */
    .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; }
    .product-card-admin {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px;
      padding: 22px; box-shadow: 0 4px 14px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;
    }
    .product-desc-preview {
      font-size: 12px; color: #64748b; line-height: 1.5; margin: 12px 0;
      max-height: 80px; overflow: hidden; position: relative;
    }
    .product-desc-preview::after {
      content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 30px;
      background: linear-gradient(transparent, #ffffff);
    }

    /* Modal Backdrop & Box */
    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
      z-index: 1000; display: none; align-items: center; justify-content: center; padding: 20px;
    }
    .modal-box {
      background: #ffffff; border-radius: 24px; max-width: 880px; width: 100%;
      max-height: 90vh; display: flex; flex-direction: column;
      box-shadow: 0 25px 60px rgba(0,0,0,0.25); position: relative; overflow: hidden;
    }
    .modal-header {
      padding: 20px 24px; border-bottom: 1px solid #e2e8f0;
      display: flex; justify-content: space-between; align-items: center; background: #ffffff;
    }
    .modal-body {
      padding: 24px; overflow-y: auto; flex: 1;
    }
    .modal-footer {
      padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc;
      display: flex; justify-content: flex-end; gap: 12px; align-items: center;
    }
    .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    @media (max-width: 768px) {
      .grid-2col, .grid-3col { grid-template-columns: 1fr; }
    }

    /* Rich Text Quill Styling */
    .editor-toolbar-custom {
      display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
      margin-bottom: 8px; padding: 6px 12px; background: #f1f5f9; border-radius: 10px; border: 1px solid #e2e8f0;
    }
    .btn-template {
      padding: 5px 10px; font-size: 11px; font-weight: 700; border-radius: 6px;
      border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;
      display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;
    }
    .btn-template:hover { background: #e0f2fe; color: #0284c7; border-color: #7dd3fc; }

    .ql-toolbar.ql-snow {
      border-radius: 12px 12px 0 0 !important; border: 1px solid #cbd5e1 !important;
      background: #f8fafc !important; font-family: inherit !important;
    }
    .ql-container.ql-snow {
      border-radius: 0 0 12px 12px !important; border: 1px solid #cbd5e1 !important;
      border-top: none !important; font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
      font-size: 14px !important; line-height: 1.6 !important; min-height: 220px !important; max-height: 340px !important;
      overflow-y: auto !important;
    }
    .ql-editor { min-height: 200px; }
    .raw-html-textarea {
      width: 100%; min-height: 220px; max-height: 340px; padding: 14px;
      border: 1px solid #cbd5e1; border-radius: 12px; font-family: 'Courier New', monospace;
      font-size: 13px; color: #0f172a; line-height: 1.5; outline: none; display: none;
    }

    /* Simulators */
    .sim-card {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px;
      padding: 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.02); margin-bottom: 32px;
    }
    #quillHeroDescWrapper .ql-container.ql-snow {
      min-height: 120px !important; max-height: 220px !important;
    }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 12px; font-weight: 800; color: #475569; margin-bottom: 6px; }
    .form-control {
      width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #cbd5e1;
      font-size: 13px; font-family: inherit; outline: none;
    }
    .btn-primary-form {
      padding: 12px 24px; border-radius: 10px;
      background: linear-gradient(135deg, var(--color-cyan), var(--color-navy));
      color: #ffffff; font-size: 13px; font-weight: 800; border: none; cursor: pointer;
      box-shadow: 0 4px 14px rgba(0, 174, 255, 0.35); transition: all 0.2s;
    }
    .btn-primary-form:hover {
      background: linear-gradient(135deg, var(--color-cyan-electric), var(--color-navy-dark));
      transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0, 174, 255, 0.5);
    }
  </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
  <!-- VIEW LOGIN -->
  <div class="login-wrapper">
    <div class="login-card">
      <div class="vault-avatar">
        <img src="/images/author-restia-240.webp" alt="Restia Moegiono">
      </div>
      <div class="login-tag">🔐 COMMAND VAULT</div>
      <h1 class="login-title">Admin Briefcase</h1>
      <p class="login-sub">Kelola produk e-book, watermark DRM, dan status order store.restiamoegiono.com</p>

      <!-- TAB PILIHAN LOGIN: KATA SANDI ATAU OTP EMAIL -->
      <div class="login-mode-tabs">
        <button type="button" class="login-tab <?= $hasActiveOtp ? '' : 'active' ?>" id="tabBtnPassword" onclick="switchLoginMode('password')">
          🔑 Kata Sandi
        </button>
        <button type="button" class="login-tab <?= $hasActiveOtp ? 'active' : '' ?>" id="tabBtnOtp" onclick="switchLoginMode('otp')">
          📧 Kode OTP Email
        </button>
      </div>

      <?php if ($errorMsg): ?>
        <div style="background:#fee2e2; border:1px solid #fecdd3; color:#b91c1c; padding:10px; border-radius:10px; font-size:12px; margin-bottom:14px;">
          <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>

      <?php if ($successMsg): ?>
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:10px; border-radius:10px; font-size:12px; margin-bottom:14px;">
          <?= htmlspecialchars($successMsg) ?>
        </div>
      <?php endif; ?>

      <!-- PANEL 1: LOGIN KATA SANDI -->
      <div id="loginPanelPassword" style="display: <?= $hasActiveOtp ? 'none' : 'block' ?>;">
        <form method="POST">
          <div style="text-align:left; margin-bottom:6px;">
            <label style="font-size:12px; font-weight:700; color:#475569;">Kata Sandi Vault Admin</label>
          </div>
          <input type="password" name="password" required placeholder="Masukkan Sandi Vault Admin..." class="login-input">
          <button type="submit" name="login_submit" class="login-btn">Masuk dengan Sandi ➔</button>
        </form>
        <div style="margin-top:14px; font-size:12px; color:#64748b;">
          Atau tidak ingat sandi? <a href="javascript:void(0)" onclick="switchLoginMode('otp')" style="color:#0284c7; font-weight:700; text-decoration:underline;">Masuk pakai Kode OTP Email</a>
        </div>
      </div>

      <!-- PANEL 2: LOGIN KODE OTP EMAIL -->
      <div id="loginPanelOtp" style="display: <?= $hasActiveOtp ? 'block' : 'none' ?>;">
        <!-- Bagian A: Permintaan OTP Baru -->
        <div id="otpRequestBox" style="display: <?= $hasActiveOtp ? 'none' : 'block' ?>; margin-top:10px;">
          <button type="button" id="btnRequestOtp" onclick="requestAdminOtp()" class="login-btn" style="background:linear-gradient(135deg, #059669, #0d9488);">
            ✉️ Kirim Kode OTP ke Email ➔
          </button>
        </div>

        <!-- Bagian B: Input & Verifikasi OTP -->
        <div id="otpVerifyBox" style="display: <?= $hasActiveOtp ? 'block' : 'none' ?>;">
          <form method="POST">
            <div style="text-align:left; margin-bottom:6px;">
              <label style="font-size:12px; font-weight:700; color:#475569;">Masukkan 6 Digit Kode OTP</label>
            </div>
            <input type="text" name="otp_code" id="inputOtpCode" required maxlength="6" pattern="[0-9]{6}" placeholder="______" class="login-input" style="font-size:24px; font-weight:900; letter-spacing:8px; text-align:center; font-family:monospace;">
            <button type="submit" name="login_otp_submit" class="login-btn">Verifikasi &amp; Masuk ➔</button>
          </form>
          <div style="margin-top:14px; font-size:12px; color:#64748b;">
            Belum menerima email? <button type="button" id="btnResendOtp" onclick="requestAdminOtp()" style="background:none; border:none; color:#0284c7; font-weight:700; cursor:pointer; padding:0; text-decoration:underline;">Kirim Ulang OTP</button> <span id="resendCountdown" style="color:#94a3b8;"></span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    let resendTimer = null;
    let resendSeconds = 0;

    function switchLoginMode(mode) {
      const pTab = document.getElementById('tabBtnPassword');
      const oTab = document.getElementById('tabBtnOtp');
      const pPanel = document.getElementById('loginPanelPassword');
      const oPanel = document.getElementById('loginPanelOtp');

      if (mode === 'password') {
        pTab.classList.add('active');
        oTab.classList.remove('active');
        pPanel.style.display = 'block';
        oPanel.style.display = 'none';
      } else {
        oTab.classList.add('active');
        pTab.classList.remove('active');
        pPanel.style.display = 'none';
        oPanel.style.display = 'block';
      }
    }

    async function requestAdminOtp() {
      const reqBtn = document.getElementById('btnRequestOtp');
      const resendBtn = document.getElementById('btnResendOtp');
      if (reqBtn) { reqBtn.disabled = true; reqBtn.innerText = 'Mengirim kode OTP...'; }
      if (resendBtn) { resendBtn.disabled = true; }

      try {
        const res = await fetch('/admin/?action=request_otp');
        const data = await res.json();
        if (data.success) {
          alert('✓ ' + data.message);
          document.getElementById('otpRequestBox').style.display = 'none';
          document.getElementById('otpVerifyBox').style.display = 'block';
          const inputEl = document.getElementById('inputOtpCode');
          if (inputEl) inputEl.focus();
          startResendCooldown(45);
        } else {
          alert('Gagal: ' + (data.error || 'Terjadi kendala pengiriman.'));
          if (reqBtn) { reqBtn.disabled = false; reqBtn.innerText = '✉️ Kirim Kode OTP ke Email ➔'; }
        }
      } catch (e) {
        alert('Kesalahan jaringan saat meminta OTP.');
        if (reqBtn) { reqBtn.disabled = false; reqBtn.innerText = '✉️ Kirim Kode OTP ke Email ➔'; }
      }
    }

    function startResendCooldown(seconds) {
      resendSeconds = seconds;
      const resendBtn = document.getElementById('btnResendOtp');
      const countdownSpan = document.getElementById('resendCountdown');
      if (resendBtn) resendBtn.disabled = true;

      clearInterval(resendTimer);
      resendTimer = setInterval(() => {
        resendSeconds--;
        if (countdownSpan) countdownSpan.innerText = `(${resendSeconds}s)`;
        if (resendSeconds <= 0) {
          clearInterval(resendTimer);
          if (countdownSpan) countdownSpan.innerText = '';
          if (resendBtn) resendBtn.disabled = false;
        }
      }, 1000);
    }
  </script>
<?php else: ?>
  <!-- VIEW DASHBOARD ADMIN -->
  <header class="dash-header">
    <div class="container dash-header-inner">
      <div class="dash-brand">
        <div style="width:36px; height:36px; border-radius:10px; background:#0284c7; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:900;">RM</div>
        <div>
          <div style="font-weight:800; font-size:15px; color:#0f172a;">Command Vault &bull; Restia Moegiono</div>
          <div style="font-size:11px; color:#64748b;">Panel Pengendali Toko, Konten Produk &amp; Enkripsi DRM</div>
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <a href="/" target="_blank" class="btn-action-sm">🌐 Lihat Toko</a>
        <a href="?logout=1" class="btn-logout">Keluar</a>
      </div>
    </div>
  </header>

  <main class="container">
    <!-- KPI CARDS -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">Total Omset Penjualan</div>
        <div class="kpi-val" id="kpiOmset">Rp 0</div>
        <div class="kpi-sub" id="kpiPaid">0 Pesanan Terverifikasi</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">E-Book Terkirim via Email</div>
        <div class="kpi-val" id="kpiTerkirim">0</div>
        <div class="kpi-sub">✓ Watermark &amp; SMTP Berhasil</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Total Transaksi Masuk</div>
        <div class="kpi-val" id="kpiTotal">0</div>
        <div class="kpi-sub">Basis Data MySQL Live</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Voucher Promo Aktif</div>
        <div class="kpi-val" id="kpiCoupons">0</div>
        <div class="kpi-sub">🏷️ Sistem Kupon Live</div>
      </div>
    </div>

    <!-- NAVIGATION CHIPS (KECIL & SIMPLE TANPA SLIDER) -->
    <div class="nav-chips-bar">
      <button type="button" class="nav-chip active" data-tab="orders" onclick="switchTab('orders', this)">
        <span class="nav-chip-icon">📋</span>
        <span>Riwayat Pesanan</span>
      </button>
      <button type="button" class="nav-chip" data-tab="products" onclick="switchTab('products', this)">
        <span class="nav-chip-icon">📚</span>
        <span>Kelola E-Book</span>
      </button>
      <button type="button" class="nav-chip" data-tab="course" onclick="switchTab('course', this)" style="background:#eff6ff; color:#0284c7; border-color:#bfdbfe; font-weight:800;">
        <span class="nav-chip-icon">📖</span>
        <span>Materi Kursus LMS</span>
      </button>
      <button type="button" class="nav-chip" data-tab="lms" onclick="switchTab('lms', this)">
        <span class="nav-chip-icon">🎓</span>
        <span>Siswa &amp; Kuis LMS</span>
      </button>
      <button type="button" class="nav-chip" data-tab="coupons" onclick="switchTab('coupons', this)">
        <span class="nav-chip-icon">🏷️</span>
        <span>Kupon Promo</span>
      </button>
      <button type="button" class="nav-chip" data-tab="briefcase" onclick="switchTab('briefcase', this)">
        <span class="nav-chip-icon">💼</span>
        <span>Briefcase Master</span>
      </button>
      <button type="button" class="nav-chip" data-tab="drm" onclick="switchTab('drm', this)">
        <span class="nav-chip-icon">⚡</span>
        <span>Simulator DRM</span>
      </button>
      <button type="button" class="nav-chip" data-tab="email" onclick="switchTab('email', this)">
        <span class="nav-chip-icon">✉️</span>
        <span>Uji Coba Email</span>
      </button>
      <button type="button" class="nav-chip" data-tab="doku" onclick="switchTab('doku', this)">
        <span class="nav-chip-icon">⚙️</span>
        <span>Status DOKU</span>
      </button>
      <button type="button" class="nav-chip" data-tab="hero" onclick="switchTab('hero', this)">
        <span class="nav-chip-icon">🖼️</span>
        <span>Hero Toko</span>
      </button>
    </div>

    <!-- TAB 1: ORDERS -->
    <div id="tabOrders" class="table-card">
      <div class="orders-table-header">
        <div class="orders-title-wrap">
          <div style="display:flex; align-items:center; gap:8px;">
            <h3 class="orders-title">Daftar Transaksi Pembeli</h3>
            <span id="ordersCounterBadge" class="orders-counter-badge">Memuat...</span>
          </div>
          <p class="orders-subtitle">Riwayat pembelian real-time, status pembayaran DOKU, dan log stempel watermark DRM</p>
        </div>

        <div class="orders-toolbar">
          <div class="orders-search-box">
            <span class="orders-search-icon">🔍</span>
            <input type="text" id="orderSearchInput" class="orders-search-input" placeholder="Cari invoice, nama, atau email pembeli..." oninput="filterOrdersTable(this.value)">
            <button type="button" id="clearSearchBtn" class="clear-search-btn" onclick="clearOrdersSearch()" style="display:none;">&times;</button>
          </div>
          <div class="orders-filter-pills">
            <button type="button" class="filter-pill active" onclick="setOrdersFilter('all', this)">Semua</button>
            <button type="button" class="filter-pill" onclick="setOrdersFilter('live', this)">💳 DOKU Live</button>
            <button type="button" class="filter-pill" onclick="setOrdersFilter('test', this)">🧪 Uji Coba</button>
            <button type="button" class="filter-pill" onclick="setOrdersFilter('paid', this)">Terbayar</button>
            <button type="button" class="filter-pill" onclick="setOrdersFilter('pending', this)">Menunggu</button>
          </div>
          <div class="orders-actions-group">
            <button class="btn-export-csv" onclick="exportOrdersCsv()" title="Unduh data dalam format CSV Microsoft Excel">
              <span>📥</span> Ekspor CSV
            </button>
            <button class="btn-refresh-data" onclick="loadAdminData()" title="Segarkan data terbaru dari server">
              <span id="refreshIcon">🔄</span> Muat Ulang
            </button>
          </div>
        </div>
      </div>

      <div>
        <table>
          <thead>
            <tr>
              <th style="width:18%;">Invoice &amp; Waktu</th>
              <th style="width:26%;">Pembeli &amp; Kontak</th>
              <th style="width:27%;">Produk E-Book</th>
              <th style="width:11%;">Total</th>
              <th style="width:10%;">Status</th>
              <th style="width:8%; text-align:right;">Aksi</th>
            </tr>
          </thead>
          <tbody id="ordersTbody">
            <tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">Memuat data transaksi...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: PRODUCTS -->
    <div id="tabProducts" style="display:none; margin-bottom:32px;">
      <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:20px 24px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
          <h2 style="font-size:18px; font-weight:900; color:#0f172a;">Katalog &amp; Manajemen Konten Produk E-Book</h2>
          <p style="font-size:13px; color:#64748b;">Sunting judul, harga, harga coret, gambar sampul, berkas PDF master, dan deskripsi produk.</p>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn-primary-form" onclick="openProductEditor(null)" style="padding:8px 16px; font-size:12px; font-weight:800; background:#059669; display:inline-flex; align-items:center; gap:6px;">
            <span>➕</span> Tambah Produk E-Book Baru
          </button>
          <button class="btn-action-sm" onclick="loadAdminData()">🔄 Muat Ulang Data</button>
        </div>
      </div>

      <!-- BANNER JALUR CEPAT KE MATERI KURSUS LMS -->
      <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:14px; padding:14px 20px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px;">
          <span style="font-size:22px;">🎓</span>
          <div>
            <b style="color:#1e40af; font-size:13px;">Mencari Materi &amp; Silabus Kursus LMS Masterclass?</b>
            <div style="color:#3b82f6; font-size:12px; margin-top:2px;">Silakan buka tab khusus materi kursus untuk menyunting modul, video pembelajaran, dan catatan kuliah.</div>
          </div>
        </div>
        <button type="button" class="btn-primary-form" onclick="switchTab('course')" style="padding:8px 16px; font-size:12px; font-weight:800; background:#0284c7;">
          Buka Materi Kursus LMS &rarr;
        </button>
      </div>

      <div class="product-grid" id="productsAdminContainer">
        <!-- Rendered by JS -->
      </div>
    </div>

    <!-- TAB DEDIKASI: MATERI KURSUS LMS -->
    <div id="tabCourse" style="display:none; margin-bottom:32px;">
      <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:22px 28px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
            <span style="background:#0284c7; color:#fff; font-size:11px; font-weight:900; padding:2px 8px; border-radius:6px;">LMS MASTERCLASS</span>
            <h2 style="font-size:20px; font-weight:900; color:#0f172a; margin:0;">Manajemen Kurikulum &amp; Materi Pelajaran LMS</h2>
          </div>
          <p style="font-size:13px; color:#64748b; margin:0;">
            Kelola 4 Modul, 12 Bab Materi, ID Video YouTube, dan Catatan Kuliah interaktif.
          </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <a href="https://restiamoegiono.com/course/learn.php" target="_blank" class="btn-action-sm" style="background:#f0f9ff; color:#0284c7; border-color:#bae6fd; font-weight:800;">
            🌐 Lihat Ruang Belajar Siswa ↗
          </a>
          <button type="button" class="btn-primary-form" onclick="openLmsLessonModal(0)">
            ➕ Tambah Bab / Materi Baru
          </button>
          <button type="button" class="btn-action-sm" onclick="loadAdminData()">
            🔄 Muat Ulang Data
          </button>
        </div>
      </div>

      <!-- KARTU OVERVIEW PROGRAM KELAS -->
      <div style="background:linear-gradient(135deg, #0f172a, #1e293b); color:#ffffff; border-radius:18px; padding:24px 28px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
          <span style="font-size:11px; font-weight:800; color:#38bdf8; background:rgba(56, 189, 248, 0.15); padding:3px 10px; border-radius:20px; border:1px solid rgba(56, 189, 248, 0.3);">
            PROGRAM UNGGULAN 2026
          </span>
          <h3 style="font-size:18px; font-weight:900; color:#ffffff; margin:8px 0 4px;">Masterclass Implementasi &amp; Lead Auditor ISO 27001:2022</h3>
          <p style="font-size:12px; color:#94a3b8; margin:0; max-width:680px; line-height:1.5;">
            Panduan Praktis Desain ISMS, Gap Analysis, Risk Treatment, &amp; Harmonisasi Kepatuhan UU PDP No. 27/2022 karya Restia Moegiono.
          </p>
        </div>
        <div style="display:flex; gap:20px; font-size:12px; border-left:1px solid rgba(255,255,255,0.1); padding-left:20px;">
          <div>
            <div style="color:#94a3b8;">Total Materi</div>
            <div style="font-size:16px; font-weight:900; color:#38bdf8;" id="courseOverviewLessons">12 Bab</div>
          </div>
          <div>
            <div style="color:#94a3b8;">Total Modul</div>
            <div style="font-size:16px; font-weight:900; color:#ffffff;">4 Modul</div>
          </div>
          <div>
            <div style="color:#94a3b8;">Harga Masuk</div>
            <div style="font-size:16px; font-weight:900; color:#34d399;">Rp 499.000</div>
          </div>
        </div>
      </div>

      <!-- CONTAINER DAFTAR MODUL & BAB MATERI DENGAN WYSIWYG -->
      <div id="lmsLessonsContainerDedicated" style="display:flex; flex-direction:column; gap:20px;">
        <div style="text-align:center; padding:40px; color:#64748b; background:#ffffff; border-radius:16px; border:1px solid #e2e8f0;">
          Memuat daftar bab kurikulum...
        </div>
      </div>
    </div>

    <!-- TAB 3: COUPONS (VOUCHERS) -->
    <div id="tabCoupons" class="table-card" style="display:none;">
      <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
          <h3 style="font-size:15px; font-weight:800;">Manajemen Kupon &amp; Kode Promo Diskon</h3>
          <p style="font-size:12px; color:#64748b;">Buat voucher diskon musiman, webinar, atau promo khusus pembeli.</p>
        </div>
        <button class="btn-primary-form" style="padding:8px 16px; font-size:12px;" onclick="openCouponModal()">
          + Buat Kupon Baru
        </button>
      </div>

      <!-- PANDUAN PITA BANNER -->
      <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px 16px; margin:16px 20px 8px; font-size:13px; color:#166534; display:flex; align-items:center; gap:10px;">
        <span style="font-size:20px;">💡</span>
        <div>
          <b>Kontrol Banner Promo Toko:</b> Kupon yang berstatus <b>Aktif</b> akan secara otomatis ditayangkan pada pita banner promosi halaman depan toko (storefront). Jika kupon dinonaktifkan atau dihapus, pita banner promo di halaman depan akan otomatis <b>hilang</b>.
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>Kode Promo</th>
              <th>Target Diskon</th>
              <th>Jenis Potongan</th>
              <th>Nilai Diskon</th>
              <th>Min. Pembelian</th>
              <th>Penggunaan / Kuota</th>
              <th>Kadaluarsa</th>
              <th>Status</th>
              <th style="text-align:right;">Aksi</th>
            </tr>
          </thead>
          <tbody id="couponsTbody">
            <tr><td colspan="9" style="text-align:center; padding:30px; color:#64748b;">Memuat data kupon...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 4: BRIEFCASE -->
    <div id="tabBriefcase" style="display:none; margin-bottom:32px;">
      <div class="briefcase-grid" id="briefcaseContainer">
        <!-- Rendered by JS -->
      </div>
    </div>

    <!-- TAB 5: DRM SIMULATOR -->
    <div id="tabDrm" class="sim-card" style="display:none;">
      <h3 style="font-size:17px; font-weight:800; margin-bottom:6px;">Simulator Stempel Watermark Lisensi DRM</h3>
      <p style="font-size:13px; color:#64748b; margin-bottom:20px;">Uji coba langsung pembuatan watermark PDF sesuai format resmi: <i>“Nama_Ebook” ini karya Restia Moegiono, salinan ini dibuat untuk “Nama_Lengkap” | “Alamat_email” | “Nama_organisasi”</i></p>

      <form onsubmit="handleTestDrm(event)">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label">Pilih Buku E-Book Master</label>
            <select id="simProduct" class="form-control">
              <option value="11-kontrol-v2">[Versi 2.0] 11 Kontrol Baru Annex A ISO 27001</option>
              <option value="kepatuhan-pdp">Panduan Praktis Kepatuhan UU PDP</option>
              <option value="ropa-dpia">RoPA dan DPIA Dalam Implementasi UU PDP</option>
              <option value="lia-tia">LIA dan TIA Dalam Implementasi UU PDP</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Nama Lengkap Pembeli</label>
            <input type="text" id="simName" value="Surya Virtu" required class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Alamat Email Pembeli</label>
            <input type="email" id="simEmail" value="suryavirtu@gmail.com" required class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Nama Instansi / Organisasi</label>
            <input type="text" id="simOrg" value="PT Solusi Data Indonesia" class="form-control">
          </div>
        </div>
        <button type="submit" id="btnRunDrm" class="btn-primary-form">⚡ Jalankan Stempel DRM &amp; Dapatkan Unduhan PDF</button>
      </form>

      <div id="simResultBox" style="display:none; margin-top:20px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:16px;">
        <div style="font-weight:800; color:#166534; font-size:14px; margin-bottom:6px;">✓ Watermark Berhasil Dihasilkan!</div>
        <div id="simResultMsg" style="font-size:13px; color:#15803d; margin-bottom:12px;"></div>
        <a id="simDownloadBtn" href="#" target="_blank" class="btn-action-sm" style="background:#166534; color:#fff; border:none; padding:8px 16px; font-size:12px;">📥 Unduh PDF Ber-Watermark</a>
      </div>
    </div>

    <!-- TAB 6: TEST EMAIL -->
    <div id="tabEmail" class="sim-card" style="display:none;">
      <h3 style="font-size:17px; font-weight:800; margin-bottom:6px;">Uji Coba Pengiriman Email Live Berlampiran E-Book</h3>
      <p style="font-size:13px; color:#64748b; margin-bottom:20px;">Kirimkan email nyata dari <code>order@restiamoegiono.com</code> dengan lampiran PDF ber-watermark untuk memastikan pengiriman SMTP cPanel berjalan tanpa kendala.</p>

      <form onsubmit="handleTestEmail(event)">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div class="form-group">
            <label class="form-label">Alamat Email Tujuan</label>
            <input type="email" id="testEmailTarget" value="suryavirtu@gmail.com" required class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">E-Book yang Akan Dilampirkan</label>
            <select id="testEmailProduct" class="form-control">
              <option value="11-kontrol-v2">[Versi 2.0] 11 Kontrol Baru Annex A ISO 27001</option>
              <option value="ropa-dpia">RoPA dan DPIA Dalam Implementasi UU PDP</option>
              <option value="lia-tia">LIA dan TIA Dalam Implementasi UU PDP</option>
            </select>
          </div>
        </div>
        <button type="submit" id="btnSendTestEmail" class="btn-primary-form">✉️ Kirimkan E-Book Uji Coba Sekarang</button>
      </form>
    </div>

    <!-- TAB 7: DOKU SETTINGS -->
    <div id="tabDoku" class="sim-card" style="display:none;">
      <h3 style="font-size:17px; font-weight:800; margin-bottom:16px;">Konfigurasi DOKU Payment Gateway</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; font-size:13px;">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
          <div style="color:#64748b; font-weight:700;">DOKU Client ID:</div>
          <div style="font-weight:900; color:#0f172a; margin-top:4px;">BRN-0237-1789470449049</div>
        </div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
          <div style="color:#64748b; font-weight:700;">Status Environment:</div>
          <div style="font-weight:900; color:#059669; margin-top:4px;">LIVE PRODUCTION (api.doku.com)</div>
        </div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
          <div style="color:#64748b; font-weight:700;">Webhook Notification URL:</div>
          <div style="font-weight:900; color:#0284c7; margin-top:4px;">https://store.restiamoegiono.com/api/doku-notify.php</div>
        </div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
          <div style="color:#64748b; font-weight:700;">Payment Result Return URL:</div>
          <div style="font-weight:900; color:#0284c7; margin-top:4px;">https://store.restiamoegiono.com/payment/result</div>
        </div>
      </div>
    </div>

    <!-- TAB 8: GAMBAR UTAMA & TAMPILAN HERO -->
    <div id="tabHero" class="sim-card" style="display:none;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
          <h3 style="font-size:18px; font-weight:800; color:#0f172a; margin-bottom:4px;">🖼️ Gambar Utama &amp; Tampilan Hero Toko</h3>
          <p style="font-size:13px; color:#64748b; margin:0;">
            Kelola gambar mockup 3D dan teks tajuk utama yang tampil di muka etalase <a href="/" target="_blank" style="color:#0284c7; font-weight:700; text-decoration:underline;">store.restiamoegiono.com</a>
          </p>
        </div>
        <a href="/" target="_blank" class="btn-action-sm" style="background:#f0fdf4; color:#166534; border-color:#bbf7d0;">
          🌐 Buka &amp; Cek Toko Langsung ➔
        </a>
      </div>

      <form id="heroSettingsForm" onsubmit="handleSaveHero(event)">
        <div style="display:grid; grid-template-columns:320px 1fr; gap:28px; margin-bottom:24px;">
          
          <!-- KOLOM KIRI: PREVIEW GAMBAR & TOMBOL UPLOAD -->
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:18px; padding:20px; text-align:center;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
              <span style="font-size:12px; font-weight:800; color:#334155;">Pratinjau Mockup</span>
              <span id="heroLiveBadge" class="status-badge status-paid" style="font-size:11px;">✓ Tayang di Toko</span>
            </div>

            <!-- Card Preview Gambar -->
            <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:16px; padding:12px; margin-bottom:14px; box-shadow:0 6px 18px rgba(0,0,0,0.04);">
              <img id="heroPreviewImg" src="/images/hero-book-420.webp" alt="Hero Mockup Preview" style="width:100%; max-height:280px; object-fit:contain; border-radius:10px; display:block;">
            </div>

            <div style="font-size:11px; color:#64748b; margin-bottom:12px; word-break:break-all;">
              Path berkas aktif: <b id="currentHeroPath" style="color:#0f172a;">/images/hero-book-420.webp</b>
            </div>

            <!-- Input File & Tombol Unggah -->
            <div>
              <label class="btn-action-sm" style="display:flex; width:100%; justify-content:center; cursor:pointer; background:#ffffff; border:1px dashed #0284c7; color:#0284c7; padding:10px 14px; font-weight:700; border-radius:10px;">
                <span>📁 Unggah / Ganti Mockup Baru</span>
                <input type="file" id="uploadHeroImage" name="hero_image_file" accept="image/webp,image/jpeg,image/png" style="display:none;" onchange="handleHeroFileSelected(this)">
              </label>
              <div id="newHeroSelectedInfo" style="display:none; font-size:11px; color:#059669; font-weight:700; margin-top:8px;"></div>
              <button type="button" id="btnCancelHeroReplace" onclick="cancelHeroReplace()" class="btn-action-sm" style="display:none; margin-top:8px; width:100%; font-size:11px; padding:6px 10px; color:#b91c1c; background:#fff1f2; border-color:#fecdd3;">
                ✕ Batalkan Penggantian Gambar
              </button>
            </div>
            <input type="hidden" id="inputHeroImage" name="hero_image" value="/images/hero-book-420.webp">
            <div style="font-size:10px; color:#94a3b8; margin-top:8px;">
              Format didukung: WebP, JPG, PNG (maks. 5MB)
            </div>
          </div>

          <!-- KOLOM KANAN: FORM TEKS & KONTEN HERO DENGAN WYSIWYG LENGKAP -->
          <div>
            <!-- FIELD 1: LABEL / TAG HERO -->
            <div class="form-group" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; margin-bottom:16px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <label class="form-label" style="margin:0; font-weight:800; color:#0f172a;">1. Label / Lencana Hero (Atas Judul)</label>
                <span style="font-size:11px; color:#64748b;">Pratinjau Kapsul Biru</span>
              </div>
              
              <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
                <input type="text" id="heroTagInput" name="hero_tag" class="form-control" style="flex:1;" placeholder="Misal: 🛡️ LITERASI RESMI & KEPATUHAN ENTERPRISE" value="🛡️ LITERASI RESMI &amp; KEPATUHAN ENTERPRISE" oninput="updateHeroTagPreview()">
                <div style="padding:6px 14px; border-radius:20px; background:#e0f2fe; border:1px solid #bae6fd; color:#0369a1; font-size:12px; font-weight:800; white-space:nowrap;" id="heroTagPreviewBadge">
                  🛡️ LITERASI RESMI &amp; KEPATUHAN ENTERPRISE
                </div>
              </div>

              <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                <span style="font-size:11px; font-weight:700; color:#64748b;">Preset Cepat:</span>
                <button type="button" class="btn-template" onclick="setHeroTagPreset('🛡️ LITERASI RESMI & KEPATUHAN ENTERPRISE')">🛡️ Literasi Resmi</button>
                <button type="button" class="btn-template" onclick="setHeroTagPreset('📘 PUSTAKA KEAMANAN SIBER 2026')">📘 Pustaka 2026</button>
                <button type="button" class="btn-template" onclick="setHeroTagPreset('⭐ DOKUMEN AUDIT & KEPATUHAN RESMI')">⭐ Dokumen Audit</button>
              </div>
            </div>

            <!-- FIELD 2: JUDUL UTAMA DENGAN VISUAL BUILDER (WYSIWYG) -->
            <div class="form-group" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; margin-bottom:16px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <div>
                  <label class="form-label" style="margin:0; font-weight:800; color:#0f172a;">2. Judul Utama Toko (Heading H1 Visual)</label>
                  <div style="font-size:11px; color:#64748b; margin-top:2px;">Ketik kata biasa &amp; kata aksen tanpa perlu menulis tag kode HTML manual.</div>
                </div>
                <button type="button" id="btnToggleTitleMode" onclick="toggleTitleMode()" class="btn-action-sm" style="font-size:11px; padding:4px 10px;">
                  &lt;&sol;&gt; Mode Kode HTML
                </button>
              </div>

              <!-- VISUAL BUILDER TITLE (DEFAULT) -->
              <div id="titleVisualBuilder">
                <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:12px; margin-bottom:12px;">
                  <div>
                    <label style="font-size:11px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Teks Judul Utama (Warna Normal):</label>
                    <input type="text" id="titlePartMain" class="form-control" placeholder="Pustaka Keamanan Siber &" oninput="updateVisualTitlePreview()">
                  </div>
                  <div>
                    <label style="font-size:11px; font-weight:700; color:#0284c7; display:block; margin-bottom:4px;">Teks Aksen Sorotan (Gradien Biru):</label>
                    <div style="display:flex; gap:6px;">
                      <input type="text" id="titlePartGradient" class="form-control" style="border-color:#93c5fd; font-weight:700; color:#0284c7;" placeholder="Privasi Data Digital" oninput="updateVisualTitlePreview()">
                      <button type="button" class="btn-template" onclick="clearTitleGradient()" title="Hapus gradien" style="padding:0 8px;">✕</button>
                    </div>
                  </div>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center; margin-bottom:12px;">
                  <span style="font-size:11px; font-weight:700; color:#64748b;">Preset Sorotan:</span>
                  <button type="button" class="btn-template" onclick="setTitlePreset('Pustaka Keamanan Siber &', 'Privasi Data Digital')">✨ Privasi Data Digital</button>
                  <button type="button" class="btn-template" onclick="setTitlePreset('Pustaka Lengkap', 'ISO 27001 & UU PDP')">✨ ISO 27001 &amp; UU PDP</button>
                  <button type="button" class="btn-template" onclick="setTitlePreset('Koleksi Panduan & Instrumen', 'Keamanan Informasi')">✨ Keamanan Informasi</button>
                </div>

                <!-- LIVE HEADING PREVIEW -->
                <div style="background:#f8fafc; border:1px dashed #93c5fd; border-radius:12px; padding:14px 18px;">
                  <div style="font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase;">Pratinjau Hasil Judul di Toko:</div>
                  <div id="titleLivePreviewBox" style="font-size:22px; font-weight:900; color:#0f172a; line-height:1.25;">
                    Pustaka Keamanan Siber &amp; <span style="background:linear-gradient(135deg, #0284c7, #2563eb); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Privasi Data Digital</span>
                  </div>
                </div>
              </div>

              <!-- RAW TITLE (TOGGLED) -->
              <div id="titleRawBox" style="display:none;">
                <input type="text" id="heroTitleInput" name="hero_title" class="form-control" placeholder="Misal: Pustaka Keamanan Siber & <span>Privasi Data Digital</span>" oninput="syncFromRawTitle()">
                <small style="font-size:11px; color:#64748b;">Mode kode langsung. Bungkus teks dengan <code>&lt;span&gt;...&lt;/span&gt;</code> untuk memberi warna gradien.</small>
              </div>
            </div>

            <!-- FIELD 3: DESKRIPSI PENGANTAR HERO DENGAN QUILL WYSIWYG -->
            <div class="form-group" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; margin-bottom:16px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div>
                  <label class="form-label" style="margin:0; font-weight:800; color:#0f172a;">3. Deskripsi / Paragraf Pengantar Hero (Editor Visual)</label>
                  <div style="font-size:11px; color:#64748b; margin-top:2px;">Format teks tebal, miring, atau link secara visual seperti mengetik di Word.</div>
                </div>
                <button type="button" id="btnToggleHeroDescHtml" onclick="toggleHeroDescHtml()" class="btn-action-sm" style="font-size:11px; padding:4px 10px;">
                  &lt;&sol;&gt; Mode HTML Source
                </button>
              </div>

              <!-- TOOLBAR SHORTCUT TEBALKAN KATA KUNCI -->
              <div class="editor-toolbar-custom">
                <span style="font-size:11px; font-weight:800; color:#64748b; margin-right:4px;">1-Klik Tebalkan Kata Kunci:</span>
                <button type="button" class="btn-template" onclick="formatHeroKeyword('ISO 27001:2022')"><b>B</b> ISO 27001:2022</button>
                <button type="button" class="btn-template" onclick="formatHeroKeyword('UU PDP No. 27/2022')"><b>B</b> UU PDP No. 27/2022</button>
                <button type="button" class="btn-template" onclick="formatHeroKeyword('Restia Moegiono')"><b>B</b> Restia Moegiono</button>
                <button type="button" class="btn-template" onclick="formatHeroKeyword('DOKU (QRIS & Virtual Account)')"><b>B</b> DOKU (QRIS)</button>
                <button type="button" class="btn-template" onclick="clearHeroDescFormat()" style="color:#b91c1c;">🧹 Bersihkan Format</button>
              </div>

              <!-- QUILL WRAPPER -->
              <div id="quillHeroDescWrapper">
                <div id="quillHeroDescEditor"></div>
              </div>

              <!-- RAW TEXTAREA FOR HTML TOGGLE & FALLBACK SUBMIT -->
              <textarea id="rawHeroDescTextarea" name="hero_desc" class="raw-html-textarea" style="min-height:140px; display:none;"></textarea>

              <div style="font-size:11px; color:#64748b; margin-top:6px; display:flex; justify-content:space-between;">
                <span>Sorot kata lalu klik tombol <b>B</b> di toolbar untuk menebalkan kata tersebut.</span>
                <span id="heroDescStats">0 karakter</span>
              </div>
            </div>

            <!-- CARD INFO LIVE -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
              <span style="font-size:22px;">✨</span>
              <div style="font-size:12px; color:#166534; line-height:1.5;">
                Semua pemformatan sekarang <b>100% Visual (WYSIWYG)</b>. Anda tidak perlu lagi mengetik kode atau tag HTML seperti <code>&lt;b&gt;</code> atau <code>&lt;span&gt;</code> secara manual!
              </div>
            </div>

            <!-- TOMBOL SIMPAN -->
            <div style="display:flex; justify-content:flex-end; gap:12px;">
              <button type="submit" id="btnSaveHeroSettings" class="btn-primary-form" style="padding:12px 28px; font-size:14px;">
                💾 Simpan &amp; Terapkan Perubahan Hero
              </button>
            </div>
          </div>

        </div>
      </form>
    </div>

    <!-- TAB 9: LMS AKADEMI -->
    <div id="tabLms" class="table-card" style="display:none;">
      <div class="orders-table-header">
        <div class="orders-title-wrap">
          <div style="display:flex; align-items:center; gap:8px;">
            <h3 class="orders-title">Manajemen Siswa Akademi &amp; Masterclass</h3>
            <span id="lmsCounterBadge" class="orders-counter-badge">Memuat...</span>
          </div>
          <p class="orders-subtitle">Daftar peserta terdaftar, pemantauan progres belajar, status kuis, dan pendaftaran B2B manual</p>
        </div>

        <div class="orders-toolbar">
          <div style="display:flex; align-items:center; gap:12px;">
            <div style="font-size:12px; font-weight:700; color:#475569;">
              Portal Siswa: <a href="https://restiamoegiono.com/course/" target="_blank" style="color:#0284c7; text-decoration:none; font-weight:800;">restiamoegiono.com/course/ ↗</a>
            </div>
          </div>
          <div class="orders-actions-group">
            <button class="btn-export-csv" onclick="openEnrollManualModal()" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">
              <span>➕</span> Daftarkan Siswa Manual (B2B)
            </button>
            <button class="btn-refresh-data" onclick="loadAdminData()">
              <span>🔄</span> Segarkan
            </button>
          </div>
        </div>
      </div>

      <!-- SUB-TABS LMS -->
      <div style="display:flex; gap:8px; padding: 0 24px 14px; border-bottom: 1px solid #e2e8f0; flex-wrap:wrap;">
        <button type="button" class="tab-btn active" id="btnSubLmsStudents" onclick="switchLmsSubTab('students', this)">👥 Daftar Siswa &amp; Progres (<span id="lmsCountStudents">0</span>)</button>
        <button type="button" class="tab-btn" id="btnSubLmsLessons" onclick="switchLmsSubTab('lessons', this)">📖 Kurikulum &amp; Materi Bab (<span id="lmsCountLessons">0</span>)</button>
        <button type="button" class="tab-btn" id="btnSubLmsDiscussions" onclick="switchLmsSubTab('discussions', this)">💬 Tanya Jawab Siswa (<span id="lmsCountDiscussions">0</span>)</button>
        <button type="button" class="tab-btn" id="btnSubLmsQuizzes" onclick="switchLmsSubTab('quizzes', this)">📝 Riwayat Kuis (<span id="lmsCountQuizzes">0</span>)</button>
      </div>

      <!-- SUB-TAB 1: SISWA & PROGRES -->
      <div id="lmsSubStudents">
        <table>
          <thead>
            <tr>
              <th style="width:28%;">Siswa &amp; Kontak</th>
              <th style="width:28%;">Program Kursus</th>
              <th style="width:24%;">Progres Belajar</th>
              <th style="width:20%; text-align:right;">Status &amp; Sertifikat</th>
            </tr>
          </thead>
          <tbody id="lmsTbody">
            <tr><td colspan="4" style="text-align:center; padding:30px; color:#64748b;">Memuat data siswa...</td></tr>
          </tbody>
        </table>
      </div>

      <!-- SUB-TAB: KURIKULUM & MATERI BAB (WYSIWYG) -->
      <div id="lmsSubLessons" style="display:none; padding: 20px 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
          <div>
            <h4 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">Kurikulum &amp; Materi Pelajaran Masterclass</h4>
            <p style="font-size:12px; color:#64748b; margin:4px 0 0;">Kelola modul, video materi, dan catatan kuliah interaktif dengan Quill WYSIWYG Editor.</p>
          </div>
          <button type="button" class="btn-primary-form" onclick="openLmsLessonModal(0)" style="padding:9px 16px; font-size:13px; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
            <span>➕</span> Tambah Bab / Materi Baru
          </button>
        </div>
        <div id="lmsLessonsContainer" style="display:flex; flex-direction:column; gap:20px;">
          <div style="text-align:center; padding:30px; color:#64748b;">Memuat kurikulum dan materi bab...</div>
        </div>
      </div>

      <!-- SUB-TAB 2: FORUM DISKUSI -->
      <div id="lmsSubDiscussions" style="display:none; padding: 20px 24px;">
        <div id="lmsDiscussionsContainer" style="display:flex; flex-direction:column; gap:14px;">
          <div style="text-align:center; padding:30px; color:#64748b;">Memuat diskusi siswa...</div>
        </div>
      </div>

      <!-- SUB-TAB 3: RIWAYAT KUIS -->
      <div id="lmsSubQuizzes" style="display:none;">
        <table>
          <thead>
            <tr>
              <th style="width:25%;">Email Peserta</th>
              <th style="width:25%;">Program Kursus</th>
              <th style="width:15%;">Skor Benar</th>
              <th style="width:15%;">Persentase</th>
              <th style="width:20%; text-align:right;">Status Kelulusan</th>
            </tr>
          </thead>
          <tbody id="lmsQuizzesTbody">
            <tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">Memuat riwayat kuis...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- MODAL PRODUCT EDITOR -->
  <div class="modal-backdrop" id="productEditorModal">
    <div class="modal-box">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="editorModalTitle" style="font-size:18px; font-weight:900; margin:0;">Edit Detail &amp; Konten E-Book</h2>
        </div>
        <button type="button" onclick="closeProductEditor()" style="width:34px; height:34px; border-radius:50%; border:none; background:#f1f5f9; color:#64748b; font-size:18px; font-weight:700; cursor:pointer;">&times;</button>
      </div>

      <div class="modal-body">
        <form id="productEditForm">
          <input type="hidden" id="editId" name="id">

          <!-- SLUG & SUBTITLE -->
          <div class="grid-2col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Slug URL Halaman Publik</label>
              <input type="text" id="editSlug" name="slug" class="form-control" placeholder="cth: course-iso-27001" oninput="updateSlugLivePreview(this.value)">
              <div style="font-size:11px; color:#64748b; margin-top:4px;">
                Tautan publik: <span id="slugLiveUrl" style="font-family:monospace; color:#0284c7; font-weight:700;">https://store.restiamoegiono.com/produk/...</span>
              </div>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Subjudul / Ringkasan Utama Singkat</label>
              <input type="text" id="editSubtitle" name="subtitle" class="form-control" placeholder="Misal: Panduan Praktis & Instrumen Audit Lengkap">
            </div>
          </div>

          <div class="grid-2col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Judul E-Book / Dokumen Kerja</label>
              <input type="text" id="editTitle" name="title" required class="form-control" placeholder="Judul Buku..." oninput="autoSuggestSlug(this.value)">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Kategori Pustaka</label>
              <input type="text" id="editCategory" name="category" required class="form-control" placeholder="Misal: ISO & Standar Keamanan">
            </div>
          </div>

          <div class="grid-3col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Harga Jual (Rp)</label>
              <input type="number" id="editPrice" name="price" required min="0" step="1000" class="form-control">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Harga Coret / Normal (Rp)</label>
              <input type="number" id="editOldPrice" name="old_price" min="0" step="1000" class="form-control">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Badge Label Unggulan</label>
              <input type="text" id="editBadge" name="badge" class="form-control" placeholder="Misal: Terlaris • Flagship">
            </div>
          </div>

          <div class="grid-3col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Versi / Edisi Buku</label>
              <input type="text" id="editVersion" name="version" class="form-control" placeholder="Misal: Edisi 2026 (v2.1)">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Jumlah Halaman</label>
              <input type="number" id="editPages" name="pages" min="1" class="form-control">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Status Penayangan Toko</label>
              <select id="editIsActive" name="is_active" class="form-control">
                <option value="1">✓ Aktif (Tayang di Toko)</option>
                <option value="0">✕ Nonaktif (Disembunyikan)</option>
              </select>
            </div>
          </div>

          <!-- SPESIFIKASI TEKNIS & METADATA -->
          <div class="grid-3col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Format Berkas</label>
              <input type="text" id="editFileFormat" name="file_format" class="form-control" placeholder="PDF (DRM Watermark)">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Ukuran Berkas</label>
              <input type="text" id="editFileSize" name="file_size" class="form-control" placeholder="12.5 MB">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Bahasa Dokumen</label>
              <input type="text" id="editLanguage" name="language" class="form-control" placeholder="Bahasa Indonesia">
            </div>
          </div>

          <div class="grid-3col" style="margin-bottom:16px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Edisi / Tahun</label>
              <input type="text" id="editEdition" name="edition" class="form-control" placeholder="Edisi Pertama 2026">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">ISBN / Identifikasi</label>
              <input type="text" id="editIsbn" name="isbn" class="form-control" placeholder="978-623-0000-00-0">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Urutan Tampil (Sort Order)</label>
              <input type="number" id="editOrderIndex" name="order_index" value="0" class="form-control">
            </div>
          </div>

          <!-- SECTION UPLOAD: MASTER PDF & COVER IMAGE -->
          <div class="grid-2col" style="margin-bottom:18px;">
            <!-- CARD 1: BERKAS MASTER PDF -->
            <div class="form-group" style="margin:0; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <label class="form-label" style="margin:0;">Berkas Master PDF di Server</label>
                <span id="currentPdfBadge" class="status-badge status-paid" style="font-size:10px;">✓ Tersedia</span>
              </div>
              
              <!-- Info Berkas Saat Ini -->
              <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:12px; background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;">
                <span style="font-size:24px;">📄</span>
                <div style="flex:1; overflow:hidden;">
                  <div style="font-weight:700; color:#0f172a; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;" id="currentPdfName">-</div>
                  <div style="font-size:11px; color:#64748b; margin-top:2px;" id="currentPdfSize">Berkas tersimpan di storage</div>
                </div>
              </div>

              <!-- Tombol Ganti / Unggah PDF Baru -->
              <div>
                <label class="btn-action-sm" style="display:flex; width:100%; justify-content:center; cursor:pointer; background:#ffffff; border:1px dashed #0284c7; color:#0284c7; padding:9px 12px; font-weight:700;">
                  <span>📁 Unggah / Ganti Berkas PDF Baru</span>
                  <input type="file" id="uploadMasterPdf" name="master_pdf_file" accept=".pdf" style="display:none;" onchange="handlePdfSelected(this)">
                </label>
                <div id="newPdfSelectedInfo" style="display:none; font-size:11px; color:#059669; font-weight:700; margin-top:6px;"></div>
                <button type="button" id="btnCancelPdfReplace" onclick="cancelPdfReplace()" class="btn-action-sm" style="display:none; margin-top:6px; font-size:10px; padding:4px 8px; color:#b91c1c;">
                  ✕ Batalkan Ganti PDF
                </button>
              </div>
              <input type="hidden" id="editMasterFile" name="master_file">
            </div>

            <!-- CARD 2: GAMBAR SAMPUL COVER -->
            <div class="form-group" style="margin:0; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <label class="form-label" style="margin:0;">Gambar Sampul Produk (Cover)</label>
                <span style="font-size:11px; color:#64748b; font-weight:600;">Pratinjau Saat Ini</span>
              </div>

              <!-- Preview Gambar Saat Ini & Gambar Baru -->
              <div style="display:flex; gap:14px; align-items:center; margin-bottom:10px;">
                <div style="width:62px; height:84px; border-radius:10px; overflow:hidden; border:1px solid #cbd5e1; background:#0f172a; flex-shrink:0; box-shadow:0 4px 10px rgba(0,0,0,0.06);">
                  <img id="coverPreviewImg" src="/images/book-cover-450.webp" style="width:100%; height:100%; object-fit:cover;" alt="Cover Preview">
                </div>
                <div style="flex:1; font-size:12px; overflow:hidden;">
                  <div style="font-weight:700; color:#0f172a; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;" id="currentCoverPath">/images/book-cover-450.webp</div>
                  <div style="font-size:11px; color:#64748b; margin-top:2px;" id="coverStatusText">Gambar sampul yang aktif saat ini</div>
                  <button type="button" id="btnCancelCoverReplace" onclick="cancelCoverReplace()" class="btn-action-sm" style="display:none; margin-top:6px; font-size:10px; padding:4px 8px; color:#b91c1c;">
                    ✕ Batalkan Penggantian
                  </button>
                </div>
              </div>

              <!-- Tombol Ganti / Unggah Gambar Baru -->
              <div>
                <label class="btn-action-sm" style="display:flex; width:100%; justify-content:center; cursor:pointer; background:#ffffff; border:1px dashed #0284c7; color:#0284c7; padding:9px 12px; font-weight:700;">
                  <span>🖼️ Unggah / Ganti Gambar Baru (WebP, JPG, PNG)</span>
                  <input type="file" id="uploadCoverImage" name="cover_file" accept="image/webp,image/jpeg,image/png" style="display:none;" onchange="handleCoverSelected(this)">
                </label>
                <div id="newCoverSelectedInfo" style="display:none; font-size:11px; color:#059669; font-weight:700; margin-top:6px;"></div>
              </div>
              <input type="hidden" id="editCoverImage" name="cover_image">
            </div>
          </div>

          <!-- RICH TEXT EDITOR SECTION -->
          <div class="form-group" style="margin:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <label class="form-label" style="margin:0;">Konten &amp; Deskripsi Lengkap E-Book</label>
              <button type="button" id="btnToggleHtml" onclick="toggleHtmlSourceMode()" class="btn-action-sm" style="font-size:11px; padding:4px 10px;">
                &lt;&sol;&gt; Mode HTML Source
              </button>
            </div>

            <!-- Quick Templates Bar -->
            <div class="editor-toolbar-custom">
              <span style="font-size:11px; font-weight:800; color:#64748b; margin-right:4px;">Sisipkan Template:</span>
              <button type="button" class="btn-template" onclick="insertEditorTemplate('silabus')">📑 Silabus &amp; Bab</button>
              <button type="button" class="btn-template" onclick="insertEditorTemplate('target')">🎯 Target Pembaca</button>
              <button type="button" class="btn-template" onclick="insertEditorTemplate('instrumen')">🔍 Instrumen Kerja</button>
              <button type="button" class="btn-template" onclick="insertEditorTemplate('clear')" style="color:#b91c1c;">🧹 Kosongkan</button>
            </div>

            <!-- Quill Canvas -->
            <div id="quillWrapper">
              <div id="quillProductEditor"></div>
            </div>
            <!-- Raw HTML Textarea (Toggled) -->
            <textarea id="rawHtmlTextarea" class="raw-html-textarea" placeholder="Kode HTML mentah..."></textarea>
            <div style="font-size:11px; color:#64748b; margin-top:6px; display:flex; justify-content:space-between;">
              <span>Gunakan formatting (Heading, Tebal, Miring, Daftar Bullet) untuk menyusun materi buku secara profesional.</span>
              <span id="editorStats">0 karakter</span>
            </div>
          </div>
          <!-- DETAILED TABS / SECTIONS FOR STANDALONE PRODUCT PAGE -->
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px; margin-top:18px;">
            <div style="font-weight:800; font-size:14px; color:#0f172a; margin-bottom:4px;">Detail Khusus Halaman Publik (/produk/slug)</div>
            <p style="font-size:12px; color:#64748b; margin-bottom:14px;">Konten berikut ditampilkan otomatis di tab Silabus, Target Pembaca, dan Nilai Tambah pada halaman tersendiri produk ini.</p>

            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label">📑 Silabus / Daftar Isi Lengkap (Bisa HTML atau Teks)</label>
              <textarea id="editTableOfContents" name="table_of_contents" class="form-control" rows="5" placeholder="Contoh:&#10;Bab 1: Tata Kelola Keamanan Siber&#10;Bab 2: Panduan Checklist & Audit&#10;Bab 3: Format Rekomendasi Resmi..."></textarea>
            </div>

            <div class="grid-2col">
              <div class="form-group" style="margin:0;">
                <label class="form-label">🎯 Target Pembaca &amp; Sasaran Organisasi</label>
                <textarea id="editTargetAudience" name="target_audience" class="form-control" rows="4" placeholder="CISO, DPO, Auditor TI, Manajer Kepatuhan, Lead Auditor..."></textarea>
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label">🌟 Manfaat Utama &amp; Nilai Tambah</label>
                <textarea id="editBenefits" name="benefits" class="form-control" rows="4" placeholder="Checklist audit siap pakai, hemat puluhan jam penyusunan instrumen..."></textarea>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeProductEditor()" class="btn-action-sm" style="padding:10px 18px; font-size:13px;">✕ Batal</button>
        <button type="button" id="btnSaveProduct" onclick="submitProductForm()" class="btn-primary-form" style="padding:10px 24px; font-size:13px;">
          💾 Simpan Perubahan Produk
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL CREATE/EDIT COUPON -->
  <div class="modal-backdrop" id="couponModal">
    <div class="modal-box" style="max-width:540px;">
      <div class="modal-header">
        <h2 class="modal-title" id="couponModalTitle" style="font-size:18px; font-weight:900; margin:0;">Buat Kupon Promo Baru</h2>
        <button type="button" onclick="closeCouponModal()" style="width:34px; height:34px; border-radius:50%; border:none; background:#f1f5f9; color:#64748b; font-size:18px; font-weight:700; cursor:pointer;">&times;</button>
      </div>
      <form id="couponForm" onsubmit="handleSaveCoupon(event)" class="modal-body">
        <input type="hidden" id="couponId" name="id" value="0">
        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label">Kode Kupon / Voucher (Huruf Besar)</label>
          <input type="text" id="couponCode" name="code" required placeholder="Misal: PROMO2026" style="text-transform:uppercase;" class="form-control">
        </div>
        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label">Berlaku Untuk (Target Promo)</label>
          <select id="couponApplicableTo" name="applicable_to" class="form-control" style="font-weight:700;">
            <option value="ALL">🌐 Semua Produk (E-Store Toko &amp; LMS Kursus)</option>
            <option value="ESTORE">📚 Khusus E-Store (Hanya E-Book &amp; Dokumen Kerja)</option>
            <option value="LMS">🎓 Khusus Pendaftaran / Join LMS Kursus</option>
          </select>
          <div style="font-size:11px; color:#64748b; margin-top:4px;">Tentukan apakah diskon ini berlaku untuk buku toko, pendaftaran kursus, atau keduanya.</div>
        </div>
        <div class="grid-2col" style="margin-bottom:14px;">
          <div class="form-group" style="margin:0;">
            <label class="form-label">Jenis Potongan</label>
            <select id="couponType" name="discount_type" class="form-control">
              <option value="PERCENT">Persentase (%)</option>
              <option value="FIXED">Nominal Tetap (Rp)</option>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Nilai Potongan</label>
            <input type="number" id="couponVal" name="discount_value" required min="1" step="any" placeholder="20 atau 50000" class="form-control">
          </div>
        </div>
        <div class="grid-2col" style="margin-bottom:14px;">
          <div class="form-group" style="margin:0;">
            <label class="form-label">Minimal Belanja (Rp)</label>
            <input type="number" id="couponMinOrder" name="min_order" value="0" min="0" class="form-control">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Batas Kuota Pemakaian</label>
            <input type="number" id="couponLimit" name="usage_limit" value="100" min="1" class="form-control">
          </div>
        </div>
        <div class="grid-2col" style="margin-bottom:14px;">
          <div class="form-group" style="margin:0;">
            <label class="form-label">Masa Berlaku (Opsional)</label>
            <input type="date" id="couponExpires" name="expires_at" class="form-control">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Status Kupon</label>
            <select id="couponActive" name="is_active" class="form-control">
              <option value="1">✓ Aktif</option>
              <option value="0">✕ Nonaktif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer" style="padding:16px 0 0; background:transparent;">
          <button type="button" onclick="closeCouponModal()" class="btn-action-sm">Batal</button>
          <button type="submit" id="btnSubmitCoupon" class="btn-primary-form">💾 Simpan Kupon</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL ENROLL MANUAL SISWA LMS AKADEMI -->
  <div class="modal-backdrop" id="enrollManualModal" style="display:none;">
    <div class="modal-box" style="max-width:520px;">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" style="font-size:18px; font-weight:900; margin:0;">Daftarkan Siswa Manual (B2B)</h2>
          <p style="font-size:12px; color:#64748b; margin:4px 0 0;">Berikan akses kelas pelatihan tanpa melalui checkout toko (contoh: peserta in-house training perusahaan / B2B).</p>
        </div>
        <button type="button" onclick="closeEnrollManualModal()" style="width:34px; height:34px; border-radius:50%; border:none; background:#f1f5f9; color:#64748b; font-size:18px; font-weight:700; cursor:pointer;">&times;</button>
      </div>

      <div class="modal-body">
        <form id="enrollManualForm" onsubmit="submitEnrollManual(event)">
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Nama Lengkap Peserta</label>
            <input type="text" id="manualStudentName" name="name" required class="form-control" placeholder="Contoh: Rian Anggara, S.Kom">
          </div>
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Email Aktif Peserta (Untuk Login OTP / Demo)</label>
            <input type="email" id="manualStudentEmail" name="email" required class="form-control" placeholder="nama@perusahaan.co.id">
          </div>
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Pilih Program Kursus</label>
            <select id="manualCourseId" name="course_id" class="form-control">
              <option value="1">Masterclass Implementasi &amp; Lead Auditor ISO 27001:2022</option>
            </select>
          </div>
          <div class="modal-footer" style="padding:16px 0 0; background:transparent;">
            <button type="button" onclick="closeEnrollManualModal()" class="btn-action-sm">Batal</button>
            <button type="submit" id="btnSubmitEnroll" class="btn-primary-form">🎓 Daftarkan Peserta</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- MODAL EDIT MATERI BAB LMS -->
  <div class="modal-backdrop" id="lmsLessonModal" style="display:none;">
    <div class="modal-box" style="max-width:860px; max-height:92vh; display:flex; flex-direction:column;">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="lmsLessonModalTitle" style="font-size:18px; font-weight:900; margin:0;">Edit Materi Bab Pelatihan</h2>
          <p style="font-size:12px; color:#64748b; margin:4px 0 0;">Ubah judul bab, video pembelajaran, dan catatan kuliah interaktif.</p>
        </div>
        <button type="button" onclick="closeLmsLessonModal()" style="width:34px; height:34px; border-radius:50%; border:none; background:#f1f5f9; color:#64748b; font-size:18px; font-weight:700; cursor:pointer;">&times;</button>
      </div>

      <div class="modal-body" style="overflow-y:auto; flex:1;">
        <form id="lmsLessonForm" onsubmit="handleSaveLmsLesson(event)">
          <input type="hidden" id="lmsLessonId" name="id" value="0">
          <input type="hidden" id="lmsLessonCourseId" name="course_id" value="1">

          <div class="grid-2col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Judul Bab Materi</label>
              <input type="text" id="lmsLessonTitle" name="title" required class="form-control" placeholder="Contoh: Orientasi Kursus & Mindset Lead Auditor">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Modul Kursus</label>
              <select id="lmsLessonModuleId" name="module_id" class="form-control">
                <option value="1">Modul 1: Fondasi ISMS & Paradigma Baru ISO 27001:2022</option>
                <option value="2">Modul 2: Eksekusi Klausul 4 hingga 10 (Siklus PDCA)</option>
                <option value="3">Modul 3: Penerapan 93 Kontrol Annex A (4 Kategori)</option>
                <option value="4">Modul 4: Kesiapan Audit Sertifikasi & Dokumen SoA</option>
              </select>
            </div>
          </div>

          <div class="grid-3col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Slug / URL Bab</label>
              <input type="text" id="lmsLessonSlug" name="slug" class="form-control" placeholder="orientasi-mindset-lead-auditor">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Durasi Pembelajaran</label>
              <input type="text" id="lmsLessonDuration" name="duration" class="form-control" placeholder="15:00">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Urutan Tampil (Sort Order)</label>
              <input type="number" id="lmsLessonSortOrder" name="sort_order" min="1" value="1" class="form-control">
            </div>
          </div>

          <div class="grid-2col" style="margin-bottom:14px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">YouTube Video ID / URL</label>
              <input type="text" id="lmsLessonVideoId" name="video_id" class="form-control" placeholder="dQw4w9WgXcQ atau URL lengkap">
              <span style="font-size:11px; color:#64748b; margin-top:3px; display:block;">Masukkan 11-karakter ID YouTube atau link lengkap video YouTube.</span>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Akses Preview Gratis</label>
              <select id="lmsLessonIsPreview" name="is_free_preview" class="form-control">
                <option value="0">🔒 Terkunci (Khusus Peserta Terdaftar)</option>
                <option value="1">🔓 Preview Gratis (Bisa Ditonton Siapapun Tanpa Login)</option>
              </select>
            </div>
          </div>

          <div class="grid-2col" style="margin-bottom:16px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Lampiran Resource Vault (Nama Dokumen)</label>
              <input type="text" id="lmsLessonAttachmentName" name="attachment_name" class="form-control" placeholder="Contoh: Checklist-Klausul-ISO27001.pdf">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">URL / Link Unduh Berkas</label>
              <input type="text" id="lmsLessonAttachmentUrl" name="attachment_url" class="form-control" placeholder="/downloads/checklist.pdf">
            </div>
          </div>

          <!-- EDITOR CATATAN KULIAH / MATERI -->
          <div class="form-group" style="margin-bottom:18px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <label class="form-label" style="margin:0; font-weight:800; color:#0f172a; font-size:13px;">
                Catatan Kuliah &amp; Materi Bab
              </label>
            </div>
            <div id="quillLmsLessonWrapper" style="border:1px solid #cbd5e1; border-radius:12px; overflow:hidden; background:#ffffff;">
              <div id="quillLmsLessonEditor" style="min-height:240px; font-size:14px; line-height:1.7;"></div>
            </div>
            <input type="hidden" id="lmsLessonContentHtml" name="content_html">
          </div>

          <div class="modal-footer" style="padding:16px 0 0; background:transparent;">
            <button type="button" onclick="closeLmsLessonModal()" class="btn-action-sm">Batal</button>
            <button type="submit" id="btnSubmitLmsLesson" class="btn-primary-form">💾 Simpan Materi</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="/assets/vendor/quill/quill.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

  <script>
    const ADMIN_KEY = '<?= ADMIN_PASSWORD ?>';
    let cachedProducts = [];
    let cachedCoupons = [];
    let cachedMasters = [];
    let cachedSettings = {};
    let productQuill = null;
    let heroDescQuill = null;
    let lmsLessonQuill = null;
    let isHtmlMode = false;
    let isHeroDescHtmlMode = false;
    let isTitleRawMode = false;

    // Inisialisasi Quill Editor (Produk, Hero Description, & Materi LMS)
    function initQuill() {
      // 1. Editor Deskripsi Produk
      if (!productQuill) {
        const el = document.getElementById('quillProductEditor');
        if (el && typeof Quill !== 'undefined') {
          productQuill = new Quill('#quillProductEditor', {
            theme: 'snow',
            placeholder: 'Tuliskan deskripsi lengkap, silabus, dan nilai tambah e-book di sini...',
            modules: {
              toolbar: [
                [{ 'header': [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'clean']
              ]
            }
          });

          productQuill.on('text-change', function() {
            const text = productQuill.getText().trim();
            document.getElementById('editorStats').innerText = text.length + ' karakter';
          });
        }
      }

      // 2. Editor Deskripsi Hero Toko (WYSIWYG)
      if (!heroDescQuill) {
        const hEl = document.getElementById('quillHeroDescEditor');
        if (hEl && typeof Quill !== 'undefined') {
          heroDescQuill = new Quill('#quillHeroDescEditor', {
            theme: 'snow',
            placeholder: 'Tuliskan deskripsi ringkas pengantar toko buku...',
            modules: {
              toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'bullet' }],
                ['link', 'clean']
              ]
            }
          });

          heroDescQuill.on('text-change', function() {
            syncHeroDescTextarea();
            const text = heroDescQuill.getText().trim();
            const statsEl = document.getElementById('heroDescStats');
            if (statsEl) statsEl.innerText = text.length + ' karakter';
          });
        }
      }

      // 3. Editor Catatan Kuliah / Materi Bab LMS (WYSIWYG)
      if (!lmsLessonQuill) {
        const lEl = document.getElementById('quillLmsLessonEditor');
        if (lEl && typeof Quill !== 'undefined') {
          lmsLessonQuill = new Quill('#quillLmsLessonEditor', {
            theme: 'snow',
            placeholder: 'Tuliskan catatan kuliah, ringkasan materi, pedoman implementasi, dan checklist bab ini...',
            modules: {
              toolbar: [
                [{ 'header': [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'clean']
              ]
            }
          });
        }
      }
    }

    function switchTab(tab, el) {
      const cards = document.querySelectorAll('.nav-chip, .cue-card, .tab-btn');
      cards.forEach(c => c.classList.remove('active'));

      if (el && el.classList) {
        el.classList.add('active');
      } else {
        const target = document.querySelector(`.nav-chip[data-tab="${tab}"], .cue-card[data-tab="${tab}"], .tab-btn[data-tab="${tab}"]`);
        if (target) target.classList.add('active');
      }

      const tabs = ['orders', 'products', 'course', 'coupons', 'briefcase', 'drm', 'email', 'doku', 'hero', 'lms'];
      tabs.forEach(t => {
        const elId = 'tab' + t.charAt(0).toUpperCase() + t.slice(1);
        const tabEl = document.getElementById(elId);
        if (tabEl) tabEl.style.display = (t === tab) ? 'block' : 'none';
      });

      if (tab === 'hero' || tab === 'course' || tab === 'lms') {
        initQuill();
      }
    }

    let cachedOrders = [];
    let currentOrdersList = [];
    let activeOrderFilter = 'all';
    let currentSearchQuery = '';

    function showToast(msg) {
      let toast = document.getElementById('toastNotice');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastNotice';
        toast.className = 'toast-notice';
        document.body.appendChild(toast);
      }
      toast.innerText = msg;
      toast.classList.add('show');
      setTimeout(() => {
        toast.classList.remove('show');
      }, 2500);
    }

    function copyInvoice(inv) {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(inv).then(() => {
          showToast('✓ Invoice ' + inv + ' disalin ke clipboard!');
        }).catch(() => {
          prompt('Salin nomor invoice:', inv);
        });
      } else {
        prompt('Salin nomor invoice:', inv);
      }
    }

    function filterOrdersTable(val) {
      currentSearchQuery = val;
      const clearBtn = document.getElementById('clearSearchBtn');
      if (clearBtn) clearBtn.style.display = val ? 'inline-flex' : 'none';
      renderOrdersTable();
    }

    function clearOrdersSearch() {
      const input = document.getElementById('orderSearchInput');
      if (input) input.value = '';
      filterOrdersTable('');
    }

    function setOrdersFilter(type, btn) {
      activeOrderFilter = type;
      document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      renderOrdersTable();
    }

    function renderOrdersTable() {
      const tbody = document.getElementById('ordersTbody');
      if (!tbody) return;

      const q = (currentSearchQuery || '').toLowerCase().trim();
      const isTestOrder = (o) => {
        return o.is_test == 1 || (o.doku_payment_id && o.doku_payment_id.startsWith('TRX-')) || (o.invoice_number && (o.invoice_number.includes('RM-1791630573') || o.invoice_number.includes('RM-1791630610')));
      };

      const filtered = currentOrdersList.filter(o => {
        const isTest = isTestOrder(o);
        const isPaid = (o.payment_status === 'PAID' || o.payment_status === 'SUCCESS');
        if (activeOrderFilter === 'live' && isTest) return false;
        if (activeOrderFilter === 'test' && !isTest) return false;
        if (activeOrderFilter === 'paid' && !isPaid) return false;
        if (activeOrderFilter === 'pending' && isPaid) return false;

        if (q) {
          const matchInv = (o.invoice_number || '').toLowerCase().includes(q);
          const matchName = (o.customer_name || '').toLowerCase().includes(q);
          const matchEmail = (o.customer_email || '').toLowerCase().includes(q);
          const matchProd = (o.product_title || '').toLowerCase().includes(q);
          return matchInv || matchName || matchEmail || matchProd;
        }
        return true;
      });

      const counterBadge = document.getElementById('ordersCounterBadge');
      if (counterBadge) {
        counterBadge.innerText = currentOrdersList.length + ' Pesanan';
      }

      if (filtered.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align:center; padding:36px 20px; color:#64748b;">
              <div style="font-size:28px; margin-bottom:6px;">🔍</div>
              <div style="font-size:14px; font-weight:800; color:#0f172a; margin-bottom:3px;">Tidak Ada Transaksi Ditemukan</div>
              <div style="font-size:12px; color:#64748b;">${q ? 'Coba ubah kata kunci pencarian Anda.' : 'Belum ada data pesanan pada filter ini.'}</div>
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = '';
      filtered.forEach(o => {
        const isTest = isTestOrder(o);
        const isPaid = (o.payment_status === 'PAID' || o.payment_status === 'SUCCESS');
        const initials = (o.customer_name || 'U').split(' ').filter(x => x).map(x => x[0]).slice(0, 2).join('').toUpperCase();

        let dateStr = '';
        if (o.created_at) {
          try {
            const d = new Date(o.created_at.replace(' ', 'T'));
            if (!isNaN(d.getTime())) {
              dateStr = d.toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
            } else {
              dateStr = o.created_at;
            }
          } catch(e) { dateStr = o.created_at; }
        }

        const couponInfo = o.coupon_code 
          ? `<div class="coupon-sub-text">🏷️ ${o.coupon_code} (-Rp ${Number(o.discount_amount || 0).toLocaleString('id-ID')})</div>` 
          : '';

        const typeBadge = isTest
          ? `<span class="tag-type-badge tag-test" title="Transaksi simulasi pengujian sistem">🧪 Uji Test</span>`
          : `<span class="tag-type-badge tag-live" title="Pembayaran resmi DOKU Payment Gateway">💳 DOKU Live</span>`;

        const statusPill = isTest
          ? `<span class="status-pill-modern test-paid">
              <span class="pulse-indicator-dot purple"></span>
              <span>TEST PAID</span>
             </span>
             <div class="delivery-sub-status sent">
               ${o.email_sent ? '✓ Email Uji Coba Terkirim' : '⏳ Belum Terkirim'}
             </div>`
          : `<span class="status-pill-modern ${isPaid ? 'paid' : 'pending'}">
              <span class="pulse-indicator-dot ${isPaid ? 'green' : 'amber'}"></span>
              <span>${o.payment_status}</span>
             </span>
             <div class="delivery-sub-status ${o.email_sent ? 'sent' : 'unsent'}">
               ${o.email_sent ? '✓ Email Dokumen Terkirim' : '⏳ Menunggu Pembayaran'}
             </div>`;

        const tr = document.createElement('tr');
        tr.className = 'order-row-item';
        tr.innerHTML = `
          <td>
            <div class="inv-wrap">
              <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                <button type="button" class="invoice-pill-btn" onclick="copyInvoice('${o.invoice_number}')" title="Klik untuk menyalin invoice">
                  <code>${o.invoice_number}</code>
                  <span class="copy-hint-icon">📋</span>
                </button>
                ${typeBadge}
              </div>
              ${dateStr ? `<div class="inv-date-sub">${dateStr}</div>` : ''}
            </div>
          </td>
          <td>
            <div class="customer-avatar-flex">
              <div class="customer-avatar-circle" ${isTest ? 'style="background:linear-gradient(135deg, #8b5cf6, #6366f1);"' : ''}>${initials || 'U'}</div>
              <div class="customer-info-col">
                <div class="customer-name-text">${o.customer_name}</div>
                <a href="mailto:${o.customer_email}" class="customer-email-link" title="Kirim email ke ${o.customer_email}">
                  ${o.customer_email}
                </a>
              </div>
            </div>
          </td>
          <td>
            <div class="product-title-cell">
              <span class="product-badge-icon">📘</span>
              <span class="product-title-text">${o.product_title}</span>
            </div>
          </td>
          <td>
            <div class="amount-val">Rp ${Number(o.amount).toLocaleString('id-ID')}</div>
            ${couponInfo}
          </td>
          <td>
            <div class="status-stack">
              ${statusPill}
            </div>
          </td>
          <td style="text-align:right;">
            <button type="button" class="btn-fulfill-pill" onclick="fulfillManual('${o.invoice_number}')" title="Stempel DRM & Kirim Ulang Email">
              <span class="btn-fulfill-icon">⚡</span>
              <span>Kirim</span>
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function exportOrdersCsv() {
      window.location.href = '/api/admin-action.php?action=export_orders&admin_key=' + encodeURIComponent(ADMIN_KEY);
    }

    async function loadAdminData() {
      try {
        const res = await fetch('/api/admin-action.php?action=get_data', {
          headers: { 'X-Admin-Auth': ADMIN_KEY }
        });
        const data = await res.json();
        if (data.success) {
          cachedProducts = data.products || [];
          cachedCoupons = data.coupons || [];
          cachedMasters = data.masters || [];
          cachedSettings = data.settings || {};

          // Render Pengaturan Hero & Gambar Toko
          if (cachedSettings.hero_image) {
            document.getElementById('currentHeroPath').innerText = cachedSettings.hero_image;
            const fileInput = document.getElementById('uploadHeroImage');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
              document.getElementById('heroPreviewImg').src = cachedSettings.hero_image;
              document.getElementById('inputHeroImage').value = cachedSettings.hero_image;
            }
          }
          const heroForm = document.getElementById('heroSettingsForm');
          const isUserTypingInHero = heroForm && heroForm.contains(document.activeElement);
          if (!isUserTypingInHero) {
            if (cachedSettings.hero_tag) {
              document.getElementById('heroTagInput').value = cachedSettings.hero_tag;
              updateHeroTagPreview();
            }
            if (cachedSettings.hero_title) {
              setVisualTitleFromHtml(cachedSettings.hero_title);
            }
            if (cachedSettings.hero_desc) {
              if (heroDescQuill) {
                heroDescQuill.root.innerHTML = cachedSettings.hero_desc;
              }
              const rawDesc = document.getElementById('rawHeroDescTextarea');
              if (rawDesc) rawDesc.value = cachedSettings.hero_desc;
            }
          }

          // Render KPI
          const realOmset = Number(data.kpi.total_omset || 0);
          const testOmset = Number(data.kpi.total_test_omset || 0);
          const realPaid = Number(data.kpi.total_paid || 0);
          const testPaid = Number(data.kpi.total_test_paid || 0);

          if (realOmset > 0) {
            document.getElementById('kpiOmset').innerText = 'Rp ' + realOmset.toLocaleString('id-ID');
            document.getElementById('kpiPaid').innerText = realPaid + ' Pesanan Resmi' + (testPaid > 0 ? ` (+${testPaid} Uji Coba)` : '');
          } else {
            document.getElementById('kpiOmset').innerText = 'Rp 0';
            document.getElementById('kpiPaid').innerText = `0 Pesanan Live (${testPaid} Uji Coba: Rp ${testOmset.toLocaleString('id-ID')})`;
          }

          document.getElementById('kpiTerkirim').innerText = data.kpi.total_sent + ' Dokumen';
          const totalEl = document.getElementById('kpiTotal');
          if (totalEl) totalEl.innerText = (data.kpi.total_orders || 0) + ' Transaksi';
          document.getElementById('kpiCoupons').innerText = data.kpi.total_coupons + ' Voucher Terdaftar';

          // Render Orders
          cachedOrders = data.orders || [];
          currentOrdersList = cachedOrders;
          renderOrdersTable();

          // Render Tab Products
          const pContainer = document.getElementById('productsAdminContainer');
          if (pContainer) {
            pContainer.innerHTML = '';
            cachedProducts.forEach(p => {
              const card = document.createElement('div');
              card.className = 'product-card-admin';
              const cleanDesc = (p.description || '').replace(/<[^>]*>?/gm, '').trim();
              card.innerHTML = `
                <div>
                  <div class="item-header">
                    <div>
                      <span class="status-badge status-paid" style="margin-bottom:6px; font-size:10px;">${p.category}</span>
                      <h3 class="item-title">${p.title}</h3>
                    </div>
                    ${p.badge ? `<span class="item-badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">${p.badge}</span>` : ''}
                  </div>
                  <div class="product-desc-preview">${cleanDesc || 'Belum ada deskripsi konten.'}</div>
                  <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; font-size:12px; margin-bottom:16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                      <span style="color:#64748b;">Harga Jual:</span>
                      <b>Rp ${Number(p.price).toLocaleString('id-ID')} ${p.old_price > p.price ? `<span style="color:#94a3b8; text-decoration:line-through; font-weight:normal;">(Rp ${Number(p.old_price).toLocaleString('id-ID')})</span>` : ''}</b>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                      <span style="color:#64748b;">Versi & Halaman:</span>
                      <b>${p.version || 'Edisi 2026'} &bull; ${p.pages || 0} Hal</b>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                      <span style="color:#64748b;">Status Toko:</span>
                      <b>${p.is_active == 1 ? '<span style="color:#059669;">✓ Aktif (Tayang)</span>' : '<span style="color:#e11d48;">✕ Nonaktif</span>'}</b>
                    </div>
                  </div>
                </div>
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                  <button class="btn-primary-form" style="flex:1; min-width:85px; text-align:center; padding:8px 10px; font-size:12px;" onclick="openProductEditor('${p.id}')">✏️ Edit</button>
                  <a href="https://store.restiamoegiono.com/produk/${encodeURIComponent(p.slug || p.id)}" target="_blank" class="btn-action-sm" style="display:inline-flex; align-items:center; gap:4px; text-decoration:none; padding:8px 10px; font-size:12px; font-weight:700;" title="Buka Halaman Publik Produk">👁️ Lihat ↗</a>
                  <button class="btn-action-sm" onclick="testBookDrm('${p.id}')" title="Uji Stempel DRM">⚡ Stempel</button>
                  <button class="btn-action-sm" style="color:#b91c1c; padding:8px 10px;" onclick="deleteProduct('${p.id}', '${p.title ? p.title.replace(/'/g, "\\'") : ''}')" title="Hapus Produk dari Katalog Toko">🗑️</button>
                </div>
              `;
              pContainer.appendChild(card);
            });
          }

          // Render Tab Coupons
          const cTbody = document.getElementById('couponsTbody');
          if (cTbody) {
            if (cachedCoupons.length === 0) {
              cTbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:24px; color:#64748b;">Belum ada kupon promo aktif.</td></tr>';
            } else {
              cTbody.innerHTML = '';
              cachedCoupons.forEach(c => {
                const tr = document.createElement('tr');
                const isAktif = (c.is_active == 1);
                const valFmt = c.discount_type === 'PERCENT' ? Number(c.discount_value) + '%' : 'Rp ' + Number(c.discount_value).toLocaleString('id-ID');
                const statusHtml = isAktif
                  ? '<span class="status-badge status-paid" style="background:#dcfce7; color:#166534; border:1px solid #86efac; font-weight:700;">✓ Aktif (Tayang di Banner Store)</span>'
                  : '<span class="status-badge status-inactive" style="background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1;">✕ Nonaktif (Banner Sembunyi)</span>';

                let targetBadge = '<span class="status-badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-weight:700;">🌐 Semua (E-Store &amp; LMS)</span>';
                if (c.applicable_to === 'ESTORE') {
                  targetBadge = '<span class="status-badge" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; font-weight:700;">📚 Khusus E-Store</span>';
                } else if (c.applicable_to === 'LMS') {
                  targetBadge = '<span class="status-badge" style="background:#f3e8ff; color:#6b21a8; border:1px solid #d8b4fe; font-weight:700;">🎓 Khusus Join LMS</span>';
                }

                tr.innerHTML = `
                  <td><b style="font-family:monospace; font-size:14px; color:#0284c7;">${c.code}</b></td>
                  <td>${targetBadge}</td>
                  <td>${c.discount_type === 'PERCENT' ? 'Persentase' : 'Nominal Tetap'}</td>
                  <td><b>${valFmt}</b></td>
                  <td>Rp ${Number(c.min_order).toLocaleString('id-ID')}</td>
                  <td>${c.used_count} / ${c.usage_limit || '∞'}</td>
                  <td>${c.expires_at || '<span style="color:#64748b;">Selamanya</span>'}</td>
                  <td>${statusHtml}</td>
                  <td style="text-align:right; white-space:nowrap;">
                    <button class="btn-action-sm" onclick="editCoupon(${c.id})">✏️ Edit</button>
                    <button class="btn-action-sm" style="${isAktif ? 'color:#b45309;' : 'color:#047857;'}" onclick="toggleCoupon(${c.id})">${isAktif ? 'Nonaktifkan' : 'Aktifkan'}</button>
                    <button class="btn-action-sm" style="color:#b91c1c;" onclick="deleteCoupon(${c.id})">Hapus</button>
                  </td>
                `;
                cTbody.appendChild(tr);
              });
            }
          }

          // Render Tab Briefcase
          const bContainer = document.getElementById('briefcaseContainer');
          bContainer.innerHTML = '';
          data.masters.forEach(m => {
            const card = document.createElement('div');
            card.className = 'briefcase-item';
            card.innerHTML = `
              <div>
                <div class="item-header">
                  <div>
                    <div class="item-title">${m.title}</div>
                    <div style="font-size:11px; color:#64748b; font-family:monospace; margin-top:4px;">${m.file}</div>
                  </div>
                  <span class="item-badge badge-ready">✓ TERSEDIA</span>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; font-size:12px; margin-bottom:14px;">
                  <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span style="color:#64748b;">Ukuran Berkas:</span> <b>${m.size_formatted}</b></div>
                  <div style="display:flex; justify-content:space-between;"><span style="color:#64748b;">Harga Katalog:</span> <b>Rp ${Number(m.price).toLocaleString('id-ID')}</b></div>
                </div>
              </div>
              <div style="display:flex; gap:8px;">
                <button class="btn-primary-form" style="flex:1; text-align:center; padding:8px 12px; font-size:12px;" onclick="openProductEditor('${m.id}')">✏️ Edit Konten</button>
                <button class="btn-action-sm" style="flex:1; text-align:center;" onclick="testBookDrm('${m.id}')">⚡ Uji Stempel DRM</button>
              </div>
            `;
            bContainer.appendChild(card);
          });

          // Render Siswa LMS Akademi
          if (data.lms_students) {
            renderLmsTable(data.lms_students);
            const countS = document.getElementById('lmsCountStudents');
            if (countS) countS.innerText = data.lms_students.length;
          }
          if (data.lms_discussions) {
            renderLmsDiscussions(data.lms_discussions);
            const countD = document.getElementById('lmsCountDiscussions');
            if (countD) countD.innerText = data.lms_discussions.length;
          }
          if (data.lms_quizzes) {
            renderLmsQuizzes(data.lms_quizzes);
            const countQ = document.getElementById('lmsCountQuizzes');
            if (countQ) countQ.innerText = data.lms_quizzes.length;
          }
          if (data.lms_modules) cachedLmsModules = data.lms_modules;
          if (data.lms_lessons) {
            cachedLmsLessons = data.lms_lessons;
            renderLmsLessons(data.lms_modules, data.lms_lessons);
            const countL = document.getElementById('lmsCountLessons');
            if (countL) countL.innerText = data.lms_lessons.length;
          }
          if (data.kpi && data.kpi.lms_enrolled !== undefined) {
            const badge = document.getElementById('lmsCounterBadge');
            if (badge) badge.innerText = `${data.kpi.lms_enrolled} Terdaftar • ${data.kpi.lms_passed} Lulus`;
          }
        }
      } catch (e) {
        console.error('Load data error:', e);
      }
    }

    // Modal Kupon
    function openCouponModal() {
      document.getElementById('couponId').value = '0';
      document.getElementById('couponCode').value = '';
      document.getElementById('couponApplicableTo').value = 'ALL';
      document.getElementById('couponType').value = 'PERCENT';
      document.getElementById('couponVal').value = '';
      document.getElementById('couponMinOrder').value = '0';
      document.getElementById('couponLimit').value = '100';
      document.getElementById('couponExpires').value = '';
      document.getElementById('couponActive').value = '1';
      document.getElementById('couponModalTitle').innerText = 'Buat Kupon Promo Baru';
      document.getElementById('couponModal').style.display = 'flex';
    }

    function editCoupon(id) {
      const c = cachedCoupons.find(x => x.id == id);
      if (!c) return;
      document.getElementById('couponId').value = c.id;
      document.getElementById('couponCode').value = c.code;
      document.getElementById('couponApplicableTo').value = c.applicable_to || 'ALL';
      document.getElementById('couponType').value = c.discount_type || 'PERCENT';
      document.getElementById('couponVal').value = Math.round(Number(c.discount_value));
      document.getElementById('couponMinOrder').value = Math.round(Number(c.min_order || 0));
      document.getElementById('couponLimit').value = c.usage_limit || 100;
      document.getElementById('couponExpires').value = c.expires_at || '';
      document.getElementById('couponActive').value = (c.is_active !== undefined ? c.is_active : 1);
      document.getElementById('couponModalTitle').innerText = 'Edit Kupon: ' + c.code;
      document.getElementById('couponModal').style.display = 'flex';
    }

    function closeCouponModal() {
      document.getElementById('couponModal').style.display = 'none';
    }

    async function handleSaveCoupon(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitCoupon');
      btn.disabled = true;
      btn.innerText = 'Menyimpan...';

      const formData = new FormData(document.getElementById('couponForm'));
      formData.append('action', 'save_coupon');

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerText = '💾 Simpan Kupon';
        if (data.success) {
          alert('✓ ' + data.message);
          closeCouponModal();
          loadAdminData();
        } else {
          alert('Gagal: ' + data.error);
        }
      } catch (err) {
        btn.disabled = false;
        btn.innerText = '💾 Simpan Kupon';
        alert('Kesalahan jaringan.');
      }
    }

    async function toggleCoupon(id) {
      const formData = new FormData();
      formData.append('action', 'toggle_coupon');
      formData.append('id', id);

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        if (data.success) loadAdminData();
      } catch (e) {
        alert('Kesalahan jaringan.');
      }
    }

    async function deleteCoupon(id) {
      if (!confirm('Yakin ingin menghapus kupon ini?')) return;
      const formData = new FormData();
      formData.append('action', 'delete_coupon');
      formData.append('id', id);

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          alert(data.message);
          loadAdminData();
        }
      } catch (e) {
        alert('Kesalahan jaringan.');
      }
    }

    // Manajemen Kurikulum & Siswa LMS Akademi
    let cachedLmsStudents = [];
    let cachedLmsModules = [];
    let cachedLmsLessons = [];

    function switchLmsSubTab(sub, btn) {
      document.querySelectorAll('#tabLms .tab-btn').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      else {
        const target = document.getElementById('btnSubLms' + sub.charAt(0).toUpperCase() + sub.slice(1));
        if (target) target.classList.add('active');
      }

      document.getElementById('lmsSubStudents').style.display = (sub === 'students') ? 'block' : 'none';
      document.getElementById('lmsSubLessons').style.display = (sub === 'lessons') ? 'block' : 'none';
      document.getElementById('lmsSubDiscussions').style.display = (sub === 'discussions') ? 'block' : 'none';
      document.getElementById('lmsSubQuizzes').style.display = (sub === 'quizzes') ? 'block' : 'none';
    }

    // Render Daftar Modul & Bab Pelajaran LMS
    function renderLmsLessons(modules, lessons) {
      const container1 = document.getElementById('lmsLessonsContainer');
      const container2 = document.getElementById('lmsLessonsContainerDedicated');
      const countOverview = document.getElementById('courseOverviewLessons');
      if (countOverview && lessons) countOverview.innerText = `${lessons.length} Bab`;

      const containers = [container1, container2].filter(c => c !== null);
      if (containers.length === 0) return;

      if (!lessons || lessons.length === 0) {
        containers.forEach(c => {
          c.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;">Belum ada bab materi kurikulum yang terdaftar.</div>';
        });
        return;
      }

      const mods = (modules && modules.length > 0) ? modules : [
        { id: 1, title: 'Modul 1: Fondasi ISMS & Paradigma Baru ISO 27001:2022' },
        { id: 2, title: 'Modul 2: Eksekusi Klausul 4 hingga 10 (Siklus PDCA)' },
        { id: 3, title: 'Modul 3: Penerapan 93 Kontrol Annex A (4 Kategori)' },
        { id: 4, title: 'Modul 4: Kesiapan Audit Sertifikasi & Dokumen SoA' }
      ];

      containers.forEach(c => { c.innerHTML = ''; });

      mods.forEach(m => {
        const modLessons = lessons.filter(l => l.module_id == m.id);

        let lessonsRows = '';
        if (modLessons.length === 0) {
          lessonsRows = '<div style="font-size:12px; color:#94a3b8; font-style:italic; padding:10px 0;">Belum ada bab materi pada modul ini.</div>';
        } else {
          modLessons.forEach(l => {
            const hasHtml = l.content_html && l.content_html.trim().length > 0;
            const previewBadge = (l.is_free_preview == 1) 
              ? '<span style="font-size:10px; font-weight:800; color:#0284c7; background:#e0f2fe; padding:2px 8px; border-radius:6px;">🔓 Preview Gratis</span>' 
              : '<span style="font-size:10px; font-weight:800; color:#64748b; background:#f1f5f9; padding:2px 8px; border-radius:6px;">🔒 Terkunci</span>';
            const htmlBadge = hasHtml
              ? '<span style="font-size:10px; font-weight:800; color:#059669; background:#ecfdf5; padding:2px 8px; border-radius:6px;">✨ Ada Catatan</span>'
              : '<span style="font-size:10px; font-weight:700; color:#94a3b8; background:#f8fafc; padding:2px 8px; border-radius:6px;">- Tanpa Catatan</span>';

            lessonsRows += `
              <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; border-bottom:1px solid #f1f5f9; gap:12px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:260px;">
                  <span style="width:28px; height:28px; border-radius:8px; background:#f1f5f9; color:#475569; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">${l.sort_order || 1}</span>
                  <div>
                    <div style="font-weight:800; font-size:14px; color:#0f172a;">${l.title}</div>
                    <div style="font-size:11px; color:#64748b; display:flex; gap:8px; align-items:center; margin-top:3px; flex-wrap:wrap;">
                      <span>⏱️ ${l.duration || '15:00'}</span>
                      <span>📺 ${l.video_id ? 'ID: ' + l.video_id : 'Belum ada video'}</span>
                      ${previewBadge}
                      ${htmlBadge}
                    </div>
                  </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <a href="https://restiamoegiono.com/course/learn.php?lesson=${encodeURIComponent(l.slug)}" target="_blank" class="btn-action-sm" style="padding:7px 12px; font-size:11px; text-decoration:none;" title="Pratinjau di Ruang Belajar Siswa">
                    👁️ Pratinjau ↗
                  </a>
                  <button type="button" class="btn-primary-form" style="padding:7px 14px; font-size:12px; font-weight:800; display:inline-flex; align-items:center; gap:4px;" onclick="openLmsLessonModal(${l.id})">
                    ✏️ Edit Materi
                  </button>
                  <button type="button" class="btn-action-sm" style="padding:7px 10px; font-size:12px; color:#dc2626; border-color:#fecaca;" onclick="deleteLmsLesson(${l.id}, '${(l.title || '').replace(/'/g, "\\'")}')" title="Hapus Bab">
                    🗑️
                  </button>
                </div>
              </div>
            `;
          });
        }

        const cardHtml = `
          <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.02);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
              <div style="font-weight:900; font-size:14px; color:#0284c7; text-transform:uppercase; letter-spacing:0.03em;">
                ${m.title}
              </div>
              <span style="font-size:11px; font-weight:800; color:#475569; background:#f1f5f9; padding:3px 10px; border-radius:20px;">
                ${modLessons.length} Bab
              </span>
            </div>
            <div>${lessonsRows}</div>
          </div>
        `;

        containers.forEach(c => {
          c.insertAdjacentHTML('beforeend', cardHtml);
        });
      });
    }

    // Modal Editor Materi Bab LMS (Quill WYSIWYG)
    function openLmsLessonModal(id) {
      initQuill();
      const modal = document.getElementById('lmsLessonModal');
      const titleEl = document.getElementById('lmsLessonModalTitle');

      // Isi dropdown modul
      const sel = document.getElementById('lmsLessonModuleId');
      if (sel && cachedLmsModules && cachedLmsModules.length > 0) {
        sel.innerHTML = '';
        cachedLmsModules.forEach(m => {
          const opt = document.createElement('option');
          opt.value = m.id;
          opt.innerText = m.title;
          sel.appendChild(opt);
        });
      }

      if (id > 0) {
        const l = cachedLmsLessons.find(x => x.id == id);
        if (!l) return;
        document.getElementById('lmsLessonId').value = l.id;
        document.getElementById('lmsLessonCourseId').value = l.course_id || 1;
        document.getElementById('lmsLessonTitle').value = l.title || '';
        document.getElementById('lmsLessonModuleId').value = l.module_id || 1;
        document.getElementById('lmsLessonSlug').value = l.slug || '';
        document.getElementById('lmsLessonDuration').value = l.duration || '15:00';
        document.getElementById('lmsLessonVideoId').value = l.video_id || '';
        document.getElementById('lmsLessonIsPreview').value = l.is_free_preview ? 1 : 0;
        document.getElementById('lmsLessonSortOrder').value = l.sort_order || 1;
        document.getElementById('lmsLessonAttachmentName').value = l.attachment_name || '';
        document.getElementById('lmsLessonAttachmentUrl').value = l.attachment_url || '';

        if (lmsLessonQuill) {
          lmsLessonQuill.root.innerHTML = l.content_html || '';
        }
        titleEl.innerText = 'Edit Materi Bab: ' + l.title;
      } else {
        document.getElementById('lmsLessonId').value = '0';
        document.getElementById('lmsLessonCourseId').value = '1';
        document.getElementById('lmsLessonTitle').value = '';
        document.getElementById('lmsLessonSlug').value = '';
        document.getElementById('lmsLessonDuration').value = '15:00';
        document.getElementById('lmsLessonVideoId').value = '';
        document.getElementById('lmsLessonIsPreview').value = '0';
        document.getElementById('lmsLessonSortOrder').value = (cachedLmsLessons.length + 1);
        document.getElementById('lmsLessonAttachmentName').value = '';
        document.getElementById('lmsLessonAttachmentUrl').value = '';

        if (lmsLessonQuill) {
          lmsLessonQuill.root.innerHTML = '<p>Tuliskan ringkasan materi, checklist, dan panduan belajar bab ini...</p>';
        }
        titleEl.innerText = 'Tambah Bab / Materi Pelatihan Baru';
      }

      modal.style.display = 'flex';
    }

    function closeLmsLessonModal() {
      const modal = document.getElementById('lmsLessonModal');
      if (modal) modal.style.display = 'none';
    }

    async function handleSaveLmsLesson(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitLmsLesson');
      btn.disabled = true;
      btn.innerText = 'Menyimpan...';

      const formData = new FormData(document.getElementById('lmsLessonForm'));
      formData.append('action', 'save_lms_lesson');
      if (lmsLessonQuill) {
        formData.set('content_html', lmsLessonQuill.root.innerHTML);
      }

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerText = '💾 Simpan Materi';

        if (data.success) {
          alert('✓ ' + data.message);
          closeLmsLessonModal();
          loadAdminData();
        } else {
          alert('Gagal: ' + (data.error || 'Terjadi kesalahan sistem.'));
        }
      } catch (err) {
        btn.disabled = false;
        btn.innerText = '💾 Simpan Materi';
        alert('Kesalahan koneksi saat menyimpan materi.');
      }
    }

    async function deleteLmsLesson(id, title) {
      if (!confirm(`Hapus bab "${title}" dari kurikulum?`)) return;
      const formData = new FormData();
      formData.append('action', 'delete_lms_lesson');
      formData.append('id', id);

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          alert('✓ ' + data.message);
          loadAdminData();
        } else {
          alert('Gagal: ' + data.error);
        }
      } catch (e) {
        alert('Kesalahan jaringan.');
      }
    }

    function renderLmsDiscussions(discussions) {
      const container = document.getElementById('lmsDiscussionsContainer');
      if (!container) return;
      if (!discussions || discussions.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;">Belum ada pertanyaan diskusi yang diajukan.</div>';
        return;
      }

      container.innerHTML = '';
      discussions.forEach(d => {
        const item = document.createElement('div');
        item.style.cssText = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px; margin-bottom:12px;';
        
        let repliesHtml = '';
        if (d.replies && d.replies.length > 0) {
          d.replies.forEach(r => {
            repliesHtml += `
              <div style="background:#ffffff; border-left:3px solid #0284c7; border-radius:8px; padding:10px 14px; margin-top:8px; font-size:12px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                  <b>💬 ${r.sender_name} <span class="status-badge status-paid" style="font-size:9px;">${r.sender_role}</span></b>
                  <span style="font-size:10px; color:#64748b;">${r.created_at ? r.created_at.substring(0, 16) : ''}</span>
                </div>
                <div style="color:#334155; line-height:1.5;">${(r.reply_text || '').replace(/\\n/g, '<br>')}</div>
              </div>
            `;
          });
        }

        item.innerHTML = `
          <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12px;">
            <div><b style="color:#0f172a;">${d.user_name}</b> <span style="color:#64748b; font-size:11px;">(${d.user_email})</span></div>
            <div><span style="color:#0284c7; font-weight:700;">Modul: ${d.lesson_title || 'Umum'}</span> • <span style="color:#64748b;">${d.created_at ? d.created_at.substring(0, 16) : ''}</span></div>
          </div>
          <div style="font-weight:800; font-size:14px; color:#0f172a; margin-bottom:4px;">${d.question_title}</div>
          <div style="font-size:13px; color:#334155; line-height:1.6; margin-bottom:10px;">${(d.question_text || '').replace(/\\n/g, '<br>')}</div>
          ${repliesHtml}
          <div style="margin-top:12px; display:flex; gap:8px;">
            <input type="text" id="replyInput_${d.id}" placeholder="Tuliskan klarifikasi atau jawaban resmi instruktur..." style="flex:1; border:1px solid #cbd5e1; border-radius:8px; padding:8px 12px; font-size:12px; outline:none;">
            <button class="btn-primary-form" style="padding:8px 16px; font-size:12px; white-space:nowrap;" onclick="submitReplyDiscussion(${d.id})">Kirim Balasan</button>
          </div>
        `;
        container.appendChild(item);
      });
    }

    async function submitReplyDiscussion(discId) {
      const input = document.getElementById('replyInput_' + discId);
      if (!input || !input.value.trim()) {
        alert('Harap ketik jawaban balasan instruktur.');
        return;
      }
      const replyText = input.value.trim();

      try {
        const fd = new FormData();
        fd.append('action', 'reply_lms_discussion');
        fd.append('discussion_id', discId);
        fd.append('reply_text', replyText);
        fd.append('sender_name', 'Restia Moegiono');

        const res = await fetch('/api/admin-action.php?action=reply_lms_discussion', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: fd
        });
        const result = await res.json();
        if (result.success) {
          showToast('✓ ' + (result.message || 'Jawaban instruktur berhasil dikirim!'));
          input.value = '';
          loadAdminData();
        } else {
          alert('Gagal: ' + (result.error || 'Terjadi kesalahan sistem.'));
        }
      } catch (e) {
        alert('Kesalahan jaringan: ' + e.message);
      }
    }

    function renderLmsQuizzes(quizzes) {
      const tbody = document.getElementById('lmsQuizzesTbody');
      if (!tbody) return;
      if (!quizzes || quizzes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">Belum ada rekaman riwayat kuis siswa.</td></tr>';
        return;
      }

      tbody.innerHTML = '';
      quizzes.forEach(q => {
        const tr = document.createElement('tr');
        const pct = q.total_questions > 0 ? Math.round((q.score / q.total_questions) * 100) : 0;
        const isPassed = (q.is_passed == 1 || pct >= 80);
        const statusBadge = isPassed
          ? '<span class="status-badge" style="background:#dcfce7; color:#166534; border:1px solid #86efac; font-weight:700;">✓ LULUS (Passed)</span>'
          : '<span class="status-badge" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; font-weight:700;">✕ REMEDIAL</span>';

        tr.innerHTML = `
          <td><b>${q.user_email}</b></td>
          <td>${q.course_title || 'Masterclass ISO 27001'}</td>
          <td><b>${q.score}</b> dari ${q.total_questions} Soal</td>
          <td><b style="color:${isPassed ? '#059669' : '#dc2626'}; font-size:14px;">${pct}%</b></td>
          <td style="text-align:right;">${statusBadge}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    function renderLmsTable(students) {
      cachedLmsStudents = students || [];
      const tbody = document.getElementById('lmsTbody');
      if (!tbody) return;
      if (cachedLmsStudents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px; color:#64748b;">Belum ada siswa terdaftar di akademi.</td></tr>';
        return;
      }

      tbody.innerHTML = '';
      cachedLmsStudents.forEach(s => {
        const tr = document.createElement('tr');
        const isCompleted = (s.progress_percent >= 100);
        const badgeStatus = isCompleted
          ? '<span class="status-badge" style="background:#dcfce7; color:#166534; border:1px solid #86efac; font-weight:700;">🎓 LULUS (100%)</span>'
          : `<span class="status-badge" style="background:#f0f9ff; color:#0369a1; border:1px solid #bae6fd; font-weight:700;">📖 BELAJAR (${s.progress_percent}%)</span>`;

        const certLink = isCompleted
          ? `<a href="https://restiamoegiono.com/course/certificate.php" target="_blank" style="display:inline-block; margin-top:4px; font-size:11px; font-weight:700; color:#0284c7; text-decoration:none;">Lihat Sertifikat ↗</a>`
          : `<span style="font-size:11px; color:#94a3b8;">Belum Lulus</span>`;

        tr.innerHTML = `
          <td>
            <div style="font-weight:800; color:#0f172a; font-size:14px;">${s.user_name || 'Peserta'}</div>
            <div style="font-size:12px; color:#64748b; font-family:monospace; margin-top:2px;">${s.user_email}</div>
            <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Ref: ${s.order_invoice || '-'}</div>
          </td>
          <td>
            <div style="font-weight:700; color:#1e293b; font-size:13px;">${s.course_title}</div>
            <div style="font-size:11px; color:#64748b; margin-top:2px;">Terdaftar: ${s.enrolled_at ? s.enrolled_at.substring(0, 16) : '-'}</div>
          </td>
          <td>
            <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
              <span style="font-weight:700; color:#334155;">${s.completed_lessons} dari ${s.total_lessons} Modul</span>
              <span style="font-weight:800; color:#0284c7;">${s.progress_percent}%</span>
            </div>
            <div style="width:100%; height:7px; background:#e2e8f0; border-radius:10px; overflow:hidden;">
              <div style="width:${s.progress_percent}%; height:100%; background:linear-gradient(90deg, #0284c7, #38bdf8); border-radius:10px; transition:width 0.3s ease;"></div>
            </div>
          </td>
          <td style="text-align:right;">
            ${badgeStatus}
            <div>${certLink}</div>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function openEnrollManualModal() {
      const modal = document.getElementById('enrollManualModal');
      if (modal) modal.style.display = 'flex';
    }

    function closeEnrollManualModal() {
      const modal = document.getElementById('enrollManualModal');
      if (modal) modal.style.display = 'none';
    }

    async function submitEnrollManual(e) {
      e.preventDefault();
      const name = document.getElementById('manualStudentName').value.trim();
      const email = document.getElementById('manualStudentEmail').value.trim();
      const courseId = document.getElementById('manualCourseId').value;
      const btn = document.getElementById('btnSubmitEnroll');

      if (!name || !email) {
        alert('Harap isi nama dan email peserta.');
        return;
      }

      btn.disabled = true;
      btn.innerText = 'Menyimpan...';

      try {
        const fd = new FormData();
        fd.append('name', name);
        fd.append('email', email);
        fd.append('course_id', courseId);

        const res = await fetch('/api/admin-action.php?action=enroll_lms_student_manual', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: fd
        });
        const result = await res.json();
        if (result.success) {
          showToast('✓ ' + (result.message || 'Siswa berhasil didaftarkan!'));
          closeEnrollManualModal();
          document.getElementById('enrollManualForm').reset();
          loadAdminData();
        } else {
          alert('Gagal: ' + (result.error || 'Terjadi kesalahan'));
        }
      } catch (err) {
        alert('Kesalahan jaringan: ' + err.message);
      } finally {
        btn.disabled = false;
        btn.innerText = '🎓 Daftarkan Peserta';
      }
    }

    function updateSlugLivePreview(slug) {
      const cleanSlug = (slug || '').toLowerCase().replace(/[^a-z0-9\-]+/g, '-').replace(/^-+|-+$/g, '');
      const span = document.getElementById('slugLiveUrl');
      if (span) {
        span.innerText = cleanSlug ? `https://store.restiamoegiono.com/produk/${cleanSlug}` : 'https://store.restiamoegiono.com/produk/...';
      }
    }

    function autoSuggestSlug(title) {
      const editId = document.getElementById('editId').value;
      const slugInput = document.getElementById('editSlug');
      if (!editId || !slugInput.value) {
        const slug = (title || '')
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
        updateSlugLivePreview(slug);
      }
    }

    async function deleteProduct(id, title) {
      if (!confirm(`Apakah Anda yakin ingin menghapus produk '${title}' (ID: ${id}) dari katalog toko? Tindakan ini permanen.`)) return;
      try {
        const formData = new FormData();
        formData.append('action', 'delete_product');
        formData.append('id', id);
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          alert('✓ ' + (data.message || 'Produk berhasil dihapus!'));
          loadAdminData();
        } else {
          alert('Gagal menghapus: ' + (data.error || 'Terjadi kesalahan sistem.'));
        }
      } catch (e) {
        alert('Kesalahan koneksi saat menghapus produk.');
      }
    }

    // Modal Editor Produk (Support Tambah Baru atau Edit)
    function openProductEditor(id) {
      initQuill();
      const isNew = !id;
      let p = null;
      if (!isNew) {
        p = cachedProducts.find(x => x.id === id);
        if (!p) {
          alert('Produk tidak ditemukan!');
          return;
        }
      }

      document.getElementById('editorModalTitle').innerText = isNew ? '➕ Tambah Produk E-Book Baru' : ('Edit Produk: ' + p.title);
      document.getElementById('editId').value = isNew ? '' : p.id;
      document.getElementById('editSlug').value = isNew ? '' : (p.slug || '');
      document.getElementById('editSubtitle').value = isNew ? '' : (p.subtitle || '');
      document.getElementById('editTitle').value = isNew ? '' : (p.title || '');
      document.getElementById('editCategory').value = isNew ? 'ISO & Standar Keamanan' : (p.category || '');
      document.getElementById('editPrice').value = isNew ? 199000 : Math.round(Number(p.price || 0));
      document.getElementById('editOldPrice').value = isNew ? 350000 : Math.round(Number(p.old_price || 0));
      document.getElementById('editBadge').value = isNew ? 'Edisi Baru' : (p.badge || '');
      document.getElementById('editVersion').value = isNew ? 'Edisi 2026' : (p.version || '');
      document.getElementById('editPages').value = isNew ? 150 : (p.pages || '');
      document.getElementById('editIsActive').value = isNew ? 1 : (p.is_active !== undefined ? p.is_active : 1);

      // Metadata Teknis
      document.getElementById('editFileFormat').value = isNew ? 'PDF (DRM Watermark)' : (p.file_format || 'PDF (DRM Watermark)');
      document.getElementById('editFileSize').value = isNew ? '12.5 MB' : (p.file_size || '');
      document.getElementById('editLanguage').value = isNew ? 'Bahasa Indonesia' : (p.language || 'Bahasa Indonesia');
      document.getElementById('editEdition').value = isNew ? 'Edisi Pertama 2026' : (p.edition || '');
      document.getElementById('editIsbn').value = isNew ? '' : (p.isbn || '');
      document.getElementById('editOrderIndex').value = isNew ? 0 : (p.order_index || 0);

      // Tab Silabus, Target, Manfaat
      document.getElementById('editTableOfContents').value = isNew ? '' : (p.table_of_contents || '');
      document.getElementById('editTargetAudience').value = isNew ? '' : (p.target_audience || '');
      document.getElementById('editBenefits').value = isNew ? '' : (p.benefits || '');

      updateSlugLivePreview(isNew ? '' : (p.slug || p.id));

      // Reset state file upload & preview
      cancelCoverReplace();
      cancelPdfReplace();

      // Set Cover Image Info
      const coverSrc = (!isNew && p.cover_image) ? p.cover_image : '/images/book-cover-450.webp';
      document.getElementById('editCoverImage').value = isNew ? '' : (p.cover_image || '');
      document.getElementById('currentCoverPath').innerText = (!isNew && p.cover_image) ? p.cover_image : '(Default: /images/book-cover-450.webp)';
      document.getElementById('coverPreviewImg').src = coverSrc;

      // Set Master PDF Info
      document.getElementById('editMasterFile').value = isNew ? '' : (p.master_file || '');
      document.getElementById('currentPdfName').innerText = (!isNew && p.master_file) ? p.master_file : '(Belum ada berkas master)';
      if (!isNew) {
        const mInfo = cachedMasters.find(m => m.file === p.master_file || m.id === p.id);
        if (mInfo && mInfo.exists) {
          document.getElementById('currentPdfSize').innerText = `Ukuran: ${mInfo.size_formatted} • Berkas siap untuk otomatisasi DRM`;
          document.getElementById('currentPdfBadge').className = 'status-badge status-paid';
          document.getElementById('currentPdfBadge').innerText = '✓ Berkas Siap';
        } else {
          document.getElementById('currentPdfSize').innerText = 'Berkas tersimpan di storage server';
          document.getElementById('currentPdfBadge').className = 'status-badge status-paid';
          document.getElementById('currentPdfBadge').innerText = '✓ Tersimpan';
        }
      } else {
        document.getElementById('currentPdfSize').innerText = 'Silakan pilih berkas PDF untuk diunggah';
        document.getElementById('currentPdfBadge').className = 'status-badge status-inactive';
        document.getElementById('currentPdfBadge').innerText = 'Menunggu Unggahan';
      }

      const descHtml = (!isNew && p.description) ? p.description : '';
      if (productQuill) {
        productQuill.root.innerHTML = descHtml;
        const textLen = productQuill.getText().trim().length;
        document.getElementById('editorStats').innerText = textLen + ' karakter';
      }
      document.getElementById('rawHtmlTextarea').value = descHtml;

      if (isHtmlMode) {
        toggleHtmlSourceMode();
      }

      document.getElementById('productEditorModal').style.display = 'flex';
    }

    function closeProductEditor() {
      document.getElementById('productEditorModal').style.display = 'none';
    }

    // Live preview saat gambar baru dipilih
    function handleCoverSelected(input) {
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('coverPreviewImg').src = e.target.result;
        };
        reader.readAsDataURL(file);

        const sizeFmt = (file.size / 1024).toFixed(1) + ' KB';
        const infoEl = document.getElementById('newCoverSelectedInfo');
        infoEl.style.display = 'block';
        infoEl.innerText = `✓ Berkas baru dipilih: ${file.name} (${sizeFmt})`;
        document.getElementById('coverStatusText').innerText = 'Gambar baru siap disimpan untuk menggantikan gambar saat ini';
        document.getElementById('coverStatusText').style.color = '#059669';
        document.getElementById('btnCancelCoverReplace').style.display = 'inline-block';
      }
    }

    function cancelCoverReplace() {
      document.getElementById('uploadCoverImage').value = '';
      const origCover = document.getElementById('editCoverImage').value || '/images/book-cover-450.webp';
      document.getElementById('coverPreviewImg').src = origCover;
      document.getElementById('newCoverSelectedInfo').style.display = 'none';
      document.getElementById('coverStatusText').innerText = 'Gambar sampul yang aktif saat ini';
      document.getElementById('coverStatusText').style.color = '#64748b';
      document.getElementById('btnCancelCoverReplace').style.display = 'none';
    }

    // Info saat berkas PDF baru dipilih
    function handlePdfSelected(input) {
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        const infoEl = document.getElementById('newPdfSelectedInfo');
        infoEl.style.display = 'block';
        infoEl.innerText = `✓ Berkas baru dipilih: ${file.name} (${sizeMb}) siap menggantikan master saat ini`;
        document.getElementById('btnCancelPdfReplace').style.display = 'inline-block';
      }
    }

    function cancelPdfReplace() {
      document.getElementById('uploadMasterPdf').value = '';
      document.getElementById('newPdfSelectedInfo').style.display = 'none';
      document.getElementById('btnCancelPdfReplace').style.display = 'none';
    }

    function toggleHtmlSourceMode() {
      const qWrap = document.getElementById('quillWrapper');
      const rawText = document.getElementById('rawHtmlTextarea');
      const btn = document.getElementById('btnToggleHtml');

      if (!isHtmlMode) {
        const html = productQuill ? productQuill.root.innerHTML : '';
        rawText.value = html;
        qWrap.style.display = 'none';
        rawText.style.display = 'block';
        btn.innerText = '👁️ Mode Visual Editor';
        btn.style.background = '#e0f2fe';
        btn.style.color = '#0284c7';
        isHtmlMode = true;
      } else {
        const html = rawText.value;
        if (productQuill) {
          productQuill.root.innerHTML = html;
        }
        rawText.style.display = 'none';
        qWrap.style.display = 'block';
        btn.innerText = '</> Mode HTML Source';
        btn.style.background = '#f8fafc';
        btn.style.color = 'inherit';
        isHtmlMode = false;
      }
    }

    function insertEditorTemplate(type) {
      if (!productQuill) return;

      if (type === 'clear') {
        if (confirm('Kosongkan konten editor ini?')) {
          productQuill.root.innerHTML = '';
          document.getElementById('rawHtmlTextarea').value = '';
        }
        return;
      }

      let templateHtml = '';
      if (type === 'silabus') {
        templateHtml = `
          <h3>Ringkasan Silabus &amp; Bab Pembahasan:</h3>
          <ul>
            <li><strong>Bab 1:</strong> Konteks Tata Kelola &amp; Landasan Regulasi Nasional</li>
            <li><strong>Bab 2:</strong> Analisis Kesenjangan (Gap Analysis) &amp; Penilaian Risiko</li>
            <li><strong>Bab 3:</strong> Langkah Praktis Implementasi &amp; Standard Operating Procedure (SOP)</li>
            <li><strong>Bab 4:</strong> Mekanisme Audit Internal, Monitoring &amp; Evaluasi Kepatuhan</li>
          </ul>
        `;
      } else if (type === 'target') {
        templateHtml = `
          <h3>Siapa yang Wajib Memiliki E-Book Ini?</h3>
          <ul>
            <li>CISO, IT Security Officer, dan Tim Information Security Korporasi</li>
            <li>Data Protection Officer (DPO) &amp; Compliance / Legal Counsel</li>
            <li>Auditor Internal, Konsultan Teknologi, dan Penilai Independen</li>
          </ul>
        `;
      } else if (type === 'instrumen') {
        templateHtml = `
          <blockquote>
            <strong>Bonus Instrumen Kerja:</strong> Pembelian dokumen ini dilengkapi dengan matriks checklist siap pakai, lembar kerja penilaian mandiri, dan format pelaporan resmi yang dapat disesuaikan langsung dengan kebutuhan instansi Anda.
          </blockquote>
        `;
      }

      if (isHtmlMode) {
        document.getElementById('rawHtmlTextarea').value += "\n" + templateHtml;
      } else {
        const cur = productQuill.root.innerHTML;
        productQuill.root.innerHTML = cur + templateHtml;
      }
    }

    async function submitProductForm() {
      const btn = document.getElementById('btnSaveProduct');
      btn.disabled = true;
      btn.innerText = 'Menyimpan & Mengunggah...';

      const descHtml = isHtmlMode ? document.getElementById('rawHtmlTextarea').value : (productQuill ? productQuill.root.innerHTML : document.getElementById('rawHtmlTextarea').value);

      const formData = new FormData();
      formData.append('action', 'save_product');
      formData.append('id', document.getElementById('editId').value);
      formData.append('slug', document.getElementById('editSlug').value);
      formData.append('subtitle', document.getElementById('editSubtitle').value);
      formData.append('title', document.getElementById('editTitle').value);
      formData.append('category', document.getElementById('editCategory').value);
      formData.append('price', document.getElementById('editPrice').value);
      formData.append('old_price', document.getElementById('editOldPrice').value);
      formData.append('badge', document.getElementById('editBadge').value);
      formData.append('version', document.getElementById('editVersion').value);
      formData.append('pages', document.getElementById('editPages').value);
      formData.append('is_active', document.getElementById('editIsActive').value);
      formData.append('file_format', document.getElementById('editFileFormat').value);
      formData.append('file_size', document.getElementById('editFileSize').value);
      formData.append('language', document.getElementById('editLanguage').value);
      formData.append('edition', document.getElementById('editEdition').value);
      formData.append('isbn', document.getElementById('editIsbn').value);
      formData.append('order_index', document.getElementById('editOrderIndex').value);
      formData.append('master_file', document.getElementById('editMasterFile').value);
      formData.append('cover_image', document.getElementById('editCoverImage').value);
      formData.append('description', descHtml);
      formData.append('table_of_contents', document.getElementById('editTableOfContents').value);
      formData.append('target_audience', document.getElementById('editTargetAudience').value);
      formData.append('benefits', document.getElementById('editBenefits').value);

      // Lampirkan berkas gambar baru jika dipilih
      const coverInput = document.getElementById('uploadCoverImage');
      if (coverInput && coverInput.files && coverInput.files[0]) {
        formData.append('cover_file', coverInput.files[0]);
      }

      // Lampirkan berkas PDF master baru jika dipilih
      const pdfInput = document.getElementById('uploadMasterPdf');
      if (pdfInput && pdfInput.files && pdfInput.files[0]) {
        formData.append('master_pdf_file', pdfInput.files[0]);
      }

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerText = '💾 Simpan Perubahan Produk';

        if (data.success) {
          alert('✓ ' + (data.message || 'Produk berhasil disimpan!'));
          closeProductEditor();
          loadAdminData();
        } else {
          alert('Gagal menyimpan: ' + (data.error || 'Terjadi kesalahan sistem.'));
        }
      } catch (err) {
        btn.disabled = false;
        btn.innerText = '💾 Simpan Perubahan Produk';
        alert('Kesalahan jaringan saat mengunggah berkas.');
      }
    }

    async function fulfillManual(inv) {
      if (!confirm('Jalankan proses stempel watermark DRM dan kirim email sekarang untuk invoice ' + inv + '?')) return;
      try {
        const formData = new FormData();
        formData.append('action', 'fulfill_manual');
        formData.append('invoice', inv);

        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        alert(data.message || (data.success ? 'Berhasil diproses!' : 'Gagal: ' + data.error));
        loadAdminData();
      } catch (e) {
        alert('Kesalahan koneksi saat memproses.');
      }
    }

    async function handleTestDrm(e) {
      e.preventDefault();
      const btn = document.getElementById('btnRunDrm');
      btn.disabled = true;
      btn.innerText = 'Memproses Stempel PDF...';

      const formData = new FormData();
      formData.append('action', 'test_drm');
      formData.append('product_id', document.getElementById('simProduct').value);
      formData.append('name', document.getElementById('simName').value);
      formData.append('email', document.getElementById('simEmail').value);
      formData.append('org', document.getElementById('simOrg').value);

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerText = '⚡ Jalankan Stempel DRM & Dapatkan Unduhan PDF';

        if (data.success) {
          document.getElementById('simResultBox').style.display = 'block';
          document.getElementById('simResultMsg').innerText = `Berkas hasil (${Number(data.file_size).toLocaleString('id-ID')} bytes) siap diunduh dengan watermark personal.`;
          document.getElementById('simDownloadBtn').href = data.download_url;
        } else {
          alert('Gagal: ' + data.error);
        }
      } catch (err) {
        btn.disabled = false;
        btn.innerText = '⚡ Jalankan Stempel DRM & Dapatkan Unduhan PDF';
        alert('Kesalahan koneksi.');
      }
    }

    async function handleTestEmail(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSendTestEmail');
      btn.disabled = true;
      btn.innerText = 'Mengirimkan Email Berlampiran PDF...';

      const formData = new FormData();
      formData.append('action', 'test_email');
      formData.append('email', document.getElementById('testEmailTarget').value);
      formData.append('product_id', document.getElementById('testEmailProduct').value);

      try {
        const res = await fetch('/api/admin-action.php', {
          method: 'POST',
          headers: { 'X-Admin-Auth': ADMIN_KEY },
          body: formData
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerText = '✉️ Kirimkan E-Book Uji Coba Sekarang';
        alert(data.message || (data.success ? 'Email terkirim!' : 'Gagal: ' + data.error));
      } catch (err) {
        btn.disabled = false;
        btn.innerText = '✉️ Kirimkan E-Book Uji Coba Sekarang';
        alert('Kesalahan koneksi pengiriman email.');
      }
    }

    function testBookDrm(id) {
      switchTab('drm');
      document.getElementById('simProduct').value = id;
    }

    // Live preview saat gambar Hero 3D baru dipilih
    function handleHeroFileSelected(input) {
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('heroPreviewImg').src = e.target.result;
        };
        reader.readAsDataURL(file);

        const sizeFmt = (file.size / 1024).toFixed(1) + ' KB';
        const infoEl = document.getElementById('newHeroSelectedInfo');
        infoEl.style.display = 'block';
        infoEl.innerText = `✓ Gambar baru dipilih: ${file.name} (${sizeFmt})`;
        document.getElementById('btnCancelHeroReplace').style.display = 'block';
      }
    }

    function cancelHeroReplace() {
      document.getElementById('uploadHeroImage').value = '';
      const origHero = (cachedSettings && cachedSettings.hero_image) ? cachedSettings.hero_image : '/images/hero-book-420.webp';
      document.getElementById('heroPreviewImg').src = origHero;
      document.getElementById('newHeroSelectedInfo').style.display = 'none';
      document.getElementById('btnCancelHeroReplace').style.display = 'none';
    }

    // Helpers WYSIWYG & Visual Title Builder Hero
    function syncHeroDescTextarea() {
      if (heroDescQuill) {
        const html = heroDescQuill.root.innerHTML;
        const clean = (html === '<p><br></p>') ? '' : html;
        const txt = document.getElementById('rawHeroDescTextarea');
        if (txt) txt.value = clean;
      }
    }

    function toggleHeroDescHtml() {
      const qWrap = document.getElementById('quillHeroDescWrapper');
      const rawText = document.getElementById('rawHeroDescTextarea');
      const btn = document.getElementById('btnToggleHeroDescHtml');

      if (!isHeroDescHtmlMode) {
        syncHeroDescTextarea();
        qWrap.style.display = 'none';
        rawText.style.display = 'block';
        btn.innerText = '👁️ Mode Visual WYSIWYG';
        btn.style.background = '#e0f2fe';
        btn.style.color = '#0284c7';
        isHeroDescHtmlMode = true;
      } else {
        const html = rawText.value;
        if (heroDescQuill) {
          heroDescQuill.root.innerHTML = html;
        }
        rawText.style.display = 'none';
        qWrap.style.display = 'block';
        btn.innerText = '</> Mode HTML Source';
        btn.style.background = '#f8fafc';
        btn.style.color = 'inherit';
        isHeroDescHtmlMode = false;
      }
    }

    function formatHeroKeyword(keyword) {
      if (!heroDescQuill) return;
      let html = heroDescQuill.root.innerHTML;
      const escaped = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp(escaped, 'gi');
      if (regex.test(html)) {
        html = html.replace(new RegExp('(<b>|<strong>)?(' + escaped + ')(</b>|</strong>)?', 'gi'), function(match, open, text, close) {
          return '<b>' + text + '</b>';
        });
        heroDescQuill.root.innerHTML = html;
      } else {
        heroDescQuill.root.innerHTML = (html === '<p><br></p>' ? '' : html) + '<p><b>' + keyword + '</b></p>';
      }
      syncHeroDescTextarea();
    }

    function clearHeroDescFormat() {
      if (!heroDescQuill) return;
      const text = heroDescQuill.getText().trim();
      heroDescQuill.setText(text);
      syncHeroDescTextarea();
    }

    function setHeroTagPreset(text) {
      const input = document.getElementById('heroTagInput');
      if (input) {
        input.value = text;
        updateHeroTagPreview();
      }
    }

    function updateHeroTagPreview() {
      const input = document.getElementById('heroTagInput');
      const preview = document.getElementById('heroTagPreviewBadge');
      if (input && preview) {
        preview.innerText = input.value || 'LABEL HERO';
      }
    }

    function setTitlePreset(main, grad) {
      document.getElementById('titlePartMain').value = main;
      document.getElementById('titlePartGradient').value = grad;
      updateVisualTitlePreview();
    }

    function clearTitleGradient() {
      document.getElementById('titlePartGradient').value = '';
      updateVisualTitlePreview();
    }

    function updateVisualTitlePreview() {
      const main = document.getElementById('titlePartMain').value;
      const grad = document.getElementById('titlePartGradient').value;
      let finalHtml = '';
      let previewHtml = '';

      if (grad.trim()) {
        finalHtml = main.trim() + ' <span>' + grad.trim() + '</span>';
        previewHtml = escapeHtml(main.trim()) + ' <span style="background:linear-gradient(135deg, #0284c7, #2563eb); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">' + escapeHtml(grad.trim()) + '</span>';
      } else {
        finalHtml = main.trim();
        previewHtml = escapeHtml(main.trim());
      }

      document.getElementById('heroTitleInput').value = finalHtml;
      document.getElementById('titleLivePreviewBox').innerHTML = previewHtml || '<span style="color:#94a3b8; font-weight:normal;">(Ketik judul di atas...)</span>';
    }

    function syncFromRawTitle() {
      const raw = document.getElementById('heroTitleInput').value;
      const spanMatch = raw.match(/^(.*?)<span>(.*?)<\/span>(.*)$/i);
      if (spanMatch) {
        document.getElementById('titlePartMain').value = spanMatch[1].trim() + ' ';
        document.getElementById('titlePartGradient').value = spanMatch[2].trim();
      } else {
        document.getElementById('titlePartMain').value = raw.replace(/<[^>]+>/g, '').trim();
        document.getElementById('titlePartGradient').value = '';
      }
      document.getElementById('titleLivePreviewBox').innerHTML = raw.replace(/<span>/gi, '<span style="background:linear-gradient(135deg, #0284c7, #2563eb); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">');
    }

    function setVisualTitleFromHtml(html) {
      document.getElementById('heroTitleInput').value = html;
      const spanMatch = html.match(/^(.*?)<span>(.*?)<\/span>(.*)$/i);
      if (spanMatch) {
        document.getElementById('titlePartMain').value = spanMatch[1].trim() + (spanMatch[1].endsWith(' ') ? ' ' : ' ');
        document.getElementById('titlePartGradient').value = spanMatch[2].trim();
      } else {
        document.getElementById('titlePartMain').value = html.replace(/<[^>]+>/g, '').trim();
        document.getElementById('titlePartGradient').value = '';
      }
      updateVisualTitlePreview();
    }

    function toggleTitleMode() {
      const visualBox = document.getElementById('titleVisualBuilder');
      const rawBox = document.getElementById('titleRawBox');
      const btn = document.getElementById('btnToggleTitleMode');

      if (!isTitleRawMode) {
        visualBox.style.display = 'none';
        rawBox.style.display = 'block';
        btn.innerText = '👁️ Mode Visual Builder';
        btn.style.background = '#e0f2fe';
        btn.style.color = '#0284c7';
        isTitleRawMode = true;
      } else {
        syncFromRawTitle();
        rawBox.style.display = 'none';
        visualBox.style.display = 'block';
        btn.innerText = '</> Mode Kode HTML';
        btn.style.background = '#f8fafc';
        btn.style.color = 'inherit';
        isTitleRawMode = false;
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    async function handleSaveHero(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSaveHeroSettings');
      btn.disabled = true;
      btn.innerText = 'Menyimpan & Mengunggah...';

      // Pastikan data deskripsi tersinkron dari WYSIWYG Quill
      syncHeroDescTextarea();

      // Pastikan data judul tersinkron dari Visual Builder
      if (!isTitleRawMode) {
        updateVisualTitlePreview();
      }

      const form = document.getElementById('heroSettingsForm');
      const formData = new FormData(form);
      formData.append('admin_key', ADMIN_KEY);

      // Pastikan hero_desc mengambil data visual terbaru
      const descVal = isHeroDescHtmlMode 
        ? document.getElementById('rawHeroDescTextarea').value 
        : (heroDescQuill ? heroDescQuill.root.innerHTML : document.getElementById('rawHeroDescTextarea').value);
      formData.set('hero_desc', descVal);

      // Pastikan hero_title terisi
      formData.set('hero_title', document.getElementById('heroTitleInput').value);

      try {
        const res = await fetch('/api/admin-action.php?action=save_hero_settings', {
          method: 'POST',
          headers: {
            'X-Admin-Auth': ADMIN_KEY
          },
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          alert('✓ ' + data.message);
          if (data.hero_image) {
            document.getElementById('currentHeroPath').innerText = data.hero_image;
            document.getElementById('inputHeroImage').value = data.hero_image;
            document.getElementById('heroPreviewImg').src = data.hero_image;
            if (cachedSettings) cachedSettings.hero_image = data.hero_image;
          }
          document.getElementById('uploadHeroImage').value = '';
          document.getElementById('newHeroSelectedInfo').style.display = 'none';
          document.getElementById('btnCancelHeroReplace').style.display = 'none';
        } else {
          alert('Gagal menyimpan: ' + (data.error || 'Terjadi kesalahan sistem.'));
        }
      } catch (err) {
        console.error(err);
        alert('Gagal menghubungi server saat menyimpan pengaturan Hero.');
      } finally {
        btn.disabled = false;
        btn.innerText = '💾 Simpan & Terapkan Perubahan Hero';
      }
    }

    // Inisialisasi awal
    initQuill();
    loadAdminData();
    setInterval(loadAdminData, 15000);

    // Buka tab otomatis jika terdapat parameter URL ?tab=... atau hash #...
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || window.location.hash.replace('#', '');
    if (initialTab) {
      setTimeout(() => { switchTab(initialTab); }, 100);
    }
  </script>
<?php endif; ?>

  <div id="toastNotice" class="toast-notice"></div>
</body>
</html>
