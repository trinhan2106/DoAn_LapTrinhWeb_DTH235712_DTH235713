<?php
/**
 * modules/dashboard/log_he_thong.php
 * Giao diện xem Nhật ký hệ thống (Audit Log) - Chỉ dành cho Admin tối cao.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Bảo mật: Chỉ Admin được xem log hệ thống
kiemTraSession();
kiemTraRole([ROLE_ADMIN]);

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn Log kết hợp với thông tin Nhân viên và Khách hàng (người thực hiện có thể là KH nếu dùng Portal)
    $sql = "
        SELECT 
            al.*, 
            nv.tenNV as nguoi_thuc_hien_nv,
            kh.tenKH as nguoi_thuc_hien_kh
        FROM AUDIT_LOG al
        LEFT JOIN NHAN_VIEN nv ON al.maNguoiDung = nv.maNV
        LEFT JOIN KHACH_HANG kh ON al.maNguoiDung = kh.maKH
        ORDER BY al.thoiGian DESC
        LIMIT 500
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("[log_he_thong.php] Error: " . $e->getMessage());
    $logs = [];
}

$pageTitle = "Nhật Ký Hệ Thống";
include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>

    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>

        <main class="admin-main-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 fw-bold text-navy mb-0">
                    <i class="bi bi-journal-text me-2"></i>NHẬT KÝ HỆ THỐNG (AUDIT LOG)
                </h2>
                <div class="text-muted small">Hiển thị 500 thao tác gần nhất</div>
            </div>

            <!-- Bộ lọc tùy chỉnh -->
            <div class="card shadow-sm border-0 mb-4 bg-light bg-opacity-50">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-auto">
                            <label class="form-label mb-0 fw-bold small text-navy me-2">Bộ lọc nhanh:</label>
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="filterNguoi" class="form-control form-control-sm" placeholder="Tên người thực hiện...">
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="filterID" class="form-control form-control-sm" placeholder="Mã ID (VD: HD-2025-001, P-001)...">
                        </div>
                        <div class="col-md-auto">
                            <button class="btn btn-sm btn-outline-secondary" id="btnResetFilter">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="logTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Thời gian</th>
                                    <th>Người thực hiện</th>
                                    <th>Hành động</th>
                                    <th>Đối tượng</th>
                                    <th>Chi tiết thao tác</th>
                                    <th class="pe-4">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): 
                                    $nguoi = $log['nguoi_thuc_hien_nv'] ?: ($log['nguoi_thuc_hien_kh'] ?: 'Hệ thống');
                                    $badgeClass = 'bg-secondary';
                                    if (strpos($log['hanhDong'], 'CREATE') !== false) $badgeClass = 'bg-success';
                                    if (strpos($log['hanhDong'], 'UPDATE') !== false) $badgeClass = 'bg-info text-dark';
                                    if (strpos($log['hanhDong'], 'DELETE') !== false || strpos($log['hanhDong'], 'HUY') !== false) $badgeClass = 'bg-danger';
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= date('d/m/Y', strtotime($log['thoiGian'])) ?></div>
                                            <div class="text-muted small"><?= date('H:i:s', strtotime($log['thoiGian'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded-circle text-navy d-flex align-items-center justify-content-center me-2" style="width:32px; height:32px;">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= e($nguoi) ?></div>
                                                    <div class="text-muted extra-small"><?= e($log['maNguoiDung']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>"><?= e($log['hanhDong']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-navy small"><?= e($log['bangBiTacDong']) ?></div>
                                            <div class="text-muted extra-small">ID: <?= e($log['recordId']) ?></div>
                                        </td>
                                        <td>
                                            <div class="small text-wrap" style="max-width: 300px;">
                                                <?= e($log['chiTiet']) ?>
                                            </div>
                                        </td>
                                        <td class="pe-4 small text-muted">
                                            <?= e($log['ipAddress']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<style>
.extra-small { font-size: 0.7rem; }
.text-navy { color: #1e3a5f; }
</style>

<script>
$(document).ready(function() {
    var table = $('#logTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json"
        },
        "dom": '<"d-none"f>rtip' // Ẩn thanh Search mặc định của DataTable
    });

    // Lọc theo tên người thực hiện (Cột 2 - index 1)
    $('#filterNguoi').on('keyup', function() {
        table.column(1).search(this.value).draw();
    });

    // Lọc theo Mã ID đối tượng (Cột 4 - index 3)
    $('#filterID').on('keyup', function() {
        table.column(3).search(this.value).draw();
    });

    // Reset bộ lọc
    $('#btnResetFilter').on('click', function() {
        $('#filterNguoi').val('');
        $('#filterID').val('');
        table.columns().search('').draw();
    });
});
</script>
