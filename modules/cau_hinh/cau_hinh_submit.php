<?php
// modules/cau_hinh/cau_hinh_submit.php
/**
 * Trình xử lý Cập nhật tham số Hệ thống (Transaction + PDO)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Kiểm duyệt Phiên & Quyền
kiemTraSession();
if ((int)($_SESSION['user_role'] ?? $_SESSION['role_id'] ?? 4) !== ROLE_ADMIN) {
    die("Access Denied: Unauthorized request.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Method Not Allowed.");
}

// Xác thực Token CSRF
$csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Mã bảo mật CSRF Token không hợp lệ.";
    header("Location: index.php");
    exit();
}

$configs = $_POST['configs'] ?? [];
if (!is_array($configs) || empty($configs)) {
    $_SESSION['error_msg'] = "Không có thông số cấu hình nào được nạp lên.";
    header("Location: index.php");
    exit();
}

$userActionId = $_SESSION['user_id'] ?? 'SYS_ADMIN';
$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // Prepare statement cập nhật thông số Key Value
    $stmtUpdate = $pdo->prepare("UPDATE CAU_HINH_HE_THONG SET key_value = ? WHERE key_name = ?");
    
    $updatedCount = 0;
    foreach ($configs as $keyName => $keyValue) {
        $keyNameStr = trim($keyName);
        $keyValueStr = trim($keyValue);
        
        $stmtUpdate->execute([$keyValueStr, $keyNameStr]);
        $updatedCount++;
    }

    // Ghi lưu vết hành động cấu hình vào Audit Log
    $logDetail = "Quản trị viên đã thực hiện Cập nhật {$updatedCount} tham số cấu hình hệ thống.";
    ghiAuditLog($pdo, $userActionId, 'CAP_NHAT_CAU_HINH_HE_THONG', 'CAU_HINH_HE_THONG', 'SYSTEM_CONFIG', $logDetail);

    $pdo->commit();

    $_SESSION['success_msg'] = "Đã lưu trữ thành công toàn bộ thông số tham số hệ thống.";
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Configuration Update PDO Error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Sự cố cập nhật CSDL: " . $e->getMessage();
    header("Location: index.php");
    exit();
}
