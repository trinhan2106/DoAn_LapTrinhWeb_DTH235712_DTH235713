<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
kiemTraSession();
kiemTraRole([1, 2]); // Chỉ Admin (1) và Quản lý Nhà (2) được thao tác
$pdo = Database::getInstance()->getConnection();

// Lấy danh sách Khách hàng chưa bị xóa (Soft Delete)
$stmt = $pdo->prepare("SELECT maKH, tenKH, cccd, sdt, email, diaChi FROM KHACH_HANG WHERE deleted_at IS NULL ORDER BY maKH DESC");
$stmt->execute();
$khachHangs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require_once __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <!-- DataTables BS5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .table-navy thead th {
            background-color: #1e3a5f !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        .text-navy { color: #1e3a5f !important; }
        .btn-gold {
            background-color: #c9a66b !important;
            color: #ffffff !important;
            border: none;
            transition: all 0.3s;
        }
        .btn-gold:hover { background-color: #b5925a; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        
        .filter-section {
            background-color: #fff;
            border-radius: 10px;
            border-left: 4px solid #c9a66b;
        }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php require_once __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/dashboard/admin.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Quản lý Khách hàng</li>
                </ol>
            </nav>

            <!-- Bộ lọc & Header giống Phòng -->
            <div class="card filter-section shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-9">
                            <h2 class="h4 mb-0 text-navy fw-bold">
                                <i class="bi bi-people-fill me-2"></i>QUẢN LÝ KHÁCH HÀNG
                            </h2>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <a href="#" class="btn btn-gold w-100 fw-bold">
                                <i class="bi bi-plus-lg me-1"></i> Thêm Khách hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng danh sách DataTables -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tblKhachHang" class="table table-hover align-middle table-navy w-100 border">
                            <thead>
                                <tr>
                                    <th>Mã KH</th>
                                    <th>Tên Khách hàng</th>
                                    <th>CCCD/CMND</th>
                                    <th>Số điện thoại</th>
                                    <th>Email</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($khachHangs as $kh): ?>
                                    <tr>
                                        <td class="fw-bold text-navy"><?= htmlspecialchars($kh['maKH'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($kh['tenKH'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($kh['cccd'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($kh['sdt'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($kh['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="kh_sua.php?id=<?= urlencode($kh['maKH']) ?>" class="btn btn-outline-primary" title="Sửa thông tin">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                 <button type="button" class="btn btn-outline-danger" title="Xóa khách hàng" onclick="xacNhanXoa('<?= htmlspecialchars($kh['maKH'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($kh['tenKH'], ENT_QUOTES, 'UTF-8') ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
        
        <?php require_once __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<!-- Modal Xác nhận Xóa Soft Delete -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Xác nhận xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                Bạn có chắc chắn muốn xóa khách hàng <strong id="deleteKhName" class="text-danger"></strong>?
                <br><small class="text-muted">Hệ thống sẽ kiểm tra hợp đồng hiệu lực trước khi cho phép xóa mềm.</small>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                <form action="kh_xoa_submit.php" method="POST" class="m-0">
                    <input type="hidden" name="maKH" id="deleteKhId" value="">
                    <button type="submit" class="btn btn-danger px-4 shadow-sm">Đồng ý Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- DataTables & Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tblKhachHang').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json" },
        "order": [[0, "desc"]], // Sắp xếp theo mã KH giảm dần
        "pageLength": 10
    });
});

function xacNhanXoa(ma, ten) {
    document.getElementById('deleteKhName').innerText = ten;
    document.getElementById('deleteKhId').value = ma;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

</body>
</html>
