<?php
// includes/common/login_throttle.php
/**
 * THUẬT TOÁN LOGIN THROTTLE - CHỐNG BRUTE FORCE
 */

/**
 * Ghi lại lịch sử đăng nhập vào bảng LOGIN_ATTEMPT
 * 
 * @param PDO $pdo 
 * @param string $username Tên đăng nhập thử nghiệm
 * @param string $ip Địa chỉ IP của client
 * @param int $status 1 nếu thành công, 0 nếu thất bại
 */
function ghiLogDangNhap($pdo, $username, $ip, $status) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO LOGIN_ATTEMPT (username, ip_address, status, attempt_time) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $ip, $status]);
    } catch (PDOException $e) {
        // Ghi log tệp tin hệ thống để không làm gãy luồng người dùng
        error_log("Lỗi Database khi ghi Lịch sử Đăng nhập: " . $e->getMessage());
    }
}

/**
 * Kiểm tra xem IP hoặc Username này có đang bị Lockout không
 * Điều kiện: Có >= 5 lần đăng nhập thất bại (status = 0) trong 15 phút vừa qua.
 * 
 * @param PDO $pdo 
 * @param string $ip Địa chỉ IP của client
 * @param string $username Tên đăng nhập
 * @return array ['locked' => boolean, 'remaining' => int phút chờ]
 */
function kiemTraLockout($pdo, $ip, $username) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_count, MAX(attempt_time) as last_attempt 
            FROM LOGIN_ATTEMPT 
            WHERE (username = ? OR ip_address = ?) 
            AND status = 0 
            AND attempt_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$username, $ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['failed_count'] >= 5) {
            // Tính số phút còn lại
            $lastAttemptTime = strtotime($row['last_attempt']);
            $unlockTime = $lastAttemptTime + (15 * 60); // Khóa 15 phút
            $remainingSeconds = $unlockTime - time();
            
            if ($remainingSeconds > 0) {
                // Làm tròn số phút lên hiển thị giao diện báo khách
                $remainingMinutes = ceil($remainingSeconds / 60);
                return ['locked' => true, 'remaining' => (int)$remainingMinutes];
            }
        }
        
        return ['locked' => false];
        
    } catch (PDOException $e) {
        error_log("Lỗi kiểm tra thuật toán Lockout: " . $e->getMessage());
        return ['locked' => false]; // Fallback pass nếu Database nghẽn cổ chai
    }
}

/**
 * Xoa cac ban ghi dang nhap that bai sau khi dang nhap thanh cong (A.2.16).
 * Tranh tinh trang user bi lockout oan do cac lan sai truoc do van con dem.
 *
 * @param PDO    $pdo
 * @param string $username Ten dang nhap
 * @param string $ip       Dia chi IP
 */
function resetLoginAttempts(PDO $pdo, string $username, string $ip): void
{
    try {
        $stmt = $pdo->prepare("
            DELETE FROM LOGIN_ATTEMPT 
            WHERE (username = ? OR ip_address = ?) 
              AND status = 0 
              AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$username, $ip]);
    } catch (PDOException $e) {
        error_log("[login_throttle] Reset login attempts error: " . $e->getMessage());
    }
}
