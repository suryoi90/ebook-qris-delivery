<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/FulfillmentService.php';

$invoice = trim($_GET['invoice'] ?? ($_POST['invoice'] ?? ''));
$email = trim($_GET['email'] ?? ($_POST['email'] ?? ''));
$action = trim($_GET['action'] ?? ($_POST['action'] ?? ''));

if (empty($invoice) && empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Masukkan nomor invoice atau alamat email Anda.']);
    exit;
}

try {
    $db = StoreDB::get();

    // 1. Aksi Kirim Ulang Berkas ke Email Pembeli (Self-Service Resend)
    if ($action === 'resend') {
        if (empty($invoice) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invoice dan Email diperlukan untuk verifikasi keamanan pengiriman ulang.']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM `store_orders` WHERE `invoice_number` = ? AND LOWER(`customer_email`) = LOWER(?) LIMIT 1");
        $stmt->execute([$invoice, $email]);
        $order = $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Data pesanan dengan kombinasi invoice dan email tersebut tidak ditemukan.']);
            exit;
        }

        if (!in_array($order['payment_status'], ['PAID', 'SUCCESS'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Pesanan ini belum berstatus Lunas (PAID). Berkas hanya dapat dikirimkan setelah pembayaran terkonfirmasi.']);
            exit;
        }

        // Jalankan pengiriman ulang
        $res = FulfillmentService::fulfillOrder($invoice, true);

        echo json_encode([
            'success' => true,
            'message' => "Berkas PDF ber-watermark resmi berhasil dikirimkan ulang ke alamat email: {$order['customer_email']}!"
        ]);
        exit;
    }

    // 2. Pencarian Status Pesanan
    if (!empty($invoice)) {
        $stmt = $db->prepare("SELECT `invoice_number`, `product_title`, `customer_name`, `customer_email`, `amount`, `discount_amount`, `coupon_code`, `payment_status`, `email_sent`, `email_sent_at`, `created_at` 
            FROM `store_orders` WHERE `invoice_number` = ? LIMIT 1");
        $stmt->execute([$invoice]);
        $order = $stmt->fetch();
        $orders = $order ? [$order] : [];
    } else {
        $stmt = $db->prepare("SELECT `invoice_number`, `product_title`, `customer_name`, `customer_email`, `amount`, `discount_amount`, `coupon_code`, `payment_status`, `email_sent`, `email_sent_at`, `created_at` 
            FROM `store_orders` WHERE LOWER(`customer_email`) = LOWER(?) ORDER BY `id` DESC LIMIT 10");
        $stmt->execute([$email]);
        $orders = $stmt->fetchAll();
    }

    if (empty($orders)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Tidak ditemukan transaksi yang sesuai dengan data pencarian.']);
        exit;
    }

    $formatted = array_map(function($o) {
        $isPaid = in_array($o['payment_status'], ['PAID', 'SUCCESS']);
        return [
            'invoice' => $o['invoice_number'],
            'product_title' => $o['product_title'],
            'customer_name' => $o['customer_name'],
            'customer_email' => $o['customer_email'],
            'amount' => (int)$o['amount'],
            'discount_amount' => (int)($o['discount_amount'] ?? 0),
            'coupon_code' => $o['coupon_code'] ?? null,
            'status' => $o['payment_status'],
            'is_paid' => $isPaid,
            'email_sent' => (bool)$o['email_sent'],
            'email_sent_at' => $o['email_sent_at'],
            'created_at' => $o['created_at']
        ];
    }, $orders);

    echo json_encode([
        'success' => true,
        'orders' => $formatted
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
