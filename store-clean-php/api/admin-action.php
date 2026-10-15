<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/FulfillmentService.php';
require_once __DIR__ . '/../services/WatermarkService.php';
require_once __DIR__ . '/../services/MailService.php';

session_start();

$authHeader = $_SERVER['HTTP_X_ADMIN_AUTH'] ?? ($_POST['admin_key'] ?? ($_GET['admin_key'] ?? ($_SESSION['store_admin_auth'] ?? '')));
if ($authHeader !== ADMIN_PASSWORD) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Akses ditolak. Silakan login admin.']);
    exit;
}

$action = trim($_GET['action'] ?? ($_POST['action'] ?? ''));

try {
    $db = StoreDB::get();

    // 0. Ekspor Laporan CSV
    if ($action === 'export_orders') {
        $stmt = $db->query("SELECT * FROM `store_orders` ORDER BY `id` DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = 'laporan-penjualan-rm-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Output UTF-8 BOM agar terbaca sempurna di Microsoft Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'ID Transaksi',
            'Nomor Invoice',
            'Jenis Transaksi',
            'Tanggal Transaksi',
            'Nama Pembeli',
            'Email Pembeli',
            'Nomor WhatsApp',
            'Instansi / Perusahaan',
            'Judul Produk',
            'Total Bayar (Rp)',
            'Kode Promo',
            'Diskon (Rp)',
            'Status Pembayaran',
            'Password PDF',
            'Status Pengiriman Email',
            'Waktu Email Terkirim'
        ]);

        foreach ($rows as $r) {
            $isTest = !empty($r['is_test']) || (isset($r['doku_payment_id']) && str_starts_with($r['doku_payment_id'], 'TRX-'));
            fputcsv($output, [
                $r['id'],
                $r['invoice_number'],
                $isTest ? 'UJI COBA (SANDBOX / SIMULASI)' : 'DOKU LIVE (PEMBAYARAN RESMI)',
                $r['created_at'],
                $r['customer_name'],
                $r['customer_email'],
                $r['customer_whatsapp'] ?: '-',
                $r['customer_org'] ?: '-',
                $r['product_title'],
                (float)$r['amount'],
                $r['coupon_code'] ?: '-',
                (float)($r['discount_amount'] ?? 0),
                $r['payment_status'],
                $r['pdf_password'] ?: '-',
                $r['email_sent'] ? 'SUDAH TERKIRIM' : 'BELUM',
                $r['email_sent_at'] ?: '-'
            ]);
        }
        fclose($output);
        exit;
    }

    // Response default selanjutnya adalah JSON
    header('Content-Type: application/json; charset=utf-8');

    // 0b. Aksi Fulfill / Stempel Ulang Berkas DRM & Kirim Email Secara Manual
    if ($action === 'fulfill_order') {
        $inv = trim($_POST['invoice'] ?? ($_GET['invoice'] ?? ''));
        if (empty($inv)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invoice nomor diperlukan.']);
            exit;
        }

        $res = FulfillmentService::fulfillOrder($inv, true);
        echo json_encode([
            'success' => true,
            'message' => 'Berkas PDF ber-watermark berhasil distempel dengan password dan dikirimkan ke pembeli!',
            'result' => $res
        ]);
        exit;
    }

    // 0c. Aksi Verifikasi Kriptografi PDF (Uji Coba Password & Dekripsi AES-256)
    if ($action === 'verify_pdf_crypto') {
        $inv = trim($_POST['invoice'] ?? ($_GET['invoice'] ?? ''));
        $stmt = $db->prepare("SELECT * FROM `store_orders` WHERE `invoice_number` = ? LIMIT 1");
        $stmt->execute([$inv]);
        $order = $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
            exit;
        }

        $pdfFile = $order['watermarked_file'];
        $pwd = $order['pdf_password'];

        if (!file_exists($pdfFile)) {
            echo json_encode(['success' => false, 'error' => "Berkas PDF tidak ditemukan di server: {$pdfFile}"]);
            exit;
        }

        $cmd = sprintf(
            'python3 -c "import sys, json; from pypdf import PdfReader; r = PdfReader(\'%s\'); is_enc = r.is_encrypted; dec_wrong = r.decrypt(\'wrong1\'); dec_ok = r.decrypt(\'%s\'); pages = len(r.pages); print(json.dumps({\'is_encrypted\': is_enc, \'decrypt_wrong\': dec_wrong, \'decrypt_ok\': dec_ok, \'page_count\': pages}))"',
            addslashes($pdfFile),
            addslashes($pwd)
        );
        $pyOutput = shell_exec($cmd);
        $cryptoResult = json_decode(trim((string)$pyOutput), true);

        echo json_encode([
            'success' => true,
            'invoice' => $inv,
            'password' => $pwd,
            'password_length' => strlen($pwd),
            'password_valid_chars' => ctype_alnum($pwd) && (strtolower($pwd) === $pwd),
            'pdf_path' => $pdfFile,
            'pdf_size' => filesize($pdfFile),
            'crypto_verification' => $cryptoResult
        ]);
        exit;
    }

    // 1. Ambil Ringkasan Statistik KPI, Pesanan, Produk, & Kupon
    if ($action === 'get_data') {
        $orders = $db->query("SELECT * FROM `store_orders` ORDER BY `id` DESC LIMIT 100")->fetchAll();
        $products = $db->query("SELECT * FROM `store_products` ORDER BY `price` DESC")->fetchAll();
        $coupons = $db->query("SELECT * FROM `store_coupons` ORDER BY `id` DESC")->fetchAll();

        $totalRealOmset = 0;
        $totalTestOmset = 0;
        $totalRealPaid = 0;
        $totalTestPaid = 0;
        $totalSent = 0;
        $totalOrders = count($orders);

        foreach ($orders as $o) {
            $isTest = !empty($o['is_test']) || (isset($o['doku_payment_id']) && str_starts_with($o['doku_payment_id'], 'TRX-'));
            if (in_array($o['payment_status'], ['PAID', 'SUCCESS'])) {
                if ($isTest) {
                    $totalTestOmset += (int)$o['amount'];
                    $totalTestPaid++;
                } else {
                    $totalRealOmset += (int)$o['amount'];
                    $totalRealPaid++;
                }
            }
            if ($o['email_sent']) {
                $totalSent++;
            }
        }

        // Cek master files di storage
        $masters = [];
        foreach ($products as $p) {
            $mPath = MASTER_EBOOKS_DIR . '/' . $p['master_file'];
            $exists = file_exists($mPath);
            $masters[] = [
                'id' => $p['id'],
                'title' => $p['title'],
                'file' => $p['master_file'],
                'price' => (int)$p['price'],
                'exists' => $exists,
                'size_formatted' => $exists ? round(filesize($mPath) / (1024 * 1024), 2) . ' MB' : '0 MB',
                'updated_at' => $p['updated_at']
            ];
        }

        // Ambil store_settings untuk tampilan Hero dan konfigurasi toko
        $settingsRows = [];
        try {
            $settingsRows = $db->query("SELECT `setting_key`, `setting_value` FROM `store_settings`")->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {}

        $heroSettings = [
            'hero_image' => $settingsRows['hero_image'] ?? '/images/hero-book-420.webp',
            'hero_tag' => $settingsRows['hero_tag'] ?? '🛡️ LITERASI RESMI & KEPATUHAN ENTERPRISE',
            'hero_title' => $settingsRows['hero_title'] ?? 'Pustaka Keamanan Siber & <span>Privasi Data Digital</span>',
            'hero_desc' => $settingsRows['hero_desc'] ?? 'Dapatkan panduan implementasi <b>ISO 27001:2022</b>, kepatuhan <b>UU PDP No. 27/2022</b>, dan instrumen audit siap pakai karya <b>Restia Moegiono</b>. Berkas PDF berlisensi resmi atas nama Anda terkirim otomatis setelah pembayaran melalui <b>DOKU (QRIS & Virtual Account)</b> terkonfirmasi.'
        ];

        // Ambil data LMS Akademi
        $lmsStudents = [];
        $totalLmsEnrolled = 0;
        $totalLmsPassed = 0;
        $totalDiscussions = 0;

        try {
            $totalLessonsInCourse = (int)$db->query("SELECT COUNT(*) FROM lms_lessons WHERE course_id = 1")->fetchColumn();
            $enrollments = $db->query("SELECT e.*, c.title AS course_title FROM lms_enrollments e JOIN lms_courses c ON e.course_id = c.id ORDER BY e.id DESC")->fetchAll();
            $totalLmsEnrolled = count($enrollments);

            foreach ($enrollments as $enr) {
                $completed = (int)$db->query("SELECT COUNT(*) FROM lms_progress WHERE user_email = " . $db->quote($enr['user_email']) . " AND course_id = {$enr['course_id']} AND is_completed = 1")->fetchColumn();
                $pct = $totalLessonsInCourse > 0 ? round(($completed / $totalLessonsInCourse) * 100) : 0;
                if ($pct >= 100) $totalLmsPassed++;

                $lmsStudents[] = [
                    'id' => $enr['id'],
                    'course_id' => $enr['course_id'],
                    'course_title' => $enr['course_title'],
                    'user_email' => $enr['user_email'],
                    'user_name' => $enr['user_name'],
                    'order_invoice' => $enr['order_invoice'],
                    'status' => $enr['status'],
                    'enrolled_at' => $enr['enrolled_at'],
                    'completed_lessons' => $completed,
                    'total_lessons' => $totalLessonsInCourse,
                    'progress_percent' => $pct,
                    'is_passed' => ($pct >= 100)
                ];
            }

            $totalDiscussions = (int)$db->query("SELECT COUNT(*) FROM lms_discussions")->fetchColumn();

            // Ambil forum diskusi dan balasan
            $lmsDiscussions = $db->query("SELECT d.*, l.title AS lesson_title,
                (SELECT COUNT(*) FROM lms_discussion_replies r WHERE r.discussion_id = d.id) AS reply_count 
                FROM lms_discussions d 
                LEFT JOIN lms_lessons l ON d.lesson_id = l.id 
                ORDER BY d.id DESC LIMIT 40")->fetchAll();
            foreach ($lmsDiscussions as &$ld) {
                $stmtRep = $db->prepare("SELECT * FROM lms_discussion_replies WHERE discussion_id = ? ORDER BY id ASC");
                $stmtRep->execute([$ld['id']]);
                $ld['replies'] = $stmtRep->fetchAll();
            }

            // Ambil riwayat pengerjaan kuis siswa
            $lmsQuizzes = $db->query("SELECT q.*, c.title AS course_title 
                FROM lms_quiz_attempts q 
                JOIN lms_courses c ON q.course_id = c.id 
                ORDER BY q.id DESC LIMIT 30")->fetchAll();

            // Ambil modul & materi bab kurikulum kursus
            $lmsCourses = $db->query("SELECT * FROM lms_courses ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            $lmsModules = $db->query("SELECT * FROM lms_modules ORDER BY sort_order ASC")->fetchAll();
            $lmsLessons = $db->query("SELECT * FROM lms_lessons ORDER BY module_id ASC, sort_order ASC")->fetchAll();

        } catch (Exception $e) {}

        echo json_encode([
            'success' => true,
            'kpi' => [
                'total_omset' => $totalRealOmset,
                'total_test_omset' => $totalTestOmset,
                'total_paid' => $totalRealPaid,
                'total_test_paid' => $totalTestPaid,
                'total_sent' => $totalSent,
                'total_orders' => $totalOrders,
                'total_products' => count($products),
                'total_coupons' => count(array_filter($coupons, function($c) { return (int)($c['is_active'] ?? 0) === 1; })),
                'lms_enrolled' => $totalLmsEnrolled,
                'lms_passed' => $totalLmsPassed,
                'lms_discussions' => $totalDiscussions,
                'lms_courses_count' => count($lmsCourses ?? []),
                'lms_lessons_count' => count($lmsLessons ?? [])
            ],
            'orders' => $orders,
            'products' => $products,
            'masters' => $masters,
            'coupons' => $coupons,
            'settings' => $heroSettings,
            'lms_courses' => $lmsCourses ?? [],
            'lms_students' => $lmsStudents,
            'lms_discussions' => $lmsDiscussions ?? [],
            'lms_quizzes' => $lmsQuizzes ?? [],
            'lms_modules' => $lmsModules ?? [],
            'lms_lessons' => $lmsLessons ?? []
        ]);
        exit;
    }

    // 2. Tambah Siswa LMS Manual (B2B Korporat)
    if ($action === 'enroll_lms_student_manual') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? 1);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Alamat email tidak valid.");
        }
        if (empty($name)) {
            throw new Exception("Nama siswa diperlukan.");
        }

        $stmt = $db->prepare("INSERT INTO lms_enrollments (course_id, user_email, user_name, order_invoice, status) 
            VALUES (?, ?, ?, 'MANUAL-B2B', 'ACTIVE') 
            ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), status = 'ACTIVE'");
        $stmt->execute([$courseId, $email, $name]);

        echo json_encode([
            'success' => true,
            'message' => "Siswa {$name} ({$email}) berhasil didaftarkan ke kursus."
        ]);
        exit;
    }

    // 2b. Balas Pertanyaan Forum Diskusi Siswa oleh Instruktur
    if ($action === 'reply_lms_discussion') {
        $discId = (int)($_POST['discussion_id'] ?? 0);
        $replyText = trim($_POST['reply_text'] ?? '');
        $senderName = trim($_POST['sender_name'] ?? 'Restia Moegiono');
        $senderRole = 'Lead Auditor & Instruktur Utama';

        if ($discId <= 0 || empty($replyText)) {
            throw new Exception("ID diskusi dan teks balasan wajib diisi.");
        }

        $stmt = $db->prepare("INSERT INTO lms_discussion_replies (discussion_id, sender_name, sender_role, reply_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$discId, $senderName, $senderRole, $replyText]);

        $db->prepare("UPDATE lms_discussions SET is_answered = 1 WHERE id = ?")->execute([$discId]);

        echo json_encode([
            'success' => true,
            'message' => "Tanggapan resmi instruktur berhasil dikirim ke siswa!"
        ]);
        exit;
    }

    // 2. Kirim Ulang Email & Stempel Watermark Manual
    if ($action === 'fulfill_manual') {
        $invoice = trim($_POST['invoice'] ?? '');
        if (empty($invoice)) {
            throw new Exception("Invoice diperlukan.");
        }

        $res = FulfillmentService::fulfillOrder($invoice, true);
        echo json_encode([
            'success' => true,
            'message' => 'E-Book berhasil distempel ulang dan dikirimkan ke pembeli!',
            'details' => $res
        ]);
        exit;
    }

    // 3. Uji Coba Stempel DRM Langsung
    if ($action === 'test_drm') {
        $prodId = trim($_POST['product_id'] ?? '11-kontrol-v2');
        $name = trim($_POST['name'] ?? 'Surya Virtu');
        $email = trim($_POST['email'] ?? 'suryavirtu@gmail.com');
        $org = trim($_POST['org'] ?? 'PT Solusi Data Indonesia');

        $stmt = $db->prepare("SELECT * FROM `store_products` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$prodId]);
        $prod = $stmt->fetch();
        if (!$prod) throw new Exception("Produk tidak ditemukan.");

        $testInv = 'TEST-DRM-' . time();
        $pdfPath = WatermarkService::stampPdf($prod['master_file'], $prod['title'], $name, $email, $org, $testInv);

        $webUrl = '/storage/generated/' . basename($pdfPath);

        echo json_encode([
            'success' => true,
            'message' => 'Stempel watermark berhasil dihasilkan!',
            'invoice' => $testInv,
            'download_url' => $webUrl,
            'file_size' => filesize($pdfPath)
        ]);
        exit;
    }

    // 4. Uji Coba Pengiriman Email Live
    if ($action === 'test_email') {
        $targetEmail = trim($_POST['email'] ?? 'suryavirtu@gmail.com');
        $prodId = trim($_POST['product_id'] ?? 'ropa-dpia');

        $stmt = $db->prepare("SELECT * FROM `store_products` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$prodId]);
        $prod = $stmt->fetch();
        if (!$prod) throw new Exception("Produk tidak ditemukan.");

        $testInv = 'TEST-MAIL-' . time();
        $pdfPath = WatermarkService::stampPdf($prod['master_file'], $prod['title'], 'Pelanggan Terverifikasi', $targetEmail, 'Instansi Pengujian', $testInv);

        MailService::sendEbook($targetEmail, 'Pelanggan Terverifikasi', $prod['title'], $testInv, $pdfPath);

        echo json_encode([
            'success' => true,
            'message' => "Email resmi uji coba dengan lampiran PDF berhasil dikirimkan ke {$targetEmail}!"
        ]);
        exit;
    }

    // 5. Ambil Detail Tunggal Produk untuk Editor
    if ($action === 'get_product') {
        $id = trim($_GET['id'] ?? ($_POST['id'] ?? ''));
        if (empty($id)) throw new Exception("ID produk wajib diisi.");

        $stmt = $db->prepare("SELECT * FROM `store_products` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) throw new Exception("Produk dengan ID '{$id}' tidak ditemukan.");

        echo json_encode([
            'success' => true,
            'product' => $prod
        ]);
        exit;
    }

    // 6. Simpan / Perbarui Konten Produk (Lengkap dengan Detail Sempurna)
    if ($action === 'save_product') {
        $id = trim($_POST['id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $oldPrice = (float)($_POST['old_price'] ?? 0);
        $version = trim($_POST['version'] ?? 'Edisi 2026');
        $edition = trim($_POST['edition'] ?? 'Edisi 2026 Revisi');
        $isbn = trim($_POST['isbn'] ?? '');
        $pages = (int)($_POST['pages'] ?? 100);
        $fileFormat = trim($_POST['file_format'] ?? 'PDF Digital (Universal)');
        $fileSize = trim($_POST['file_size'] ?? '15-25 MB');
        $language = trim($_POST['language'] ?? 'Bahasa Indonesia');
        $badge = trim($_POST['badge'] ?? '');
        $masterFile = trim($_POST['master_file'] ?? '');
        $coverImage = trim($_POST['cover_image'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $tableOfContents = trim($_POST['table_of_contents'] ?? '');
        $targetAudience = trim($_POST['target_audience'] ?? '');
        $benefits = trim($_POST['benefits'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $orderIndex = (int)($_POST['order_index'] ?? 0);

        if (empty($title)) throw new Exception("Judul produk wajib diisi.");

        // Otomatis buat slug dan ID jika belum ada
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
        }
        if (empty($id)) {
            $id = $slug;
        }

        // 1. Handle Upload Gambar Sampul Baru (WebP, JPG, PNG) jika ada
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $fTmp = $_FILES['cover_file']['tmp_name'];
            $fName = $_FILES['cover_file']['name'];
            $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['webp', 'jpg', 'jpeg', 'png'])) {
                throw new Exception("Format gambar sampul tidak didukung. Harap unggah format WebP, JPG, atau PNG.");
            }
            $safeSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $slug)));
            $newCoverName = "cover-{$safeSlug}-" . time() . ".{$ext}";
            $targetCoverDir = '/home/restiamo/public_html/store.restiamoegiono.com/images';
            if (!is_dir($targetCoverDir)) {
                @mkdir($targetCoverDir, 0755, true);
            }
            $targetCoverPath = $targetCoverDir . '/' . $newCoverName;
            if (!move_uploaded_file($fTmp, $targetCoverPath)) {
                throw new Exception("Gagal memindahkan berkas gambar sampul.");
            }
            $coverImage = '/images/' . $newCoverName;
        }

        // 2. Handle Upload Berkas Master PDF Baru (.pdf) jika ada
        if (isset($_FILES['master_pdf_file']) && $_FILES['master_pdf_file']['error'] === UPLOAD_ERR_OK) {
            $fPdfTmp = $_FILES['master_pdf_file']['tmp_name'];
            $fPdfName = $_FILES['master_pdf_file']['name'];
            $extPdf = strtolower(pathinfo($fPdfName, PATHINFO_EXTENSION));
            if ($extPdf !== 'pdf') {
                throw new Exception("Berkas master harus berformat PDF (.pdf).");
            }
            $safePdfSlug = preg_replace('/[^a-z0-9\-_]/', '', strtolower(pathinfo($fPdfName, PATHINFO_FILENAME)));
            if (empty($safePdfSlug)) $safePdfSlug = str_replace(' ', '-', $slug);
            $newPdfName = "{$safePdfSlug}.pdf";
            
            $targetPdfDir = MASTER_EBOOKS_DIR;
            if (!is_dir($targetPdfDir)) {
                @mkdir($targetPdfDir, 0755, true);
            }
            $targetPdfPath = $targetPdfDir . '/' . $newPdfName;
            if (!move_uploaded_file($fPdfTmp, $targetPdfPath)) {
                throw new Exception("Gagal memindahkan berkas master PDF ke storage.");
            }
            $masterFile = $newPdfName;
        }

        $stmtCheck = $db->prepare("SELECT `id` FROM `store_products` WHERE `id` = ? LIMIT 1");
        $stmtCheck->execute([$id]);
        $exists = $stmtCheck->fetch();

        if ($exists) {
            $stmt = $db->prepare("UPDATE `store_products` SET 
                `slug` = ?,
                `title` = ?,
                `subtitle` = ?,
                `category` = ?,
                `price` = ?,
                `old_price` = ?,
                `version` = ?,
                `edition` = ?,
                `isbn` = ?,
                `pages` = ?,
                `file_format` = ?,
                `file_size` = ?,
                `language` = ?,
                `badge` = ?,
                `description` = ?,
                `table_of_contents` = ?,
                `target_audience` = ?,
                `benefits` = ?,
                `is_active` = ?,
                `order_index` = ?,
                `master_file` = COALESCE(NULLIF(?, ''), `master_file`),
                `cover_image` = COALESCE(NULLIF(?, ''), `cover_image`),
                `updated_at` = NOW()
                WHERE `id` = ?");
            
            $stmt->execute([
                $slug,
                $title,
                $subtitle,
                $category,
                $price,
                $oldPrice,
                $version,
                $edition,
                $isbn,
                $pages,
                $fileFormat,
                $fileSize,
                $language,
                $badge,
                $description,
                $tableOfContents,
                $targetAudience,
                $benefits,
                $isActive,
                $orderIndex,
                $masterFile,
                $coverImage,
                $id
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO `store_products` 
                (`id`, `slug`, `title`, `subtitle`, `category`, `price`, `old_price`, `version`, `edition`, `isbn`, `pages`, `file_format`, `file_size`, `language`, `badge`, `description`, `table_of_contents`, `target_audience`, `benefits`, `is_active`, `order_index`, `master_file`, `cover_image`, `created_at`, `updated_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $stmt->execute([
                $id,
                $slug,
                $title,
                $subtitle,
                $category,
                $price,
                $oldPrice,
                $version,
                $edition,
                $isbn,
                $pages,
                $fileFormat,
                $fileSize,
                $language,
                $badge,
                $description,
                $tableOfContents,
                $targetAudience,
                $benefits,
                $isActive,
                $orderIndex,
                $masterFile,
                $coverImage
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => "Konten dan spesifikasi produk '{$title}' berhasil disimpan!",
            'product_id' => $id,
            'slug' => $slug
        ]);
        exit;
    }

    // 7. Hapus Produk
    if ($action === 'delete_product') {
        $id = trim($_POST['id'] ?? '');
        if (empty($id)) throw new Exception("ID produk wajib diisi.");

        $stmt = $db->prepare("DELETE FROM `store_products` WHERE `id` = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Produk berhasil dihapus dari sistem toko."
        ]);
        exit;
    }

    // 7. Simpan / Buat Kupon Baru (Voucher)
    if ($action === 'save_coupon') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = in_array($_POST['discount_type'] ?? '', ['PERCENT', 'FIXED']) ? $_POST['discount_type'] : 'PERCENT';
        $val = (float)($_POST['discount_value'] ?? 0);
        $minOrder = (float)($_POST['min_order'] ?? 0);
        $maxDiscount = (float)($_POST['max_discount'] ?? 0);
        $limit = (int)($_POST['usage_limit'] ?? 100);
        $expires = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $applicableTo = in_array($_POST['applicable_to'] ?? '', ['ALL', 'ESTORE', 'LMS']) ? $_POST['applicable_to'] : 'ALL';

        if (empty($code)) throw new Exception("Kode promo wajib diisi.");
        if ($val <= 0) throw new Exception("Nilai potongan harus lebih dari 0.");

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE `store_coupons` SET 
                `code` = ?,
                `discount_type` = ?,
                `discount_value` = ?,
                `applicable_to` = ?,
                `min_order` = ?,
                `max_discount` = ?,
                `usage_limit` = ?,
                `expires_at` = ?,
                `is_active` = ?
                WHERE `id` = ?");
            $stmt->execute([$code, $type, $val, $applicableTo, $minOrder, $maxDiscount, $limit, $expires, $isActive, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO `store_coupons` 
                (`code`, `discount_type`, `discount_value`, `applicable_to`, `min_order`, `max_discount`, `usage_limit`, `expires_at`, `is_active`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $val, $applicableTo, $minOrder, $maxDiscount, $limit, $expires, $isActive]);
        }

        echo json_encode([
            'success' => true,
            'message' => "Kode kupon '{$code}' berhasil disimpan!"
        ]);
        exit;
    }

    // 8. Hapus Kupon
    if ($action === 'delete_coupon') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID kupon tidak valid.");

        $stmt = $db->prepare("DELETE FROM `store_coupons` WHERE `id` = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Kupon berhasil dihapus."
        ]);
        exit;
    }

    // 9. Toggle Status Kupon (Aktif / Nonaktif)
    if ($action === 'toggle_coupon') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID kupon tidak valid.");

        $stmt = $db->prepare("UPDATE `store_coupons` SET `is_active` = IF(`is_active`=1, 0, 1) WHERE `id` = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Status keaktifan kupon berhasil diperbarui."
        ]);
        exit;
    }

    // 10. Simpan Pengaturan Tampilan & Upload Gambar Hero Toko
    if ($action === 'save_hero_settings') {
        $heroTag = trim($_POST['hero_tag'] ?? '');
        $heroTitle = trim($_POST['hero_title'] ?? '');
        $heroDesc = trim($_POST['hero_desc'] ?? '');
        $heroImage = trim($_POST['hero_image'] ?? '');

        // Upload berkas gambar baru jika ada
        if (isset($_FILES['hero_image_file']) && $_FILES['hero_image_file']['error'] === UPLOAD_ERR_OK) {
            $fTmp = $_FILES['hero_image_file']['tmp_name'];
            $fName = $_FILES['hero_image_file']['name'];
            $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['webp', 'jpg', 'jpeg', 'png'])) {
                throw new Exception("Format gambar harus WebP, JPG, atau PNG.");
            }
            $newHeroName = "hero-book-" . time() . ".{$ext}";
            $targetHeroDir = STORE_ROOT . '/images';
            if (!is_dir($targetHeroDir)) {
                @mkdir($targetHeroDir, 0755, true);
            }
            $targetHeroPath = $targetHeroDir . '/' . $newHeroName;
            if (!move_uploaded_file($fTmp, $targetHeroPath)) {
                throw new Exception("Gagal memindahkan berkas gambar hero ke direktori /images.");
            }
            $heroImage = '/images/' . $newHeroName;
        }

        $stmt = $db->prepare("INSERT INTO `store_settings` (`setting_key`, `setting_value`, `updated_at`) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW()");

        if (!empty($heroImage)) {
            $stmt->execute(['hero_image', $heroImage]);
        }
        if (!empty($heroTag)) {
            $stmt->execute(['hero_tag', $heroTag]);
        }
        if (!empty($heroTitle)) {
            $stmt->execute(['hero_title', $heroTitle]);
        }
        if (!empty($heroDesc)) {
            $stmt->execute(['hero_desc', $heroDesc]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Gambar dan teks Hero Muka Toko berhasil diperbarui & disimpan!',
            'hero_image' => $heroImage
        ]);
        exit;
    }

    // 10. Ambil Data Detail 1 Bab / Materi LMS untuk Editor WYSIWYG
    if ($action === 'get_lms_lesson') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM `lms_lessons` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $lesson = $stmt->fetch();
        if (!$lesson) {
            throw new Exception('Materi bab tidak ditemukan.');
        }
        echo json_encode(['success' => true, 'lesson' => $lesson]);
        exit;
    }

    // 11. Simpan Perubahan Materi Bab LMS (Judul, Video, Durasi & Catatan Kuliah WYSIWYG)
    if ($action === 'save_lms_lesson') {
        $id = (int)($_POST['id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 1);
        $moduleId = (int)($_POST['module_id'] ?? 1);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $slug = trim($slug, '-');
        }
        $duration = trim($_POST['duration'] ?? '15:00');
        $videoType = trim($_POST['video_type'] ?? 'youtube');
        $videoId = trim($_POST['video_id'] ?? '');
        $contentHtml = trim($_POST['content_html'] ?? '');
        $attachmentName = trim($_POST['attachment_name'] ?? '');
        $attachmentUrl = trim($_POST['attachment_url'] ?? '');
        $isFreePreview = !empty($_POST['is_free_preview']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 1);

        if (empty($title)) {
            throw new Exception('Judul bab materi tidak boleh kosong.');
        }

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE `lms_lessons` SET 
                `module_id` = ?, `title` = ?, `slug` = ?, `duration` = ?, 
                `video_type` = ?, `video_id` = ?, `content_html` = ?, 
                `attachment_name` = ?, `attachment_url` = ?, `is_free_preview` = ?, `sort_order` = ?
                WHERE `id` = ?");
            $stmt->execute([$moduleId, $title, $slug, $duration, $videoType, $videoId, $contentHtml, $attachmentName, $attachmentUrl, $isFreePreview, $sortOrder, $id]);
            $msg = 'Materi bab kursus berhasil disimpan & diperbarui!';
        } else {
            $stmt = $db->prepare("INSERT INTO `lms_lessons` 
                (`course_id`, `module_id`, `title`, `slug`, `duration`, `video_type`, `video_id`, `content_html`, `attachment_name`, `attachment_url`, `is_free_preview`, `sort_order`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$courseId, $moduleId, $title, $slug, $duration, $videoType, $videoId, $contentHtml, $attachmentName, $attachmentUrl, $isFreePreview, $sortOrder]);
            $id = (int)$db->lastInsertId();
            $msg = 'Materi bab baru berhasil ditambahkan!';
        }

        echo json_encode(['success' => true, 'message' => $msg, 'id' => $id]);
        exit;
    }

    // 12. Hapus Materi Bab LMS
    if ($action === 'delete_lms_lesson') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID bab materi tidak valid.');
        }
        $stmt = $db->prepare("DELETE FROM `lms_lessons` WHERE `id` = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Materi bab berhasil dihapus dari kurikulum.']);
        exit;
    }

    // 13. Simpan / Tambah Program Kursus LMS Baru
    if ($action === 'save_course') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $slug = trim($slug, '-');
        }
        $subtitle = trim($_POST['subtitle'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'Keamanan Informasi');
        $badge = trim($_POST['badge'] ?? 'EXECUTIVE MASTERCLASS');
        $price = (float)($_POST['price'] ?? 499000);
        $oldPrice = (float)($_POST['old_price'] ?? 1499000);
        $instructorName = trim($_POST['instructor_name'] ?? 'Restia Moegiono');
        $instructorTitle = trim($_POST['instructor_title'] ?? 'Lead Auditor ISO 27001 & Praktisi Keamanan Siber');
        $instructorBio = trim($_POST['instructor_bio'] ?? '');
        $instructorAvatar = trim($_POST['instructor_avatar'] ?? '/images/author-restia-formal.webp');
        $totalDuration = trim($_POST['total_duration'] ?? '4 Jam 30 Menit');
        $totalLessons = (int)($_POST['total_lessons'] ?? 12);
        $isPublished = isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1;
        $coverImage = trim($_POST['cover_image'] ?? '/images/iso-27001-cover.webp');

        if (empty($title)) throw new Exception("Judul program kursus wajib diisi.");

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE `lms_courses` SET 
                `slug` = ?, `title` = ?, `subtitle` = ?, `description` = ?,
                `category` = ?, `badge` = ?, `cover_image` = ?, `price` = ?, `old_price` = ?,
                `instructor_name` = ?, `instructor_title` = ?, `instructor_bio` = ?, `instructor_avatar` = ?,
                `total_duration` = ?, `total_lessons` = ?, `is_published` = ?
                WHERE `id` = ?");
            $stmt->execute([
                $slug, $title, $subtitle, $description,
                $category, $badge, $coverImage, $price, $oldPrice,
                $instructorName, $instructorTitle, $instructorBio, $instructorAvatar,
                $totalDuration, $totalLessons, $isPublished, $id
            ]);
            $msg = "Program kursus '{$title}' berhasil diperbarui!";
        } else {
            $stmt = $db->prepare("INSERT INTO `lms_courses` 
                (`slug`, `title`, `subtitle`, `description`, `category`, `badge`, `cover_image`, `price`, `old_price`, `instructor_name`, `instructor_title`, `instructor_bio`, `instructor_avatar`, `total_duration`, `total_lessons`, `is_published`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $slug, $title, $subtitle, $description,
                $category, $badge, $coverImage, $price, $oldPrice,
                $instructorName, $instructorTitle, $instructorBio, $instructorAvatar,
                $totalDuration, $totalLessons, $isPublished
            ]);
            $id = (int)$db->lastInsertId();
            $msg = "Program kursus baru '{$title}' berhasil ditambahkan!";
        }

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'course_id' => $id,
            'slug' => $slug,
            'public_url' => 'https://restiamoegiono.com/course/' . $slug,
            'learn_url' => 'https://restiamoegiono.com/course/learn.php?course=' . $slug
        ]);
        exit;
    }

    // 14. Hapus Program Kursus LMS
    if ($action === 'delete_course') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID kursus tidak valid.");

        $stmt = $db->prepare("DELETE FROM `lms_courses` WHERE `id` = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Program kursus berhasil dihapus dari LMS."
        ]);
        exit;
    }

    throw new Exception("Aksi tidak dikenal: {$action}");

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
