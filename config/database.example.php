<?php
/**
 * config/database.example.php
 * File TEMPLATE cấu hình Database & SMTP - COPY thành database.php và sửa giá trị thật.
 * File database.php (file thật) đã được gitignore để tránh lộ credentials.
 *
 * Cách dùng:
 *   1. Copy file này: cp config/database.example.php config/database.php
 *   2. Sửa DB_USER, DB_PASS theo môi trường local (XAMPP/Laragon) của bạn.
 *   3. Sửa SMTP_* nếu cần gửi email thật (lấy App Password của Gmail).
 */

// Cấu hình Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'quan_ly_cao_oc');
define('DB_USER', 'root');
define('DB_PASS', '');

// Cấu hình SMTP để gửi email (PHPMailer dùng)
// Với Gmail: vào Google Account -> Security -> App Passwords để tạo
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password_here');
define('SMTP_FROM_EMAIL', 'no-reply@bluesky.vn');
define('SMTP_FROM_NAME', 'Hệ Thống THE SAPPHIRE');

