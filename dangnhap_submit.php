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
            . "Vui lòng thử lại sau <strong>{$lockStatus['remaining']} phút</strong>.";
        header("Location: dangnhap.php");
        exit();
    }

    // -------------------------------------------------------------
    // 4. TRUY VẤN DB + XÁC THỰC MẬT KHẨU
    // Lưu ý: schema DB dùng cột `role_id`, nhưng toàn hệ thống đọc
    // $_SESSION['user_role'] (xem auth.php::kiemTraRole). Query SELECT
    // cột role_id của DB, gán vào session key user_role.
    // -------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT maNV, tenNV, role_id, password_hash, phai_doi_matkhau
        FROM NHAN_VIEN
        WHERE username = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Nếu không tìm thấy tài khoản HOẶC mật khẩu không khớp
    if (!$user || !password_verify($password, $user['password_hash'])) {
        ghiLogDangNhap($pdo, $username, $ip, 0); // 5. Ghi LOGIN_ATTEMPT thất bại
        $_SESSION['error_msg'] = "Thông tin đăng nhập không đúng.";
        header("Location: dangnhap.php");
        exit();
    }

    // -------------------------------------------------------------
    // 5. GHI LOGIN_ATTEMPT THÀNH CÔNG + RESET FAILED ATTEMPTS (FIX-16)
    // -------------------------------------------------------------
    ghiLogDangNhap($pdo, $username, $ip, 1);
    resetLoginAttempts($pdo, $username, $ip);

    // -------------------------------------------------------------
    // 6. KHỞI TẠO SESSION + REGENERATE_ID CHỐNG SESSION FIXATION
    // -------------------------------------------------------------
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['maNV'];
    $_SESSION['username']      = $username;
    $_SESSION['ten_user']      = $user['tenNV'];
    $_SESSION['user_role']     = (int) $user['role_id'];
    $_SESSION['last_activity'] = time();

    // Reset CSRF token để phiên mới sinh token mới (FIX-14)
    rotateCSRFToken();

    // -------------------------------------------------------------
    // 7a. FORCE ĐỔI MẬT KHẨU LẦN ĐẦU (nếu có)
    // -------------------------------------------------------------
    if ((int) $user['phai_doi_matkhau'] === 1) {
        header("Location: " . BASE_URL . "modules/ho_so/doi_mat_khau_batbuoc.php");
        exit();
    }

    // -------------------------------------------------------------
    // 7b. REDIRECT THEO ROLE (RBAC - Task 2.2)
    // -------------------------------------------------------------
    switch ((int) $user['role_id']) {
        case ROLE_ADMIN:
        case ROLE_QUAN_LY_NHA:
        case ROLE_KE_TOAN:
            // Nhân sự nội bộ -> Dashboard admin
            header("Location: " . BASE_URL . "modules/dashboard/admin.php");
            exit();

        case ROLE_KHACH_HANG:
            // Khách hàng -> Tenant dashboard
            header("Location: " . BASE_URL . "modules/tenant/dashboard.php");
            exit();

        default:
            // Role lạ/không xác định -> trang chủ public
            header("Location: " . BASE_URL . "index.php");
            exit();
    }

} catch (PDOException $e) {
    error_log("dangnhap_submit PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Hệ thống đang gặp sự cố kết nối. Vui lòng thử lại sau.";
    header("Location: dangnhap.php");
    exit();
}
