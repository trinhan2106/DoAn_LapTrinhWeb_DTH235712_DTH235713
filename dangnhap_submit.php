<?php
// dangnhap_submit.php
/**
 * Xử lý đăng nhập chung cho Nhân viên (Admin, Quản lý Nhà, Kế toán).
 * Các bước:
 *  1. Validate CSRF token
 *  2. Validate input (username, password không rỗng)
 *  3. Kiểm tra lockout brute-force (5 lần sai trong 15 phút)
 *  4. Truy vấn DB + password_verify bcrypt
 *  5. Ghi LOGIN_ATTEMPT (thành công / thất bại)
 *  6. Khởi tạo session + regenerate_id để chống session fixation
 *  7. Redirect theo role (RBAC) hoặc force đổi mật khẩu lần đầu
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/roles.php';
require_once __DIR__ . '/includes/common/db.php';
require_once __DIR__ . '/includes/common/csrf.php';
require_once __DIR__ . '/includes/common/login_throttle.php';

// Chặn truy cập trực tiếp bằng GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dangnhap.php");
    exit();
}

// -------------------------------------------------------------
// 1. CSRF TOKEN CHECK
// -------------------------------------------------------------
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.";
    header("Location: dangnhap.php");
    exit();
}

// -------------------------------------------------------------
// 2. VALIDATE INPUT
// -------------------------------------------------------------
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (empty($username) || empty($password)) {
    $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ tài khoản và mật khẩu.";
    header("Location: dangnhap.php");
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    // -------------------------------------------------------------
    // 3. KIỂM TRA LOCKOUT BRUTE-FORCE
    // -------------------------------------------------------------
    $lockStatus = kiemTraLockout($pdo, $ip, $username);
    if ($lockStatus['locked']) {
        $_SESSION['error_msg'] = "Tài khoản đã bị khóa do nhập sai quá 5 lần. "
            . "Vui lòng thử lại sau <strong>{$lockStatus['remaining']