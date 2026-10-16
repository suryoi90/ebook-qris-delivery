<?php
/**
 * DownloadService.php — Layanan Pengunduhan Aman & Streaming Cerdas Berkelanjutan
 * 
 * Prinsip Kerja:
 * 1. Token HMAC-SHA256: Validasi tanda tangan kriptografi tanpa payload rentan.
 * 2. 8 KB Chunk Streaming: Memori RAM server konstan < 8 KB saat streaming file berukuran berapa pun.
 * 3. Smart Cache Lifecycle: Berkas PDF ber-watermark disimpan sebagai cache sementara, dan dibersihkan berkala.
 * 4. On-The-Fly Regeneration: Jika pembeli mengunduh berkas lama yang sudah dibersihkan, sistem men-generate ulang secara otomatis.
 * 5. Anti-Abuse Rate Limiting: Mencegah penyedotan bandwidth berulang oleh bot/scrapers (maks 25x/24 jam per invoice).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/WatermarkService.php';

class DownloadService
{
    /**
     * Membuat token tanda tangan HMAC-SHA256 yang aman
     */
    public static function generateDownloadToken(string $invoiceNumber, string $productId, string $customerEmail): string
    {
        $payload = strtolower(trim($invoiceNumber)) . '|' . strtolower(trim($productId)) . '|' . strtolower(trim($customerEmail));
        return hash_hmac('sha256', $payload, DOWNLOAD_SALT);
    }

    /**
     * Memverifikasi keabsahan token unduhan (constant-time comparison)
     */
    public static function verifyToken(string $invoiceNumber, string $productId, string $customerEmail, string $token): bool
    {
        $expected = self::generateDownloadToken($invoiceNumber, $productId, $customerEmail);
        return hash_equals($expected, $token);
    }

    /**
     * Menghasilkan URL lengkap untuk pengunduhan e-book
     */
    public static function generateDownloadUrl(string $invoiceNumber, string $productId, string $customerEmail): string
    {
        $token = self::generateDownloadToken($invoiceNumber, $productId, $customerEmail);
        $query = http_build_query([
            'inv'   => $invoiceNumber,
            'prod'  => $productId,
            'token' => $token
        ]);
        return STORE_URL . '/download.php?' . $query;
    }

    /**
     * Menangani proses pengunduhan aman dan streaming chunk 8 KB ke browser pembeli
     */
    public static function serveFile(string $invoiceNumber, string $productId, string $token): void
    {
        if (empty($invoiceNumber) || empty($productId) || empty($token)) {
            self::renderError("Parameter unduhan tidak lengkap.", 400);
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

        // 4. Anti-Abuse Rate Limiting (Maks 25 unduhan per 24 jam per invoice)
        if (!self::checkRateLimit($db, (int)$order['id'], $invoiceNumber, 25)) {
            self::renderError("Batas frekuensi unduhan harian (25 kali/24 jam) untuk invoice ini telah tercapai demi kestabilan server. Silakan hubungi dukungan jika Anda membutuhkan berkas tambahan.", 429);
            return;
        }

        // 5. Opportunistic Smart Cache Lifecycle (Pembersihan otomatis cache PDF > 21 hari dengan probabilitas 1:20)
        if (random_int(1, 20) === 1) {
            self::cleanupExpiredCache(21);
        }

        // 6. Cari informasi produk (dari items_json atau tabel store_products)
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

        // 7. Tentukan path file ter-watermark
        $cleanInv = preg_replace('/[^a-zA-Z0-9_\-]/', '', $invoiceNumber);
        $cleanMaster = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $targetProduct['master_file']);
        $filePath = GENERATED_EBOOKS_DIR . '/' . $cleanInv . '_' . $cleanMaster;

        // On-the-fly regeneration: jika file fisik belum ada atau sudah dibersihkan oleh siklus cache, buat ulang otomatis
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

        // 8. Nama berkas unduhan yang ramah dan formal untuk pembeli
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $targetProduct['title']);
        $safeTitle = trim(preg_replace('/_+/', '_', $safeTitle), '_');
        $downloadName = ($safeTitle ?: 'EBook_Resmi_Restia_Moegiono') . '.pdf';

        // 9. Bersihkan output buffer sebelum streaming berkas
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 10. Header HTTP Pengunduhan Aman & Kompatibel
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');

        // 11. Streaming Berkas dalam Chunk 8 KB (Memori RAM server konstan < 8 KB)
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
     * Pelacakan & Batasan Frekuensi Unduhan (Anti-Abuse Rate Limiting)
     */
    private static function checkRateLimit(PDO $db, int $orderId, string $invoiceNumber, int $maxDaily = 25): bool
    {
        try {
            static $migrated = false;
            if (!$migrated) {
                $check = $db->query("SHOW COLUMNS FROM `store_orders` LIKE 'download_count'")->fetch();
                if (!$check) {
                    $db->exec("ALTER TABLE `store_orders` ADD COLUMN `download_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `email_sent_at`");
                    $db->exec("ALTER TABLE `store_orders` ADD COLUMN `last_download_at` DATETIME DEFAULT NULL AFTER `download_count`");
                }
                $migrated = true;
            }

            $stmt = $db->prepare("SELECT `download_count`, `last_download_at` FROM `store_orders` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $lastTime = !empty($row['last_download_at']) ? strtotime($row['last_download_at']) : 0;
                $count = (int)$row['download_count'];

                // Reset counter jika sudah melewati 24 jam
                if (time() - $lastTime > 86400) {
                    $count = 0;
                }

                if ($count >= $maxDaily) {
                    return false;
                }

                $upStmt = $db->prepare("UPDATE `store_orders` SET `download_count` = :cnt, `last_download_at` = NOW() WHERE `id` = :id");
                $upStmt->execute([':cnt' => $count + 1, ':id' => $orderId]);
            }
        } catch (Throwable $e) {
            // Abaikan kesalahan pencatatan rate-limit agar unduhan pembeli tetap berjalan
        }
        return true;
    }

    /**
     * Membersihkan berkas PDF hasil watermark yang sudah melebihi N hari
     * Berkas master tetap 100% aman dan PDF dapat digenerate ulang on-the-fly kapan saja.
     */
    public static function cleanupExpiredCache(int $days = 21): array
    {
        $genDir = GENERATED_EBOOKS_DIR;
        if (!is_dir($genDir)) {
            return ['deleted_count' => 0, 'freed_bytes' => 0, 'freed_formatted' => '0 B'];
        }

        $threshold = time() - ($days * 86400);
        $files = glob($genDir . '/*.pdf') ?: [];
        $deleted = 0;
        $freed = 0;

        foreach ($files as $f) {
            if (is_file($f)) {
                $mtime = filemtime($f);
                if ($mtime !== false && $mtime < $threshold) {
                    $sz = filesize($f);
                    if (@unlink($f)) {
                        $deleted++;
                        $freed += $sz;
                    }
                }
            }
        }

        return [
            'deleted_count' => $deleted,
            'freed_bytes' => $freed,
            'freed_formatted' => self::formatBytes($freed),
        ];
    }

    /**
     * Mengosongkan seluruh berkas PDF di folder cache (Proteksi .htaccess tetap dipertahankan)
     */
    public static function clearAllGeneratedCache(): array
    {
        $genDir = GENERATED_EBOOKS_DIR;
        if (!is_dir($genDir)) {
            return ['deleted_count' => 0, 'freed_bytes' => 0, 'freed_formatted' => '0 B'];
        }

        $files = glob($genDir . '/*.pdf') ?: [];
        $deleted = 0;
        $freed = 0;

        foreach ($files as $f) {
            if (is_file($f)) {
                $sz = filesize($f);
                if (@unlink($f)) {
                    $deleted++;
                    $freed += $sz;
                }
            }
        }

        return [
            'deleted_count' => $deleted,
            'freed_bytes' => $freed,
            'freed_formatted' => self::formatBytes($freed),
        ];
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $base = log($bytes, 1024);
        $floor = floor($base);
        return round(pow(1024, $base - $floor), 2) . ' ' . ($units[$floor] ?? 'B');
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
