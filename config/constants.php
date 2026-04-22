<?php
/**
 * config/constants.php
 * Các hằng số CHUNG cho toàn hệ thống (không nhạy cảm, có thể commit Git).
 * Thông tin kết nối Database được tách ra config/database.php (đã gitignore).
 */

// Đường dẫn gốc của ứng dụng (Lưu ý dấu slash / ở cuối)
// Có thể tùy chỉnh nếu source code không đặt trực tiếp đúng như cấu trúc localhost
define('BASE_URL', 'http://localhost/DoAn_LapTrinhWeb_DTH235712_DTH235713/');

// define('BASE_URL', 'http://192.168.2.117/DoAn_LapTrinhWeb_DTH235712_DTH235713/');

// Tên hiển thị hệ thống (dùng trong email, header...)
define('APP_NAME', 'THE SAPPHIRE');

// Nạp cấu hình DB từ file riêng (file này được gitignore, mỗi máy tự cấu hình)
require_once __DIR__ . '/database.php';
