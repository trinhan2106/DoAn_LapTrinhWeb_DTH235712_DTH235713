<?php
// modules/khach_hang_account/kh_dangnhap_submit.php
/**
 * Xử lý đăng nhập dành riêng cho Tenant (Khách Thuê).
 * Cách ly hoàn toàn với bảng NHAN_VIEN. Sử dụng chung Throttle IP.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/login_throttle.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: kh_dangnhap.php");
    exit();
}

$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Request hết hạn, reload lại trang.";
    header("Location: kh_dangnhap.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (empty($username) || empty($password)) {
    $_SESSION['error_msg'] = "Dữ liệu không đầy đủ.";
    header("Location: kh_dangnhap.php");
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Kiểm tra khóa tạm thời Brute-force
    $lockStatus = kiemTraLockout($pdo, $ip, "TENANT_" . $username); // Thêm tiền tố Tenant tránh nhầm lẫn log nội bộ
    if ($lockStatus['locked']) {
        $_SESSION['error_msg'] = "Tài khoản bị khóa do vượt định mức đăng nhập sai. Chờ {$lockStatus['remaining']} phút.";
        header("Location: kh_dangnhap.php");
        exit();
    }

    // Truy vấn thông tin tài khoản Khách hàng
    $stmt = $pdo->prepare("
        SELECT acc.accountId, acc.maKH, acc.username, acc.password_hash, acc.phai_doi_matkhau, acc.role_id, kh.tenKH 
        FROM KHACH_HANG_ACCOUNT acc
        JOIN KHACH_HANG kh ON acc.maKH = kh.maKH
        WHERE acc.username = ? AND acc.deleted_at IS NULL
    ");
    $stmt->execute([$username]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account || !password_verify($password, $account['password_hash'])) {
        ghiLogDangNhap($pdo, "TENANT_" . $username, $ip, 0);
        $_SESSION['error_msg'] = "Sai thông tin hoặc mật khẩu.";
        header("Location: kh_dangnhap.php");
        exit();
    }

    // Xác thực Thành Công.
    ghiLogDangNhap($pdo, "TENANT_" . $username, $ip, 1);
    resetLoginAttempts($pdo, "TENANT_" . $username, $ip); // FIX-16
    
    // Setup định dạng Session
    session_regenerate_id(true);

    // Ở Tenant Portal, user_id là maKH (Mã cấu trúc liên kết Database Hợp đồng gốc)
    $_SESSION['user_id']       = $account['maKH']; 
    $_SESSION['accountId']     = $account['accountId']; 
    $_SESSION['username']      = $account['username'];
    $_SESSION['ten_user']      = $account['tenKH'];
    $_SESSION['user_role']     = (int) $account['role_id']; 
    $_SESSION['last_activity'] = time();

    rotateCSRFToken(); // FIX-14

    // Check force change password block
    if ((int) $account['phai_doi_matkhau'] === 1) {
        header("Location: " . BASE_URL . "modules/ho_so/doi_mat_khau_batbuoc.php"); 
        // Note: Đảm bảo module này chấp nhận role 4
        exit();
    }

    // Điều hướng vào Tenant Backend
    header("Location: " . BASE_URL . "modules/tenant/dashboard.php");
    exit();

} catch (PDOException $e) {
    error_log("DB Tenant Login Fail: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi kết nối máy chủ dịch vụ.";
    header("Location: kh_dangnhap.php");
    exit();
}
