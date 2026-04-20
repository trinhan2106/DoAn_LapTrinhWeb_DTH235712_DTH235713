<?php
/**
 * includes/common/mailer.php
 * Wrapper PHPMailer để gửi email qua SMTP (Hỗ trợ UTF-8 Tiếng Việt).
 */

// 1. Khai báo các Namespace cần dùng
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. Nạp thư viện PHPMailer (Hãy mở comment 1 trong 2 trường hợp dưới đây tùy thực tế)
// -------------------------------------------------------------------------
// TRƯỜNG HỢP 1: Nạp thủ công (Nếu bạn tải source PHPMailer về libs/)
// Thư mục PHPMailer/src/ phải nằm tại: includes/common/libs/PHPMailer/src/
// -------------------------------------------------------------------------
require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';

// -------------------------------------------------------------------------
// TRƯỜNG HỢP 2: Sử dụng Composer (Nếu project có thư mục vendor/autoload.php)
// -------------------------------------------------------------------------
// require_once __DIR__ . '/../../vendor/autoload.php';

// 3. Nạp cấu hình hệ thống (Database, SMTP_HOST, SMTP_USER,...)
require_once __DIR__ . '/../../config/constants.php';

/**
 * Gửi email qua SMTP bằng PHPMailer.
 *
 * @param string $to      Địa chỉ email người nhận
 * @param string $subject Tiêu đề email (hỗ trợ Unicode tiếng Việt)
 * @param string $body    Nội dung HTML của email
 * @return bool True nếu gửi thành công, false nếu gặp lỗi từ MailServer
 */
function sendEmail(string $to, string $subject, string $body): bool
{
    // [KHẮC PHỤC LỖI FATAL ERROR] Kiểm tra xem Class có tồn tại không trước khi gọi
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("[CRITICAL MAILER ERROR] Class PHPMailer NOT FOUND. Hãy đảm bảo bạn đã mở comment require_once ở đầu file mailer.php và đã tải thư viện về đúng thư mục.");
        // Trả về false thay vì để crash hệ thống (Fatal Error)
        return false; 
    }

    $mail = new PHPMailer(true);

    try {
        // ===========================================
        // 1. CẤU HÌNH SMTP SERVER (đọc từ config/database.php)
        // ===========================================
        // $mail->SMTPDebug = 2; // bật khi cần debug quá trình bắt tay SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // ===========================================
        // 2. NGƯỜI GỬI & NGƯỜI NHẬN
        // ===========================================
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // ===========================================
        // 3. NỘI DUNG & GỬI
        // ===========================================
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // fallback cho mail client không hỗ trợ HTML

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Không echo/die trực tiếp - chỉ log để không làm gãy luồng Request của caller
        error_log("[MAILER FAIL] to={$to}, subject={$subject}: " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
        return false;
    }
}

