import urllib.request
import urllib.parse
import ssl
import json
import secrets

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

code = '''<?php
header('Content-Type: application/json');
try {
    $pdo = new PDO('mysql:host=localhost;dbname=restiamo_portal;charset=utf8mb4', 'restiamo_puser', '%nZc#FxLrFY%_cGFsdE3A1!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cols = $pdo->query("SHOW COLUMNS FROM `store_products`")->fetchAll(PDO::FETCH_COLUMN);
    
    $added = [];
    if (!in_array('slug', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `slug` VARCHAR(100) NULL AFTER `id`");
        $pdo->exec("UPDATE `store_products` SET `slug` = `id` WHERE `slug` IS NULL OR `slug` = ''");
        $pdo->exec("ALTER TABLE `store_products` ADD UNIQUE KEY `idx_slug` (`slug`)");
        $added[] = 'slug';
    }

    if (!in_array('subtitle', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `subtitle` VARCHAR(255) NULL AFTER `title`");
        $added[] = 'subtitle';
    }

    if (!in_array('table_of_contents', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `table_of_contents` LONGTEXT NULL AFTER `description`");
        $added[] = 'table_of_contents';
    }

    if (!in_array('target_audience', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `target_audience` TEXT NULL AFTER `table_of_contents`");
        $added[] = 'target_audience';
    }

    if (!in_array('benefits', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `benefits` TEXT NULL AFTER `target_audience`");
        $added[] = 'benefits';
    }

    if (!in_array('file_format', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `file_format` VARCHAR(50) DEFAULT 'PDF Digital (Universal)' AFTER `pages`");
        $added[] = 'file_format';
    }

    if (!in_array('file_size', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `file_size` VARCHAR(50) DEFAULT '15-25 MB' AFTER `file_format`");
        $added[] = 'file_size';
    }

    if (!in_array('language', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `language` VARCHAR(50) DEFAULT 'Bahasa Indonesia' AFTER `file_size`");
        $added[] = 'language';
    }

    if (!in_array('edition', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `edition` VARCHAR(100) DEFAULT 'Edisi 2026 Revisi' AFTER `version`");
        $added[] = 'edition';
    }

    if (!in_array('isbn', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `isbn` VARCHAR(50) DEFAULT '' AFTER `edition`");
        $added[] = 'isbn';
    }

    if (!in_array('order_index', $cols)) {
        $pdo->exec("ALTER TABLE `store_products` ADD COLUMN `order_index` INT(11) DEFAULT 0 AFTER `is_active`");
        $added[] = 'order_index';
    }

    // Pastikan slug terisi untuk semua produk yang ada
    $pdo->exec("UPDATE `store_products` SET `slug` = `id` WHERE `slug` IS NULL OR `slug` = ''");

    // Populate sample rich data for current 4 products if empty
    $products = $pdo->query("SELECT id, title, description, table_of_contents FROM `store_products`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $p) {
        $updates = [];
        $params = [];

        if (empty($p['table_of_contents'])) {
            if ($p['id'] === 'course-iso-27001') {
                $updates[] = "`subtitle` = ?";
                $params[] = "Panduan Sistematis Membangun, Mendokumentasikan, dan Mengaudit SMKI Berbasis Risiko";
                $updates[] = "`table_of_contents` = ?";
                $params[] = "<ul>
<li><strong>Bab I:</strong> ISO/IEC 27001:2022 dalam Praktik Organisasi</li>
<li><strong>Bab II:</strong> Ruang Lingkup, Acuan Normatif, dan Istilah Klausul 1-3</li>
<li><strong>Bab III:</strong> Klausul 4 Konteks Organisasi & Identifikasi Isu Internal/Eksternal</li>
<li><strong>Bab IV:</strong> Klausul 5 Kepemimpinan, Komitmen Manajemen, & Kebijakan SMKI</li>
<li><strong>Bab V:</strong> Klausul 6 Perencanaan, Penilaian Risiko, & Sasaran Keamanan</li>
<li><strong>Bab VI:</strong> Klausul 7 Dukungan, Sumber Daya, Kompetensi, & Kesadaran Keamanan</li>
<li><strong>Bab VII:</strong> Klausul 8 Operasi & Pengendalian Risiko Keamanan Informasi</li>
<li><strong>Bab VIII:</strong> Klausul 9 Evaluasi Kinerja, Pemantauan, Audit Internal, & Tinjauan Manajemen</li>
<li><strong>Bab IX:</strong> Klausul 10 Tindakan Korektif & Peningkatan Berkelanjutan</li>
<li><strong>Lampiran:</strong> Statement of Applicability (SoA) & Kisi-Kisi Audit Sertifikasi</li>
</ul>";
                $updates[] = "`target_audience` = ?";
                $params[] = "<ul>
<li>Lead Auditor & Auditor Internal Sistem Manajemen Keamanan Informasi</li>
<li>Tim Implementasi ISO 27001 & Koordinator Proyek Sertifikasi</li>
<li>Chief Information Security Officer (CISO) & IT Security Manager</li>
<li>Tim IT Operasional, Infrastructure, & Network Engineer</li>
<li>Pengelola Risiko Korporat & Compliance Officer</li>
</ul>";
                $updates[] = "`benefits` = ?";
                $params[] = "<ul>
<li>Penjelasan per klausul (4-10) dengan pendekatan When, Who, dan How</li>
<li>Format informasi terdokumentasi (kebijakan, SOP, data dukung) siap audit</li>
<li>Kisi-kisi pertanyaan audit sertifikasi eksternal</li>
<li>Dilengkapi watermark lisensi resmi personal pembeli</li>
<li>Gratis pembaruan materi digital jika ada revisi pedoman</li>
</ul>";
            } elseif ($p['id'] === 'kepatuhan-pdp') {
                $updates[] = "`subtitle` = ?";
                $params[] = "Panduan Terstruktur Pemenuhan Kewajiban Regulasi UU No. 27 Tahun 2022 di Organisasi";
                $updates[] = "`table_of_contents` = ?";
                $params[] = "<ul>
<li><strong>Bab I:</strong> UU PDP dalam Praktik Organisasi & Dinamika Penegakan Regulasi</li>
<li><strong>Bab II:</strong> Ketentuan Umum & Asas-Asas Perlindungan Data Pribadi</li>
<li><strong>Bab III:</strong> Klasifikasi Data Pribadi Umum vs Spesifik</li>
<li><strong>Bab IV:</strong> Pemenuhan Hak-Hak Subjek Data Pribadi (Akses, Koreksi, Penghapusan)</li>
<li><strong>Bab V:</strong> Kewajiban Pengendali & Prosesor Data Pribadi</li>
<li><strong>Bab VI:</strong> Tata Kelola Transfer Data Pribadi Domestik & Lintas Batas Negara</li>
<li><strong>Bab VII:</strong> Penanganan Insiden Kebocoran Data & Notifikasi Resmi 72 Jam</li>
<li><strong>Bab VIII:</strong> Lembaga Pengawas, Partisipasi Masyarakat, & Sanksi Administratif/Pidana</li>
<li><strong>Lampiran:</strong> Matriks Checklist Kesiapan Kepatuhan UU PDP</li>
</ul>";
                $updates[] = "`target_audience` = ?";
                $params[] = "<ul>
<li>Data Protection Officer (DPO) & Tim Kepatuhan Privasi</li>
<li>Legal Counsel, Bagian Hukum Korporasi, & Compliance Officer</li>
<li>Pimpinan Unit Bisnis yang Memproses Data Pelanggan (HR, Marketing, Operasional)</li>
<li>Product Manager, Data Engineer, & Pengembang Aplikasi</li>
<li>Manajemen Puncak & Direksi Penanggung Jawab Kepatuhan</li>
</ul>";
                $updates[] = "`benefits` = ?";
                $params[] = "<ul>
<li>Ulasan pasal per pasal penting yang berdampak langsung pada operasional bisnis</li>
<li>Strategi penyusunan dokumentasi kepatuhan yang diakui pengawas</li>
<li>Identifikasi indikator ketidakpatuhan kritis dan mitigasinya</li>
<li>Dilengkapi watermark lisensi personal pembeli</li>
</ul>";
            } elseif ($p['id'] === 'ropa-dpia') {
                $updates[] = "`subtitle` = ?";
                $params[] = "Instrumen Aplikatif Inventarisasi Pemrosesan (RoPA) & Penilaian Dampak Privasi (DPIA)";
                $updates[] = "`table_of_contents` = ?";
                $params[] = "<ul>
<li><strong>Bab I:</strong> Kewajiban Hukum Penyusunan RoPA & DPIA Berdasarkan UU PDP</li>
<li><strong>Bab II:</strong> Panduan Praktis & Metodologi Kertas Kerja RoPA</li>
<li><strong>Bab III:</strong> Matriks Penilaian Risiko & Kertas Kerja DPIA</li>
<li><strong>Bab IV:</strong> Template Standar RoPA untuk Pengendali & Prosesor Data</li>
<li><strong>Bab V:</strong> Template Evaluasi DPIA & Rencana Mitigasi Risiko</li>
<li><strong>Bab VI:</strong> Studi Kasus Nyata (Use Case) Penerapan RoPA & DPIA di Berbagai Sektor</li>
</ul>";
                $updates[] = "`target_audience` = ?";
                $params[] = "<ul>
<li>Pengelola Privasi Data & Petugas DPO</li>
<li>Tim Compliance, Tata Kelola Risiko, & Internal Control</li>
<li>Pemilik Proses Bisnis (Business Process Owners)</li>
<li>Tim Pengembang Sistem & Arsitek Aplikasi Digital</li>
</ul>";
                $updates[] = "`benefits` = ?";
                $params[] = "<ul>
<li>Petunjuk pengisian detail dengan contoh pengisian yang benar vs salah</li>
<li>Formulir dan tabel siap pakai dalam format dokumen standar</li>
<li>Pedoman validasi dan tinjauan berkala dokumen RoPA & DPIA</li>
<li>Watermark DRM resmi personal atas nama pembeli</li>
</ul>";
            } elseif ($p['id'] === 'lia-tia') {
                $updates[] = "`subtitle` = ?";
                $params[] = "Panduan Evaluasi Kepentingan yang Sah (LIA) & Penilaian Dampak Transfer Data (TIA)";
                $updates[] = "`table_of_contents` = ?";
                $params[] = "<ul>
<li><strong>Bab I:</strong> Konsep Legitimate Interest (LIA) dan Transfer Impact (TIA) dalam UU PDP</li>
<li><strong>Bab II:</strong> Tiga Uji Kritis LIA: Purpose Test, Necessity Test, dan Balancing Test</li>
<li><strong>Bab III:</strong> Prosedur & Kertas Kerja Transfer Impact Assessment (TIA)</li>
<li><strong>Bab IV:</strong> Template Kerja LIA & Checklist Dokumentasi</li>
<li><strong>Bab V:</strong> Template Kerja TIA & Klausul Pengamanan Kontrak Vendor (DPA)</li>
<li><strong>Bab VI:</strong> Contoh Kasus Nyata Pertukaran Data Pihak Ketiga & Integrasi Cloud</li>
</ul>";
                $updates[] = "`target_audience` = ?";
                $params[] = "<ul>
<li>Data Protection Officer (DPO) & Tim Kepatuhan Regulasi</li>
<li>Tim Legal Korporasi & Pengelola Kontrak Vendor Pihak Ketiga</li>
<li>Tim IT, Cloud Architect, & Integrasi API Data</li>
<li>Manajer Pengadaan (Procurement) & Vendor Management</li>
</ul>";
                $updates[] = "`benefits` = ?";
                $params[] = "<ul>
<li>Instrumen kerja LIA dan TIA yang siap diterapkan langsung</li>
<li>Contoh uji keseimbangan hak subjek data vs kepentingan bisnis</li>
<li>Petunjuk mitigasi risiko transfer data antar entitas atau lintas negara</li>
<li>Watermark personal berlisensi sah</li>
</ul>";
            }

            if (!empty($updates)) {
                $params[] = $p['id'];
                $sql = "UPDATE `store_products` SET " . implode(', ', $updates) . " WHERE `id` = ?";
                $st = $pdo->prepare($sql);
                $st->execute($params);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'added_columns' => $added,
        'current_columns' => $pdo->query("SHOW COLUMNS FROM `store_products`")->fetchAll(PDO::FETCH_COLUMN)
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
unlink(__FILE__);
'''

t = secrets.token_hex(4)
fn = f'migrate_{t}.php'
boundary = '----B' + t
body = (
    f'--{boundary}\r\nContent-Disposition: form-data; name="dir"\r\n\r\n/home/restiamo/public_html/store.restiamoegiono.com\r\n'
    f'--{boundary}\r\nContent-Disposition: form-data; name="overwrite"\r\n\r\n1\r\n'
    f'--{boundary}\r\nContent-Disposition: form-data; name="file-1"; filename="{fn}"\r\nContent-Type: application/octet-stream\r\n\r\n{code}\r\n'
    f'--{boundary}--\r\n'
).encode()

req = urllib.request.Request(
    'https://103.125.180.56:2083/execute/Fileman/upload_files',
    data=body,
    headers={'Authorization': 'cpanel restiamo:OWGHEMIOPPS1I4985VNW9S6NXW2ZX5HT', 'Content-Type': f'multipart/form-data; boundary={boundary}'}
)
urllib.request.urlopen(req, context=ctx)

req2 = urllib.request.Request(f'https://store.restiamoegiono.com/{fn}', headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req2, context=ctx) as r:
    data = json.loads(r.read().decode())
    print(json.dumps(data, indent=2))
