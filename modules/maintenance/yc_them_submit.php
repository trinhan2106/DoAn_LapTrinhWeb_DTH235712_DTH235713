<?php
// modules/maintenance/yc_them_submit.php
/**
 * Xử lý Insert Maintenance Request vào hệ thống.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
if ((int)($_SESSION['user_role'] ?? 0) !== ROLE_KHACH_HANG) {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Method Not Allowed.");

$csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Mã xác thực không hợp lệ.";
    header("Location: yc_them.php"); exit();
}

$maPhong = trim($_POST['maPhong'] ?? '');
$mucDoUT = (int)($_POST['mucDoUT'] ?? 2);
$moTa = trim($_POST['moTa'] ?? '');
$nguoiYeuCau = $_SESSION['user_id'] ?? ''; // Thuộc về mã Khách Hàng

if (empty($maPhong) || empty($moTa)) {
    $_SESSION['error_msg'] = "Thiếu dữ liệu bắt buộc.";
    header("Location: yc_them.php"); exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // Xác thực quyền sở hữu phòng thông qua logic Hợp đồng
    $stmtVerify = $pdo->prepare("
        SELECT COUNT(*) FROM CHI_TIET_HOP_DONG c
        JOIN HOP_DONG h ON c.soHopDong = h.soHopDong
        WHERE c.maPhong = ? AND h.maKH = ? AND h.trangThai = 'DangHieuLuc'
    ");
    $stmtVerify->execute([$maPhong, $nguoiYeuCau]);
    if ($stmtVerify->fetchColumn() == 0) {
        $_SESSION['error_msg'] = "Lỗi nghiệp vụ: Phòng không do bạn quản lý tại thời điểm này.";
        header("Location: yc_them.php"); exit();
    }

    $idYeuCau = sinhMaNgauNhien('MR-', 6);

    $stmtInsert = $pdo->prepare("
        INSERT INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT) 
        VALUES (?, ?, ?, ?, 0, ?)
    ");
    $stmtInsert->execute([$idYeuCau, $maPhong, $moTa, $nguoiYeuCau, $mucDoUT]);

    $_SESSION['success_msg'] = "Đã phát lệnh yêu cầu bảo trì: [$idYeuCau]. Bộ phận điều hành sẽ tiếp nhận ngay.";
    header("Location: yc_them.php");
    exit();

} catch (PDOException $e) {
    error_log("Submit Maintenance Request Error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi kỹ thuật lưu trữ DB.";
    header("Location: yc_them.php");
    exit();
}
