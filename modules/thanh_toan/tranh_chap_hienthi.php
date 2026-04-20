<?php
/**
 * modules/thanh_toan/tranh_chap_hienthi.php
 * Giao diện Xử lý khiếu nại/tranh chấp hóa đơn cho Kế toán
 * Tuân thủ TUYỆT ĐỐI cấu trúc Database gốc.
 */
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// 1. Bảo mật cấp Route
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN]);

$pdo = Database::getInstance()->getConnection();

// 2. Xử lý bộ lọc trạng thái
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$whereClause = "WHERE h.deleted_at IS NULL";
$params = [];

if ($filterStatus !== '') {
    $whereClause .= " AND tc.trangThai = ?";
    $params[] = (int)$filterStatus;
}

// 3. Truy vấn JOIN (Schema gốc - TRANH_CHAP_HOA_DON join HOA_DON qua maHoaDon)
$sql = "
    SELECT 
        tc.id, 
        tc.maHoaDon, 
        tc.noiDung, 
        tc.trangThai, 
        tc.ngayTao,
        h.soHoaDon,
        kh.tenKH
    FROM TRANH_CHAP_HOA_DON tc
    JOIN HOA_DON h ON tc.maHoaDon = h.soHoaDon
    JOIN HOP_DONG hd ON h.soHopDong = hd.soHopDong
    JOIN KHACH_HANG kh ON hd.maKH = kh.maKH
    $whereClause
    ORDER BY tc.ngayTao DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$disputes = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require_once __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --brand-navy: #1e3a5f;
            --brand-gold: #c9a66b;
        }
        .text-navy { color: var(--brand-navy) !important; }
        .bg-navy { background-color: var(--brand-navy) !important; }
        .table-navy thead th {
            background-color: var(--brand-navy) !important;
            color: #ffffff !important;
        }
        .btn-gold {
            background-color: var(--brand-gold) !important;
            color: #ffffff !important;
            border: none;
        }
        .btn-gold:hover { background-color: #b5925a; color: white; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 500; font-size: 0.85rem; }
        .filter-card { border-left: 4px solid var(--brand-gold); }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php require_once __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">

            <div class="card filter-card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="h4 mb-0 text-navy fw-bold">
                                <i class="bi bi-shield-exclamation me-2"></i>QUẢN LÝ TRANH CHẤP HÓA ĐƠN
                            </h2>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" class="d-flex justify-content-md-end">
                                <select name="status" class="form-select w-auto" onchange="this.form.submit()">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="0" <?= $filterStatus === '0' ? 'selected' : '' ?>>Mới tạo</option>
                                    <option value="1" <?= $filterStatus === '1' ? 'selected' : '' ?>>Đang xử lý</option>
                                    <option value="2" <?= $filterStatus === '2' ? 'selected' : '' ?>>Đã giải quyết</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tblTranhChap" class="table table-hover table-navy align-middle w-100 border">
                            <thead>
                                <tr>
                                    <th>ID Ticket</th>
                                    <th>Số Hóa Đơn</th>
                                    <th>Khách Hàng</th>
                                    <th>Ngày Gửi</th>
                                    <th>Trạng Thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($disputes as $tc): ?>
                                    <tr>
                                        <td class="fw-bold text-navy"><?= e($tc['id']) ?></td>
                                        <td><?= e($tc['maHoaDon']) ?></td>
                                        <td><?= e($tc['tenKH']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($tc['ngayTao'])) ?></td>
                                        <td>
                                            <?php if ($tc['trangThai'] == 0): ?>
                                                <span class="badge bg-danger status-badge">Mới</span>
                                            <?php elseif ($tc['trangThai'] == 1): ?>
                                                <span class="badge bg-warning text-dark status-badge">Đang xử lý</span>
                                            <?php else: ?>
                                                <span class="badge bg-success status-badge">Đã giải quyết</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-gold btn-sm px-3 fw-bold" 
                                                    onclick="moModalXuLy('<?= e($tc['id']) ?>', '<?= e($tc['maHoaDon']) ?>', '<?= e($tc['tenKH']) ?>', '<?= e($tc['noiDung']) ?>', '<?= e($tc['trangThai']) ?>')">
                                                Xử lý
                                            </button>
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

<!-- Modal Xu Ly -->
<div class="modal fade" id="modalXuLy" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="tranh_chap_xuly_submit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <input type="hidden" name="id" id="modal_id">
                
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title">Cập nhật quy trình xử lý tranh chấp</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">NỘI DUNG KHIẾU NẠI</label>
                        <div class="p-3 bg-light rounded border shadow-sm" id="modal_noiDung"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Trạng thái mới</label>
                        <select name="trangThai" id="modal_trangThai" class="form-select shadow-sm">
                            <option value="0">Mới tạo</option>
                            <option value="1">Đang xử lý</option>
                            <option value="2">Đã giải quyết (Đóng ticket)</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Ghi chú hướng giải quyết (Lưu Audit Log)</label>
                        <textarea name="ghiChu" class="form-control shadow-sm" rows="4" placeholder="Nhập chi tiết hướng xử lý..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-gold px-4 fw-bold">Lưu cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
function moModalXuLy(id, soHD, tenKH, noiDung, trangThai) {
    $('#modal_id').val(id);
    $('#modal_noiDung').text(noiDung);
    $('#modal_trangThai').val(trangThai);
    new bootstrap.Modal(document.getElementById('modalXuLy')).show();
}
</script>

</body>
</html>
