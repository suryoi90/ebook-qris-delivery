<?php
require_once __DIR__ . '/../config.php';

class WatermarkService
{
    /**
     * Stempel Watermark DRM Lisensi Personal ke PDF
     */
    public static function stampPdf(
        string $masterFileName,
        string $title,
        string $customerName,
        string $customerEmail,
        string $customerOrg,
        string $invoiceNumber
    ): string {
        $masterPath = MASTER_EBOOKS_DIR . '/' . $masterFileName;
        if (!file_exists($masterPath)) {
            // Cek fallback direktori lokal jika dipindahkan
            $fallback = STORE_ROOT . '/storage/ebooks/' . $masterFileName;
            if (file_exists($fallback)) {
                $masterPath = $fallback;
            } else {
                throw new Exception("Master PDF file tidak ditemukan: {$masterPath}");
            }
        }

        $cleanInv = preg_replace('/[^a-zA-Z0-9_\-]/', '', $invoiceNumber);
        $cleanMaster = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $masterFileName);

        $outputDir = GENERATED_EBOOKS_DIR;
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/' . $cleanInv . '_' . $cleanMaster;

        $scriptPath = BIN_DIR . '/watermark.py';
        if (!file_exists($scriptPath)) {
            throw new Exception("Script watermark.py tidak ditemukan di: {$scriptPath}");
        }

        $cmd = sprintf(
            'python3 %s --input %s --output %s --title %s --name %s --email %s --org %s --invoice %s 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($masterPath),
            escapeshellarg($outputPath),
            escapeshellarg($title),
            escapeshellarg($customerName),
            escapeshellarg($customerEmail),
            escapeshellarg($customerOrg ?: '-'),
            escapeshellarg($invoiceNumber)
        );

        $output = shell_exec($cmd);

        if (!file_exists($outputPath) || filesize($outputPath) < 5000) {
            throw new Exception("Gagal menghasilkan PDF ber-watermark. Output: " . trim((string)$output));
        }

        return $outputPath;
    }
}
