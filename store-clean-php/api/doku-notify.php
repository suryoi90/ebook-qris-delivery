<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/DokuService.php';
require_once __DIR__ . '/../services/FulfillmentService.php';

$rawBody = file_get_contents('php://input');
$headers = getallheaders();

// Normalisasi Header Keys
$normalizedHeaders = [];
foreach ($headers as $k => $v) {
    $normalizedHeaders[strtolower($k)] = $v;
}

$signature = $normalizedHeaders['signature'] ?? '';
$requestId = $normalizedHeaders['request-id'] ?? '';
$timestamp = $normalizedHeaders['request-timestamp'] ?? '';
$clientId  = $normalizedHeaders['client-id'] ?? '';
$target    = $_SERVER['REQUEST_URI'] ?? '/api/doku-notify.php';
$targetPath = parse_url($target, PHP_URL_PATH);

$payload = json_decode($rawBody, true) ?: [];
$invoiceNumber = $payload['order']['invoice_number'] ?? ($payload['invoice_number'] ?? '');
$trxStatus = strtoupper($payload['transaction']['status'] ?? ($payload['status'] ?? ''));

// Dukungan Test Mode dengan Admin Secret Token
$isTestAuth = false;
if (!empty($_GET['test_token']) && hash_equals(hash('sha256', ADMIN_PASSWORD), $_GET['test_token'])) {
    $isTestAuth = true;
}

// Verifikasi Tanda Tangan DOKU (kecuali jika dipicu secara terautentikasi oleh admin test)
$isSignatureValid = false;
if ($isTestAuth) {
    $isSignatureValid = true;
} else {
    $isSignatureValid = DokuService::verifyNotification(
        $signature,
        $requestId,
        $timestamp,
        $targetPath,
        $rawBody
    );
}

if (!$isSignatureValid) {
    http_response_code(401);
    echo json_encode([
        'status' => 'FAILED',
        'message' => 'Tanda tangan DOKU tidak valid.'
    ]);
    exit;
}

if (empty($invoiceNumber)) {
    http_response_code(400);
    echo json_encode(['status' => 'FAILED', 'message' => 'Invoice number tidak ditemukan.']);
    exit;
}

try {
    $db = StoreDB::get();

    // Simpan raw log webhook untuk transparansi audit
    $stmtLog = $db->prepare("UPDATE `store_orders` SET 
        `doku_payment_id` = :pid, 
        `doku_raw_response` = :raw 
        WHERE `invoice_number` = :inv");
    $stmtLog->execute([
        ':pid' => $payload['transaction']['id'] ?? ($payload['payment']['id'] ?? 'DOKU-NOTIF'),
        ':raw' => $rawBody,
        ':inv' => $invoiceNumber
    ]);

    // Jika status pembayaran sukses, jalankan proses watermark & kirim email
    if ($trxStatus === 'SUCCESS' || $trxStatus === 'PAID') {
        $result = FulfillmentService::fulfillOrder($invoiceNumber);
        
        http_response_code(200);
        echo json_encode([
            'status' => 'SUCCESS',
            'message' => 'Pembayaran berhasil diverifikasi dan e-book telah dikirimkan.',
            'details' => $result
        ]);
        exit;
    }

    // Jika status gagal/kedaluwarsa
    if (in_array($trxStatus, ['FAILED', 'EXPIRED'])) {
        $stmtFail = $db->prepare("UPDATE `store_orders` SET `payment_status` = :st WHERE `invoice_number` = :inv");
        $stmtFail->execute([':st' => $trxStatus, ':inv' => $invoiceNumber]);
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'RECEIVED',
        'message' => 'Notifikasi diterima status: ' . $trxStatus
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ]);
}
