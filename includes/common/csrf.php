<?php

// Đảm bảo session không bị start lại nếu đã gọi trước đó
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Tạo một CSRF token hoàn toàn ngẫu nhiên và lưu nó vào session.
 * Ở các form (POST), gán token này vào ẩn: <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
 * 
 * @return string
 */
function generateCSRFToken(): string
{
    // Nếu token đã tồn tại, dùng lại nguyên token cho tới khi hết hạn session. 
    if (empty($_SESSION['csrf_token'])) {
        try {
            // Sinh mã hóa theo chuẩn ngẫu nhiên an toàn (Mặc định trong PHP 7/8+)
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        } catch (Exception $e) {
            // Trường hợp random_bytes bị lỗi (Rất hiếm khi xảy ra)
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * So sánh token mà form gửi lên (POST/GET) với token đang được lưu trong session/server.
 * Dùng `hash_equals` để ngăn chặn hacker dùng kỹ thuật tấn công Time-Based/Timing Attack.
 * 
 * @param string $token
 * @return bool True nếu hợp lệ, False nếu không hợp lệ
 */
function validateCSRFToken(string $token): bool
{
    // Phải kiểm tra isset() tránh PHP throw Warning và hash_equals giúp so sánh chuỗi bảo mật an toàn thời gian.
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Xoa token cu va tao token moi. Goi sau moi lan submit thanh cong
 * de tranh rui ro Token Reuse (A.2.1).
 *
 * @return string Token moi da duoc luu vao session
 */
function rotateCSRFToken(): string
{
    unset($_SESSION['csrf_token']);
    return generateCSRFToken();
}
