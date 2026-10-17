<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/DokuService.php';

$db = StoreDB::get();

// Fungsi Evaluasi Kupon Diskon (Mendukung Pembagian Scope E-Store vs LMS)
function evaluateCoupon($db, $couponCode, $amount, $productId = '') {
    if (empty($couponCode)) {
        return ['valid' => false, 'error' => 'Kode kupon tidak boleh kosong.'];
    }
    $codeClean = strtoupper(trim($couponCode));
    $stmt = $db->prepare("SELECT * FROM `store_coupons` WHERE `code` = ? AND `is_active` = 1 LIMIT 1");
    $stmt->execute([$codeClean]);
    $cpn = $stmt->fetch();

    if (!$cpn) {
        return ['valid' => false, 'error' => "Kode promo '{$codeClean}' tidak ditemukan atau tidak aktif."];
    }
    if (!empty($cpn['expires_at']) && strtotime($cpn['expires_at']) < strtotime(date('Y-m-d'))) {
        return ['valid' => false, 'error' => "Kode promo '{$codeClean}' telah melewati masa berlaku."];
    }
    if ((int)$cpn['usage_limit'] > 0 && (int)$cpn['used_count'] >= (int)$cpn['usage_limit']) {
        return ['valid' => false, 'error' => "Kuota penggunaan kode promo '{$codeClean}' sudah habis."];
    }
    if ($amount < (float)$cpn['min_order']) {
        return ['valid' => false, 'error' => "Kode promo ini berlaku untuk minimal pembelian Rp " . number_format($cpn['min_order'], 0, ',', '.')];
    }

    // Pembedaan Target Kupon: ESTORE vs LMS vs ALL
    $applicableTo = $cpn['applicable_to'] ?? 'ALL';
    if ($applicableTo !== 'ALL' && !empty($productId)) {
        $stmtP = $db->prepare("SELECT `category` FROM `store_products` WHERE `id` = ? LIMIT 1");
        $stmtP->execute([$productId]);
        $cat = $stmtP->fetchColumn() ?: '';
        $isLms = ($productId === 'course-iso-27001' || stripos($productId, 'course') !== false || stripos($cat, 'Akademi') !== false || stripos($cat, 'LMS') !== false);

        if ($applicableTo === 'ESTORE' && $isLms) {
            return ['valid' => false, 'error' => "Kode promo '{$codeClean}' khusus untuk pembelian E-Book di Toko (tidak berlaku untuk kelas LMS)."];
        }
        if ($applicableTo === 'LMS' && !$isLms) {
            return ['valid' => false, 'error' => "Kode promo '{$codeClean}' khusus untuk pendaftaran / join kelas LMS Masterclass (tidak berlaku untuk E-Book)."];
        }
    }

    $discount = 0;
    if ($cpn['discount_type'] === 'PERCENT') {
        $discount = $amount * ((float)$cpn['discount_value'] / 100);
        if ((float)$cpn['max_discount'] > 0 && $discount > (float)$cpn['max_discount']) {
            $discount = (float)$cpn['max_discount'];
        }
    } else {
        $discount = (float)$cpn['discount_value'];
    }

    $discount = min($discount, $amount);
    $finalAmount = max(1000, $amount - $discount);

    return [
        'valid' => true,
        'coupon' => $cpn,
        'code' => $cpn['code'],
        'applicable_to' => $applicableTo,
        'discount_type' => $cpn['discount_type'],
        'discount_value' => (float)$cpn['discount_value'],
        'discount_amount' => $discount,
        'original_amount' => $amount,
        'final_amount' => $finalAmount,
        'message' => 'Kode promo berhasil diterapkan!'
    ];
}

// 1. Endpoint Cek Kupon (GET / POST action=check_coupon atau validate_coupon)
$rawBody = file_get_contents('php://input');
$rawJson = json_decode($rawBody, true) ?: [];

$action = trim($_GET['action'] ?? ($_POST['action'] ?? ($rawJson['action'] ?? '')));
if ($action === 'check_coupon' || $action === 'validate_coupon') {
    $code = trim($_GET['code'] ?? ($_POST['code'] ?? ($rawJson['code'] ?? '')));
    $amt = (float)($_GET['amount'] ?? ($_POST['amount'] ?? ($rawJson['amount'] ?? 0)));
    $prodId = trim($_GET['product_id'] ?? ($_POST['product_id'] ?? ($rawJson['product_id'] ?? '')));
    
    $eval = evaluateCoupon($db, $code, $amt, $prodId);
    if (!$eval['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'valid' => false,
            'error' => $eval['error'],
            'message' => $eval['error']
        ]);
        exit;
    }
    echo json_encode([
        'success' => true,
        'valid' => true,
        'code' => $eval['code'],
        'applicable_to' => $eval['applicable_to'],
        'discount_type' => $eval['discount_type'],
        'discount_value' => $eval['discount_value'],
        'discount_amount' => $eval['discount_amount'],
        'final_amount' => $eval['final_amount'],
        'max_discount' => (float)($eval['coupon']['max_discount'] ?? 0),
        'message' => $eval['message']
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metode request tidak diizinkan.']);
    exit;
}

// 2. Submit Pembuatan Pesanan & Sesi DOKU
$input = !empty($rawJson) ? $rawJson : $_POST;

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$org = trim($input['org'] ?? '');
$whatsapp = trim($input['whatsapp'] ?? '');
$couponCode = trim($input['coupon_code'] ?? '');

if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Harap lengkapi Nama Lengkap dan Alamat Email Anda.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Alamat email tidak valid.']);
    exit;
}

