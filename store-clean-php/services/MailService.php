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
     * Kirim email konfirmasi pengiriman e-book terpadu (Super Ringan < 30 KB)
     * Memuat: Password 6 karakter, Tautan Unduh Ber-Token, dan Panduan Lisensi Resmi
     */
    public static function sendDeliveryEmail(
        string $toEmail,
        string $toName,
        string $productTitle,
        string $invoiceNumber,
        array $items,
        string $pdfPassword = ''
    ): bool {
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

            // Konten Email Berformat Eksekutif Terpadu (Tanpa lampiran berat, ukuran email < 30 KB)
            $mail->isHTML(true);
            $mail->Subject = "Akses Dokumen Resmi & Kata Sandi: {$productTitle}";
            $mail->Body    = self::getEmailTemplate($toName, $toEmail, $productTitle, $invoiceNumber, $items, $pdfPassword);

            // Plain text alternative
            $pwdLine = !empty($pdfPassword) ? "\nKATA SANDI BUKA PDF:\t{$pdfPassword}\n(Gunakan 6 karakter acak di atas saat membuka berkas PDF)" : "";
            $linksList = "";
            foreach ($items as $idx => $it) {
                $n = $idx + 1;
                $t = $it['title'] ?? 'E-Book';
                $u = $it['download_url'] ?? '';
                $linksList .= "\n{$n}. {$t}\n   Tautan Unduh: {$u}\n";
            }

            $mail->AltBody = "DOKUMEN RESMI TERVERIFIKASI\nRestia Moegiono Official Store\nPustaka Keamanan Informasi & Privasi Data Digital\n\nHalo, {$toName}\nTerima kasih atas kepercayaan Anda telah memesan karya e-book karya Restia Moegiono.\n\nNo. Invoice:\t{$invoiceNumber}\nProduk E-Book:\t{$productTitle}\nStatus Pembayaran:\t✓ LUNAS (TERVERIFIKASI){$pwdLine}\n\nDAFTAR BERKAS UNDUHAN ANDA:{$linksList}\n\nPANDUAN MEMBUKA DOKUMEN:\n1. Buka tautan unduhan di atas untuk menyimpan berkas PDF ke perangkat Anda.\n2. Masukkan kata sandi 6 karakter di atas saat membuka dokumen di Adobe Acrobat Reader / Google Chrome.\n3. Berkas dilindungi watermark lisensi sah identitas Anda di setiap halaman.\n\nUnduh ulang mandiri kapan saja di: https://store.restiamoegiono.com/cek-pesanan\n\nSalam hangat,\nRestia Moegiono & Tim Redaksi\nPraktisi Keamanan Siber dan Pegiat Literasi Digital\n\n© 2026 Restia Moegiono.\nstore.restiamoegiono.com · restiamoegiono.com";

            return $mail->send();
        } catch (Exception $e) {
            throw new Exception("Gagal mengirim email via SMTP: " . $mail->ErrorInfo);
        }
    }

    /**
     * Backward-compatible alias untuk sendEbook
     */
    public static function sendEbook(
        string $toEmail,
        string $toName,
        string $productTitle,
        string $invoiceNumber,
        $itemsOrPaths,
        string $pdfPassword = ''
    ): bool {
        // Jika format yang diberikan adalah array items lengkap
        if (is_array($itemsOrPaths) && isset($itemsOrPaths[0]) && is_array($itemsOrPaths[0])) {
            return self::sendDeliveryEmail($toEmail, $toName, $productTitle, $invoiceNumber, $itemsOrPaths, $pdfPassword);
        }

        // Jika dipanggil dengan format legacy (paths), buat item ringkas
        require_once __DIR__ . '/DownloadService.php';
        $items = [];
        $db = StoreDB::get();
        $stmt = $db->prepare("SELECT `items_json`, `product_id`, `product_title` FROM `store_orders` WHERE `invoice_number` = ? LIMIT 1");
        $stmt->execute([$invoiceNumber]);
        $ord = $stmt->fetch();

        if ($ord && !empty($ord['items_json'])) {
            $parsed = json_decode($ord['items_json'], true);
            if (is_array($parsed)) {
                foreach ($parsed as $p) {
                    $items[] = [
                        'id' => $p['id'],
                        'title' => $p['title'],
                        'download_url' => DownloadService::generateDownloadUrl($invoiceNumber, $p['id'], $toEmail)
                    ];
                }
            }
        }

        if (empty($items)) {
            $prodId = $ord['product_id'] ?? 'ebook';
            $prodTitle = $ord['product_title'] ?? $productTitle;
            $items[] = [
                'id' => $prodId,
                'title' => $prodTitle,
                'download_url' => DownloadService::generateDownloadUrl($invoiceNumber, $prodId, $toEmail)
            ];
        }

        return self::sendDeliveryEmail($toEmail, $toName, $productTitle, $invoiceNumber, $items, $pdfPassword);
    }

    /**
     * Template Email HTML Elegan, Mewah, & Super Ringan (< 25 KB)
     */
    private static function getEmailTemplate(
        string $name,
        string $email,
        string $title,
        string $invoice,
        array $items,
        string $password = ''
    ): string {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeInv = htmlspecialchars($invoice, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

        // 1. Password Box & Row
        $passwordRowHtml = '';
        $passwordBoxHtml = '';
        if (!empty($password)) {
            $passwordRowHtml = <<<HTML
          <tr>
            <td style="color:#64748b; padding:6px 0; font-size:13px; font-weight:600;">Kata Sandi PDF:</td>
            <td style="font-weight:900; color:#059669; font-family:'SF Mono', Consolas, Monaco, monospace; letter-spacing:2px; text-align:right; font-size:16px;">{$safePassword}</td>
          </tr>
HTML;
            $passwordBoxHtml = <<<HTML
      <div style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 2px dashed #059669; border-radius: 14px; padding: 22px 20px; margin: 24px 0; text-align: center; box-shadow: 0 4px 12px rgba(5,150,105,0.08);">
        <div style="font-size: 11px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
          🔐 KATA SANDI PEMBUKA SELURUH DOKUMEN PDF ANDA
        </div>
        <div style="font-size: 34px; font-weight: 900; color: #065f46; letter-spacing: 8px; font-family: 'SF Mono', Consolas, Monaco, monospace; margin: 6px 0;">
          {$safePassword}
        </div>
        <div style="font-size: 12.5px; color: #047857; line-height: 1.5; max-width: 460px; margin: 0 auto;">
          Gunakan 6 karakter acak di atas saat membuka seluruh berkas PDF berlisensi resmi Anda.
        </div>
      </div>
HTML;
        }

        // 2. Daftar Item Unduhan
        $itemsHtml = '';
        foreach ($items as $idx => $it) {
            $num = $idx + 1;
            $itTitle = htmlspecialchars($it['title'] ?? 'E-Book Lisensi Resmi', ENT_QUOTES, 'UTF-8');
            $itUrl = htmlspecialchars($it['download_url'] ?? '#', ENT_QUOTES, 'UTF-8');

            $itemsHtml .= <<<HTML
        <div style="background: #ffffff; border: 1px solid #D6EAFF; border-radius: 12px; padding: 18px 20px; margin-bottom: 12px; box-shadow: 0 2px 6px rgba(15,34,74,0.03);">
          <div style="font-weight: 800; font-size: 14px; color: #0F224A; margin-bottom: 4px;">
            📘 {$itTitle}
          </div>
          <div style="font-size: 11.5px; color: #64748b; margin-bottom: 12px;">
            Format Berkas: PDF Digital Ber-Watermark Lisensi Resmi Sah
          </div>
          <a href="{$itUrl}" target="_blank" style="background: linear-gradient(135deg, #00AEFF 0%, #0F224A 100%); color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 800; padding: 10px 20px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 12px rgba(0,174,255,0.3);">
            📥 Unduh Berkas E-Book (PDF) &rarr;
          </a>
        </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px 12px; color: #1e293b; line-height: 1.6; }
    .email-container { max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #D6EAFF; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15,34,74,0.06); }
    .header { background: linear-gradient(135deg, #0F224A 0%, #0B1B3D 100%); padding: 34px 28px; text-align: center; color: #ffffff; border-bottom: 3px solid #00AEFF; }
    .brand-tag { display: inline-block; padding: 4px 12px; background: rgba(0, 174, 255, 0.2); border: 1px solid #00AEFF; border-radius: 20px; font-size: 11px; font-weight: 800; color: #00AEFF; letter-spacing: 0.5px; margin-bottom: 12px; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 900; }
    .header p { margin: 6px 0 0; font-size: 13px; color: #94a3b8; }
    .content { padding: 32px 28px; font-size: 14px; }
    .greeting { font-size: 16px; font-weight: 800; color: #0F224A; margin-bottom: 8px; }
    .order-box { background: #E8F3FF; border: 1px solid #D6EAFF; border-radius: 14px; padding: 18px 20px; margin: 20px 0; }
    .section-title { font-size: 14px; font-weight: 800; color: #0F224A; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
    .guide-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin: 24px 0; font-size: 13px; color: #334155; line-height: 1.6; }
    .guide-box ol { margin: 0; padding-left: 20px; }
    .guide-box li { margin-bottom: 8px; }
    .guide-box li:last-child { margin-bottom: 0; }
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
      <p style="color:#475569; margin-bottom:16px;">
        Terima kasih atas pesanan Anda. Pembayaran transaksi telah berhasil diverifikasi secara resmi. Seluruh berkas e-book berlisensi Anda telah siap diunduh.
      </p>
      
      <!-- RINCIAN PESANAN -->
      <div class="order-box">
        <table style="width: 100%; border-collapse: collapse;">
          <tr>
            <td style="color:#64748b; padding:4px 0; font-size:13px; font-weight:600;">No. Invoice:</td>
            <td style="font-weight:800; color:#0F224A; text-align:right; font-size:13px; font-family:monospace;">{$safeInv}</td>
          </tr>
          <tr>
            <td style="color:#64748b; padding:4px 0; font-size:13px; font-weight:600;">Produk E-Book:</td>
            <td style="font-weight:800; color:#0284c7; text-align:right; font-size:13px;">{$safeTitle}</td>
          </tr>
          <tr>
            <td style="color:#64748b; padding:4px 0; font-size:13px; font-weight:600;">Status Bayar:</td>
            <td style="font-weight:800; color:#059669; text-align:right; font-size:13px;">✓ LUNAS (TERVERIFIKASI DOKU)</td>
          </tr>
          {$passwordRowHtml}
        </table>
      </div>

      <!-- KOTAK PASSWORD 6-KARAKTER -->
      {$passwordBoxHtml}

      <!-- DAFTAR TOMBOL UNDUH E-BOOK -->
      <div style="margin: 24px 0;">
        <div class="section-title">
          <span>📥</span> Berkas E-Book Berlisensi Anda:
        </div>
        {$itemsHtml}
      </div>

      <!-- PANDUAN LENGKAP PENGGUNAAN & KEAMANAN LISENSI -->
      <div class="guide-box">
        <div style="font-weight: 800; font-size: 13.5px; color: #0F224A; margin-bottom: 10px;">
          📖 Panduan Penggunaan &amp; Keamanan Dokumen:
        </div>
        <ol>
          <li>
            <b>Unduh Berkas:</b> Klik tombol biru <i>"Unduh Berkas E-Book"</i> di atas untuk menyimpan file PDF ke perangkat Anda.
          </li>
          <li>
            <b>Aplikasi Pembaca PDF:</b> Buka berkas menggunakan aplikasi standar (direkomendasikan <b>Adobe Acrobat Reader</b>, <b>Google Chrome</b>, atau <b>Apple Books</b>).
          </li>
          <li>
            <b>Masukkan Kata Sandi:</b> Masukkan kata sandi 6 karakter di atas (<code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 800; color: #047857;">{$safePassword}</code>) saat diminta. Anda dapat menyalinnya langsung dari email ini.
          </li>
          <li>
            <b>Stempel Hak Cipta DRM:</b> Dokumen ini dilindungi stempel watermark digital permanen yang memuat identitas nama dan email Anda pada setiap halaman untuk mencegah distribusi tanpa izin.
          </li>
          <li>
            <b>Unduh Ulang Kapan Saja:</b> Berkas dan lisensi Anda tersimpan aman di server kami. Anda dapat mengunduh ulang sewaktu-waktu melalui halaman <a href="https://store.restiamoegiono.com/cek-pesanan" style="color: #00AEFF; font-weight: 800; text-decoration: none;">Cek Pesanan Mandiri</a>.
          </li>
        </ol>
      </div>

      <p style="font-size:13px; color:#64748b; margin-top:20px;">
        Jika Anda memerlukan bantuan teknis atau klarifikasi mengenai isi panduan, silakan hubungi tim kami dengan membalas email ini atau via WhatsApp.
      </p>

      <p style="margin-top:24px; margin-bottom:0;">
        Salam hangat,<br>
        <strong>Restia Moegiono &amp; Tim Redaksi</strong><br>
        <span style="font-size:12px; color:#64748b;">Praktisi Keamanan Siber &amp; Perlindungan Data Pribadi</span>
      </p>
    </div>

    <div class="footer">
      &copy; 2026 Restia Moegiono.<br>
      <a href="https://store.restiamoegiono.com" style="color:#00AEFF; text-decoration:none; font-weight:700;">store.restiamoegiono.com</a> &middot; <a href="https://restiamoegiono.com" style="color:#00AEFF; text-decoration:none;">restiamoegiono.com</a>
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
