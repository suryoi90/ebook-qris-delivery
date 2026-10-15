<?php
/**
 * Handler Pengunduhan Berkas E-Book Berlisensi Resmi
 * Restia Moegiono Official Store
 * 
 * Melayani pengunduhan streaming hemat RAM dengan verifikasi tanda tangan kriptografi
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/DownloadService.php';

$invoice = trim($_GET['inv'] ?? ($_GET['invoice'] ?? ''));
$productId = trim($_GET['prod'] ?? ($_GET['product_id'] ?? ($_GET['id'] ?? '')));
$token = trim($_GET['token'] ?? ($_GET['t'] ?? ''));

DownloadService::serveFile($invoice, $productId, $token);
