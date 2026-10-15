<?php
require_once __DIR__ . '/config.php';

function getLmsDb() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . LMS_DB_HOST . ";dbname=" . LMS_DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, LMS_DB_USER, LMS_DB_PASS, $options);
    }
    return $pdo;
}
