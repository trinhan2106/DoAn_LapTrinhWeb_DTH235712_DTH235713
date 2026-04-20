<?php
/**
 * Cấu hình các tham số hệ thống
 */

// Thiết lập múi giờ mặc định cho ứng dụng
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Số dòng dữ liệu hiển thị trên 1 trang cho chức năng phân trang
define('PAGINATION_LIMIT', 10);

// Thời gian timeout session (Ngắt phiên nếu người dùng không tương tác)
// Tính bằng giây (Ví dụ: 3600 = 1 giờ, 1800 = 30 phút)
define('SESSION_TIMEOUT', 3600);

// Khóa bí mật JWT (Dùng cho QR Code bảo mật - Task 9.2)
// Trong thực tế nên dùng biến môi trường hoặc file .env
define('JWT_SECRET_KEY', 'CaoOc_Sapphire_JWT_Secure_Key_2026_@#!');
