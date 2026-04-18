<?php
// includes/common/login_throttle.php

/**
 * Ghi nhận một lần đăng nhập thất bại vào CSDL.
 * Dữ liệu log này để tính toán giam IP nhằm ngăn chặn Brute-Force Password.
 * 
 * @param PDO $pdo Data base connection
 * @param string $ip Địa chỉ IP trình duyệt
 * @param string $username Tên đăng nhập gửi lên
 */
function ghiNhanDangNhapSai(PDO $pdo, string $ip, string $username): void {
    // status = 0 tượng trưng cho Log Failed
    $stmt = $pdo->prepare("INSERT INTO LOGIN_ATTEMPT (username, ip_address, status) VALUES (:user, :ip, 0)");
    $stmt->execute([
        ':user' => $username,
        ':ip' => $ip
    ]);
}

/**
 * Quét theo Rule hệ thống: Nếu sai >= 5 lần trong vòng 15 phút là Khóa (Ban).
 * Đo đạc lấy ra thời gian Phút Giam còn lại.
 * 
 * @param PDO $pdo Data base connection
 * @param string $ip Địa chỉ IP trình duyệt
 * @param string $username Tên đăng nhập
 * @return int Trả về 0 nếu Tốt. Trả về Int > 0 = Số PHÚT còn bị cấm.
 */
function kiemTraKhoaTaiKhoan(PDO $pdo, string $ip, string $username): int {
    // Quét Log của 15 phút vừa qua
    $limitTime = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
        FROM LOGIN_ATTEMPT 
        WHERE ip_address = :ip AND username = :user AND status = 0 AND attempt_time >= :l_time
    ");
    $stmt->execute([
        ':ip' => $ip,
        ':user' => $username,
        ':l_time' => $limitTime
    ]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $attempts = (int)$row['attempts'];
    
    if ($attempts >= 5) {
        $lastAttemptTime = strtotime($row['last_attempt']); // Lần thất bại cuối cùng
        $unlockTime = $lastAttemptTime + (15 * 60);         // + 15 Phút penalty
        
        $minutesLeft = ceil(($unlockTime - time()) / 60);
        return $minutesLeft > 0 ? (int)$minutesLeft : 0;
    }
    
    return 0; // Chưa đạt ngưỡng Threshold 5 lần
}

/**
 * Xóa án phạt ngay lập tức hoặc ghi nhận lại trạng thái khi User nhập đúng Password sau nhiều lần bị cảnh cáo.
 * 
 * @param PDO $pdo Data base connection
 * @param string $ip Địa chỉ IP trình duyệt
 * @param string $username Tên đăng nhập
 */
function xoaLichSuDangNhapSai(PDO $pdo, string $ip, string $username): void {
    // Xóa record các lần Failed liên đới để Reset sạch Bộ Đếm
    $stmt = $pdo->prepare("DELETE FROM LOGIN_ATTEMPT WHERE ip_address = :ip AND username = :user AND status = 0");
    $stmt->execute([
        ':ip' => $ip,
        ':user' => $username
    ]);
}
