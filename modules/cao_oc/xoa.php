<?php
/**
 * modules/cao_oc/xoa.php
 * Xử lý xóa mềm cao ốc
 * ==================================================================
 */

require_once '../../includes/common/auth.php';
require_once '../../includes/common/db.php';
require_once '../../includes/common/functions.php';

// 1. Bảo mật: Kiểm tra session và quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Lấy ID từ GET
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: index.php");
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // 3. Kiểm tra sự tồn tại (Tránh xóa bậy bạ)
    $stmtCheck = $pdo->prepare("SELECT tenCaoOc FROM CAO_OC WHERE maCaoOc = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$id]);
    $caoOc = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$caoOc) {
        header("Location: index.php?status=error&msg=" . urlencode("Không tìm thấy cao ốc hoặc dữ liệu đã bị xóa trước đó."));
        exit();
    }

    // 4. Thực hiện xóa mềm (Soft Delete)
    // UPDATE cột deleted_at bằng thời gian hiện tại
    $sql = "UPDATE CAO_OC SET deleted_at = NOW() WHERE maCaoOc = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    // 5. Ghi nhật ký hệ thống (Audit Log)
    ghiAuditLog(
        $pdo, 
        $_SESSION['user_id'], 
        'DELETE', 
        'CAO_OC', 
        $id, 
        "Xóa mềm cao ốc: " . $caoOc['tenCaoOc'], 
        layIP()
    );

    // 6. Chuyển hướng về trang danh sách
    header("Location: index.php?status=success&msg=" . urlencode("Đã xóa cao ốc thành công (Vào thùng rác)!"));
    exit();

} catch (PDOException $e) {
    error_log("Lỗi DELETE CAO_OC: " . $e->getMessage());
    header("Location: index.php?status=error&msg=" . urlencode("Có lỗi kỹ thuật xảy ra. Vui lòng thử lại sau."));
    exit();
}
