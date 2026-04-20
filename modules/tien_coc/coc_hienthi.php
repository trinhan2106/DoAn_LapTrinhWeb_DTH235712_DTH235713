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
    $stmt = $pdo->query("SELECT maTienCoc, soHopDong, soTien, trangThai FROM TIEN_COC ORDER BY maTienCoc DESC");
    $listCoc = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
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
                                        <th width="15%">Mã cọc</th>
                                        <th width="20%">Số HĐ</th>
                                        <th width="25%">Số tiền (VNĐ)</th>
                                        <th width="20%">Trạng thái</th>
                                        <th width="20%" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listCoc as $coc): ?>
                                        <tr>
                                            <td class="fw-bold">#<?= htmlspecialchars($coc['maTienCoc']) ?></td>
                                            <td><?= htmlspecialchars($coc['soHopDong']) ?></td>
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
                                                <button class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i></button>
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
