<?php
// dang_ky_thue_submit.php
/**
 * Xu ly yeu cau thue phong tu trang public (khong can dang nhap).
 * - Sinh ma bang sinhMaNgauNhien (Convention C.5).
 * - Rate limit: toi da 3 yeu cau / 10 phut theo session.
 * - Validate SDT Viet Nam bang regex.
 * - Escape tat ca bien truoc khi render vao email (Convention C.4).
 * - Khong die() — dung flash message + redirect.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/common/db.php';
require_once __DIR__ . '/includes/common/csrf.php';
require_once __DIR__ . '/includes/common/functions.php';

// Kiem tra phuong thuc HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Validate CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
    header("Location: index.php");
    exit();
}

// Doc input
$hoTen       = trim($_POST['hoTen'] ?? '');
$soDienThoai = trim($_POST['soDienThoai'] ?? '');
$email       = trim($_POST['email'] ?? '');
$maPhong     = trim($_POST['maPhong'] ?? '');
$ghiChu      = trim($_POST['ghiChu'] ?? '');

$redirectUrl = "chi_tiet_phong.php?id=" . urlencode($maPhong);

// Validate input bat buoc
if (empty($hoTen) || empty($soDienThoai) || empty($maPhong)) {
    $_SESSION['error_msg'] = "Vui long dien day du ho ten, so dien thoai va ma phong.";
    header("Location: {$redirectUrl}&msg=error_empty");
    exit();
}

// Validate do dai input
if (strlen($hoTen) > 100 || strlen($maPhong) > 50) {
    $_SESSION['error_msg'] = "Du lieu vuot qua do dai cho phep.";
    header("Location: {$redirectUrl}&msg=error_empty");
    exit();
}

// Validate SDT Viet Nam (A.1.8): 0xxxxxxxxx hoac +84xxxxxxxxx
if (!preg_match('/^(0|\+84)\d{9,10}$/', $soDienThoai)) {
    $_SESSION['error_msg'] = "So dien thoai khong hop le. Vui long nhap so dien thoai Viet Nam (VD: 0901234567).";
    header("Location: {$redirectUrl}&msg=error_phone");
    exit();
}

// Validate email (neu co nhap)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_msg'] = "Dia chi email khong hop le.";
    header("Location: {$redirectUrl}&msg=error_email");
    exit();
}

// Rate limit: toi da 3 yeu cau / 10 phut theo session (A.1.9)
$rateLimitKey = 'rental_request_timestamps';
$rateLimitMax = 3;
$rateLimitWindow = 600; // 10 phut

if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = [];
}

// Loc bo cac timestamp da het han
$now = time();
$_SESSION[$rateLimitKey] = array_filter(
    $_SESSION[$rateLimitKey],
    fn($ts) => ($now - $ts) < $rateLimitWindow
);

if (count($_SESSION[$rateLimitKey]) >= $rateLimitMax) {
    $_SESSION['error_msg'] = "Ban da gui qua nhieu yeu cau. Vui long doi 10 phut roi thu lai.";
    header("Location: {$redirectUrl}&msg=error_ratelimit");
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Kiem tra rate limit tu DB theo IP (bo sung)
    $clientIP = layIP();
    $stmtRate = $pdo->prepare("
        SELECT COUNT(*) FROM YEU_CAU_THUE 
        WHERE sdt = ? AND ngayYeuCau > DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND deleted_at IS NULL
    ");
    $stmtRate->execute([$soDienThoai]);
    $countRecent = (int)$stmtRate->fetchColumn();

    if ($countRecent >= $rateLimitMax) {
        $_SESSION['error_msg'] = "So dien thoai nay da gui qua nhieu yeu cau. Vui long doi 10 phut roi thu lai.";
        header("Location: {$redirectUrl}&msg=error_ratelimit");
        exit();
    }

    // Sinh ma yeu cau an toan (Convention C.5)
    $maYC = sinhMaNgauNhien('YC-' . date('Ym') . '-', 6);

    // INSERT yeu cau thue
    $stmt = $pdo->prepare("
        INSERT INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email) 
        VALUES (:mayc, :phong, :ten, :sdt, :mail)
    ");
    $stmt->execute([
        ':mayc'  => $maYC,
        ':phong' => $maPhong,
        ':ten'   => $hoTen,
        ':sdt'   => $soDienThoai,
        ':mail'  => $email ?: null
    ]);

    // Ghi nhan timestamp vao session de rate limit
    $_SESSION[$rateLimitKey][] = time();

    // Gui email xac nhan neu co email hop le
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        require_once __DIR__ . '/includes/common/mailer.php';

        // Escape tat ca bien truoc khi dua vao template (Convention C.4 — chong XSS Reflected)
        $hoTen_e       = htmlspecialchars($hoTen, ENT_QUOTES, 'UTF-8');
        $maPhong_e     = htmlspecialchars($maPhong, ENT_QUOTES, 'UTF-8');
        $maYC_e        = htmlspecialchars($maYC, ENT_QUOTES, 'UTF-8');
        $soDienThoai_e = htmlspecialchars($soDienThoai, ENT_QUOTES, 'UTF-8');

        $subject = "Xac Nhan Dang Ky Thue Van Phong";
        $htmlBody = "
            <div style='font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; max-width: 600px;'>
                <h2 style='color: #1e3a5f;'>Kinh gui {$hoTen_e},</h2>
                <p>He thong quan ly <strong>Blue Sky Tower</strong> xin cam on anh/chi da quan tam va dang ky nhan tu van thue phong: <strong>{$maPhong_e}</strong>.</p>
                
                <div style='background-color: #f4f7f9; padding: 15px; border-left: 4px solid #c9a66b; margin-top:15px; margin-bottom: 20px;'>
                    <strong>Ma yeu cau: <span style='color: #d35400; font-size: 1.25rem;'>{$maYC_e}</span></strong>
                </div>
                
                <p>Doi ngu chuyen vien se lien lac qua so dien thoai <i>{$soDienThoai_e}</i> de tu van va sap xep lich hen.</p>
                <p>Tran trong cam on,</p>
                <br/>
                <p><strong>BQL Toa Nha Blue Sky Tower</strong></p>
                <hr style='border: none; border-top: 1px dashed #ccc;'/>
                <p style='font-size: 0.85em; color: #7f8c8d; text-align: center;'>Email tu dong, vui long khong reply.</p>
            </div>
        ";

        try {
            sendEmail($email, $subject, $htmlBody);
        } catch (Exception $mailErr) {
            // Yeu cau da luu thanh cong, chi ghi log loi mail
            error_log("[dang_ky_thue_submit] Gui email loi: " . $mailErr->getMessage());
        }
    }

    $_SESSION['success_msg'] = "Dang ky thanh cong! Ma yeu cau cua ban la: {$maYC}. Chung toi se lien he voi ban som nhat.";
    header("Location: {$redirectUrl}&msg=success");
    exit();

} catch (PDOException $e) {
    error_log("[dang_ky_thue_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi gui yeu cau. Vui long thu lai sau.";
    header("Location: {$redirectUrl}&msg=error_exception");
    exit();
}
