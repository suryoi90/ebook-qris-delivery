<?php
require_once __DIR__ . '/../config.php';

class DokuService
{
    /**
     * Buat Sesi Pembayaran DOKU Checkout
     */
    public static function createPayment(
        string $invoiceNumber,
        int $amount,
        string $productTitle,
        string $customerName,
        string $customerEmail
    ): array {
        $target = '/checkout/v1/payment';
        $requestId = bin2hex(random_bytes(16));
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        $payload = [
            'order' => [
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'currency' => 'IDR',
                'callback_url' => CALLBACK_URL . '?invoice=' . urlencode($invoiceNumber),
                'callback_url_result' => CALLBACK_URL . '?invoice=' . urlencode($invoiceNumber),
                'auto_redirect' => true,
                'line_items' => [
                    [
                        'name' => mb_strimwidth($productTitle, 0, 50, '...'),
                        'price' => $amount,
                        'quantity' => 1
                    ]
                ]
            ],
            'payment' => [
                'payment_due_date' => 120 // 2 Jam masa berlaku QRIS / VA
            ],
            'customer' => [
                'name' => $customerName,
                'email' => $customerEmail
            ]
        ];

        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $digest = base64_encode(hash('sha256', $rawBody, true));

        $component = "Client-Id:" . DOKU_CLIENT_ID . "\n"
                   . "Request-Id:" . $requestId . "\n"
                   . "Request-Timestamp:" . $timestamp . "\n"
                   . "Request-Target:" . $target . "\n"
                   . "Digest:" . $digest;

        $sig = base64_encode(hash_hmac('sha256', $component, DOKU_SECRET_KEY, true));
        $signature = 'HMACSHA256=' . $sig;

        $headers = [
            'Client-Id: ' . DOKU_CLIENT_ID,
            'Request-Id: ' . $requestId,
            'Request-Timestamp: ' . $timestamp,
            'Signature: ' . $signature,
            'api-key: ' . DOKU_API_KEY,
            'Content-Type: application/json'
        ];

        $ch = curl_init(DOKU_BASE_URL . $target);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $rawBody,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 20
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Koneksi cURL ke DOKU gagal: " . $curlError);
        }

        $resData = json_decode($response, true) ?: [];
        $paymentUrl = $resData['response']['payment']['url'] ?? null;

        if ($httpCode >= 200 && $httpCode < 300 && !empty($paymentUrl)) {
            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'raw' => $resData
            ];
        }

        $msg = $resData['message'][0] ?? ($resData['error']['message'] ?? "HTTP {$httpCode}: Gagal membuat sesi pembayaran DOKU");
        return [
            'success' => false,
            'error' => $msg,
            'raw' => $resData
        ];
    }

    /**
     * Verifikasi Tanda Tangan Webhook Notifikasi DOKU
     */
    public static function verifyNotification(
        string $signatureHeader,
        string $requestId,
        string $timestamp,
        string $target,
        string $rawBody
    ): bool {
        if (empty($signatureHeader) || empty(DOKU_SECRET_KEY)) {
            return false;
        }

        $digest = base64_encode(hash('sha256', $rawBody, true));
        $component = "Client-Id:" . DOKU_CLIENT_ID . "\n"
                   . "Request-Id:" . $requestId . "\n"
                   . "Request-Timestamp:" . $timestamp . "\n"
                   . "Request-Target:" . $target . "\n"
                   . "Digest:" . $digest;

        $expectedSig = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $component, DOKU_SECRET_KEY, true));

        return hash_equals($expectedSig, $signatureHeader);
    }
}
