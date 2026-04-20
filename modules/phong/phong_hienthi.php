<?php
/**
 * modules/phong/phong_hienthi.php
 * Trang danh sÃ¡ch PhÃ²ng - TÃ­ch há»£p bá»™ lá»c Cao á»‘c/Táº§ng
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

// Láº¥y danh sÃ¡ch Cao á»‘c vÃ  Táº§ng Ä‘á»ƒ phá»¥c vá»¥ bá»™ lá»c
$dsCaoOc = $db->query("SELECT maCaoOc, tenCaoOc FROM CAO_OC WHERE deleted_at IS NULL ORDER BY tenCaoOc")->fetchAll();
$dsTang = $db->query("SELECT maTang, tenTang, maCaoOc FROM TANG WHERE deleted_at IS NULL ORDER BY tenTang")->fetchAll();

// Truy váº¥n danh sÃ¡ch phÃ²ng
$sql = "
    SELECT 
        p.*, t.tenTang, c.tenCaoOc, c.maCaoOc
    FROM PHONG p
    JOIN TANG t ON p.maTang = t.maTang
    JOIN CAO_OC c ON t.maCaoOc = c.maCaoOc
    WHERE p.deleted_at IS NULL
    ORDER BY c.tenCaoOc, t.tenTang, p.maPhong
";
$dsPhong = $db->query($sql)->fetchAll();
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
        .text-navy { color: #1e3a5f !important; }
        .btn-gold {
            background-color: #c9a66b !important;
            color: #ffffff !important;
            border: none;
            transition: all 0.3s;
        }
        .btn-gold:hover { background-color: #b5925a; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        
        /* Status Badges Custom */
        .badge-status--1 { background-color: #27ae60; color: #fff; } /* Trá»‘ng */
        .badge-status--2 { background-color: #1e3a5f; color: #fff; } /* ÄÃ£ thuÃª */
        .badge-status--3 { background-color: #f39c12; color: #fff; } /* Sá»­a chá»¯a */
        .badge-status--4 { background-color: #e74c3c; color: #fff; } /* Lock */
        
        .filter-section {
            background-color: #fff;
            border-radius: 10px;
            border-left: 4px solid #c9a66b;
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
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Quáº£n lÃ½ PhÃ²ng</li>
                </ol>
            </nav>

            <!-- Bá»™ lá»c & Header -->
            <div class="card filter-section shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-4">
                            <h2 class="h4 mb-0 text-navy fw-bold">
                                <i class="bi bi-grid-3x3-gap me-2"></i>QUáº¢N LÃ PHÃ’NG
                            </h2>
                        </div>
                        <div class="col-md-3">
                            <select id="filterCaoOc" class="form-select border-navy-subtle">
                                <option value="">-- Táº¥t cáº£ Cao á»‘c --</option>
                                <?php foreach ($dsCaoOc as $co): ?>
                                    <option value="<?= e($co['tenCaoOc']) ?>"><?= e($co['tenCaoOc']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterTang" class="form-select border-navy-subtle">
                                <option value="">-- Táº¥t cáº£ Táº§ng --</option>
                                <?php foreach ($dsTang as $t): ?>
                                    <option value="<?= e($t['tenTang']) ?>" data-caooc="<?= e($t['maCaoOc']) ?>"><?= e($t['tenTang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <a href="phong_them.php" class="btn btn-gold w-100 fw-bold">
                                <i class="bi bi-plus-lg me-1"></i> ThÃªm PhÃ²ng
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
                                    <th>MÃ£ PhÃ²ng</th>
                                    <th>TÃªn PhÃ²ng</th>
                                    <th>TÃ²a nhÃ  / Táº§ng</th>
                                    <th class="text-end">Diá»‡n tÃ­ch</th>
                                    <th class="text-end">GiÃ¡ thuÃª/thÃ¡ng</th>
                                    <th class="text-center">Tráº¡ng thÃ¡i</th>
                                    <th class="text-center">Thao tÃ¡c</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dsPhong as $p): 
                                    $statusNames = [1 => 'Trá»‘ng', 2 => 'ÄÃ£ thuÃª', 3 => 'Báº£o trÃ¬', 4 => 'ÄÃ£ khÃ³a'];
                                ?>
                                    <tr>
                                        <td class="fw-bold text-navy"><?= e($p['maPhong']) ?></td>
                                        <td class="fw-semibold"><?= e($p['tenPhong']) ?></td>
                                        <td>
                                            <div class="small fw-bold text-navy"><?= e($p['tenCaoOc']) ?></div>
                                            <div class="small text-muted"><?= e($p['tenTang']) ?></div>
                                        </td>
                                        <td class="text-end"><?= number_format($p['dienTich'], 1) ?> mÂ²</td>
                                        <td class="text-end fw-bold text-navy"><?= formatTien($p['giaThue']) ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-status--<?= $p['trangThai'] ?> px-3 py-2">
                                                <?= $statusNames[$p['trangThai']] ?? 'N/A' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="phong_sua.php?id=<?= e($p['maPhong']) ?>" class="btn btn-outline-primary" title="Sá»­a">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="javascript:void(0)" onclick="xacNhanXoa('<?= e($p['maPhong']) ?>', '<?= e($p['tenPhong']) ?>')" class="btn btn-outline-danger" title="XÃ³a">
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

<!-- Modal xÃ¡c nháº­n xÃ³a -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>XÃ¡c nháº­n xÃ³a</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                Báº¡n cÃ³ cháº¯c cháº¯n muá»‘n xÃ³a phÃ²ng <strong id="deleteRoomName" class="text-danger"></strong>?
                <br><small class="text-muted">Há»‡ thá»‘ng sáº½ kiá»ƒm tra há»£p Ä‘á»“ng hiá»‡u lá»±c trÆ°á»›c khi cho phÃ©p xÃ³a má»m.</small>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Há»§y</button>
                <a id="btnConfirmDelete" href="#" class="btn btn-danger px-4">Äá»“ng Ã½ XÃ³a</a>
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
    var table = $('#tblPhong').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json" },
        "order": [[2, "asc"], [0, "asc"]],
        "pageLength": 10
    });

    // Lá»c theo Cao á»‘c (Cá»™t 2 - index 2)
    $('#filterCaoOc').on('change', function() {
        table.column(2).search(this.value).draw();
    });

    // Lá»c theo Táº§ng (Cá»™t 2 - index 2 - search partial)
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
