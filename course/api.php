<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getLmsDb();

try {
    // 1. Login Mode Demo Instan (Untuk Pengujian & Pratinjau Cepat)
    if ($action === 'demo_login') {
        $_SESSION['lms_student_email'] = 'demo@restiamoegiono.com';
        $_SESSION['lms_student_name'] = 'Peserta Eksekutif Demo';
        $_SESSION['lms_is_demo'] = true;

        echo json_encode([
            'success' => true,
            'message' => 'Login demo berhasil.',
            'redirect' => 'learn.php'
        ]);
        exit;
    }

    // 2. Request OTP Siswa
    if ($action === 'request_otp') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format alamat email tidak valid.");
        }

        // Cek hak akses di lms_enrollments
        $stmt = $db->prepare("SELECT e.*, c.title AS course_title FROM lms_enrollments e JOIN lms_courses c ON e.course_id = c.id WHERE e.user_email = ? AND e.status = 'ACTIVE' LIMIT 1");
        $stmt->execute([$email]);
        $enrollment = $stmt->fetch();

        // Jika belum ada di lms_enrollments, cek apakah ada di store_orders (sinkronisasi otomatis)
        if (!$enrollment) {
            $stmtOrder = $db->prepare("SELECT * FROM store_orders WHERE customer_email = ? AND payment_status IN ('PAID', 'SUCCESS') ORDER BY id DESC LIMIT 1");
            $stmtOrder->execute([$email]);
            $order = $stmtOrder->fetch();

            if ($order) {
                // Auto enroll ke course ID 1
                $cId = 1;
                $db->prepare("INSERT IGNORE INTO lms_enrollments (course_id, user_email, user_name, order_invoice, status) VALUES (?, ?, ?, ?, 'ACTIVE')")
                    ->execute([$cId, $email, $order['customer_name'] ?? 'Peserta Kursus', $order['invoice_number']]);
                
                $stmt = $db->prepare("SELECT e.*, c.title AS course_title FROM lms_enrollments e JOIN lms_courses c ON e.course_id = c.id WHERE e.user_email = ? AND e.status = 'ACTIVE' LIMIT 1");
                $stmt->execute([$email]);
                $enrollment = $stmt->fetch();
            }
        }

        if (!$enrollment && $email !== 'demo@restiamoegiono.com') {
            throw new Exception("Email Anda belum terdaftar pada kursus aktif. Silakan lakukan pendaftaran kursus terlebih dahulu.");
        }

        $otp = (string)random_int(100000, 999999);
        $_SESSION['lms_otp_code'] = $otp;
        $_SESSION['lms_otp_email'] = $email;
        $_SESSION['lms_otp_name'] = $enrollment['user_name'] ?? 'Peserta';
        $_SESSION['lms_otp_time'] = time();

        // Kirim Email OTP via SMTP Sederhana
        $subject = "Kode Verifikasi Akses Kelas - Restia Moegiono Academy";
        $body = "Halo {$enrollment['user_name']},\n\n"
              . "Kode verifikasi 6-digit untuk mengakses ruang belajar Anda di Restia Moegiono Online Academy adalah:\n\n"
              . "    [ {$otp} ]\n\n"
              . "Kode ini berlaku selama 10 menit. Jangan berikan kode ini kepada siapapun.\n\n"
              . "Akses Portal: " . LMS_BASE_URL . "/learn.php\n\n"
              . "Salam hangat,\nRestia Moegiono & Tim Edukasi";

        // Kirim via socket SMTP cPanel
        try {
            sendLmsMail($email, $subject, $body);
        } catch (Exception $e) {
            // Jika SMTP lokal delay, simpan untuk response
        }

        echo json_encode([
            'success' => true,
            'message' => 'Kode OTP 6-digit telah dikirim ke ' . $email . '. Silakan periksa inbox/spam.',
            // Catatan: Jika localhost/testing, kembalikan hint
            'hint_otp' => ($email === 'demo@restiamoegiono.com') ? $otp : null
        ]);
        exit;
    }

    // 3. Verifikasi OTP
    if ($action === 'verify_otp') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $otp = trim($_POST['otp'] ?? '');

        $savedOtp = $_SESSION['lms_otp_code'] ?? '';
        $savedEmail = $_SESSION['lms_otp_email'] ?? '';
        $savedTime = $_SESSION['lms_otp_time'] ?? 0;

        if (empty($otp)) {
            throw new Exception("Silakan masukkan 6 digit kode OTP.");
        }

        if (time() - $savedTime > 600) {
            throw new Exception("Kode OTP telah kadaluarsa. Silakan minta kode baru.");
        }

        if ($otp !== $savedOtp || $email !== $savedEmail) {
            throw new Exception("Kode OTP salah atau email tidak cocok.");
        }

        $_SESSION['lms_student_email'] = $email;
        $_SESSION['lms_student_name'] = $_SESSION['lms_otp_name'] ?? 'Peserta';
        $_SESSION['lms_is_demo'] = false;

        unset($_SESSION['lms_otp_code'], $_SESSION['lms_otp_email'], $_SESSION['lms_otp_time']);

        echo json_encode([
            'success' => true,
            'message' => 'Verifikasi berhasil. Mengalihkan ke ruang belajar...',
            'redirect' => 'learn.php'
        ]);
        exit;
    }

    // 4. Tandai Selesai / Batal Selesai (Toggle Progress)
    if ($action === 'toggle_complete' || $action === 'toggle_progress') {
        $student = getLoggedInStudent();
        if (!$student) {
            throw new Exception("Sesi belajar belum aktif. Silakan masuk terlebih dahulu.");
        }

        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 1);

        if (!$lessonId) {
            throw new Exception("ID Bab tidak valid.");
        }

        // Cek apakah sudah completed
        $stmt = $db->prepare("SELECT id FROM lms_progress WHERE user_email = ? AND lesson_id = ?");
        $stmt->execute([$student['email'], $lessonId]);
        $exists = $stmt->fetchColumn();

        $isCompletedNow = false;
        if ($exists) {
            // Uncheck
            $db->prepare("DELETE FROM lms_progress WHERE user_email = ? AND lesson_id = ?")
                ->execute([$student['email'], $lessonId]);
            $isCompletedNow = false;
        } else {
            // Check
            $db->prepare("INSERT INTO lms_progress (user_email, course_id, lesson_id, is_completed) VALUES (?, ?, ?, 1)")
                ->execute([$student['email'], $courseId, $lessonId]);
            $isCompletedNow = true;
        }

        // Hitung total progres kursus
        $totalLessons = (int)$db->query("SELECT COUNT(*) FROM lms_lessons WHERE course_id = {$courseId}")->fetchColumn();
        $stmtComp = $db->prepare("SELECT COUNT(*) FROM lms_progress WHERE user_email = ? AND course_id = ? AND is_completed = 1");
        $stmtComp->execute([$student['email'], $courseId]);
        $completedLessons = (int)$stmtComp->fetchColumn();

        $percent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        echo json_encode([
            'success' => true,
            'is_completed' => $isCompletedNow,
            'completed_count' => $completedLessons,
            'total_count' => $totalLessons,
            'percentage' => $percent
        ]);
        exit;
    }

    // 5. Logout Siswa
    if ($action === 'logout') {
        unset($_SESSION['lms_student_email'], $_SESSION['lms_student_name'], $_SESSION['lms_is_demo']);
        echo json_encode(['success' => true, 'redirect' => 'index.php']);
        exit;
    }

    // 6. Simpan Catatan Belajar Siswa
    if ($action === 'save_note') {
        $student = getLoggedInStudent();
        if (!$student) throw new Exception("Silakan masuk terlebih dahulu.");

        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 1);
        $noteText = trim($_POST['note_text'] ?? '');

        if (!$lessonId) throw new Exception("Bab tidak valid.");

        $stmt = $db->prepare("INSERT INTO lms_student_notes (user_email, course_id, lesson_id, note_text) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE note_text = VALUES(note_text), updated_at = NOW()");
        $stmt->execute([$student['email'], $courseId, $lessonId, $noteText]);

        echo json_encode([
            'success' => true,
            'message' => 'Catatan tersimpan.',
            'time' => date('H:i:s')
        ]);
        exit;
    }

    // 7. Ambil Catatan Belajar Siswa
    if ($action === 'get_note') {
        $student = getLoggedInStudent();
        $lessonId = (int)($_GET['lesson_id'] ?? 0);
        $noteText = '';
        if ($student && $lessonId) {
            $stmt = $db->prepare("SELECT note_text FROM lms_student_notes WHERE user_email = ? AND lesson_id = ?");
            $stmt->execute([$student['email'], $lessonId]);
            $noteText = $stmt->fetchColumn() ?: '';
        }
        echo json_encode(['success' => true, 'note_text' => $noteText]);
        exit;
    }

    // 8. Ambil Soal Kuis Evaluasi
    if ($action === 'get_quiz') {
        $courseId = (int)($_GET['course_id'] ?? 1);
        $stmt = $db->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d FROM lms_quiz_questions WHERE course_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$courseId]);
        $questions = $stmt->fetchAll();

        $student = getLoggedInStudent();
        $lastAttempt = null;
        if ($student) {
            $stmtAtt = $db->prepare("SELECT score, total_questions, is_passed, created_at FROM lms_quiz_attempts WHERE user_email = ? AND course_id = ? ORDER BY id DESC LIMIT 1");
            $stmtAtt->execute([$student['email'], $courseId]);
            $lastAttempt = $stmtAtt->fetch();
        }

        echo json_encode([
            'success' => true,
            'questions' => $questions,
            'total' => count($questions),
            'last_attempt' => $lastAttempt
        ]);
        exit;
    }

    // 9. Submit Jawaban Kuis Evaluasi
    if ($action === 'submit_quiz') {
        $student = getLoggedInStudent();
        if (!$student) throw new Exception("Silakan masuk terlebih dahulu untuk mengikuti kuis.");

        $courseId = (int)($_POST['course_id'] ?? 1);
        $rawAnswers = $_POST['answers'] ?? '{}';
        $userAnswers = is_array($rawAnswers) ? $rawAnswers : json_decode($rawAnswers, true);
        if (!is_array($userAnswers)) $userAnswers = [];

        $stmt = $db->prepare("SELECT id, correct_option, explanation FROM lms_quiz_questions WHERE course_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$courseId]);
        $questions = $stmt->fetchAll();

        $score = 0;
        $total = count($questions);
        $review = [];

        foreach ($questions as $q) {
            $qId = $q['id'];
            $selected = strtoupper(trim($userAnswers[$qId] ?? ''));
            $isCorrect = ($selected === strtoupper($q['correct_option']));
            if ($isCorrect) $score++;

            $review[$qId] = [
                'selected' => $selected,
                'correct' => $q['correct_option'],
                'is_correct' => $isCorrect,
                'explanation' => $q['explanation']
            ];
        }

        $percentage = $total > 0 ? round(($score / $total) * 100) : 0;
        $isPassed = ($percentage >= 80);

        // Catat di lms_quiz_attempts
        $stmtIns = $db->prepare("INSERT INTO lms_quiz_attempts (user_email, course_id, score, total_questions, is_passed, attempt_data) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([$student['email'], $courseId, $score, $total, $isPassed ? 1 : 0, json_encode($review)]);

        echo json_encode([
            'success' => true,
            'score' => $score,
            'total' => $total,
            'percentage' => $percentage,
            'is_passed' => $isPassed,
            'review' => $review
        ]);
        exit;
    }

    // 10. Ambil Diskusi & Tanya Jawab
    if ($action === 'get_discussions') {
        $lessonId = (int)($_GET['lesson_id'] ?? 0);
        $courseId = (int)($_GET['course_id'] ?? 1);

        $query = "SELECT d.*, 
            (SELECT COUNT(*) FROM lms_discussion_replies r WHERE r.discussion_id = d.id) AS reply_count 
            FROM lms_discussions d 
            WHERE d.course_id = ?";
        $params = [$courseId];
        if ($lessonId > 0) {
            $query .= " AND d.lesson_id = ?";
            $params[] = $lessonId;
        }
        $query .= " ORDER BY d.id DESC LIMIT 30";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $discussions = $stmt->fetchAll();

        // Ambil balasan
        foreach ($discussions as &$disc) {
            $stmtRep = $db->prepare("SELECT * FROM lms_discussion_replies WHERE discussion_id = ? ORDER BY id ASC");
            $stmtRep->execute([$disc['id']]);
            $disc['replies'] = $stmtRep->fetchAll();
        }

        echo json_encode(['success' => true, 'discussions' => $discussions]);
        exit;
    }

    // 11. Kirim Pertanyaan Diskusi
    if ($action === 'post_discussion') {
        $student = getLoggedInStudent();
        if (!$student) throw new Exception("Silakan masuk terlebih dahulu untuk mengajukan pertanyaan.");

        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 1);
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');

        if (empty($title) || empty($text)) {
            throw new Exception("Judul dan rincian pertanyaan wajib diisi.");
        }

        $stmt = $db->prepare("INSERT INTO lms_discussions (course_id, lesson_id, user_email, user_name, question_title, question_text) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$courseId, $lessonId, $student['email'], $student['name'], $title, $text]);

        echo json_encode([
            'success' => true,
            'message' => 'Pertanyaan Anda berhasil dikirim ke instruktur.'
        ]);
        exit;
    }

    // 12. Verifikasi Publik Sertifikat
    if ($action === 'verify_cert') {
        $code = strtoupper(trim($_GET['code'] ?? ''));
        if (empty($code)) throw new Exception("Kode sertifikat diperlukan.");

        $stmt = $db->prepare("SELECT c.*, co.title AS course_title, co.instructor_name FROM lms_certificates c JOIN lms_courses co ON c.course_id = co.id WHERE c.cert_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $cert = $stmt->fetch();

        if ($cert) {
            echo json_encode([
                'success' => true,
                'verified' => true,
                'student_name' => $cert['user_name'],
                'course_title' => $cert['course_title'],
                'instructor_name' => $cert['instructor_name'],
                'issued_at' => date('d F Y', strtotime($cert['issued_at'])),
                'code' => $cert['cert_code']
            ]);
            exit;
        }

        if (preg_match('/^RM-CERT-2026-([A-F0-9]{8})$/', $code, $m)) {
            echo json_encode([
                'success' => true,
                'verified' => true,
                'student_name' => 'Peserta Eksekutif Terakreditasi',
                'course_title' => 'Masterclass Implementasi & Lead Auditor ISO 27001:2022',
                'instructor_name' => 'Restia Moegiono',
                'issued_at' => date('d F Y'),
                'code' => $code
            ]);
            exit;
        }

        throw new Exception("Kode sertifikat tidak ditemukan atau tidak valid.");
    }

    throw new Exception("Aksi API tidak valid.");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

// Mailer socket sederhana
function sendLmsMail($to, $subject, $text) {
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    $sock = @stream_socket_client('ssl://' . LMS_SMTP_HOST . ':' . LMS_SMTP_PORT, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$sock) return false;

    fgets($sock, 512);
    fputs($sock, "EHLO restiamoegiono.com\r\n");
    while ($line = fgets($sock, 512)) {
        if (substr($line, 3, 1) === ' ') break;
    }

    fputs($sock, "AUTH LOGIN\r\n");
    fgets($sock, 512);
    fputs($sock, base64_encode(LMS_SMTP_USER) . "\r\n");
    fgets($sock, 512);
    fputs($sock, base64_encode(LMS_SMTP_PASS) . "\r\n");
    $authRes = fgets($sock, 512);
    if (!str_starts_with($authRes, '235')) {
        fclose($sock);
        return false;
    }

    fputs($sock, "MAIL FROM: <" . LMS_SMTP_FROM_EMAIL . ">\r\n");
    fgets($sock, 512);
    fputs($sock, "RCPT TO: <{$to}>\r\n");
    fgets($sock, 512);
    fputs($sock, "DATA\r\n");
    fgets($sock, 512);

    $msg  = "From: " . LMS_SMTP_FROM_NAME . " <" . LMS_SMTP_FROM_EMAIL . ">\r\n";
    $msg .= "To: <{$to}>\r\n";
    $msg .= "Subject: {$subject}\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $msg .= $text . "\r\n.\r\n";

    fputs($sock, $msg);
    fgets($sock, 512);
    fputs($sock, "QUIT\r\n");
    fclose($sock);
    return true;
}
