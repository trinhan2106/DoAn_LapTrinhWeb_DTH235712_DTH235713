<?php
/**
 * modules/khach_hang_account/xoa_account.php
 * Xử lý xóa quyền truy cập (Xóa bản ghi tài khoản login) của khách hàng
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
    $_SESSION['error_msg'] = "Mã xác thực không hợp lệ.";
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
    // Lấy thông tin username trước khi xóa để ghi log
    $stmtFind = $pdo->prepare("SELECT username, maKH FROM KHACH_HANG_ACCOUNT WHERE accountId = ?");
    $stmtFind->execute([$accountId]);
    $accInfo = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if (!$accInfo) {
        $_SESSION['error_msg'] = "Tài khoản không tồn tại hoặc đã bị xóa trước đó.";
        header("Location: index.php");
        exit();
    }

    // 4. Thực hiện Xóa (Hard delete vì đây là tài khoản login, không phải dữ liệu nghiệp vụ chính)
    $stmtDel = $pdo->prepare("DELETE FROM KHACH_HANG_ACCOUNT WHERE accountId = ?");
    $result = $stmtDel->execute([$accountId]);

    if ($result) {
        // Ghi Audit Log
        ghiAuditLog(
            $pdo, 
            $_SESSION['user_id'] ?? 'System', 
            'DELETE_ACCOUNT', 
            'KHACH_HANG_ACCOUNT', 
            $accountId, 
            "Gỡ bỏ quyền truy cập của khách hàng {$accInfo['maKH']} (Username: {$accInfo['username']})"
        );
        
        $_SESSION['success_msg'] = "Đã xóa tài khoản login của khách hàng thành công.";
    } else {
        $_SESSION['error_msg'] = "Lỗi khi thực hiện xóa bản ghi từ cơ sở dữ liệu.";
    }

} catch (PDOException $e) {
    error_log("Lỗi Xóa Account: " . $e->getMessage());
    $_SESSION['error_msg'] = "Sự cố hệ thống: Không thể gỡ bỏ tài khoản.";
}

header("Location: index.php");
exit();
