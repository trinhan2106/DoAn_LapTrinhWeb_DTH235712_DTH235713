<?php
// modules/nhan_vien/sua_submit.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
if ((int)($_SESSION['user_role'] ?? $_SESSION['role_id'] ?? 4) !== ROLE_ADMIN) {
    die("BLOCK: Access Denied.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Must POST");

$csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Lỗi CSRF Token.";
    header("Location: index.php"); exit();
}

$maNV_old = trim($_POST['maNV_old'] ?? '');
$username = trim($_POST['username'] ?? '');
$tenNV = trim($_POST['tenNV'] ?? '');
$chucVu = trim($_POST['chucVu'] ?? '');
$role_id = (int)($_POST['role_id'] ?? 2);
$sdt = trim($_POST['sdt'] ?? '');
$email = trim($_POST['email'] ?? '');

$adminThucHien = $_SESSION['user_id'] ?? 'Admin_Unknown';

$pdo = Database::getInstance()->getConnection();

try {
    // 1. NGĂN CHẶN USERNAME TRÙNG LẶP NẾU CÓ ĐỔI
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM NHAN_VIEN WHERE username = ? AND maNV != ?");
    $stmtCheck->execute([$username, $maNV_old]);
    if ($stmtCheck->fetchColumn() > 0) {
        $_SESSION['error_msg'] = "Username muốn đổi sang đã bị người khác sở hữu!";
        header("Location: sua.php?maNV=" . urlencode($maNV_old)); exit();
    }

    // 2. UPDATE KHÁNG CỰ CẤP Database
    // Không cho phép sửa maNV cứng vì là Khóa Lõi System (Foreign keys bound strictly)
    $stmtUpd = $pdo->prepare("
        UPDATE NHAN_VIEN 
        SET tenNV=?, chucVu=?, sdt=?, email=?, username=?, role_id=? 
        WHERE maNV=?
    ");
    $stmtUpd->execute([$tenNV, $chucVu, $sdt, $email, $username, $role_id, $maNV_old]);

    // 3. GHI NHẬT KÝ KIỂM TOÁN LÕI (AUDIT LOG)
    $logChiTiet = "Cập nhật hồ sơ Nhân Sự mới nhất. Role hiện tại là ($role_id).";
    ghiAuditLog($pdo, $adminThucHien, 'SUA_NHAN_VIEN', 'NHAN_VIEN', $maNV_old, $logChiTiet);

    $_SESSION['success_msg'] = "Đã cập nhật Mệnh Căn Thành Công cho Sếp [$tenNV].";
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error_msg'] = "Lỗi lõi SQL: " . $e->getMessage();
    header("Location: sua.php?maNV=" . urlencode($maNV_old));
    exit();
}
