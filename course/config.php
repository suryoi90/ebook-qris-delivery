<?php
/**
 * Konfigurasi Terpusat LMS Restia Moegiono
 * Domain: restiamoegiono.com/course/
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basis Data MySQL
define('LMS_DB_HOST', 'localhost');
define('LMS_DB_NAME', 'restiamo_portal');
define('LMS_DB_USER', 'restiamo_puser');
define('LMS_DB_PASS', '%nZc#FxLrFY%_cGFsdE3A1!');

// URL & Direktori
define('LMS_BASE_URL', 'https://restiamoegiono.com/course');
define('STORE_URL', 'https://store.restiamoegiono.com');
define('MAIN_URL', 'https://restiamoegiono.com');

// SMTP Email Transaksional
define('LMS_SMTP_HOST', 'localhost');
define('LMS_SMTP_PORT', 465);
define('LMS_SMTP_USER', 'order@restiamoegiono.com');
define('LMS_SMTP_PASS', 'RestiaOrder2026!Secure');
define('LMS_SMTP_FROM_NAME', 'Restia Moegiono Online Academy');
define('LMS_SMTP_FROM_EMAIL', 'order@restiamoegiono.com');

// Helper Session Siswa
function getLoggedInStudent() {
    if (!empty($_SESSION['lms_student_email'])) {
        return [
            'email' => $_SESSION['lms_student_email'],
            'name' => $_SESSION['lms_student_name'] ?? 'Peserta Kursus',
            'is_demo' => !empty($_SESSION['lms_is_demo'])
        ];
    }
    return null;
}
