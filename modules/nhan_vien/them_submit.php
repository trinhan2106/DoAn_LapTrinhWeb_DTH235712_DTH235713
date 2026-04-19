<?php
// modules/nhan_vien/them_submit.php
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
    $_SESSION['error_msg'] = "Lỗi CSRF Token!";
    header("Location: them.php"); exit();
}

// Bóc tách dữ liệu
$maNV = trim($_POST['maNV'] ?? '');
$username = trim($_POST['username'] ?? '');
$tenNV = trim($_POST['tenNV'] ?? '');
$chucVu = trim($_POST['chucVu'] ?? '');
$role_id = (int)($_POST['role_id'] ?? 2);
$sdt = trim($_POST['sdt'] ?? '');
$email = trim($_POST['email'] ?? '');

$adminThucHien = $_SESSION['user_id'] ?? 'Admin_Unknown';

if (empty($maNV) || empty($username) || empty($tenNV)) {
    $_SESSION['error_msg'] = "Không được bỏ trống Mã NV, Username và Tên.";
    header("Location: them.php"); exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // 1. Kiểm tra Username hoặc maNV Đã Tồn Tại
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM NHAN_VIEN WHERE maNV = ? OR username = ?");
    $stmtCheck->execute([$maNV, $username]);
    if ($stmtCheck->fetchColumn() > 0) {
        $_SESSION['error_msg'] = "Mã NV hoặc Username này đã có người xài!";
        header("Location: them.php"); exit();
    }

    // 2. Hash Password Định Danh Mặc Định "123456" VÀ Ép Phải Đổi Pass
    $defaultPassHash = password_hash('123456', PASSWORD_BCRYPT);
    $phaiDoiMatKhau = 1;

    // 3. INSERT VÀO DB
    $stmtIns = $pdo->prepare("
        INSERT INTO NHAN_VIEN (maNV, tenNV, chucVu, sdt, email, username, password_hash, role_id, phai_doi_matkhau) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtIns->execute([$maNV, $tenNV, $chucVu, $sdt, $email, $username, $defaultPassHash, $role_id, $phaiDoiMatKhau]);

    // 4. GHI NHẬT KÝ KIỂM TOÁN LÕI (AUDIT LOG)
    $logChiTiet = "Tạo mới Nhân viên: $tenNV (Role: $role_id) với mật khẩu khởi tạo 123456.";
    ghiAuditLog($pdo, $adminThucHien, 'THEM_NHAN_VIEN', 'NHAN_VIEN', $maNV, $logChiTiet);

    // XONG
    $_SESSION['success_msg'] = "Khai báo biên chế thành công cho nhân sự: MÃ $maNV!";
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    error_log("[them_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi them nhan vien. Vui long thu lai.";
    header("Location: them.php");
    exit();
}
