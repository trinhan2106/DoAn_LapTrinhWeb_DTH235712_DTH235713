<?php
/**
 * modules/cao_oc/index.php
 * Trang danh sÃ¡ch Cao á»‘c - Thiáº¿t káº¿ Ä‘á»“ng bá»™ vá»›i module Táº§ng
 */

// 1. KHá»žI Táº O & Báº¢O Máº¬T
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// XÃ¡c thá»±c Session & Role
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Káº¿t ná»‘i CSDL
$db = Database::getInstance()->getConnection();

// Truy váº¥n danh sÃ¡ch cao á»‘c kÃ¨m sá»‘ lÆ°á»£ng phÃ²ng hiá»‡n cÃ³ (chÆ°a xÃ³a)
$sql = " 
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
    ORDER BY c.tenCaoOc ASC
";
$dsCaoOc = $db->query($sql)->fetchAll();
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
        
        .card-main {
            width: 90%; /* Máº­t Ä‘á»™ cao */
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
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4 card-main">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quáº£n lÃ½ Cao á»‘c</li>
                </ol>
            </nav>

            <div class="card shadow-sm border-0 card-main">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0 text-navy fw-bold">
                        <i class="bi bi-buildings me-2"></i>DANH SÃCH CAO á»C
                    </h2>
                    <a href="them.php" class="btn btn-gold shadow-sm px-4">
                        <i class="bi bi-plus-circle me-2"></i>ThÃªm Cao á»c Má»›i
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tblCaoOc" class="table table-hover align-middle table-navy">
                            <thead>
                                <tr>
                                    <th width="120">MÃ£</th>
                                    <th>TÃªn Cao á»‘c</th>
                                    <th>Äá»‹a chá»‰</th>
                                    <th width="100" class="text-center">Sá»‘ táº§ng</th>
                                    <th width="100" class="text-center">PhÃ²ng</th>
                                    <th width="150" class="text-center">Thao tÃ¡c</th>
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
                                            <a href="sua.php?id=<?= e($co['maCaoOc']) ?>" class="action-link action-link--edit me-1" title="Sá»­a">
                                                <i class="bi bi-pencil-square"></i> Sá»­a
                                            </a>
                                            <a href="javascript:void(0)" onclick="xacNhanXoa('<?= e($co['maCaoOc']) ?>', '<?= e($co['tenCaoOc']) ?>')" class="action-link action-link--delete" title="XÃ³a">
                                                <i class="bi bi-trash"></i> XÃ³a
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

<!-- Modal xÃ¡c nháº­n xÃ³a -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>XÃ¡c nháº­n xÃ³a</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="mb-0">Báº¡n cÃ³ cháº¯c cháº¯n muá»‘n xÃ³a cao á»‘c <strong id="deleteBuildingName" class="text-danger"></strong>?</p>
                <p class="text-muted small mt-2">HÃ nh Ä‘á»™ng nÃ y sáº½ thá»±c hiá»‡n "XÃ³a má»m" báº£n ghi. CÃ¡c dá»¯ liá»‡u liÃªn quan sáº½ táº¡m thá»i bá»‹ áº©n khá»i danh sÃ¡ch váº­n hÃ nh.</p>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Há»§y</button>
                <a id="btnConfirmDelete" href="#" class="btn btn-danger px-4 fw-bold">XÃ¡c nháº­n xÃ³a</a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tblCaoOc').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json"
        },
        "order": [[1, "asc"]],
        "pageLength": 10
    });
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
