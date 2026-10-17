<?php
/**
 * Konfigurasi Terpusat Toko Digital Restia Moegiono
 * Domain: store.restiamoegiono.com
 */

define('STORE_ROOT', __DIR__);

// Basis Data MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'restiamo_portal');
define('DB_USER', 'restiamo_puser');
define('DB_PASS', '%nZc#FxLrFY%_cGFsdE3A1!');

// Kredensial DOKU Payment Gateway Resmi
define('DOKU_CLIENT_ID', 'BRN-0237-1789470449049');
define('DOKU_SECRET_KEY', 'SK-3QTfVTdvS1fUruD65iLl');
define('DOKU_API_KEY', 'doku_key_3105fd2db316451a8c99894973505ffa');
define('DOKU_BASE_URL', 'https://api.doku.com'); // Live Production

// Kredensial Email & SMTP cPanel
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 465);
define('SMTP_USER', 'order@restiamoegiono.com');
define('SMTP_PASS', 'RestiaOrder2026!Secure');
define('SMTP_FROM_NAME', 'Restia Moegiono Official Store');
define('SMTP_FROM_EMAIL', 'order@restiamoegiono.com');

// Direktori Penyimpanan Berkas Master & Berkas Hasil Watermark
define('MASTER_EBOOKS_DIR', '/home/restiamo/store-app/storage/ebooks');
define('GENERATED_EBOOKS_DIR', STORE_ROOT . '/storage/generated');
define('BIN_DIR', STORE_ROOT . '/bin');

// URL Publik
define('APP_URL', 'https://store.restiamoegiono.com');
define('STORE_URL', 'https://store.restiamoegiono.com');
define('CALLBACK_URL', APP_URL . '/payment/result');

// Admin Auth
define('ADMIN_PASSWORD', '##Bismillah26');
define('ADMIN_EMAIL', 'restia@restiamoegiono.com');

// Token Rahasia Kriptografi Unduhan Terproteksi
define('DOWNLOAD_SALT', 'RM_SECURE_DOWNLOAD_KEY_#Bismillah26_2026!');

// Enforce Modern HTTP Security Headers & Unset Server Leakage
@ini_set('expose_php', 'off');
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(self "https://pay.doku.com" "https://sandbox.doku.com")');
    if (function_exists('header_remove')) {
        header_remove('X-Powered-By');
        header_remove('X-Turbo-Charged-By');
    }
}

