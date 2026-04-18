<?php
// modules/thung_rac/restore_submit.php
/**
 * LÕI BACKEND XỬ LÝ TRANSACTION HỒI SINH TÀI SẢN (RESTORE DATA) & GHI LOG
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

// Chống Hack Quyền Truy Cập Lõi
$role = (int)($_SESSION['role_id'] ?? 4);
if (!in_array($role, [1, 2])) {
    die("Blocked: SQL Injection / Permission Escalation Attack Detected.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Trạm GET bị niêm phong tại đây.");

// Validate Cổng CSRF 
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Lỗi Hết Hạn Form CSRF. Yêu cầu tải lại.";
    header("Location: index.php");
    exit();
}

$maPhong = trim($_POST['maPhong'] ?? '');
$maNguoiDung = $_SESSION['user_id'] ?? 'Auto-System';

if (empty($maPhong)) {
    $_SESSION['error_msg'] = "Thiếu vật thể mục tiêu cần Restore!";
    header("Location: index.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // -------------------------------------------------------------
    // KHAI LƯỚI TRANSACTION BẢO HỘ 2 LỚP: RESTORE + AUDIT LOG
    // -------------------------------------------------------------
    $pdo->beginTransaction();

    // 1. UPDATE Cứu Hộ Lõi Data (Hủy NULL deleted_at)
    $stmtRestore = $pdo->prepare("UPDATE PHONG SET deleted_at = NULL WHERE maPhong = ?");
    $stmtRestore->execute([$maPhong]);

    if ($stmtRestore->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Không chớp được Target [$maPhong] hoặc phòng vốn không ở trạng thái Xóa.";
        header("Location: index.php");
        exit();
    }

    // 2. GHI LẠI DẤU CHÂN AUDIT TRAIL ĐỂ THANH TRA DOANH NGHIỆP DÒ RA
    $chiTietLog = "Hồi sinh Phòng [$maPhong] từ cõi chết (Xóa Mềm) trở lại trạng thái khả dụng kinh doanh.";
    $stmtAudit = $pdo->prepare("
        INSERT INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, thoiGian) 
        VALUES (?, 'RESTORE_DATA', 'PHONG', ?, ?, NOW())
    ");
    $stmtAudit->execute([$maNguoiDung, $maPhong, $chiTietLog]);

    // CHỐT CỨNG Ổ MẠCH
    $pdo->commit();

    // TRẢ CỜ VỀ THÙNG RÁC XANH LÁ
    $_SESSION['success_msg'] = "Đại Hồi Sinh Thành Công Phòng: $maPhong. Lệnh đã được Audit Log niêm phong vĩnh viễn.";
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Lỗi System Rollback khi Restore Phòng: " . $e->getMessage());
    $_SESSION['error_msg'] = "Mã Lỗi Hệ Thoái: Không gỡ được File - " . $e->getMessage();
    header("Location: index.php");
    exit();
}
