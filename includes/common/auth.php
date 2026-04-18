<?php
// Require các file cấu hình và phân quyền cần thiết
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/roles.php';

// Kiểm tra session một cách an toàn nhất - nếu chưa start mới start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa.
 * Đồng thời áp dụng logic kiểm tra timeout phiên xử lý tự động ngắt kết nối.
 */
function kiemTraSession(): void
{
    // 'user_id' phải được set sau bước verify password thành công lúc Login
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "dangnhap.php");
        exit(); // Bắt buộc dùng exit/die sau Location
    }

    // Cơ chế quản lý Timeout của Session
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Hết hạn sử dụng do vượt quá thời gian không tương tác (SESSION_TIMEOUT định nghĩa ở app.php)
        session_unset();
        session_destroy();
        
        // Điều hướng tới trang đăng nhập kèm cờ cảnh báo
        header("Location: " . BASE_URL . "dangnhap.php?error=timeout");
        exit();
    }

    // Cập nhật lại thời điểm tương tác thao tác cuối cùng của người dùng
    $_SESSION['last_activity'] = time();
}

/**
 * Kiểm tra quyền hạn của người dùng vào Role để xem có được phép đi tiếp (Authorization).
 * Buộc phải dùng kèm khâu đăng nhập tài khoản.
 * 
 * @param array $allowedRoles mảng chứa các quyền ví dụ: [ROLE_ADMIN, ROLE_QUAN_LY_NHA]
 */
function kiemTraRole(array $allowedRoles): void
{
    // Đảm bảo người dùng phải đã đăng nhập rồi mới được check quyền
    kiemTraSession();

    // Giả định role đang được lưu bằng key 'user_role' (Set kiểu int từ lúc thực hiện login)
    // Lưu ý in_array dùng tham số true thứ 3 (Kiểm tra nghiêm ngặt Type) nên session_role phải ép kiểu int khi lưu
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles, true)) {
        // Có đăng nhập nhưng user_role đói chiếu không nằm trong $allowedRoles
        http_response_code(403);
        die("403 Forbidden: Tài khoản của bạn không có quyền truy cập khu vực chức năng này.");
    }
}
