<?php
/**
 * modules/thanh_toan/tranh_chap_xuly_submit.php
 * Xử lý cập nhật trạng thái tranh chấp hóa đơn và ghi Audit Log
 */
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// 1. Bảo mật cấp Route (P0)
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tranh_chap_hienthi.php");
    exit();
}

// 2. Chống CSRF
$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    $_SESSION['error_msg'] = "Lỗi bảo mật: Token không hợp lệ.";
    header("Location: tranh_chap_hienthi.php");
    exit();
}

// 3. Lấy dữ liệu
$id = $_POST['id'] ?? '';
$trangThai = (int)($_POST['trangThai'] ?? 0);
$ghiChu = trim($_POST['ghiChu'] ?? '');

if (empty($id) || empty($ghiChu)) {
    $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ trạng thái và ghi chú xử lý.";
    header("Location: tranh_chap_hienthi.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // 4. Chống IDOR: Xác minh ticket tồn tại
    $stmtCheck = $pdo->prepare("SELECT id FROM TRANH_CHAP_HOA_DON WHERE id = ?");
    $stmtCheck->execute([$id]);
    if (!$stmtCheck->fetch()) {
        throw new Exception("Ticket tranh chấp không tồn tại trong hệ thống.");
    }

    // 5. Nghiệp vụ: Transaction
    $pdo->beginTransaction();

    // UPDATE trạng thái (Vì database không có cột phản hồi, không update cột phanHoi)
    $stmtUpdate = $pdo->prepare("UPDATE TRANH_CHAP_HOA_DON SET trangThai = ? WHERE id = ?");
    $stmtUpdate->execute([$trangThai, $id]);

    // Ghi nhật ký hướng giải quyết vào bảng AUDIT_LOG (Yêu cầu đặc thù của user)
    // action='RESOLVE_DISPUTE', table='TRANH_CHAP_HOA_DON', recordId=id, chiTiet=ghiChu, ip=layIP()
    ghiAuditLog(
        $pdo, 
        $_SESSION['user_id'], 
        'RESOLVE_DISPUTE', 
        'TRANH_CHAP_HOA_DON', 
        $id, 
        $ghiChu,
        layIP()
    );

    $pdo->commit();

    // 6. Hoàn tất & Bảo mật nâng cao
    rotateCSRFToken();
    $_SESSION['success_msg'] = "Đã cập nhật trạng thái và ghi nhận hướng giải quyết vào hệ thống.";
    header("Location: tranh_chap_hienthi.php");
    exit();

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[DISPUTE_ERROR] " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi xử lý: " . $e->getMessage();
    header("Location: tranh_chap_hienthi.php");
    exit();
}
