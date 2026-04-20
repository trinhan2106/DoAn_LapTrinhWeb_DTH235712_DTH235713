<?php
/**
 * modules/yeu_cau_thue/yc_xuly_submit.php
 * Xử lý Duyệt hoặc Từ chối yêu cầu thuê phòng.
 */

// 1. Khởi tạo & Bảo mật
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Kiểm tra quyền: Admin hoặc Quản lý Nhà (P0)
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Nếu không phải POST thì không xử lý
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: yc_hienthi.php");
    exit();
}

// Chống CSRF: Validate Token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    $_SESSION['error_msg'] = "Lỗi bảo mật: CSRF Token không hợp lệ.";
    header("Location: yc_hienthi.php");
    exit();
}

$maYeuCau = $_POST['maYeuCau'] ?? '';
$action = $_POST['action'] ?? '';

// Kiểm tra dữ liệu đầu vào
if (empty($maYeuCau) || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['error_msg'] = "Dữ liệu không hợp lệ.";
    header("Location: yc_hienthi.php");
    exit();
}

// Kết nối DB Singleton
$pdo = Database::getInstance()->getConnection();

try {
    // 1. Chống IDOR: Verify maYeuCau tồn tại và chưa bị xóa mềm (P0)
    $stmtCheck = $pdo->prepare("SELECT maYeuCau, trangThai FROM YEU_CAU_THUE WHERE maYeuCau = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$maYeuCau]);
    $yeuCau = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$yeuCau) {
        $_SESSION['error_msg'] = "Yêu cầu không tồn tại hoặc đã bị xóa.";
        header("Location: yc_hienthi.php");
        exit();
    }

    if ($yeuCau['trangThai'] != 0) {
        $_SESSION['error_msg'] = "Yêu cầu này đã được xử lý trước đó.";
        header("Location: yc_hienthi.php");
        exit();
    }

    // 2. Logic xử lý: Cập nhật trạng thái
    $newStatus = ($action === 'approve') ? 1 : 2;
    $statusLabel = ($action === 'approve') ? "Chấp nhận/Đã liên hệ" : "Từ chối/Hủy";
    
    $stmtUpdate = $pdo->prepare("UPDATE YEU_CAU_THUE SET trangThai = ? WHERE maYeuCau = ?");
    $stmtUpdate->execute([$newStatus, $maYeuCau]);

    // 3. Ghi Audit Log (P0 - Nghiệp vụ)
    $auditAction = ($action === 'approve') ? 'APPROVE_RENTAL_REQUEST' : 'REJECT_RENTAL_REQUEST';
    ghiAuditLog(
        $pdo, 
        $_SESSION['user_id'], 
        $auditAction, 
        'YEU_CAU_THUE', 
        $maYeuCau, 
        "Trạng thái cập nhật thành: $statusLabel. Thực hiện bởi: " . $_SESSION['ten_user'],
        layIP()
    );

    // 4. Chống CSRF: Rotate Token khi thành công (P0)
    rotateCSRFToken();

    $_SESSION['success_msg'] = "Thành công: Đã " . ($action === 'approve' ? "duyệt" : "từ chối") . " yêu cầu của khách hàng.";
    header("Location: yc_hienthi.php");
    exit();

} catch (PDOException $e) {
    error_log("Lỗi xử lý yêu cầu thuê: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi hệ thống: Không thể xử lý yêu cầu lúc này.";
    header("Location: yc_hienthi.php");
    exit();
}
