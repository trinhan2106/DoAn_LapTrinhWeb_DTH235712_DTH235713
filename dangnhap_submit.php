<?php
// dangnhap_submit.php
/**
 * TRẠM KIỂM SOÁT ĐĂNG NHẬP (AUTH ENTRY POINT)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/common/db.php';
require_once __DIR__ . '/includes/common/csrf.php';
require_once __DIR__ . '/includes/common/login_throttle.php'; // Engine bảo vệ Brute Force

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Truy cập bất hợp pháp (Method Not Allowed).");
}

// 1. CHẶN CSRF (Hợp kim bảo vệ Request)
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phiên giao dịch đã hết hạn. Vui lòng tải lại trang đăng nhập.";
    header("Location: dangnhap.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];

if (empty($username) || empty($password)) {
    $_SESSION['error_msg'] = "Vui lòng cung cấp cả Tài Khoản Mạng và Mật Khẩu Phiên.";
    header("Location: dangnhap.php");
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    // -------------------------------------------------------------
    // LỚP KIỂM SOÁT SỐ 1: KIỂM TRA LOCKOUT BRUTE FORCE
    // -------------------------------------------------------------
    $lockStatus = kiemTraLockout($pdo, $ip, $username);
    if ($lockStatus['locked']) {
        $_SESSION['error_msg'] = "Tài khoản hoặc Dải IP của bạn đã bị MẠNG CỤC BỘ KHÓA DO NHẬP SAI QUÁ 5 LẦN. Vui lòng bẻ khóa hoặc thử lại sau <strong>{$lockStatus['remaining']} phút</strong>.";
        header("Location: dangnhap.php");
        exit();
    }

    // -------------------------------------------------------------
    // LỚP KIỂM SOÁT SỐ 2: TRUY VẤN VÀ XÁC THỰC MẬT MÃ BCRYPT
    // -------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT maNV, tenNV, role_id, password_hash, phai_doi_matkhau 
        FROM NHAN_VIEN 
        WHERE username = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Nếu Mất dấu mục tiêu HOẶC Hash Bcrypt Gãy
    if (!$user || !password_verify($password, $user['password_hash'])) {
        ghiLogDangNhap($pdo, $username, $ip, 0); // Lưu Vết Hacking: Status 0
        $_SESSION['error_msg'] = "Thông tin xác thực danh tính không trùng khớp.";
        header("Location: dangnhap.php");
        exit();
    }

    // -------------------------------------------------------------
    // VƯỢT RÀO THÀNH CÔNG: KHỞI TẠO SESION KHUNG XƯƠNG
    // -------------------------------------------------------------
    ghiLogDangNhap($pdo, $username, $ip, 1); // Cổng Trắng: Status 1
    
    $_SESSION['user_id']  = $user['maNV'];
    $_SESSION['username'] = $username;
    $_SESSION['ten_user'] = $user['tenNV'];
    $_SESSION['role_id']  = (int)$user['role_id'];

    // -------------------------------------------------------------
    // CHUỖI REDIRECT PHÂN LUỒNG MỤC TIÊU (RBAC)
    // -------------------------------------------------------------
    // NẾU Tài khoản nằm trong diện Bắt Buộc Kích Hoạt Mật Khẩu (Lần đầu đăng nhập)
    if ($user['phai_doi_matkhau'] == 1) {
        header("Location: modules/ho_so/doi_mat_khau_batbuoc.php");
        exit();
    }

    // Pass hết thì về Main Matrix
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    error_log("Lỗi Xương Sống Kết Nối Đăng Nhập: " . $e->getMessage());
    $_SESSION['error_msg'] = "Mất kết nối Tâm Máy Trạm. Bộ Phận Sửa Chữa đang hồi phục...";
    header("Location: dangnhap.php");
    exit();
}
