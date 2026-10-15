<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/WatermarkService.php';
require_once __DIR__ . '/MailService.php';

class FulfillmentService
{
    /**
     * Jalankan proses lengkap otomatis: Stempel Watermark DRM & Kirim Email
     */
    public static function fulfillOrder(string $invoiceNumber, bool $force = false): array
    {
        $db = StoreDB::get();

        $stmt = $db->prepare("SELECT * FROM `store_orders` WHERE `invoice_number` = ? LIMIT 1");
        $stmt->execute([$invoiceNumber]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception("Pesanan dengan invoice {$invoiceNumber} tidak ditemukan.");
        }

        if ($order['email_sent'] && !$force) {
            return [
                'success' => true,
                'message' => 'Pesanan ini sudah pernah diproses dan email telah terkirim.',
                'invoice' => $invoiceNumber
            ];
        }

        $stmtProd = $db->prepare("SELECT * FROM `store_products` WHERE `id` = ? LIMIT 1");
        $stmtProd->execute([$order['product_id']]);
        $product = $stmtProd->fetch();

        if (!$product) {
            throw new Exception("Produk e-book ({$order['product_id']}) tidak ditemukan di basis data.");
        }

        $watermarkText = sprintf(
            '“%s” ini karya Restia Moegiono, salinan ini dibuat untuk “%s” | “%s” | “%s”',
            $product['title'],
            $order['customer_name'],
            $order['customer_email'],
            $order['customer_org'] ?: '-'
        );

        // Generate password 6 karakter acak (huruf kecil a-z atau angka 0-9)
        $pdfPassword = !empty($order['pdf_password']) ? $order['pdf_password'] : self::generateRandomPassword(6);

        try {
            // 1. Eksekusi Stempel Watermark & Proteksi Password AES-256 ke PDF
            $pdfPath = WatermarkService::stampPdf(
                $product['master_file'],
                $product['title'],
                $order['customer_name'],
                $order['customer_email'],
                $order['customer_org'],
                $invoiceNumber,
                $pdfPassword
            );

            // 2. Eksekusi Pengiriman Email Berlampiran PDF via SMTP cPanel
            MailService::sendEbook(
                $order['customer_email'],
                $order['customer_name'],
                $product['title'],
                $invoiceNumber,
                $pdfPath,
                $pdfPassword
            );

            // 3. Simpan Pembaruan Status & Password ke Database
            $upd = $db->prepare("UPDATE `store_orders` SET 
                `payment_status` = 'PAID',
                `pdf_password` = :pwd,
                `watermarked_file` = :pdf,
                `drm_watermark_text` = :wm,
                `email_sent` = 1,
                `email_sent_at` = NOW(),
                `email_error` = NULL
                WHERE `id` = :id");

            $upd->execute([
                ':pwd' => $pdfPassword,
                ':pdf' => $pdfPath,
                ':wm' => $watermarkText,
                ':id' => $order['id']
            ]);

            // 4. Auto-enroll ke Akademi Kursus LMS (Akses Pembeli Instan)
            try {
                $enrStmt = $db->prepare("INSERT INTO `lms_enrollments` (`course_id`, `user_email`, `user_name`, `order_invoice`, `status`)
                    VALUES (1, :email, :name, :inv, 'ACTIVE')
                    ON DUPLICATE KEY UPDATE `user_name` = VALUES(`user_name`), `status` = 'ACTIVE'");
                $enrStmt->execute([
                    ':email' => strtolower(trim($order['customer_email'])),
                    ':name' => trim($order['customer_name']),
                    ':inv' => $invoiceNumber
                ]);
            } catch (Throwable $eEnr) {
                // Non-blocking error handling
            }

            return [
                'success' => true,
                'message' => 'Watermark dan password PDF berhasil dipasang serta email telah dikirimkan ke pembeli!',
                'invoice' => $invoiceNumber,
                'pdf_path' => $pdfPath,
                'pdf_password' => $pdfPassword
            ];

        } catch (Throwable $e) {
            $err = $db->prepare("UPDATE `store_orders` SET 
                `email_error` = :err 
                WHERE `id` = :id");
            $err->execute([
                ':err' => $e->getMessage(),
                ':id' => $order['id']
            ]);

            throw $e;
        }
    }

    /**
     * Generate password acak 6 karakter terdiri dari huruf kecil (a-z) dan angka (0-9)
     */
    public static function generateRandomPassword(int $length = 6): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $maxIndex = strlen($chars) - 1;
        $pwd = '';
        for ($i = 0; $i < $length; $i++) {
            $pwd .= $chars[random_int(0, $maxIndex)];
        }
        return $pwd;
    }
}
