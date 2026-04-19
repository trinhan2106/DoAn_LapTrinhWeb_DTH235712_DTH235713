<?php
/**
 * modules/tang/index.php
 * Trang danh sách các Tầng trong hệ thống
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Xác thực Session & Phân quyền (Admin=1, Quản lý nhà=2 mới được truy cập)
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Kết nối CSDL
$db = Database::getInstance()->getConnection();

// Truy vấn danh sách tầng (JOIN với CAO_OC để lấy tên tòa nhà)
// Chỉ lấy các bản ghi chưa bị xóa mềm (deleted_at IS NULL)
$sql = "
    SELECT t.maTang, t.tenTang, t.heSoGia, c.tenCaoOc, c.maCaoOc
    FROM TANG t
    JOIN CAO_OC c ON t.maCaoOc = c.maCaoOc
    WHERE t.deleted_at IS NULL AND c.deleted_at IS NULL
    ORDER BY c.tenCaoOc ASC, t.tenTang ASC
";
$stmt = $db->query($sql);
$dsTang = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <!-- DataTables BS5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .table-navy thead th {
            background-color: #1e3a5f !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        .text-navy {
            color: #1e3a5f !important;
        }
        .border-navy-subtle {
            border-color: rgba(30, 58, 95, 0.2) !important;
        }
        .btn-gold {
            background-color: #c9a66b !important;
            color: #ffffff !important;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-gold:hover {
            background-color: #b5925a !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .action-link {
            text-decoration: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: background-color 0.2s;
        }
        .action-link--edit { color: #1e3a5f; }
        .action-link--edit:hover { background-color: rgba(30, 58, 95, 0.1); }
        .action-link--delete { color: #e74c3c; }
        .action-link--delete:hover { background-color: rgba(231, 76, 60, 0.1); }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <!-- Nạp Sidebar -->
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <!-- Nạp Topbar -->
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        <!-- Nạp Notifications -->
        <?php include __DIR__ . '/../../includes/admin/notifications.php'; ?>
        
        <main class="admin-main-content p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý Tầng</li>
                </ol>
            </nav>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0 text-navy fw-bold">
                        <i class="bi bi-layers me-2"></i>DANH SÁCH TẦNG
                    </h2>
                    <a href="them.php" class="btn btn-gold shadow-sm px-4">
                        <i class="bi bi-plus-circle me-2"></i>Thêm Tầng Mới
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tblTang" class="table table-hover align-middle table-navy">
                            <thead>
                                <tr>
                                    <th width="120">Mã Tầng</th>
                                    <th>Tên Tầng</th>
                                    <th>Tòa Nhà (Cao ốc)</th>
                                    <th width="150">Hệ số giá</th>
                                    <th width="150" class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dsTang as $tang): ?>
                                    <tr>
                                        <td class="fw-bold"><?= e($tang['maTang']) ?></td>
                                        <td><?= e($tang['tenTang']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-navy border border-navy-subtle">
                                                <i class="bi bi-building me-1"></i><?= e($tang['tenCaoOc']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold"><?= number_format($tang['heSoGia'], 2) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="sua.php?id=<?= e($tang['maTang']) ?>" class="action-link action-link--edit me-2" title="Chỉnh sửa">
                                                <i class="bi bi-pencil-square"></i> Sửa
                                            </a>
                                            <a href="javascript:void(0)" onclick="xacNhanXoa('<?= e($tang['maTang']) ?>', '<?= e($tang['tenTang']) ?>')" class="action-link action-link--delete" title="Xóa">
                                                <i class="bi bi-trash"></i> Xóa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Nạp Footer -->
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
            <div class="modal-body p-4">
                Bạn có chắc chắn muốn xóa tầng <strong id="deleteFloorName"></strong>? 
                <br><small class="text-muted">Hành động này sẽ thực hiện xóa mềm bản ghi khỏi hệ thống.</small>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <a id="btnConfirmDelete" href="#" class="btn btn-danger px-4">Xác nhận xóa</a>
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
    $('#tblTang').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
        },
        "order": [[2, "asc"], [1, "asc"]], // Sắp xếp theo tòa nhà rồi đến tên tầng
        "pageLength": 10,
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    });
});

function xacNhanXoa(maTang, tenTang) {
    document.getElementById('deleteFloorName').innerText = tenTang;
    document.getElementById('btnConfirmDelete').href = 'xoa.php?id=' + maTang;
    var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    myModal.show();
}
</script>

</body>
</html>
