<?php
/**
 * modules/phong/phong_xoa.php
 * Xử lý xóa mềm Phòng với kiểm tra ràng buộc Hợp đồng
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. NHẬN DỮ LIỆU
$id = $_GET['id'] ?? '';
if (empty($id)) {
    $_SESSION['error_msg'] = "Mã phòng không hợp lệ.";
    header("Location: phong_hienthi.php");
    exit();
}

$db = Database::getInstance()->getConnection();

try {
    // 3. KIỂM TRA RÀNG BUỘC HỢP ĐỒNG (Chống xóa phòng đang được thuê hiệu lực)
    $sqlCheck = "
        SELECT COUNT(*) 
        FROM CHI_TIET_HOP_DONG ct
        JOIN HOP_DONG h ON ct.soHopDong = h.soHopDong
        WHERE ct.maPhong = ? AND h.trangThai = 1 AND h.deleted_at IS NULL
    ";
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->execute([$id]);
    $activeContracts = $stmtCheck->fetchColumn();

    if ($activeContracts > 0) {
        $_SESSION['error_msg'] = "Không thể xóa phòng này vì đang tồn tại {$activeContracts} hợp đồng thuê có hiệu lực liên quan.";
        header("Location: phong_hienthi.php");
        exit();
    }

    // 4. THỰC HIỆN XÓA MỀM
    $db->beginTransaction();

    $stmtUpdate = $db->prepare("UPDATE PHONG SET deleted_at = NOW() WHERE maPhong = ? AND deleted_at IS NULL");
    $stmtUpdate->execute([$id]);

    if ($stmtUpdate->rowCount() > 0) {
        // Ghi Audit Log
        ghiAuditLog(
            $db,
            $_SESSION['user_id'],
            'DELETE',
            'PHONG',
            $id,
            "Xóa mềm phòng [{$id}]",
            layIP()
        );

        $db->commit();
        $_SESSION['success_msg'] = "Đã xóa phòng thành công!";
    } else {
        $db->rollBack();
        $_SESSION['error_msg'] = "Không tìm thấy phòng hoặc phòng đã bị xóa trước đó.";
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Lỗi xóa phòng: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi hệ thống: " . $e->getMessage();
}

// 5. ĐIỀU HƯỚNG
header("Location: phong_hienthi.php");
exit();
