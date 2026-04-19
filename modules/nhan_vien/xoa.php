<?php
// modules/nhan_vien/xoa.php
/**
 * TRẠM KIỂM SOÁT TỬ THẦN (SOFT DELETE ENGINE) & RÀNG BUỘC KẾ TOÁN
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "CSRF Alert. Phiên lỗi.";
    header("Location: index.php"); exit();
}

$maNV_Huy = trim($_POST['maNV'] ?? '');
$adminLog = $_SESSION['user_id'] ?? '';

if (empty($maNV_Huy)) {
    $_SESSION['error_msg'] = "Trượt tiêu điểm, Không xác định được Nhân viên.";
    header("Location: index.php"); exit();
}
// Chống tự sát
if ($maNV_Huy === $adminLog) {
    $_SESSION['error_msg'] = "Lỗi: Không Thể Tự Sa Thải Chính Mình!";
    header("Location: index.php"); exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // ---------------------------------------------------------------------------------
    // LỚP CHẶN SỐ 1: RÀNG BUỘC NGHIỆP VỤ BẮT BUỘC - KIỂM SÁT NỢ HOA_DON DO NHÂN VIÊN LẬP
    // Theo cấu trúc: HOA_DON nối HOP_DONG nối NHAN_VIEN. 
    // Chúng ta phải xem có tờ Hóa đơn nào SoTienConNo > 0 thuộc các HĐ do NV này nắm không.
    // ---------------------------------------------------------------------------------
    $stmtTienKiem = $pdo->prepare("
        SELECT COUNT(hd.maHoaDon) as debtCount 
        FROM HOA_DON hd
        JOIN HOP_DONG hp ON hd.soHopDong = hp.soHopDong
        WHERE hp.maNV = ? AND hd.soTienConNo > 0 AND hd.trangThai != 3
    ");
    // (Bỏ qua hóa đơn bị Hủy trạng thái 3, tính cả bill lẻ tẻ đang nợ)
    $stmtTienKiem->execute([$maNV_Huy]);
    $debtResult = $stmtTienKiem->fetch(PDO::FETCH_ASSOC);

    if ($debtResult && $debtResult['debtCount'] > 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Lệnh Thất Bại: CẤM XÓA TUYỆT ĐỐI! Nhân sự này đang đứng mũi chịu sào quản lý <strong>{$debtResult['debtCount']} Hợp Đồng/Hóa Đơn Còn Khoản Nợ Chưa Thu</strong>. Hãy thu hồi dứt điểm Công Nợ trước khi thanh lý Hệ Sinh Thái của Nhân Viên này!";
        header("Location: index.php");
        exit();
    }

    // ---------------------------------------------------------------------------------
    // LỚP THI HÀNH SỐ 2: KHAI ĐAO SOFT DELETE TÀI KHOẢN (UPDATE LÕI DELETED_AT)
    // ---------------------------------------------------------------------------------
    // Để giữ toàn vẹn 24 Bảng của Hãng (Schema không có cột dangLamViec), 
    // Chúng ta triển khai deleted_at = NOW() (Tương Đương Đã Nghỉ).
    $stmtDo = $pdo->prepare("UPDATE NHAN_VIEN SET deleted_at = NOW() WHERE maNV = ? AND deleted_at IS NULL");
    $stmtDo->execute([$maNV_Huy]);

    if ($stmtDo->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Sai bét! NV không tồn tại hoặc đã bị Sa thải trước đó.";
        header("Location: index.php");
        exit();
    }

    // ---------------------------------------------------------------------------------
    // LỚP LOG SỐ 3: BỒI MỰC KIỂM TOÁN VÀO CĂN BẢN AUDIT LOG CHỨNG MINH THỰC THI
    // ---------------------------------------------------------------------------------
    $lyDoChinh = "Ra quyết định Tước quyền và Khóa Tài Khoản (Đã Nghỉ Việc) - Soft Delete Triggered.";
    ghiAuditLog($pdo, $adminLog, 'SA_THAI_NHAN_VIEN', 'NHAN_VIEN', $maNV_Huy, $lyDoChinh);

    $pdo->commit();

    $_SESSION['success_msg'] = "Đã Cách chức và Đình chỉ công tác Nhân viên Mã số [$maNV_Huy]. Dữ liệu đã đóng gói vào Không Gian Chết của Database.";
    header("Location: index.php");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Gãy Lưỡi Kiềm Xóa: " . $e->getMessage());
    $_SESSION['error_msg'] = "Database Sập: Giải phóng Dữ liệu Bất Thành!";
    header("Location: index.php");
    exit();
}
