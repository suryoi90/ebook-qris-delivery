<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

class BackupService
{
    /**
     * Mendapatkan path direktori penyimpanan backup dan memastikan proteksi folder aktif.
     */
    public static function getBackupDir(): string
    {
        $dir = dirname(__DIR__) . '/storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Pastikan .htaccess terpasang agar berkas backup tidak bisa diakses publik secara bebas
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $htContent = "# Proteksi Direktori Cadangan - Akses Publik Ditolak\n"
                       . "<IfModule mod_authz_core.c>\n"
                       . "  Require all denied\n"
                       . "</IfModule>\n"
                       . "<IfModule !mod_authz_core.c>\n"
                       . "  Deny from all\n"
                       . "</IfModule>\n";
            @file_put_contents($htaccess, $htContent);
        }

        return $dir;
    }

    /**
     * Menghasilkan dump SQL seluruh tabel MySQL dengan streaming cursor (hemat memori cPanel).
     *
     * @param string|null $targetPath Path file output. Jika null, akan dibuat di storage/backups/
     * @param bool $streamDirect Jika true, output akan langsung di-stream ke browser sebagai unduhan
     */
    public static function exportSqlDump(?string $targetPath = null, bool $streamDirect = false): array
    {
        $db = StoreDB::get();
        $timestamp = date('Ymd_His');
        $filename = "store_sql_dump_{$timestamp}.sql";
        $backupDir = self::getBackupDir();

        if ($streamDirect) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            $handle = fopen('php://output', 'w');
        } else {
            if (!$targetPath) {
                $targetPath = $backupDir . '/' . $filename;
            }
            $handle = fopen($targetPath, 'w');
        }

        if (!$handle) {
            throw new Exception("Gagal membuka stream file untuk SQL Dump.");
        }

        // Header SQL
        fwrite($handle, "-- =========================================================\n");
        fwrite($handle, "-- PENCADANGAN BASIS DATA RESMI (SQL DUMP)\n");
        fwrite($handle, "-- Sistem: Toko Resmi & LMS Restia Moegiono\n");
        fwrite($handle, "-- Basis Data: " . DB_NAME . "\n");
        fwrite($handle, "-- Waktu Dibuat: " . date('Y-m-d H:i:s') . " WIB\n");
        fwrite($handle, "-- Host: " . DB_HOST . "\n");
        fwrite($handle, "-- =========================================================\n\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+07:00\";\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        $tablesStmt = $db->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        $totalTables = count($tables);
        $totalRowsExported = 0;

        foreach ($tables as $table) {
            // 1. Ekspor DDL Struktur Tabel
            $createStmt = $db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
            $createTableSql = $createRow['Create Table'] ?? '';

            fwrite($handle, "-- ---------------------------------------------------------\n");
            fwrite($handle, "-- Struktur Tabel `{$table}`\n");
            fwrite($handle, "-- ---------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createTableSql . ";\n\n");

            // 2. Ekspor Baris Data Tabel dengan batch multi-row INSERT (Ringkas & Cepat)
            $countStmt = $db->query("SELECT COUNT(*) FROM `{$table}`");
            $rowCount = (int)$countStmt->fetchColumn();

            if ($rowCount > 0) {
                fwrite($handle, "-- Data untuk Tabel `{$table}` (Total {$rowCount} baris)\n");
                $selectStmt = $db->query("SELECT * FROM `{$table}`");

                $batchSize = 50;
                $currentBatch = [];
                $columns = null;

                while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
                    $totalRowsExported++;
                    if ($columns === null) {
                        $columns = "`" . implode("`, `", array_keys($row)) . "`";
                    }

                    $escapedVals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $escapedVals[] = "NULL";
                        } elseif (is_numeric($val) && !str_starts_with((string)$val, '0')) {
                            $escapedVals[] = $val;
                        } else {
                            $escapedVals[] = $db->quote((string)$val);
                        }
                    }
                    $currentBatch[] = "(" . implode(", ", $escapedVals) . ")";

                    if (count($currentBatch) >= $batchSize) {
                        fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $currentBatch) . ";\n");
                        $currentBatch = [];
                    }
                }

                if (!empty($currentBatch)) {
                    fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $currentBatch) . ";\n");
                }

                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fwrite($handle, "-- =========================================================\n");
        fwrite($handle, "-- AKHIR DARI PENCADANGAN BASIS DATA\n");
        fwrite($handle, "-- =========================================================\n");

        fclose($handle);

        if ($streamDirect) {
            exit;
        }

        $fileSize = filesize($targetPath);
        $checksum = hash_file('sha256', $targetPath);

        return [
            'success' => true,
            'type' => 'SQL',
            'filename' => $filename,
            'path' => $targetPath,
            'size' => $fileSize,
            'size_formatted' => self::formatBytes($fileSize),
            'checksum' => $checksum,
            'tables_count' => $totalTables,
            'rows_count' => $totalRowsExported,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Membuat Full Backup Sistem (ZIP: Database SQL Dump + Source Code Toko + Scripts).
     */
    public static function createFullBackup(): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception("Ekstensi PHP ZipArchive tidak aktif di server.");
        }

        $backupDir = self::getBackupDir();
        $timestamp = date('Ymd_His');
        $zipFilename = "store_full_backup_{$timestamp}.zip";
        $zipPath = $backupDir . '/' . $zipFilename;
        $tempSqlPath = $backupDir . "/temp_db_{$timestamp}.sql";

        // 1. Buat Dump Database SQL
        $sqlResult = self::exportSqlDump($tempSqlPath, false);

        // 2. Inisialisasi ZIP Archive
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempSqlPath);
            throw new Exception("Gagal membuat berkas arsip ZIP: {$zipPath}");
        }

        // 3. Masukkan Berkas SQL Dump ke dalam ZIP
        $zip->addFile($tempSqlPath, 'database/database_dump.sql');

        // 4. Masukkan Berkas Kode Sumber Inti Aplikasi Toko
        $rootDir = dirname(__DIR__);

        $filesToInclude = [
            'index.php',
            'product.php',
            'cart-drawer.php',
            'track.php',
            'guide.php',
            'license.php',
            'terms.php',
            'db.php',
            'config.php',
            '.htaccess'
        ];

        foreach ($filesToInclude as $fn) {
            $full = $rootDir . '/' . $fn;
            if (file_exists($full)) {
                $zip->addFile($full, 'source/' . $fn);
            }
        }

        // Tambahkan Direktori Penting: services, api, admin, payment, bin
        self::addDirToZip($zip, $rootDir . '/services', 'source/services');
        self::addDirToZip($zip, $rootDir . '/api', 'source/api');
        self::addDirToZip($zip, $rootDir . '/admin', 'source/admin');
        self::addDirToZip($zip, $rootDir . '/payment', 'source/payment');
        self::addDirToZip($zip, $rootDir . '/bin', 'source/bin');

        // 5. Tambahkan Manifest JSON
        $manifest = [
            'system_name' => 'Restia Moegiono Official Store & LMS',
            'backup_type' => 'FULL_SYSTEM_BACKUP',
            'created_at' => date('Y-m-d H:i:s'),
            'database' => DB_NAME,
            'tables_backed_up' => $sqlResult['tables_count'],
            'rows_backed_up' => $sqlResult['rows_count'],
            'php_version' => PHP_VERSION,
            'sql_checksum' => $sqlResult['checksum']
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zip->close();

        // Hapus file dump SQL sementara
        @unlink($tempSqlPath);

        $fileSize = filesize($zipPath);
        $checksum = hash_file('sha256', $zipPath);

        return [
            'success' => true,
            'type' => 'FULL',
            'filename' => $zipFilename,
            'path' => $zipPath,
            'size' => $fileSize,
            'size_formatted' => self::formatBytes($fileSize),
            'checksum' => $checksum,
            'tables_count' => $sqlResult['tables_count'],
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Menambahkan direktori ke ZipArchive secara rekursif.
     */
    private static function addDirToZip(ZipArchive $zip, string $dirPath, string $zipSubdir, array $excludeNames = []): void
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $items = scandir($dirPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $excludeNames, true)) {
                continue;
            }

            $fullPath = $dirPath . '/' . $item;
            $zipPath = ($zipSubdir !== '' ? $zipSubdir . '/' : '') . $item;

            // Hindari file cache, log, atau direktori rahasia besar
            if ($item === '.git' || str_ends_with($item, '.log')) {
                continue;
            }

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($zipPath);
                self::addDirToZip($zip, $fullPath, $zipPath, $excludeNames);
            } else {
                $zip->addFile($fullPath, $zipPath);
            }
        }
    }

    /**
     * Membaca seluruh daftar berkas backup yang tersedia di server.
     */
    public static function listBackups(): array
    {
        $backupDir = self::getBackupDir();
        $files = scandir($backupDir);
        $backups = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['sql', 'zip'], true)) {
                continue;
            }

            $fullPath = $backupDir . '/' . $file;
            $size = filesize($fullPath);
            $type = ($ext === 'sql') ? 'SQL' : 'FULL';

            $backups[] = [
                'filename' => $file,
                'type' => $type,
                'size' => $size,
                'size_formatted' => self::formatBytes($size),
                'created_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
                'checksum' => hash_file('sha256', $fullPath)
            ];
        }

        // Urutkan backup terbaru di paling atas
        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $backups;
    }

    /**
     * Mengunduh berkas backup dengan aman melalui streaming header.
     */
    public static function downloadBackup(string $filename): void
    {
        $safeFilename = basename($filename);
        $ext = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

        if (!in_array($ext, ['sql', 'zip'], true)) {
            throw new Exception("Tipe berkas tidak valid untuk diunduh.");
        }

        $backupDir = self::getBackupDir();
        $filePath = $backupDir . '/' . $safeFilename;

        if (!file_exists($filePath)) {
            throw new Exception("Berkas cadangan tidak ditemukan di server: {$safeFilename}");
        }

        $mimeType = ($ext === 'sql') ? 'application/sql' : 'application/zip';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Menghapus berkas cadangan dari server.
     */
    public static function deleteBackup(string $filename): bool
    {
        $safeFilename = basename($filename);
        $backupDir = self::getBackupDir();
        $filePath = $backupDir . '/' . $safeFilename;

        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return false;
    }

    /**
     * Format ukuran byte ke unit yang ramah dibaca (KB / MB).
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        return $bytes . ' B';
    }
}
