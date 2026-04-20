<?php
/**
 * modules/tien_coc/coc_hienthi.php
 * Giao diện Quản lý Tiền cọc (Phiên bản cơ bản)
 */
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([1, 2]);

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn lấy danh sách tiền cọc
    $stmt = $pdo->query("SELECT tc.maTienCoc, tc.soHopDong, tc.soTien, tc.trangThai, kh.tenKH FROM TIEN_COC tc JOIN HOP_DONG hd ON tc.soHopDong = hd.soHopDong JOIN KHACH_HANG kh ON hd.maKH = kh.maKH ORDER BY tc.maTienCoc DESC");
    $listCoc = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    error_log("[" . basename(__FILE__) . "] Lỗi DB: " . $e->getMessage());
    $_SESSION['error_msg'] = "Đã xảy ra lỗi hệ thống. Vui lòng liên hệ quản trị viên.";
    header("Location: " . BASE_URL . "modules/dashboard/admin.php");
    exit();
}

include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <div class="card shadow border-0 rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold text-navy"><i class="bi bi-cash-stack me-2"></i>QUẢN LÝ TIỀN CỌC</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle table-datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="12%">Mã cọc</th>
                                        <th width="15%">Số HĐ</th>
                                        <th width="20%">Khách hàng</th>
                                        <th width="20%">Số tiền (VNĐ)</th>
                                        <th width="15%">Trạng thái</th>
                                        <th width="18%" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listCoc as $coc): ?>
                                        <tr>
                                            <td class="fw-bold"><code><?= htmlspecialchars($coc['maTienCoc']) ?></code></td>
                                            <td class="fw-bold text-primary"><?= htmlspecialchars($coc['soHopDong']) ?></td>
                                            <td><?= htmlspecialchars($coc['tenKH']) ?></td>
                                            <td class="fw-bold text-success text-end"><?= number_format($coc['soTien'], 0, ',', '.') ?> ₫</td>
                                            <td>
                                                <?php
                                                    if($coc['trangThai'] == 1) echo '<span class="badge bg-success">Đã thu</span>';
                                                    elseif($coc['trangThai'] == 2) echo '<span class="badge bg-primary">Đã hoàn</span>';
                                                    elseif($coc['trangThai'] == 3) echo '<span class="badge bg-danger">Bị tịch thu</span>';
                                                    else echo '<span class="badge bg-secondary">Khác</span>';
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (in_array((int)$coc['trangThai'], [1, 4])): ?>
                                                    <a href="tc_xuly.php?id=<?= urlencode($coc['maTienCoc']) ?>" class="btn btn-sm btn-warning fw-bold">
                                                        <i class="bi bi-scale me-1"></i>Xử lý Cọc
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary opacity-50" disabled>
                                                        <i class="bi bi-lock me-1"></i>Đã chốt
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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

</body>
</html>
