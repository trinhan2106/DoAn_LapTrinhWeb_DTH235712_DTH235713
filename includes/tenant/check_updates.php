<?php
/**
 * Endpoint kiểm tra phiên bản dữ liệu tenant.
 * Trả về hash dựa trên timestamps mới nhất — nếu hash thay đổi, frontend biết có dữ liệu mới.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/auth.php';

kiemTraSession();

if ((int)($_SESSION['user_role'] ?? 0) !== 4) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$maKH = $_SESSION['user_id'];
$pdo = Database::getInstance()->getConnection();

// Lấy timestamp mới nhất của maintenance status log liên quan đến KH này
$stmtMR = $pdo->prepare("
    SELECT COALESCE(MAX(msl.created_at), '2000-01-01')
    FROM MAINTENANCE_STATUS_LOG msl
    JOIN MAINTENANCE_REQUEST mr ON msl.request_id = mr.id
    JOIN PHONG p ON mr.maPhong = p.maPhong
    JOIN CHI_TIET_HOP_DONG cthd ON p.maPhong = cthd.maPhong
    JOIN HOP_DONG h ON cthd.soHopDong = h.soHopDong
    WHERE h.maKH = ?
");
$stmtMR->execute([$maKH]);
$lastMR = $stmtMR->fetchColumn();

// Timestamp mới nhất của hóa đơn KH này
$stmtHD = $pdo->prepare("
    SELECT COALESCE(MAX(hd.ngayLap), '2000-01-01')
    FROM HOA_DON hd
    JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong
    WHERE h.maKH = ? AND hd.deleted_at IS NULL
");
$stmtHD->execute([$maKH]);
$lastHD = $stmtHD->fetchColumn();

// Số thông báo chưa đọc
$stmtTB = $pdo->prepare("SELECT COUNT(*) FROM THONG_BAO WHERE nguoiNhan = ? AND daDoc = 0");
$stmtTB->execute([$maKH]);
$countTB = (int)$stmtTB->fetchColumn();

$version = md5($lastMR . '|' . $lastHD . '|' . $countTB);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['version' => $version, 'unread' => $countTB]);
