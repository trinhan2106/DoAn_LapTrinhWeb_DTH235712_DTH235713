<?php
/**
 * modules/phong/phong_hienthi.php
 * Trang danh sách Phòng - Hệ thống Quản lý Cao ốc
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Xác thực Session
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA, ROLE_KE_TOAN]);

$db = Database::getInstance()->getConnection();

// 2. LẤY DANH SÁCH DỮ LIỆU
try {
    // Lấy danh sách Cao ốc phục vụ Filter
    $dsCaoOc = $db->query("SELECT maCaoOc, tenCaoOc FROM CAO_OC WHERE deleted_at IS NULL ORDER BY tenCaoOc")->fetchAll();
    
    // Lấy danh sách Tầng phục vụ Filter
    $dsTang = $db->query("SELECT maTang, tenTang, maCaoOc FROM TANG WHERE deleted_at IS NULL ORDER BY tenTang")->fetchAll();
    
    // Lấy danh sách Phòng kèm thông tin Tầng và Cao ốc
    $sql = "
        SELECT p.*, t.tenTang, c.tenCaoOc, c.maCaoOc
        FROM PHONG p
        JOIN TANG t ON p.maTang = t.maTang
        JOIN CAO_OC c ON t.maCaoOc = c.maCaoOc
        WHERE p.deleted_at IS NULL
        ORDER BY c.tenCaoOc, t.tenTang, p.tenPhong
    ";
    $dsPhong = $db->query($sql)->fetchAll();
} catch (PDOException $e) {
    die("Lỗi Database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .table-navy thead th { background-color: #1e3a5f !important; color: #ffffff !important; font-weight: 600; }
        .text-navy { color: #1e3a5f !important; }
        .btn-gold { background-color: #c9a66b !important; color: #ffffff !important; border: none; font-weight: 600; }
        .btn-gold:hover { background-color: #b5925a !important; }
        .filter-section { border-radius: 12px; background-color: #ffffff; }
        .badge-status--1 { background-color: #2ecc71; color: #ffffff; } /* Trống */
        .badge-status--2 { background-color: #3498db; color: #ffffff; } /* Đã thuê */
        .badge-status--3 { background-color: #f1c40f; color: #000000; } /* Bảo trì */
        .badge-status--4 { background-color: #e74c3c; color: #ffffff; } /* Đã khóa */
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Quản lý Phòng</li>
                </ol>
            </nav>

            <!-- Bộ lọc & Header -->
            <div class="card filter-section shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-4">
                            <h2 class="h4 mb-0 text-navy fw-bold">
                                <i class="bi bi-grid-3x3-gap me-2"></i>QUẢN LÝ PHÒNG
                            </h2>
                        </div>
                        <div class="col-md-3">
                            <select id="filterCaoOc" class="form-select">
                                <option value="">-- Tất cả Cao ốc --</option>
                                <?php foreach ($dsCaoOc as $co): ?>
                                    <option value="<?= e($co['tenCaoOc']) ?>"><?= e($co['tenCaoOc']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterTang" class="form-select">
                                <option value="">-- Tất cả Tầng --</option>
                                <?php foreach ($dsTang as $t): ?>
                                    <option value="<?= e($t['tenTang']) ?>"><?= e($t['tenTang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <a href="phong_them.php" class="btn btn-gold w-100">
                                <i class="bi bi-plus-lg me-1"></i> Thêm Phòng
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tblPhong" class="table table-hover align-middle table-navy">
                            <thead>
                                <tr>
                                    <th>Mã Phòng</th>
                                    <th>Tên Phòng</th>
                                    <th>Tòa nhà / Tầng</th>
                                    <th class="text-end">Diện tích</th>
                                    <th class="text-end">Giá thuê/tháng</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dsPhong as $p): 
                                    $statusNames = [1 => 'Trống', 2 => 'Đã thuê', 3 => 'Bảo trì', 4 => 'Đã khóa'];
                                ?>
                                    <tr>
                                        <td class="fw-bold text-navy"><?= e($p['maPhong']) ?></td>
                                        <td class="fw-semibold"><?= e($p['tenPhong']) ?></td>
                                        <td>
                                            <div class="small fw-bold text-navy"><?= e($p['tenCaoOc']) ?></div>
                                            <div class="small text-muted"><?= e($p['tenTang']) ?></div>
                                        </td>
                                        <td class="text-end text-nowrap"><?= number_format($p['dienTich'], 1) ?> m²</td>
                                        <td class="text-end fw-bold text-navy text-nowrap"><?= formatTien($p['giaThue']) ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-status--<?= $p['trangThai'] ?> px-3 py-2">
                                                <?= $statusNames[$p['trangThai']] ?? 'N/A' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="phong_sua.php?id=<?= e($p['maPhong']) ?>" class="btn btn-outline-primary" title="Sửa">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="javascript:void(0)" onclick="xacNhanXoa('<?= e($p['maPhong']) ?>', '<?= e($p['tenPhong']) ?>')" class="btn btn-outline-danger" title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
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

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                Bạn có chắc chắn muốn xóa phòng <strong id="deleteRoomName" class="text-danger"></strong>?
                <br><small class="text-muted">Hệ thống sẽ kiểm tra hợp đồng hiệu lực trước khi cho phép xóa mềm.</small>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Hủy</button>
                <a id="btnConfirmDelete" href="#" class="btn btn-danger px-4">Đồng Ý Xóa</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#tblPhong').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json" },
        "order": [[2, "asc"], [0, "asc"]],
        "pageLength": 10
    });

    $('#filterCaoOc').on('change', function() {
        table.column(2).search(this.value).draw();
    });

    $('#filterTang').on('change', function() {
        table.column(2).search(this.value).draw();
    });
});

function xacNhanXoa(ma, ten) {
    document.getElementById('deleteRoomName').innerText = ten;
    document.getElementById('btnConfirmDelete').href = 'phong_xoa.php?id=' + encodeURIComponent(ma);
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

</body>
</html>
