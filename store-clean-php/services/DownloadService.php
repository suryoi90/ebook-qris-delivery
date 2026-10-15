<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/WatermarkService.php';

class DownloadService
{
    /**
     * Menghasilkan token tanda tangan kriptografi (HMAC-SHA256) untuk tautan unduhan aman
     */
    public static function generateDownloadToken(string $invoiceNumber, string $productId, string $customerEmail): string
    {
        $payload = trim($invoiceNumber) . '|' . trim($productId) . '|' . strtolower(trim($customerEmail));
        return substr(hash_hmac('sha256', $payload, DOWNLOAD_SALT), 0, 32);
    }

    /**
     * Menghasilkan URL unduh resmi ber-token tanda tangan
     */
    public static function generateDownloadUrl(string $invoiceNumber, string $productId, string $customerEmail): string
    {
        $token = self::generateDownloadToken($invoiceNumber, $productId, $customerEmail);
        return APP_URL . '/download.php?' . http_build_query([
            'inv' => trim($invoiceNumber),
            'prod' => trim($productId),
            'token' => $token
        ]);
    }

    /**
     * Memvalidasi token tanda tangan unduhan
     */
    public static function verifyToken(string $invoiceNumber, string $productId, string $customerEmail, string $token): bool
    {
        $expected = self::generateDownloadToken($invoiceNumber, $productId, $customerEmail);
        return hash_equals($expected, trim($token));
    }

    /**
     * Melayani pengunduhan berkas PDF ber-watermark & password dengan streaming hemat RAM (8 KB buffer)
     */
    public static function serveFile(string $invoiceNumber, string $productId, string $token): void
    {
        $invoiceNumber = trim($invoiceNumber);
        $productId = trim($productId);
        $token = trim($token);

        if (empty($invoiceNumber) || empty($productId) || empty($token)) {
            self::renderError("Parameter tautan unduhan tidak lengkap atau rusak.", 400);
            return;
        }

        $db = StoreDB::get();

        // 1. Ambil data pesanan berdasarkan invoice
        $stmt = $db->prepare("SELECT * FROM `store_orders` WHERE `invoice_number` = :inv LIMIT 1");
        $stmt->execute([':inv' => $invoiceNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            self::renderError("Pesanan dengan nomor invoice '{$invoiceNumber}' tidak ditemukan.", 404);
            return;
        }

        // 2. Verifikasi status pembayaran harus LUNAS
        if (!in_array($order['payment_status'], ['PAID', 'SUCCESS'], true)) {
            self::renderError("Pesanan ini belum berstatus LUNAS. Berkas PDF berlisensi hanya dapat diunduh setelah pembayaran terkonfirmasi.", 403);
            return;
        }

        // 3. Verifikasi token tanda tangan kriptografi
        if (!self::verifyToken($invoiceNumber, $productId, $order['customer_email'], $token)) {
            self::renderError("Tautan unduhan tidak sah atau telah kedaluwarsa. Keamanan lisensi dilindungi.", 403);
            return;
        }

        // 4. Cari informasi produk (dari items_json atau tabel store_products)
        $targetProduct = null;
        if (!empty($order['items_json'])) {
            $items = json_decode($order['items_json'], true);
            if (is_array($items)) {
                foreach ($items as $it) {
                    if (($it['id'] ?? '') === $productId) {
                        $targetProduct = $it;
                        break;
                    }
                }
            }
        }

        if (!$targetProduct) {
            $pStmt = $db->prepare("SELECT * FROM `store_products` WHERE `id` = :id LIMIT 1");
            $pStmt->execute([':id' => $productId]);
            $targetProduct = $pStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$targetProduct || empty($targetProduct['master_file'])) {
            self::renderError("Berkas materi untuk produk ini tidak terdaftar di sistem pustaka.", 404);
            return;
        }

        // 5. Tentukan path file ter-watermark
        $cleanInv = preg_replace('/[^a-zA-Z0-9_\-]/', '', $invoiceNumber);
        $cleanMaster = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $targetProduct['master_file']);
        $filePath = GENERATED_EBOOKS_DIR . '/' . $cleanInv . '_' . $cleanMaster;

        // Jika file belum di-generate (atau hilang), generate ulang on-the-fly secara otomatis
        if (!file_exists($filePath) || filesize($filePath) < 5000) {
            $pdfPassword = $order['pdf_password'] ?: '123456';
            $filePath = WatermarkService::stampPdf(
                $targetProduct['master_file'],
                $targetProduct['title'],
                $order['customer_name'],
                $order['customer_email'],
                $order['customer_org'] ?? '-',
                $invoiceNumber,
                $pdfPassword
            );
        }

        if (!file_exists($filePath)) {
            self::renderError("Gagal mempersiapkan berkas PDF berlisensi resmi di server.", 500);
            return;
        }

        // 6. Nama berkas unduhan yang ramah dan formal untuk pembeli
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $targetProduct['title']);
        $safeTitle = trim(preg_replace('/_+/', '_', $safeTitle), '_');
        $downloadName = ($safeTitle ?: 'EBook_Resmi_Restia_Moegiono') . '.pdf';

        // 7. Bersihkan output buffer sebelum streaming berkas
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 8. Header HTTP Pengunduhan Aman & Kompatibel
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');

        // 9. Streaming Berkas dalam Chunk 8 KB (Memori RAM server konstan < 8 KB)
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            self::renderError("Gagal membaca berkas penyimpanan server.", 500);
            return;
        }

        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        exit;
    }

    /**
     * Halaman antarmuka ramah saat terjadi galat pada tautan unduhan
     */
    private static function renderError(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        $safeMsg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kendala Pengunduhan E-Book — Toko Resmi Restia Moegiono</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #F8FAFC; color: #0F224A; line-height: 1.6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #ffffff; border: 1px solid #D6EAFF; border-radius: 24px; padding: 40px 32px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 16px 40px rgba(15, 34, 74, 0.06); }
    .icon { font-size: 48px; margin-bottom: 16px; }
    .title { font-size: 20px; font-weight: 800; color: #0F224A; margin-bottom: 8px; }
    .msg { font-size: 13.5px; color: #64748b; margin-bottom: 24px; line-height: 1.5; }
    .btn { display: inline-block; width: 100%; padding: 13px; border-radius: 12px; background: #00AEFF; color: #ffffff; text-decoration: none; font-weight: 800; font-size: 13.5px; transition: background 0.15s; }
    .btn:hover { background: #0F224A; }
    .help { margin-top: 16px; font-size: 12px; color: #94a3b8; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">⚠️</div>
    <h1 class="title">Tautan Unduhan Terkendala</h1>
    <p class="msg">{$safeMsg}</p>
    <a href="/cek-pesanan" class="btn">🔍 Buka Halaman Cek Pesanan Mandiri</a>
    <p class="help">Atau hubungi kami via WhatsApp jika Anda memerlukan bantuan verifikasi.</p>
  </div>
</body>
</html>
HTML;
        exit;
    }
}
