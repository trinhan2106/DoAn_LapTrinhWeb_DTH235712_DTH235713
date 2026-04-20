<?php
/**
 * modules/cao_oc/index.php
 * Giao diện quản lý Danh sách Cao ốc - Hệ thống Quản lý Cao ốc
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Xác thực Session & Phân quyền (ADMIN, QUAN_LY_NHA)
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

$db = Database::getInstance()->getConnection();

// 2. TRUY VẤN DANH SÁCH CAO ỐC (Kèm số lượng phòng)
$sql = "
    SELECT c.*, 
        (SELECT COUNT(p.maPhong) 
         FROM TANG t 
         JOIN PHONG p ON t.maTang = p.maTang 
         WHERE t.maCaoOc = c.maCaoOc AND p.deleted_at IS NULL AND t.deleted_at IS NULL) as tongSoPhong
    FROM CAO_OC c
    WHERE c.deleted_at IS NULL
    ORDER BY c.tenCaoOc ASC
";
$dsCaoOc = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
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
        
        .card-main {
            width: 95%; 
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">

            <div class="card shadow-sm border-0 card-main">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0 text-navy fw-bold">
                        <i class="bi bi-buildings me-2"></i>DANH SÁCH CAO ỐC
                    </h2>
                    <a href="them.php" class="btn btn-gold shadow-sm px-4">
                        <i class="bi bi-plus-circle me-2"></i>Thêm Cao ốc Mới
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tblCaoOc" class="table table-hover align-middle table-navy table-datatable">
                            <thead>
                                <tr>
                                    <th width="120">MÃ</th>
                                    <th>Tên Cao ốc</th>
                                    <th>Địa chỉ</th>
                                    <th width="100" class="text-center">Số tầng</th>
                                    <th width="100" class="text-center">Phòng</th>
                                    <th width="150" class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dsCaoOc as $co): ?>
                                    <tr>
                                        <td class="fw-bold text-navy"><?= e($co['maCaoOc']) ?></td>
                                        <td class="fw-semibold"><?= e($co['tenCaoOc']) ?></td>
                                        <td><small class="text-muted"><?= e($co['diaChi']) ?></small></td>
                                        <td class="text-center"><?= e($co['soTang']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-navy border border-navy-subtle px-3">
                                                <?= (int)$co['tongSoPhong'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="sua.php?id=<?= e($co['maCaoOc']) ?>" class="action-link action-link--edit me-1" title="Sửa">
                                                <i class="bi bi-pencil-square"></i> Sửa
                                            </a>
                                            <a href="javascript:void(0)" onclick="xacNhanXoa('<?= e($co['maCaoOc']) ?>', '<?= e($co['tenCaoOc']) ?>')" class="action-link action-link--delete" title="Xóa">
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
                <p class="mb-0">Bạn có chắc chắn muốn xóa cao ốc <strong id="deleteBuildingName" class="text-danger"></strong>?</p>
                <p class="text-muted small mt-2">Hành động này sẽ thực hiện "Xóa mềm" bản ghi. Các dữ liệu liên quan sẽ tạm thời bị ẩn khỏi danh sách vận hành.</p>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Hủy</button>
                <a id="btnConfirmDelete" href="#" class="btn btn-danger px-4 fw-bold">Xác nhận xóa</a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->

<script>
$(document).ready(function() {
    $('#tblCaoOc').DataTable();
});

function xacNhanXoa(ma, ten) {
    document.getElementById('deleteBuildingName').innerText = ten;
    document.getElementById('btnConfirmDelete').href = 'xoa.php?id=' + encodeURIComponent(ma);
    var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    myModal.show();
}
</script>

</body>
</html>
