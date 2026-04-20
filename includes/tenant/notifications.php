<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/auth.php';

kiemTraSession();

$role = (int)($_SESSION['user_role'] ?? 0);
if ($role !== 4) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$maKH = $_SESSION['user_id'];
$pdo = Database::getInstance()->getConnection();
$notifications = [];
$totalUnread = 0;

// 1. Thông báo hệ thống chưa đọc (push từ admin - ví dụ: cập nhật bảo trì)
$stmt1 = $pdo->prepare("
    SELECT maThongBao, tieuDe, loaiThongBao FROM THONG_BAO
    WHERE nguoiNhan = ? AND daDoc = 0
    ORDER BY ngayGui DESC LIMIT 10
");
$stmt1->execute([$maKH]);
$dsThongBao = $stmt1->fetchAll(PDO::FETCH_ASSOC);
$countTB = count($dsThongBao);

if ($countTB > 0) {
    $totalUnread += $countTB;
    $loaiIcon = ['BaoTri' => 'bi-tools', 'HoaDon' => 'bi-receipt', 'HopDong' => 'bi-file-earmark-text'];
    foreach ($dsThongBao as $tb) {
        $icon = $loaiIcon[$tb['loaiThongBao']] ?? 'bi-bell-fill';
        $notifications[] = [
            'title' => htmlspecialchars($tb['tieuDe'], ENT_QUOTES, 'UTF-8'),
            'link'  => BASE_URL . 'modules/tenant/dashboard.php#thongbao',
            'icon'  => $icon,
            'color' => 'text-primary'
        ];
    }
}

// 2. Hóa đơn còn nợ / chưa thanh toán đủ
$stmt2 = $pdo->prepare("
    SELECT COUNT(*) FROM HOA_DON hd
    JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong
    WHERE h.maKH = ? AND hd.trangThai IN ('ConNo','DaThuMotPhan') AND hd.deleted_at IS NULL
");
$stmt2->execute([$maKH]);
$countHD = (int)$stmt2->fetchColumn();
if ($countHD > 0) {
    $totalUnread += $countHD;
    $notifications[] = [
        'title' => "Có <b>$countHD</b> hóa đơn chưa thanh toán",
        'link'  => BASE_URL . 'modules/tenant/hoa_don.php',
        'icon'  => 'bi-receipt-cutoff',
        'color' => 'text-danger'
    ];
}

// 3. Yêu cầu bảo trì đang chờ / đang xử lý
$stmt3 = $pdo->prepare("
    SELECT COUNT(*) FROM MAINTENANCE_REQUEST mr
    JOIN PHONG p ON mr.maPhong = p.maPhong
    JOIN CHI_TIET_HOP_DONG cthd ON p.maPhong = cthd.maPhong
    JOIN HOP_DONG h ON cthd.soHopDong = h.soHopDong
    WHERE h.maKH = ? AND mr.trangThai IN (0, 1) AND mr.deleted_at IS NULL
");
$stmt3->execute([$maKH]);
$countMR = (int)$stmt3->fetchColumn();
if ($countMR > 0) {
    $totalUnread += $countMR;
    $notifications[] = [
        'title' => "Có <b>$countMR</b> yêu cầu bảo trì đang xử lý",
        'link'  => BASE_URL . 'modules/tenant/maintenance.php',
        'icon'  => 'bi-tools',
        'color' => 'text-warning'
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'total' => $totalUnread,
    'items' => $notifications
]);
