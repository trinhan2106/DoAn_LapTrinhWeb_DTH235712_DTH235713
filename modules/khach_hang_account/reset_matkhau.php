<?php
/**
 * modules/khach_hang_account/reset_matkhau.php
 * Xử lý đặt lại mật khẩu tài khoản khách hàng về mặc định
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// 1. Kiểm tra Quyền hạn
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Kiểm tra Phương thức & CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Mã xác thực không hợp lệ hoặc đã hết hạn.";
    header("Location: index.php");
    exit();
}

// 3. Nhận dữ liệu
$accountId = trim($_POST['accountId'] ?? '');

if (empty($accountId)) {
    $_SESSION['error_msg'] = "Thông tin tài khoản không hợp lệ.";
    header("Location: index.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // 4. Thực hiện Reset (Mật khẩu mặc định: 123456)
    $new_hash = password_hash('123456', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        UPDATE KHACH_HANG_ACCOUNT 
        SET password_hash = ?, phai_doi_matkhau = 1 
        WHERE accountId = ?
    ");
    $result = $stmt->execute([$new_hash, $accountId]);

    if ($result && $stmt->rowCount() > 0) {
        // Ghi Audit Log
        ghiAuditLog($pdo, $_SESSION['user_id'] ?? 'System', 'RESET_PASSWORD', 'KHACH_HANG_ACCOUNT', $accountId, "Đặt lại mật khẩu KH về 123456");
        
        $_SESSION['flash_msg'] = "Đã đặt lại mật khẩu cho tài khoản {$accountId} thành công. Mật khẩu mặc định là: 123456";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_msg'] = "Không tìm thấy tài khoản để cập nhật hoặc lỗi hệ thống.";
        $_SESSION['flash_type'] = "danger";
    }

} catch (PDOException $e) {
    error_log("Lỗi Reset Password: " . $e->getMessage());
    $_SESSION['flash_msg'] = "Sự cố cơ sở dữ liệu: Không thể reset mật khẩu.";
    $_SESSION['flash_type'] = "danger";
}

header("Location: index.php");
exit();
