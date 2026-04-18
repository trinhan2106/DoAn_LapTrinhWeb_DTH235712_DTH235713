<?php
/**
 * Chức năng xử lý Đăng Xuất (Log-out)
 */

// Bắt đầu hoặc kết nối lại với phiên làm việc hiện tại
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa sạch toàn bộ mảng dữ liệu Session của user
session_unset();

// Hủy hoàn toàn định danh ID của session khỏi bộ nhớ server
session_destroy();

// An toàn: Trỏ user về form đăng nhập và kết thúc luồng
header("Location: dangnhap.php");
exit();
