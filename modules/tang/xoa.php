<?php
/**
 * modules/tang/xoa.php
 * Xử lý xóa mềm (Soft Delete) Tầng
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
    $_SESSION['error_msg'] = "Mã tầng không hợp lệ.";
    header("Location: index.php");
    exit();
}

$db = Database::getInstance()->getConnection();

try {
    // Kiểm tra xem tầng có đang chứa phòng nào không (Ràng buộc nghiệp vụ)
    // Nếu tầng vẫn còn phòng (chưa xóa), có thể chặn xóa tầng hoặc cảnh báo.
    // Ở đây ta thực hiện xóa mềm TẦNG, logic hiển thị PHÒNG sẽ cần check deleted_at của TẦNG nếu cần.
    
    // Thực hiện xóa mềm: Cập nhật deleted_at = NOW()
    $sql = "UPDATE TANG SET deleted_at = NOW() WHERE maTang = ? AND deleted_at IS NULL";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        // Ghi Audit Log thành công
        ghiAuditLog(
            $db,
            $_SESSION['user_id'],
            'DELETE',
            'TANG',
            $id,
            "Xóa mềm tầng [{$id}]",
            layIP()
        );

        $_SESSION['success_msg'] = "Đã xóa tầng thành công!";
    } else {
        $_SESSION['error_msg'] = "Không tìm thấy tầng yêu cầu hoặc tầng đã bị xóa trước đó.";
    }

} catch (PDOException $e) {
    error_log("Lỗi xóa tầng: " . $e->getMessage());
    $_SESSION['error_msg'] = "Có lỗi xảy ra trong quá trình xóa dữ liệu.";
}

// Điều hướng về trang danh sách
header("Location: index.php");
exit();
