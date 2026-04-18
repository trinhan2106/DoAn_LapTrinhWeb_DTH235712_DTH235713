<?php
/**
 * config/database.php
 * Thông tin kết nối Cơ sở dữ liệu MySQL và SMTP.
 * ==================================================================
 * ⚠️ FILE NÀY ĐƯỢC GITIGNORE - KHÔNG COMMIT LÊN GIT
 * Mỗi máy (developer, server) tự tạo file này dựa trên database.example.php
 * ==================================================================
 */

// Cấu hình Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'quan_ly_cao_oc');
define('DB_USER', 'root');
define('DB_PASS', '');

// Cấu hình SMTP (điền App Password của Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password_here');
define('SMTP_FROM_EMAIL', 'no-reply@bluesky.vn');
define('SMTP_FROM_NAME', 'Hệ Thống Blue Sky Tower');

