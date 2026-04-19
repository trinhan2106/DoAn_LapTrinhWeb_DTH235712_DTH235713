<?php
// modules/khach_hang_account/tao_taikhoan_submit.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Mã xác thực lỗi.";
    header("Location: tao_taikhoan.php"); 
    exit();
}

$maKH = trim($_POST['maKH'] ?? '');
$username = trim($_POST['username'] ?? '');
$adminId = $_SESSION['user_id'] ?? 'Admin_Sys';

if (empty($maKH) || empty($username)) {
    $_SESSION['error_msg'] = "Thông tin đăng ký thiếu.";
    header("Location: tao_taikhoan.php"); 
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // Kiểm tra username đã tồn tại trong hệ thống Tenant
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM KHACH_HANG_ACCOUNT WHERE username = ?");
    $stmtCheck->execute([$username]);
    if ($stmtCheck->fetchColumn() > 0) {
        $_SESSION['error_msg'] = "Username {$username} đã bị đăng ký.";
        header("Location: tao_taikhoan.php"); 
        exit();
    }

    // 1. Chuẩn bị Insert
    $accountId = sinhMaNgauNhien('ACC-KH-', 6); // Hàm helper từ functions.php
    $password_hash = password_hash('123456', PASSWORD_BCRYPT);
    $phai_doi_matkhau = 1;
    $role_id = ROLE_KHACH_HANG; // Default 4

    $stmtIns = $pdo->prepare("
        INSERT INTO KHACH_HANG_ACCOUNT (accountId, maKH, username, password_hash, role_id, phai_doi_matkhau) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtIns->execute([$accountId, $maKH, $username, $password_hash, $role_id, $phai_doi_matkhau]);

    // 2. Gán Audit Log
    $logChiTiet = "Cấp tài khoản truy cập. Tên đăng nhập: {$username}. Khách Hàng Mapping: {$maKH}";
    ghiAuditLog($pdo, $adminId, 'CREATE_TENANT_ACCOUNT', 'KHACH_HANG_ACCOUNT', $accountId, $logChiTiet);

    $_SESSION['success_msg'] = "Tạo tài khoản Tenant thành công [ID: {$accountId}]!";
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    error_log("Lõi ghi nhận DB Lỗi: " . $e->getMessage());
    $_SESSION['error_msg'] = "Sự cố chèn bản ghi: Database Error.";
    header("Location: tao_taikhoan.php");
    exit();
}
