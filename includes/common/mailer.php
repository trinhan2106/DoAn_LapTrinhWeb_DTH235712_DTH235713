<?php
// includes/common/mailer.php
/**
 * Wrapper PHPMailer để gửi email qua SMTP.
 * SMTP credentials được đọc từ config/database.php (đã gitignore).
 *
 * Cài đặt PHPMailer (1 trong 2 cách):
 *   (a) Composer:  composer require phpmailer/phpmailer
 *       -> uncomment dòng require vendor/autoload bên dưới
 *   (b) Tải ZIP source, đặt vào includes/common/libs/PHPMailer/
 *       -> uncomment 3 dòng require_once libs bên dưới
 */
require_once __DIR__ . '/../../config/constants.php'; // tự động include database.php

// (a) Composer autoload
// require_once __DIR__ . '/../../vendor/autoload.php';

// (b) Source ZIP thủ công
// require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
// require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
// require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        error_log("[MAILER FAIL] to={$to}, subject={$subject}: " . $mail->ErrorInfo);
        return false;
    }
}

