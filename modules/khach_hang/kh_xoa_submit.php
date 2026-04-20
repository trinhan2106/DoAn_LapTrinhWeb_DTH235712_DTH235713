<?php
/**
 * modules/khach_hang/kh_xoa_submit.php
 * Xử lý Xóa Mềm Khách hàng - Áp dụng CSRF, Anti-IDOR, Ràng buộc Hợp Đồng & Audit Log
 */

require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([1, 2]); // Admin & Quản lý Nhà

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_msg'] = "Phương thức không hợp lệ!";
    header("Location: kh_hienthi.php");
    exit();
}

$maKH = $_POST['maKH'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

// 1. Chống CSRF
if (!validateCSRFToken($csrfToken)) {
    $_SESSION['error_msg'] = "Lỗi bảo mật (CSRF). Vui lòng thử lại!";
    header("Location: kh_hienthi.php");
    exit();
}

if (empty($maKH)) {
    $_SESSION['error_msg'] = "Thiếu thông tin mã khách hàng cần xóa!";
    header("Location: kh_hienthi.php");
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    // 2. Chống IDOR: Xác minh maKH tồn tại và chưa bị soft delete
    $stmtCheck = $pdo->prepare("SELECT maKH FROM KHACH_HANG WHERE maKH = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$maKH]);
    if (!$stmtCheck->fetch()) {
        $_SESSION['error_msg'] = "Khách hàng không tồn tại hoặc đã bị xóa trước đó!";
        header("Location: kh_hienthi.php");
        exit();
    }

    // 3. Nghiệp vụ: Kiểm tra bảng HOP_DONG. KHÔNG cho xóa nếu có hợp đồng 'Hiệu lực' (trangThai = 1)
    $stmtHD = $pdo->prepare("SELECT COUNT(*) FROM HOP_DONG WHERE maKH = ? AND trangThai = 1 AND deleted_at IS NULL");
    $stmtHD->execute([$maKH]);
    if ($stmtHD->fetchColumn() > 0) {
        $_SESSION['error_msg'] = "Không thể xóa Khách hàng [{$maKH}] vì đang còn Hợp đồng Đang hiệu lực!";
        header("Location: kh_hienthi.php");
        exit();
    }

    // 4. Thực hiện xóa mềm
    $stmtDelete = $pdo->prepare("UPDATE KHACH_HANG SET deleted_at = NOW() WHERE maKH = ?");
    $result = $stmtDelete->execute([$maKH]);

    if ($result) {
        // 5. Ghi Audit Log
        ghiAuditLog($pdo, $_SESSION['user_id'] ?? null, 'SOFT_DELETE', 'KHACH_HANG', $maKH);
        
        // 6. Rotate Token sau khi submit thành công
        rotateCSRFToken();

        $_SESSION['success_msg'] = "Đã xóa khách hàng [{$maKH}] thành công.";
    } else {
        $_SESSION['error_msg'] = "Xóa thất bại. Vui lòng kiểm tra lại hệ thống!";
    }

} catch (PDOException $e) {
    $_SESSION['error_msg'] = "Lỗi CSDL khi xóa khách hàng!";
    error_log("Soft Delete Khach Hang Error: " . $e->getMessage());
}

header("Location: kh_hienthi.php");
exit();
