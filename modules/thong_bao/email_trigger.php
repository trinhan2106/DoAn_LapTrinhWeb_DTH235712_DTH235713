<?php
// modules/thong_bao/email_trigger.php
/**
 * API Cronjob Xử lý Tự Động Event Trigger Background:
 * 1. Gửi Notify Nhắc Nợ Hóa Đơn Mới xuất (Trong ngày).
 * 2. Cảnh báo gia hạn Hợp Đồng Gần Hết Hạn (<= 30 days) gửi nội bộ Staff.
 */
if (php_sapi_name() !== 'cli' && (!isset($_GET['secure_key']) || $_GET['secure_key'] !== 'CRON_TRIGGER_2026')) {
    // Chỉ bảo vệ chống click nhầm bằng http url nếu không có token ẩn. Không áp dụng CLI.
    die("Forbidden Task.");
}

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/mailer.php'; 
// mailer.php assumed to have guiEmail(to, subject, body) built-in logic previously created (Task 1.11).

$pdo = Database::getInstance()->getConnection();
$logBuffer = [];
$logBuffer[] = "[Cron Trigger Started]: " . date('Y-m-d H:i:s');

// ==========================================
// THREAT 1: Cảnh báo Khách hàng Nợ Phí Mới (Kỳ hiện tại)
// ==========================================
try {
    // Truy vấn Hóa Đơn Còn Nợ VÀ Sinh Ra TRONG NGÀY HÔM NAY.
    $stmtHD = $pdo->query("
        SELECT hd.maHoaDon, hd.soTienConNo, hd.thang, hd.nam, kh.tenKH, kh.email 
        FROM HOA_DON hd
        JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong
        JOIN KHACH_HANG kh ON h.maKH = kh.maKH
        WHERE hd.soTienConNo > 0 
          AND DATE(hd.ngayLap) = CURRENT_DATE()
    ");
    $listHD = $stmtHD->fetchAll(PDO::FETCH_ASSOC);

    $countSentKhach = 0;
    foreach ($listHD as $biLL) {
        if (!empty($biLL['email'])) {
            $toMail = $biLL['email'];
            $subject = "[BMS] Thông báo phát hành cấu trúc phí Kỳ {$biLL['thang']}/{$biLL['nam']}";
            $body = "Kính gửi Quý Khách {$biLL['tenKH']},<br><br>";
            $body .= "Hệ thống đã phát hành hóa đơn mã <b>{$biLL['maHoaDon']}</b> với số tiền ghi nợ hiện tại: <b>" . number_format($biLL['soTienConNo']) . " VNĐ</b><br>";
            $body .= "Vui lòng truy cập Portal Tenant để xử lý bù trừ nhanh nhất rào cản lãi.<br><br>Trân trọng.";
            
            try {
                // Wrapper guiEmail tự bẫy Try Catch nội bộ. Default true/false.
                if(function_exists('guiEmail')) {
                    guiEmail($toMail, $subject, $body);
                    $countSentKhach++;
                }
            } catch (Exception $e) {
                // Ignore soft error (Mailer fail timeout etc) để không vỡ Loop Notification
                error_log("Trigger Fail Mail KH {$biLL['maHoaDon']}: " . $e->getMessage());
            }
        }
    }
    $logBuffer[] = "- Đã xử lý Cảnh báo Nợ: Gửi " . $countSentKhach . " Emails Hóa đơn hôm nay.";

} catch (PDOException $e) {
    $logBuffer[] = "- [LỖI] TryCatch Trigger Hóa Đơn: " . $e->getMessage();
}

// ==========================================
// THREAT 2: Cảnh báo Nhân viên (Nhạy Cảm Hợp Đồng Hết Hạn <= 30 ngày)
// ==========================================
try {
    $stmtExpire = $pdo->query("
        SELECT h.soHopDong, h.ngayKetThuc, k.tenKH, nv.tenNV, nv.email 
        FROM HOP_DONG h
        JOIN KHACH_HANG k ON h.maKH = k.maKH
        JOIN NHAN_VIEN nv ON h.maNV = nv.maNV
        WHERE h.trangThai = 1 
          AND h.ngayKetThuc IS NOT NULL 
          AND DATEDIFF(h.ngayKetThuc, CURRENT_DATE()) <= 30
          AND DATEDIFF(h.ngayKetThuc, CURRENT_DATE()) >= 0
    ");
    $listExpire = $stmtExpire->fetchAll(PDO::FETCH_ASSOC);

    $countSentNV = 0;
    foreach ($listExpire as $hopDong) {
        if (!empty($hopDong['email'])) {
            $toMail = $hopDong['email'];
            $subject = "[WARNING ALERT] Hợp đồng {$hopDong['soHopDong']} sắp trễ SLA";
            $body = "Chào bạn {$hopDong['tenNV']},<br><br>";
            $body .= "Hợp đồng tham chiếu <b>{$hopDong['soHopDong']}</b> (Tenant phụ trách: {$hopDong['tenKH']}) sẽ chính thức Hết hiệu lực vào ngày <b>" . date('d/m/Y', strtotime($hopDong['ngayKetThuc'])) . "</b>.<br>";
            $body .= "Vui lòng Action nghiệp vụ: Xác nhận Thanh Lý hoặc tư vấn Gia Hạn ngay.<br>";
            
            try {
                if(function_exists('guiEmail')) {
                    guiEmail($toMail, $subject, $body);
                    $countSentNV++;
                }
            } catch (Exception $e) {
                error_log("Trigger Fail Mail NV {$hopDong['soHopDong']}: " . $e->getMessage());
            }
        }
    }
    $logBuffer[] = "- Đã xử lý Nhắc Hạn Cũ: Bắn " . $countSentNV . " Emails Ticket tới HR/Bussiness Team.";

} catch (PDOException $e) {
    $logBuffer[] = "- [LỖI] TryCatch Trigger Contract TTL: " . $e->getMessage();
}

// Write Result To STD.
echo implode("<br>", $logBuffer);
?>
