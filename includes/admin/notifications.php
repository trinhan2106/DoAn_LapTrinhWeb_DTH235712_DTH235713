<?php
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/auth.php';
kiemTraSession();

// Ngăn Khách hàng (Role 4) hoặc người dùng không xác định truy cập API của Admin
$role = (int)($_SESSION['user_role'] ?? 0);
if ($role === 4 || $role === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$pdo = Database::getInstance()->getConnection();
$notifications = [];
$totalUnread = 0;

// 1. Yêu cầu thuê phòng mới (Cho Admin & QLN)
if (in_array($role, [1, 2])) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM YEU_CAU_THUE WHERE trangThai = 0 AND deleted_at IS NULL")->fetchColumn();
    if ($count > 0) {
        $totalUnread += $count;
        $notifications[] = [
            'id' => 'rent_' . time(),
            'title' => "Có <b>$count</b> yêu cầu thuê phòng chờ duyệt",
            'link' => BASE_URL . 'modules/yeu_cau_thue/yc_hienthi.php',
            'icon' => 'bi-person-lines-fill',
            'color' => 'text-primary'
        ];
    }
}

// 2. Yêu cầu bảo trì/báo hỏng (Cho Admin & QLN)
if (in_array($role, [1, 2])) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM MAINTENANCE_REQUEST WHERE trangThai = 0 AND deleted_at IS NULL")->fetchColumn();
    if ($count > 0) {
        $totalUnread += $count;
        $notifications[] = [
            'id' => 'maint_' . time(),
            'title' => "Có <b>$count</b> báo hỏng đang chờ tiếp nhận",
            'link' => BASE_URL . 'modules/maintenance/yc_quan_ly.php',
            'icon' => 'bi-tools',
            'color' => 'text-danger'
        ];
    }
}

// 3. Khiếu nại hóa đơn (Cho Admin & Kế Toán)
if (in_array($role, [1, 3])) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM TRANH_CHAP_HOA_DON WHERE trangThai = 0")->fetchColumn();
    if ($count > 0) {
        $totalUnread += $count;
        $notifications[] = [
            'id' => 'dispute_' . time(),
            'title' => "Có <b>$count</b> khiếu nại hóa đơn mới",
            'link' => BASE_URL . 'modules/thanh_toan/tranh_chap_hienthi.php',
            'icon' => 'bi-receipt',
            'color' => 'text-warning'
        ];
    }
}

// 4. Hợp đồng sắp hết hạn trong 30 ngày (Cho Admin & QLN)
if (in_array($role, [1, 2])) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM HOP_DONG WHERE trangThai = 1 AND deleted_at IS NULL AND ngayHetHanCuoiCung <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)")->fetchColumn();
    if ($count > 0) {
        $totalUnread += $count;
        $notifications[] = [
            'id' => 'expiry_' . time(),
            'title' => "Có <b>$count</b> hợp đồng sắp hết hạn",
            'link' => BASE_URL . 'modules/bao_cao/bao_cao.php',
            'icon' => 'bi-file-earmark-text',
            'color' => 'text-info'
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'total' => $totalUnread,
    'items' => $notifications
]);
exit();
