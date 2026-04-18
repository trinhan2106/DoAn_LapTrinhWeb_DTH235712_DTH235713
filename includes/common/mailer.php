<?php
// includes/common/mailer.php

/**
 * Đảm bảo bạn đã cài đặt PHPMailer. Nếu đang dùng Composer tại Project Root:
 * Gõ lệnh Terminal: composer require phpmailer/phpmailer
 * Và uncomment dòng Autoload bên dưới nếu dự án chưa tích hợp global autoload.
 */
// require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Hàm gửi tin rập theo cấu trúc chuẩn SMTP qua thư viện PHPMailer
 * 
 * @param string $to Địa chỉ Email người nhận
 * @param string $subject Tiêu đề email (Cần đảm bảo tiếng Việt Unicode)
 * @param string $body Nội dung HTML của email
 * @return bool Trả về true nếu cổng SMTP đẩy thành công, false nếu gặp lỗi từ MailServer
 */
function sendEmail(string $to, string $subject, string $body): bool 
{
    // Nếu bạn tải Core thư viện PHPMailer thủ công bằng source code ZIP thì Import cứng như sau:
    // require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';
    // require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
    // require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';

    // Tạo object PHP Mailer (Tham số `true` để kích hoạt ném Exception khi gặp lỗi)
    $mail = new PHPMailer(true);

    try {
        // ===========================================
        // 1. CẤU HÌNH SERVER SMTP (MOCK-UP CHO GMAIL)
        // ===========================================
        // $mail->SMTPDebug = 2;                      // Uncomment nếu cần xem quá trình bắt tay với Mail Server để sửa lỗi
        $mail->isSMTP();                              // Khai báo giao thức là SMTP
        $mail->Host       = 'smtp.gmail.com';         // Máy chủ SMTP của Google
        $mail->SMTPAuth   = true;                     // Bắt buộc xác thực tài khoản
        
        // [CẦN Backend SỬA VỊ TRÍ NÀY]: Bạn phải tự điền cấu hình thực tế vào đây
        $mail->Username   = 'nhap_email_cua_he_thong_o_day@gmail.com';
        $mail->Password   = 'mat_khau_ung_dung_app_password_nhap_o_day'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Khởi tạo mã hóa TLS
        $mail->Port       = 587;                            // Cổng khuyên dùng của hệ TLS

        // Ép định dạng Unicode Tiếng Việt tránh lỗi Font ký tự lạ
        $mail->CharSet    = 'UTF-8';

        // ===========================================
        // 2. KHAI BÁO NGƯỜI GỬI & NHẬN
        // ===========================================
        // 'no-reply' thường được ưu tiên set để nhấn mạnh việc gửi thông báo tĩnh
        $mail->setFrom('nhap_email_cua_he_thong_o_day@gmail.com', 'Hệ Thống Blue Sky Tower');
        $mail->addAddress($to); // Người nhận linh động (đăng ký mới)

        // ===========================================
        // 3. SOẠN NỘI DUNG VÀ THỰC THI GỬI
        // ===========================================
        $mail->isHTML(true); // Flag cờ HTML để hỗ trợ chèn CSS
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Backup: Nếu máy tính/ứng dụng đọc Mail của khách quá cũ tắt HTML, nó sẽ tự lòi AltBody này
        $mail->AltBody = strip_tags($body);

        // Tung lệnh kết nối TCP Socket và bắn Data sang Google
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // [QUAN TRỌNG]: Không nên dùng lệnh Die/Echo trực tiếp ở FrontApp vì sẽ làm đứt mạch Request 
        // Thay vào đó Log ẩn đi để Backend tự mở file log đọc
        error_log("LỖI GỬI EMAIL THẤT BẠI. Dữ liệu lỗi cấu hình: {$mail->ErrorInfo}");
        return false;
    }
}