// Ambil daftar produk yang dibeli (bisa array items dari Keranjang Belanja, atau single product_id)
$requestedIds = [];
if (!empty($input['items']) && is_array($input['items'])) {
    foreach ($input['items'] as $it) {
        $pid = is_array($it) ? ($it['id'] ?? '') : (is_string($it) ? $it : '');
        if (!empty($pid)) {
            $requestedIds[] = trim($pid);
        }
    }
} elseif (!empty($input['product_id'])) {
    $requestedIds[] = trim($input['product_id']);
}

$requestedIds = array_values(array_unique(array_filter($requestedIds)));
if (empty($requestedIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Keranjang belanja Anda masih kosong. Silakan pilih e-book terlebih dahulu.']);
    exit;
}

try {
    // Ambil data seluruh produk yang dipilih
    $placeholders = implode(',', array_fill(0, count($requestedIds), '?'));
    $stmt = $db->prepare("SELECT * FROM `store_products` WHERE `id` IN ($placeholders) AND `is_active` = 1");
    $stmt->execute($requestedIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Produk e-book yang dipilih tidak ditemukan atau sudah tidak aktif.']);
        exit;
    }

    $rawPrice = 0.00;
    $itemsData = [];
    $titles = [];
    foreach ($products as $p) {
        $rawPrice += (float)$p['price'];
        $itemsData[] = [
            'id' => $p['id'],
            'title' => $p['title'],
            'price' => (float)$p['price'],
            'master_file' => $p['master_file'],
            'cover_image' => $p['cover_image'],
            'pages' => (int)$p['pages']
        ];
        $titles[] = $p['title'];
    }

    if (count($products) === 1) {
        $orderTitle = $products[0]['title'];
        $primaryProductId = $products[0]['id'];
    } else {
        $orderTitle = 'Paket ' . count($products) . ' E-Book: ' . implode(', ', $titles);
        if (mb_strlen($orderTitle) > 240) {
            $orderTitle = mb_substr($orderTitle, 0, 237) . '...';
        }
        $primaryProductId = implode(',', array_column($products, 'id'));
    }

    $discountAmount = 0.00;
    $appliedCoupon = null;

    // Evaluasi jika ada kupon promo
    if (!empty($couponCode)) {
        $cRes = evaluateCoupon($db, $couponCode, $rawPrice, $products[0]['id']);
        if ($cRes['valid']) {
            $discountAmount = $cRes['discount_amount'];
            $appliedCoupon = $cRes['code'];
            // Tambahkan hitungan penggunaan kupon
            $db->prepare("UPDATE `store_coupons` SET `used_count` = `used_count` + 1 WHERE `code` = ?")->execute([$appliedCoupon]);
        }
    }

    $finalAmount = max(1000, round($rawPrice - $discountAmount));
    $invoiceNumber = 'RM-' . time() . '-' . substr(bin2hex(random_bytes(3)), 0, 4);

    // Simpan order status PENDING ke basis data (termasuk items_json)
    $stmtIns = $db->prepare("INSERT INTO `store_orders` 
        (`invoice_number`, `product_id`, `product_title`, `items_json`, `customer_name`, `customer_email`, `customer_org`, `customer_whatsapp`, `amount`, `coupon_code`, `discount_amount`, `payment_status`)
        VALUES (:inv, :pid, :title, :items, :name, :email, :org, :wa, :amt, :coupon, :discount, 'PENDING')");

    $stmtIns->execute([
        ':inv' => $invoiceNumber,
        ':pid' => $primaryProductId,
        ':title' => $orderTitle,
        ':items' => json_encode($itemsData, JSON_UNESCAPED_UNICODE),
        ':name' => $name,
        ':email' => $email,
        ':org' => $org,
        ':wa' => $whatsapp,
        ':amt' => $finalAmount,
        ':coupon' => $appliedCoupon,
        ':discount' => $discountAmount
    ]);

    $orderId = $db->lastInsertId();

    // Panggil API DOKU Gateway Server-Side
    $dokuRes = DokuService::createPayment(
        $invoiceNumber,
        $finalAmount,
        $orderTitle,
        $name,
        $email
    );

    if (!$dokuRes['success']) {
        throw new Exception($dokuRes['error'] ?? 'Gagal membuat tagihan DOKU');
    }

    $paymentUrl = $dokuRes['payment_url'];

    // Perbarui payment_url di order
    $upd = $db->prepare("UPDATE `store_orders` SET `payment_url` = :url, `doku_raw_response` = :raw WHERE `id` = :id");
    $upd->execute([
        ':url' => $paymentUrl,
        ':raw' => json_encode($dokuRes['raw']),
        ':id' => $orderId
    ]);

    echo json_encode([
        'success' => true,
        'invoice' => $invoiceNumber,
        'amount' => $finalAmount,
        'discount_amount' => $discountAmount,
        'coupon_code' => $appliedCoupon,
        'payment_url' => $paymentUrl
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
