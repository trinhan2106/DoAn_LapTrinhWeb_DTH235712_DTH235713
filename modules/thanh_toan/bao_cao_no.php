<?php
/**
 * modules/thanh_toan/bao_cao_no.php
 * Báo cáo tổng hợp nợ xấu khách hàng - Chuẩn Admin Layout
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Bảo mật: Chỉ Admin và Kế toán được xem báo cáo tài chính
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN]);

$db = Database::getInstance()->getConnection();

// --- TRUY VẤN KPI BÁO CÁO NỢ ---
try {
    // 1. Tổng tiền nợ toàn bộ khách hàng
    $totalDebt = $db->query("SELECT COALESCE(SUM(soTienConNo), 0) FROM HOA_DON WHERE (trangThai IN ('ConNo', 'DaThuMotPhan') OR soTienConNo < 0) AND deleted_at IS NULL AND loaiHoaDon = 'Chinh'")->fetchColumn() ?: 0;
    
    // 2. Số lượng khách hàng đang nợ
    $debtorCount = $db->query("SELECT COUNT(DISTINCT h.maKH) FROM HOA_DON hd JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong WHERE (hd.trangThai IN ('ConNo', 'DaThuMotPhan') OR hd.soTienConNo < 0) AND hd.deleted_at IS NULL AND hd.loaiHoaDon = 'Chinh'")->fetchColumn() ?: 0;
    
    // 3. Khoản nợ lớn nhất
    $maxDebt = $db->query("
        SELECT SUM(hd.soTienConNo) as total 
        FROM HOA_DON hd 
        WHERE (hd.trangThai IN ('ConNo', 'DaThuMotPhan') OR hd.soTienConNo < 0) AND hd.deleted_at IS NULL AND hd.loaiHoaDon = 'Chinh'
        GROUP BY hd.soHopDong 
        ORDER BY total DESC LIMIT 1
    ")->fetchColumn() ?: 0;

    // --- TRUY VẤN DANH SÁCH CHI TIẾT ---
    $sqlDetails = "
        SELECT 
            kh.tenKH, 
            kh.sdt,
            h.soHopDong, 
            SUM(CASE 
                WHEN hd.thang = MONTH(CURRENT_DATE()) AND hd.nam = YEAR(CURRENT_DATE()) 
                THEN hd.soTienConNo ELSE 0 END) as no_trong_han,
            SUM(CASE 
                WHEN (hd.nam < YEAR(CURRENT_DATE())) OR (hd.nam = YEAR(CURRENT_DATE()) AND hd.thang < MONTH(CURRENT_DATE())) 
                THEN hd.soTienConNo ELSE 0 END) as no_qua_han,
            SUM(hd.soTienConNo) as tong_no,
            MIN(hd.created_at) as ngay_no_dau_tien,
            COUNT(hd.soHoaDon) as so_luong_hd_no
        FROM HOA_DON hd
        JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong
        JOIN KHACH_HANG kh ON h.maKH = kh.maKH
        WHERE (hd.trangThai IN ('ConNo', 'DaThuMotPhan') OR hd.soTienConNo < 0)
          AND hd.loaiHoaDon = 'Chinh'
          AND hd.deleted_at IS NULL
          AND h.deleted_at IS NULL
        GROUP BY kh.maKH, h.soHopDong
        HAVING tong_no > 0
        ORDER BY tong_no DESC
    ";
    $debtList = $db->query($sqlDetails)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("[bao_cao_no.php] Error: " . $e->getMessage());
    $debtList = [];
}

$pageTitle = "Báo Cáo Nợ Xấu Tổng Hợp";
include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h3 fw-bold text-navy mb-1"><i class="bi bi-file-earmark-bar-graph me-2 text-danger"></i> BÁO CÁO NỢ XẤU TỔNG HỢP</h2>
                        <p class="text-muted small mb-0">Thống kê chi tiết các khoản nợ quá hạn từ khách hàng thuê mặt bằng.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="window.print()" class="btn btn-navy shadow-sm btn-sm px-3">
                            <i class="bi bi-printer me-2"></i> Xuất Báo Cáo
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4" style="border-left: 5px solid #dc3545 !important;">
                            <div class="card-body p-4 text-center">
                                <div class="text-muted small fw-bold text-uppercase mb-1">Tổng Nợ Toàn Hệ Thống</div>
                                <h3 class="fw-bold text-danger mb-0"><?= number_format($totalDebt, 0) ?> đ</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4" style="border-left: 5px solid #1e3a5f !important;">
                            <div class="card-body p-4 text-center">
                                <div class="text-muted small fw-bold text-uppercase mb-1">Số Lượng Khách Hàng Nợ</div>
                                <h3 class="fw-bold text-navy mb-0"><?= $debtorCount ?> <small class="fs-6 fw-normal">Đơn vị</small></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4" style="border-left: 5px solid #c9a66b !important;">
                            <div class="card-body p-4 text-center">
                                <div class="text-muted small fw-bold text-uppercase mb-1">Khoản Nợ Lớn Nhất</div>
                                <h3 class="fw-bold text-dark mb-0"><?= number_format($maxDebt, 0) ?> đ</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-navy small text-uppercase">Danh sách chi tiết theo hợp đồng</h5>
                        <div class="badge bg-danger-subtle text-danger border border-danger-subtle">
                            Tính đến ngày <?= date('d/m/Y') ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Khách Hàng / SDT</th>
                                        <th>Hợp Đồng</th>
                                        <th class="text-center">HĐ Nợ</th>
                                        <th class="text-end">Trong Hạn</th>
                                        <th class="text-end">Quá Hạn</th>
                                        <th class="text-end">Tổng Nợ (VND)</th>
                                        <th class="text-center pe-4">Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($debtList) > 0): ?>
                                        <?php foreach ($debtList as $debt): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-navy"><?= htmlspecialchars($debt['tenKH']) ?></div>
                                                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($debt['sdt']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($debt['soHopDong']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge rounded-pill bg-secondary"><?= $debt['so_luong_hd_no'] ?></span>
                                                </td>
                                                <td class="text-end text-primary">
                                                    <?= number_format($debt['no_trong_han'], 0) ?> đ
                                                </td>
                                                <td class="text-end text-warning fw-bold">
                                                    <?= number_format($debt['no_qua_han'], 0) ?> đ
                                                </td>
                                                <td class="text-end fw-bold text-danger">
                                                    <?= number_format($debt['tong_no'], 0) ?> đ
                                                </td>
                                                <td class="text-center pe-4">
                                                    <a href="tt_tao.php?soHopDong=<?= urlencode($debt['soHopDong']) ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-none">
                                                        <i class="bi bi-cash-stack me-1"></i> Thu tiền
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-check-circle-fill text-success d-block fs-1 mb-3"></i>
                                                Hiện tại không có khoản nợ xấu nào trong hệ thống.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<style>
    .btn-navy { background-color: #1e3a5f; color: white; border: none; }
    .btn-navy:hover { background-color: #152943; color: #c9a66b; }
    .table thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 1rem 0.5rem; }
</style>
</body>
</html>
