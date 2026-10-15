<?php
require_once __DIR__ . '/../config.php';

// Load PHPMailer dari vendor terpasang di server
if (file_exists('/home/restiamo/portal-cms/core/vendor/autoload.php')) {
    require_once '/home/restiamo/portal-cms/core/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    /**
     * Kirim email berlampiran PDF lisensi resmi ke pembeli
     */
    public static function sendEbook(
        string $toEmail,
        string $toName,
        string $productTitle,
        string $invoiceNumber,
        string $pdfAttachmentPath,
        string $pdfPassword = ''
    ): bool {
        if (!file_exists($pdfAttachmentPath)) {
            throw new Exception("Berkas lampiran tidak ditemukan: {$pdfAttachmentPath}");
        }

        $mail = new PHPMailer(true);

        try {
            // Konfigurasi Server SMTP cPanel
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;
            $mail->Timeout    = 15;
            $mail->CharSet    = 'UTF-8';

            // Opsi TLS aman untuk koneksi internal localhost
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Pengirim & Penerima
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo('order@restiamoegiono.com', 'Restia Moegiono Store');

            // Lampiran Berkas PDF (mendukung single file maupun multiple files dari keranjang belanja)
            $attachments = is_array($pdfAttachmentPath) ? $pdfAttachmentPath : [$pdfAttachmentPath];
            foreach ($attachments as $attPath) {
                if (!empty($attPath) && file_exists($attPath)) {
                    $baseName = basename($attPath);
                    // Bersihkan prefix invoice dari nama berkas lampiran
                    $displayAttachName = preg_replace('/^RM-\d+-[a-zA-Z0-9]+_/', '', $baseName);
                    $mail->addAttachment($attPath, $displayAttachName);
                }
            }

            // Konten Email Berformat Eksekutif
            $mail->isHTML(true);
            $mail->Subject = "Pengiriman Dokumen Resmi: {$productTitle}";
            $mail->Body    = self::getEmailTemplate($toName, $productTitle, $invoiceNumber, $pdfPassword);

            $pwdLine = !empty($pdfPassword) ? "\nPassword Buka PDF:\t{$pdfPassword}\n(Gunakan 6 karakter acak di atas saat membuka lampiran berkas PDF)" : "";
            $mail->AltBody = "DOKUMEN RESMI TERVERIFIKASI\nRestia Moegiono Official Store\nPustaka Keamanan Informasi & Privasi Data Digital\n\nHalo, {$toName}\nTerima kasih atas kepercayaan Anda telah memesan karya e-book karya Restia Moegiono.\n\nNo. Invoice:\t{$invoiceNumber}\nProduk E-Book:\t{$productTitle}\nStatus Pembayaran:\t✓ LUNAS (TERVERIFIKASI){$pwdLine}\n\n🔐 Dokumen Lisensi Resmi Telah Dilampirkan: Berkas PDF master e-book telah dipersonalisasi dengan stempel watermark nama, email, dan identitas instansi Anda di setiap halaman. Silakan unduh lampiran pada email ini.\n\nJika Anda memerlukan klarifikasi teknis atau bantuan terkait dokumen ini, Anda dapat membalas email ini secara langsung.\n\nSalam hangat,\nRestia Moegiono & Tim Redaksi\nPraktisi Keamanan Siber dan Pegiat Literasi Digital\n\n© 2026 Restia Moegiono.\nstore.restiamoegiono.com · restiamoegiono.com";

            return $mail->send();
        } catch (Exception $e) {
            throw new Exception("Gagal mengirim email via SMTP: " . $mail->ErrorInfo);
        }
    }

    /**
     * Template Email HTML Elegan & Profesional
     */
    private static function getEmailTemplate(string $name, string $title, string $invoice, string $password = ''): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeInv = htmlspecialchars($invoice, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

        $passwordRowHtml = '';
        $passwordBoxHtml = '';
        if (!empty($password)) {
            $passwordRowHtml = <<<HTML
          <tr>
            <td style="color:#64748b; padding:4px 0; font-size:13px;">Password Buka PDF:</td>
            <td style="font-weight:900; color:#059669; font-family:'SF Mono', Consolas, Monaco, monospace; letter-spacing:2px; text-align:right; font-size:15px;">{$safePassword}</td>
          </tr>
HTML;
            $passwordBoxHtml = <<<HTML
      <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 2px dashed #059669; border-radius: 12px; padding: 18px 20px; margin: 20px 0; text-align: center;">
        <div style="font-size: 11px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
          🔐 Password Buka Berkas PDF Anda
        </div>
        <div style="font-size: 32px; font-weight: 900; color: #065f46; letter-spacing: 6px; font-family: 'SF Mono', Consolas, Monaco, monospace; margin: 4px 0;">
          {$safePassword}
        </div>
        <div style="font-size: 12px; color: #047857;">
          Gunakan 6 karakter acak huruf kecil / angka di atas saat membuka lampiran berkas PDF berlisensi resmi Anda.
        </div>
      </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px 0; color: #1e293b; line-height: 1.6; }
    .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.03); }
    .header { background: linear-gradient(135deg, #0f172a, #1e293b); padding: 32px 28px; text-align: center; color: #ffffff; }
    .brand-tag { display: inline-block; padding: 4px 12px; background: rgba(2, 132, 199, 0.25); border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 20px; font-size: 11px; font-weight: 700; color: #38bdf8; letter-spacing: 0.5px; margin-bottom: 10px; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 800; }
    .header p { margin: 6px 0 0; font-size: 13px; color: #94a3b8; }
    .content { padding: 32px 28px; font-size: 14px; }
    .greeting { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
    .order-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 20px 0; }
    .order-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
    .order-row:last-child { margin-bottom: 0; }
    .order-lbl { color: #64748b; font-weight: 600; }
    .order-val { color: #0f172a; font-weight: 800; text-align: right; }
    .security-badge { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 14px; margin: 20px 0; font-size: 13px; color: #065f46; }
    .security-badge strong { display: block; margin-bottom: 4px; color: #047857; }
    .footer { border-top: 1px solid #e2e8f0; padding: 24px; text-align: center; font-size: 12px; color: #94a3b8; background: #fafafa; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <div class="brand-tag">DOKUMEN RESMI TERVERIFIKASI</div>
      <h1>Restia Moegiono Official Store</h1>
      <p>Pustaka Keamanan Informasi &amp; Privasi Data Digital</p>
    </div>
    <div class="content">
      <div class="greeting">Halo, {$safeName}</div>
      <p>Terima kasih atas kepercayaan Anda telah memesan karya e-book karya Restia Moegiono.</p>
      
      <div class="order-box">
        <table style="width: 100%; border-collapse: collapse;">
          <tr>
            <td style="color:#64748b; padding:4px 0; font-size:13px;">No. Invoice:</td>
            <td style="font-weight:800; color:#0f172a; text-align:right; font-size:13px;">{$safeInv}</td>
          </tr>
          <tr>
            <td style="color:#64748b; padding:4px 0; font-size:13px;">Produk E-Book:</td>
            <td style="font-weight:800; color:#0284c7; text-align:right; font-size:13px;">{$safeTitle}</td>
          </tr>
          <tr>
            <td style="color:#64748b; padding:4px 0; font-size:13px;">Status Pembayaran:</td>
            <td style="font-weight:800; color:#059669; text-align:right; font-size:13px;">✓ LUNAS (TERVERIFIKASI)</td>
          </tr>
          {$passwordRowHtml}
        </table>
      </div>

      {$passwordBoxHtml}

      <div class="security-badge">
        <strong>🔐 Dokumen Lisensi Resmi Telah Dilampirkan:</strong>
        Berkas PDF master e-book telah dipersonalisasi dengan stempel watermark nama, email, dan identitas instansi Anda di setiap halaman. Silakan unduh lampiran pada email ini.
      </div>

      <p style="font-size:13px; color:#64748b; margin-top:24px;">
        Jika Anda memerlukan klarifikasi teknis atau bantuan terkait dokumen ini, Anda dapat membalas email ini secara langsung.
      </p>

      <p style="margin-top:24px; margin-bottom:0;">
        Salam hangat,<br>
        <strong>Restia Moegiono &amp; Tim Redaksi</strong><br>
        <span style="font-size:12px; color:#64748b;">Praktisi Keamanan Siber dan Pegiat Literasi Digital</span>
      </p>
    </div>
    <div class="footer">
      &copy; 2026 Restia Moegiono.<br>
      <a href="https://store.restiamoegiono.com" style="color:#0284c7; text-decoration:none;">store.restiamoegiono.com</a> &middot; <a href="https://restiamoegiono.com" style="color:#0284c7; text-decoration:none;">restiamoegiono.com</a>
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Kirim email kode verifikasi OTP untuk login Admin Command Vault
     */
    public static function sendAdminOtp(string $toEmail, string $otpCode): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;
            $mail->Timeout    = 10;
            $mail->CharSet    = 'UTF-8';

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(SMTP_FROM_EMAIL, 'Restia Moegiono Command Vault');
            $mail->addAddress($toEmail, 'Restia Moegiono');
            $mail->addReplyTo('order@restiamoegiono.com', 'Restia Moegiono Support');

            $mail->isHTML(true);
            $mail->Subject = "🔐 Kode Verifikasi OTP Masuk Admin: {$otpCode}";
            $mail->Body    = self::getOtpTemplate($otpCode);
            $mail->AltBody = "Halo Restia Moegiono,\n\nKode verifikasi OTP untuk masuk ke Command Vault Admin store.restiamoegiono.com adalah:\n\n{$otpCode}\n\nKode ini berlaku selama 10 menit. Jangan bagikan kode ini kepada siapa pun demi keamanan akun Anda.\n\nSalam hangat,\nCommand Vault Security System";

            return $mail->send();
        } catch (Exception $e) {
            throw new Exception("Gagal mengirim kode OTP via SMTP: " . $mail->ErrorInfo);
        }
    }

    /**
     * Template Email OTP Elegan & Berkeamanan Tinggi
     */
    private static function getOtpTemplate(string $otpCode): string
    {
        $safeOtp = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px 0; color: #1e293b; line-height: 1.6; }
    .email-container { max-width: 520px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,0.04); }
    .header { background: linear-gradient(135deg, #0f172a, #1e293b); padding: 30px 24px; text-align: center; color: #ffffff; }
    .brand-tag { display: inline-block; padding: 4px 12px; background: rgba(2, 132, 199, 0.25); border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 20px; font-size: 11px; font-weight: 800; color: #38bdf8; letter-spacing: 0.5px; margin-bottom: 8px; }
    .header h1 { margin: 0; font-size: 20px; font-weight: 900; }
    .header p { margin: 4px 0 0; font-size: 12px; color: #94a3b8; }
    .content { padding: 32px 28px; text-align: center; }
    .greeting { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 10px; }
    .instruction { font-size: 13px; color: #475569; margin-bottom: 24px; }
    .otp-box { background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 2px dashed #86efac; border-radius: 14px; padding: 20px; margin-bottom: 20px; display: inline-block; width: 85%; }
    .otp-code { font-size: 38px; font-weight: 900; color: #047857; letter-spacing: 8px; font-family: 'SF Mono', Consolas, Monaco, monospace; margin: 4px 0; }
    .otp-sub { font-size: 11px; color: #059669; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .warning-box { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 12px 14px; font-size: 12px; color: #92400e; text-align: left; margin-bottom: 20px; }
    .footer { border-top: 1px solid #e2e8f0; padding: 20px; text-align: center; font-size: 11px; color: #94a3b8; background: #fafafa; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <div class="brand-tag">🔐 COMMAND VAULT SECURITY</div>
      <h1>Autentikasi Panel Admin</h1>
      <p>store.restiamoegiono.com</p>
    </div>
    <div class="content">
      <div class="greeting">Halo, Restia Moegiono</div>
      <p class="instruction">Berikut adalah kode verifikasi sekali pakai (One-Time Password) untuk masuk ke Command Vault Briefcase:</p>
      
      <div class="otp-box">
        <div class="otp-code">{$safeOtp}</div>
        <div class="otp-sub">⏱️ Berlaku Selama 10 Menit</div>
      </div>

      <div class="warning-box">
        <b>⚠️ Peringatan Keamanan:</b><br>
        Jangan bagikan kode ini kepada siapa pun. Jika Anda tidak merasa melakukan permintaan masuk ke panel admin, segera periksa aktivitas keamanan akun Anda.
      </div>

      <p style="font-size:12px; color:#64748b; margin:0;">
        Pesan ini dikirimkan secara otomatis oleh sistem keamanan internal Restia Moegiono Store.
      </p>
    </div>
    <div class="footer">
      &copy; 2026 Restia Moegiono &middot; Command Vault Security Infrastructure<br>
      <a href="https://store.restiamoegiono.com" style="color:#0284c7; text-decoration:none;">store.restiamoegiono.com</a>
    </div>
  </div>
</body>
</html>
HTML;
    }
}
