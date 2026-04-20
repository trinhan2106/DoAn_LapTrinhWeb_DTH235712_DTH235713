<?php
// modules/maintenance/yc_capnhat.php
/**
 * Xử lý Transaction cập nhật Trạng thái Maintainance + Lưu dấu vết Tracking Log
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
if (!in_array((int)($_SESSION['user_role'] ?? 0), [ROLE_ADMIN, ROLE_QUAN_LY_NHA])) {
    die("Access Denied.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Method Not Allowed.");

$csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Mã bảo vệ form tĩnh CSRF không xác thực.";
    header("Location: yc_quan_ly.php"); exit();
}

$reqId = trim($_POST['id'] ?? '');
$trangThaiCu = (int)($_POST['trangThaiCu'] ?? 0);
$trangThaiMoi = (int)($_POST['trangThaiMoi'] ?? 0);
$updateBy = $_SESSION['user_id'] ?? 'SYS';

if (empty($reqId)) {
    header("Location: yc_quan_ly.php"); exit();
}
// Nếu không cấu thay đổi, đẩy lùi
if ($trangThaiCu === $trangThaiMoi) {
    header("Location: yc_quan_ly.php"); exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Cập nhật record Core Request
    $stmtUpd = $pdo->prepare("UPDATE MAINTENANCE_REQUEST SET trangThai = ? WHERE id = ?");
    $stmtUpd->execute([$trangThaiMoi, $reqId]);

    // 2. Lưu Track Status Log đảm bảo tính trong suốt
    $stmtLog = $pdo->prepare("
        INSERT INTO MAINTENANCE_STATUS_LOG (request_id, trangThaiCu, trangThaiMoi, nguoiCapNhat)
        VALUES (?, ?, ?, ?)
    ");
    $stmtLog->execute([$reqId, $trangThaiCu, $trangThaiMoi, $updateBy]);

    // 2b. Ghi THONG_BAO đến khách hàng nếu status thay đổi đáng kể
    $statusLabels = [0 => 'Chờ tiếp nhận', 1 => 'Đang xử lý', 2 => 'Hoàn thành', 3 => 'Đã hủy'];
    $stmtKH = $pdo->prepare("
        SELECT h.maKH FROM HOP_DONG h
        JOIN CHI_TIET_HOP_DONG cthd ON h.soHopDong = cthd.soHopDong
        JOIN MAINTENANCE_REQUEST mr ON cthd.maPhong = mr.maPhong
        WHERE mr.id = ? AND h.trangThai = 1
        LIMIT 1
    ");
    $stmtKH->execute([$reqId]);
    $maKHNhan = $stmtKH->fetchColumn();
    if ($maKHNhan) {
        $labelMoi = $statusLabels[$trangThaiMoi] ?? "Trạng thái $trangThaiMoi";
        $tieuDe = "Yêu cầu bảo trì #$reqId đã được cập nhật";
        $noiDung = "Yêu cầu bảo trì của bạn (#{$reqId}) vừa được cập nhật sang trạng thái: <b>{$labelMoi}</b>.";
        $maThongBao = 'TB_MR_' . $reqId . '_' . time();
        $stmtTB = $pdo->prepare("
            INSERT INTO THONG_BAO (maThongBao, tieuDe, noiDung, nguoiNhan, loaiThongBao, daDoc)
            VALUES (?, ?, ?, ?, 'BaoTri', 0)
        ");
        $stmtTB->execute([$maThongBao, $tieuDe, $noiDung, $maKHNhan]);
    }

    // 3. Kéo cờ Audit Log
    $logDetail = "Yêu cầu bảo trì {$reqId}: Đổi luồng trạng thái từ {$trangThaiCu} sang {$trangThaiMoi} bởi {$updateBy}.";
    ghiAuditLog($pdo, $updateBy, 'CAP_NHAT_MAINTENANCE', 'MAINTENANCE_STATUS_LOG', $reqId, $logDetail);

    $pdo->commit();

    $_SESSION['success_msg'] = "Đã cập nhật tình trạng tiến độ cho Yêu cầu [$reqId].";
    header("Location: yc_quan_ly.php");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Maintenance Update Error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi kỹ thuật lưu trữ DB Transaction.";
    header("Location: yc_quan_ly.php");
    exit();
}
