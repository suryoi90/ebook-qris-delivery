<?php
/**
 * LogService.php — Layanan Pemantauan Kesehatan Sistem & Agregator Log
 * Restia Moegiono Command Vault
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

class LogService {

    /**
     * Memeriksa kesehatan ekosistem (Store, Website Utama, LMS, Database, Disk Storage)
     */
    public static function getSystemHealth(): array {
        $health = [
            'timestamp' => date('Y-m-d H:i:s T'),
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'LiteSpeed / Apache',
                'memory_limit' => ini_get('memory_limit') ?: 'N/A',
                'max_execution_time' => ini_get('max_execution_time') . 's',
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_current' => self::formatBytes(memory_get_usage(true)),
                'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
            ],
            'database' => [
                'status' => 'ONLINE',
                'database_name' => DB_NAME,
                'host' => DB_HOST,
                'tables_count' => 0,
                'total_orders' => 0,
                'paid_orders' => 0,
                'latency_ms' => 0,
            ],
            'storage' => [
                'generated_cache_count' => 0,
                'generated_cache_size' => '0 B',
                'backups_count' => 0,
                'backups_size' => '0 B',
                'htaccess_protected' => false,
            ],
            'endpoints' => [
                'store' => ['url' => 'https://store.restiamoegiono.com', 'status' => 'ONLINE', 'http_code' => 200, 'latency_ms' => 0],
                'main_website' => ['url' => 'https://restiamoegiono.com', 'status' => 'CHECKING', 'http_code' => null, 'latency_ms' => 0],
                'lms' => ['url' => 'https://restiamoegiono.com/course/', 'status' => 'CHECKING', 'http_code' => null, 'latency_ms' => 0],
            ],
            'log_files' => self::getLogFilesSummary(),
        ];

        // 1. Cek Latensi & Status Database MySQL
        $t0 = microtime(true);
        try {
            $db = StoreDB::get();
            $stmt = $db->query("SHOW TABLES");
            $health['database']['tables_count'] = count($stmt->fetchAll(PDO::FETCH_COLUMN));

            $ordStmt = $db->query("SELECT 
                COUNT(*) as total, 
                SUM(CASE WHEN `payment_status` IN ('PAID', 'SUCCESS') THEN 1 ELSE 0 END) as paid 
                FROM `store_orders`");
            $ordData = $ordStmt->fetch(PDO::FETCH_ASSOC);
            $health['database']['total_orders'] = (int)($ordData['total'] ?? 0);
            $health['database']['paid_orders'] = (int)($ordData['paid'] ?? 0);
            $health['database']['latency_ms'] = round((microtime(true) - $t0) * 1000, 2);
        } catch (Throwable $e) {
            $health['database']['status'] = 'OFFLINE: ' . $e->getMessage();
        }

        // 2. Cek Kapasitas Storage & Cache
        $genDir = __DIR__ . '/../storage/generated';
        if (is_dir($genDir)) {
            $files = glob($genDir . '/*.pdf') ?: [];
            $health['storage']['generated_cache_count'] = count($files);
            $totBytes = 0;
            foreach ($files as $f) {
                if (is_file($f)) $totBytes += filesize($f);
            }
            $health['storage']['generated_cache_size'] = self::formatBytes($totBytes);

            $htaccessFile = $genDir . '/.htaccess';
            if (file_exists($htaccessFile)) {
                $htContent = file_get_contents($htaccessFile);
                if (stripos($htContent, 'Require all denied') !== false || stripos($htContent, 'Deny from all') !== false) {
                    $health['storage']['htaccess_protected'] = true;
                }
            }
        }

        $backDir = __DIR__ . '/../storage/backups';
        if (is_dir($backDir)) {
            $bFiles = glob($backDir . '/*') ?: [];
            $health['storage']['backups_count'] = count($bFiles);
            $bBytes = 0;
            foreach ($bFiles as $bf) {
                if (is_file($bf)) $bBytes += filesize($bf);
            }
            $health['storage']['backups_size'] = self::formatBytes($bBytes);
        }

        // 3. Cek Status Endpoint Ekosistem secara Internal
        $health['endpoints']['main_website'] = self::pingEndpoint('https://restiamoegiono.com');
        $health['endpoints']['lms'] = self::pingEndpoint('https://restiamoegiono.com/course/');
        $health['endpoints']['store'] = self::pingEndpoint('https://store.restiamoegiono.com');

        return $health;
    }

    /**
     * Memeriksa daftar dan ukuran berkas log yang tersedia
     */
    public static function getLogFilesSummary(): array {
        $targets = [
            'store' => [
                'name' => 'Toko E-Book (Store)',
                'path' => self::resolveLogPath('store'),
                'domain' => 'store.restiamoegiono.com'
            ],
            'main' => [
                'name' => 'Website Utama & LMS',
                'path' => self::resolveLogPath('main'),
                'domain' => 'restiamoegiono.com'
            ]
        ];

        $summary = [];
        foreach ($targets as $k => $info) {
            $exists = file_exists($info['path']);
            $size = $exists ? filesize($info['path']) : 0;
            $mod = $exists ? filemtime($info['path']) : null;
            $summary[$k] = [
                'key' => $k,
                'name' => $info['name'],
                'domain' => $info['domain'],
                'path' => $info['path'],
                'exists' => $exists,
                'size_bytes' => $size,
                'size_formatted' => self::formatBytes($size),
                'last_modified' => $mod ? date('Y-m-d H:i:s T', $mod) : null,
            ];
        }

        return $summary;
    }

    /**
     * Membaca N baris terakhir dari berkas log secara efisien (menggunakan fseek)
     * dan mem-parsing ke dalam format data terstruktur.
     */
    public static function readLog(string $target = 'store', int $maxLines = 300): array {
        $filePath = self::resolveLogPath($target);
        if (!file_exists($filePath)) {
            return [
                'target' => $target,
                'file_path' => $filePath,
                'exists' => false,
                'entries' => [],
                'stats' => ['total' => 0, 'fatal' => 0, 'warning' => 0, 'notice' => 0, 'other' => 0],
            ];
        }

        $rawLines = self::tailFile($filePath, $maxLines);
        $entries = [];
        $stats = ['total' => 0, 'fatal' => 0, 'warning' => 0, 'notice' => 0, 'other' => 0];

        $currentEntry = null;

        foreach ($rawLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Pola format log PHP standar: [16-Oct-2026 21:50:12 UTC] PHP Fatal error: ...
            if (preg_match('/^\[([^\]]+)\]\s+(?:PHP\s+)?([A-Za-z\s]+?):\s*(.*)$/i', $line, $matches)) {
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }

                $timeRaw = $matches[1];
                $typeRaw = trim($matches[2]);
                $messageRaw = trim($matches[3]);

                $severity = 'OTHER';
                $lowerType = strtolower($typeRaw);
                if (strpos($lowerType, 'fatal') !== false || strpos($lowerType, 'parse') !== false || strpos($lowerType, 'uncaught') !== false) {
                    $severity = 'FATAL';
                    $stats['fatal']++;
                } elseif (strpos($lowerType, 'warning') !== false) {
                    $severity = 'WARNING';
                    $stats['warning']++;
                } elseif (strpos($lowerType, 'notice') !== false || strpos($lowerType, 'deprecated') !== false) {
                    $severity = 'NOTICE';
                    $stats['notice']++;
                } else {
                    $stats['other']++;
                }
                $stats['total']++;

                // Deteksi file dan line jika ada (contoh: "in /path/to/file.php on line 123")
                $file = null;
                $lineNum = null;
                if (preg_match('/in\s+([^\s]+)\s+on\s+line\s+(\d+)/i', $messageRaw, $fMatches)) {
                    $file = $fMatches[1];
                    $lineNum = (int)$fMatches[2];
                }

                $currentEntry = [
                    'timestamp' => $timeRaw,
                    'severity' => $severity,
                    'type' => $typeRaw,
                    'message' => $messageRaw,
                    'file' => $file,
                    'line' => $lineNum,
                    'stack_trace' => [],
                ];
            } else {
                // Baris lanjutan / Stack trace
                if ($currentEntry !== null) {
                    $currentEntry['stack_trace'][] = $line;
                }
            }
        }

        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        // Urutkan dari yang paling baru (terbaru di atas)
        $entries = array_reverse($entries);

        return [
            'target' => $target,
            'file_path' => $filePath,
            'exists' => true,
            'file_size' => self::formatBytes(filesize($filePath)),
            'entries' => $entries,
            'stats' => $stats,
        ];
    }

    /**
     * Mengosongkan berkas log
     */
    public static function clearLog(string $target = 'store'): bool {
        $filePath = self::resolveLogPath($target);
        if (file_exists($filePath)) {
            $fp = @fopen($filePath, 'w');
            if ($fp) {
                fclose($fp);
                return true;
            }
        }
        return false;
    }

    /**
     * Membaca N baris terakhir dari file besar secara efisien
     */
    private static function tailFile(string $filepath, int $lines = 300): array {
        $f = @fopen($filepath, "rb");
        if ($f === false) return [];

        $buffer = "";
        fseek($f, 0, SEEK_END);
        $pos = ftell($f);
        $lineCount = 0;
        $chunkSize = 4096;

        while ($pos > 0 && $lineCount <= $lines) {
            $seek = max(0, $pos - $chunkSize);
            $bytesToRead = $pos - $seek;
            fseek($f, $seek, SEEK_SET);
            $chunk = fread($f, $bytesToRead);
            $buffer = $chunk . $buffer;
            $pos = $seek;
            $lineCount = substr_count($buffer, "\n");
        }
        fclose($f);

        $allLines = explode("\n", $buffer);
        return array_slice($allLines, -$lines);
    }

    /**
     * Resolusi path absolut ke error_log
     */
    private static function resolveLogPath(string $target): string {
        if ($target === 'main') {
            return '/home/restiamo/public_html/error_log';
        }
        // default store
        return __DIR__ . '/../error_log';
    }

    /**
     * Cek respon endpoint menggunakan cURL
     */
    private static function pingEndpoint(string $url, int $timeout = 4): array {
        $t0 = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'RestiaMoegiono-HealthBot/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $latency = round((microtime(true) - $t0) * 1000, 2);
        $err = curl_error($ch);
        curl_close($ch);

        $status = ($httpCode >= 200 && $httpCode < 400) ? 'ONLINE' : ($httpCode > 0 ? "HTTP_{$httpCode}" : 'OFFLINE');

        return [
            'url' => $url,
            'status' => $status,
            'http_code' => $httpCode ?: null,
            'latency_ms' => $latency,
            'error' => $err ?: null,
        ];
    }

    /**
     * Format byte ke satuan yang mudah dibaca manusia (KB, MB, GB)
     */
    public static function formatBytes(int $bytes, int $precision = 2): string {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $floor = floor($base);
        return round(pow(1024, $base - $floor), $precision) . ' ' . ($units[$floor] ?? 'B');
    }
}
