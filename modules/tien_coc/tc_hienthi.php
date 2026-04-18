<?php
// modules/tien_coc/tc_hienthi.php
/**
 * TRẠM KIỂM SOÁT TÀI CHÍNH: QUẢN LÝ TIỀN CỌC
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn Tiền cọc kết hợp Hợp đồng và Khách hàng
    $stmt = $pdo->query("
        SELECT tc.maTienCoc, tc.soHopDong, hd.maKH, kh.tenKH, kh.sdt, tc.soTien, tc.ngayNop, tc.phuongThuc, tc.trangThai
        FROM TIEN_COC tc
        JOIN HOP_DONG hd ON tc.soHopDong = hd.soHopDong
        JOIN KHACH_HANG kh ON hd.maKH = kh.maKH
        ORDER BY tc.trangThai ASC, tc.ngayNop DESC
    ");
    $listTienCoc = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy xuất Kho Tiền Cọc: " . htmlspecialchars($e->getMessage()));
}

// Hàm render thẻ Badge theo Trạng Thái (1: Xanh lá, 2: Xanh dương, 3: Đỏ)
function renderBadge($stt) {
    switch ($stt) {
        case 1: return '<span class="badge bg-success"><i class="fa-solid fa-vault me-1"></i>Đã Thu (Đang Giữ)</span>';
        case 2: return '<span class="badge bg-primary"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Đã Hoàn Khách</span>';
        case 3: return '<span class="badge bg-danger"><i class="fa-solid fa-gavel me-1"></i>Bị Tịch Thu</span>';
        default: return '<span class="badge bg-secondary">Không Rõ</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sổ Quản Lý Tiền Cọc Hợp Đồng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --primary: #1e3a5f; --accent: #c9a66b; --bg: #f4f7f9; }
        body { background-color: var(--bg); }
        .page-header { background: var(--primary); border-bottom: 4px solid var(--accent); color: #fff; }
        .table-custom th { background-color: #e9ecef; }
        .amount-text { font-family: 'Courier New', Courier, monospace; font-weight: bold; color: #198754; font-size: 1.1rem; }
    </style>
</head>
<body class="p-4">

<div class="container shadow p-0 bg-white rounded overflow-hidden">
    <!-- Header -->
    <div class="page-header p-4 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0 fw-bold"><i class="fa-solid fa-piggy-bank me-2 text-warning"></i>KẾ TOÁN: BẢO LÃNH TIỀN CỌC</h3>
            <p class="mb-0 text-light opacity-75">Theo dõi dòng tiền cọc và thực thi quyết định Tất toán/Tịch thu khi HĐ kết thúc.</p>
        </div>
        <a href="../../index.php" class="btn btn-outline-light"><i class="fa-solid fa-home me-1"></i>Về Dashboard</a>
    </div>

    <!-- Alert Thông báo -->
    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success m-3 rounded-0"><i class="fa-solid fa-check me-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger m-3 rounded-0"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <!-- Table Danh Sách -->
    <div class="p-4">
        <table class="table table-bordered table-hover align-middle table-custom">
            <thead>
                <tr>
                    <th>Mã Phiếu Cọc</th>
                    <th>Hợp Đồng Gốc</th>
                    <th>Khách Hàng Ký Gửi</th>
                    <th class="text-end">Số Tiền (VNĐ)</th>
                    <th>Thời Điểm Nộp</th>
                    <th class="text-center">Trạng Thái Tạm Giữ</th>
                    <th class="text-center">Bộ Điều Khiển</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($listTienCoc)): ?>
                     <tr><td colspan="7" class="text-center text-muted fw-bold py-4">Chưa có giao dịch thế chân nào được lập.</td></tr>
                <?php else: ?>
                    <?php foreach($listTienCoc as $tc): ?>
                        <tr>
                            <td class="fw-bold px-3"><code><?= htmlspecialchars($tc['maTienCoc']) ?></code></td>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($tc['soHopDong']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($tc['tenKH']) ?></strong><br>
                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($tc['sdt']) ?></small>
                            </td>
                            <td class="text-end amount-text"><?= number_format($tc['soTien'], 0, ',', '.') ?> ₫</td>
                            <td><i class="fa-regular fa-clock me-1 text-muted"></i><?= date('d/m/Y H:i', strtotime($tc['ngayNop'])) ?></td>
                            <td class="text-center"><?= renderBadge((int)$tc['trangThai']) ?></td>
                            <td class="text-center">
                                <?php if($tc['trangThai'] == 1): ?>
                                    <a href="tc_xuly.php?id=<?= urlencode($tc['maTienCoc']) ?>" class="btn btn-warning btn-sm fw-bold shadow-sm">
                                        <i class="fa-solid fa-scale-balanced me-1"></i>Xử lý Cọc
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm opacity-50" disabled><i class="fa-solid fa-lock"></i> Đã Chốt</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
