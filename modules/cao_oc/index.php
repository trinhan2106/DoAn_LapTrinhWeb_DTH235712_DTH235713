<?php
/**
 * modules/cao_oc/index.php
 * Trang danh sách cao ốc (Building Management List)
 * ==================================================================
 */

require_once '../../includes/common/auth.php';
require_once '../../includes/common/db.php';
require_once '../../includes/common/functions.php';

// 1. Bảo mật: Kiểm tra session và quyền (Admin=1, QLN=2)
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

$pdo = Database::getInstance()->getConnection();

// 2. Truy vấn danh sách cao ốc chưa bị xóa mềm, kèm số lượng phòng
$query = "
    SELECT 
        c.maCaoOc, 
        c.tenCaoOc, 
        c.diaChi, 
        c.soTang,
        (SELECT COUNT(*) 
         FROM TANG t 
         JOIN PHONG p ON t.maTang = p.maTang 
         WHERE t.maCaoOc = c.maCaoOc AND p.deleted_at IS NULL AND t.deleted_at IS NULL) as tongSoPhong
    FROM CAO_OC c
    WHERE c.deleted_at IS NULL
    ORDER BY c.maCaoOc DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $caoOcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}

// Cấu hình Header
$pageTitle = "Quản lý Cao ốc - Danh sách";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include_once '../../includes/admin/admin-header.php'; ?>
    <!-- DataTables BS5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .table-navy thead {
            background-color: #1e3a5f;
            color: #ffffff;
        }
        .btn-gold {
            background-color: #c9a66b;
            color: #ffffff;
            border: none;
        }
        .btn-gold:hover {
            background-color: #b38f5a;
            color: #ffffff;
        }
        .card-main {
            width: 85%; /* Mật độ 70-80% */
            margin: 0 auto;
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include_once '../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper">
        <?php include_once '../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4 card-main">
                    <h2 class="h4 mb-0 text-brand-primary fw-bold">
                        <i class="bi bi-building me-2"></i>QUẢN LÝ CAO ỐC
                    </h2>
                    <a href="them.php" class="btn btn-gold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Thêm mới Cao ốc
                    </a>
                </div>

                <div class="card card-brand card-main shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="tableCaoOc" class="table table-hover align-middle table-navy">
                                <thead>
                                    <tr>
                                        <th width="12%">Mã Cao ốc</th>
                                        <th width="25%">Tên Cao ốc</th>
                                        <th width="30%">Địa chỉ</th>
                                        <th width="10%" class="text-center">Số tầng</th>
                                        <th width="10%" class="text-center">Số phòng</th>
                                        <th width="13%" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($caoOcs as $co): ?>
                                    <tr>
                                        <td class="fw-bold text-navy"><?= e($co['maCaoOc']) ?></td>
                                        <td><?= e($co['tenCaoOc']) ?></td>
                                        <td><span class="text-muted small"><?= e($co['diaChi']) ?></span></td>
                                        <td class="text-center"><?= e($co['soTang']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-dark">
                                                <?= (int)$co['tongSoPhong'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="sua.php?id=<?= urlencode($co['maCaoOc']) ?>" 
                                                   class="btn btn-outline-primary" title="Chỉnh sửa">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="confirmDelete('<?= e($co['maCaoOc']) ?>', '<?= e($co['tenCaoOc']) ?>')"
                                                        title="Xóa">
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
            </div>
        </main>

        <?php include_once '../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tableCaoOc').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    function confirmDelete(id, name) {
        if (confirm(`Bạn có chắc chắn muốn xóa cao ốc "${name}" (Mã: ${id})?\nLưu ý: Thao tác này sẽ không xóa vật lý nhưng cao ốc sẽ không hiển thị trên danh sách.`)) {
            window.location.href = `xoa.php?id=${encodeURIComponent(id)}`;
        }
    }
</script>

</body>
</html>
