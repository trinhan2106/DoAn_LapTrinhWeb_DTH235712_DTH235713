<?php
/**
 * modules/tenant/hoa_don.php
 * Quản lý hóa đơn dành cho Khách hàng (Tenant)
 * Phân quyền: ROLE_KHACH_HANG (4)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// P0: Bảo mật - Kiểm tra quyền truy cập
kiemTraSession();
kiemTraRole([ROLE_KHACH_HANG]);

$maKH = $_SESSION['user_id'];
$pdo = Database::getInstance()->getConnection();

// --- XỬ LÝ BỘ LỌC & TÌM KIẾM ---
$searchTerm = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

// --- LOGIC TRUY VẤN DỮ LIỆU ---

// 1. Tính tổng nợ còn lại (Fix-01) - Không bị ảnh hưởng bởi bộ lọc tìm kiếm để khách luôn thấy tổng nợ thực tế
$sqlDebt = "SELECT SUM(hd.soTienConNo) as tong_no 
            FROM HOA_DON hd 
            JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong 
            WHERE h.maKH = ? AND hd.trangThai IN ('ConNo', 'DaThuMotPhan') AND hd.deleted_at IS NULL";
$stmtDebt = $pdo->prepare($sqlDebt);
$stmtDebt->execute([$maKH]);
$tongNo = (float)$stmtDebt->fetchColumn();

// 2. Lấy hóa đơn mới nhất chưa đóng
$sqlLatest = "SELECT hd.* 
              FROM HOA_DON hd 
              JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong 
              WHERE h.maKH = ? AND hd.trangThai IN ('ConNo', 'DaThuMotPhan') AND hd.deleted_at IS NULL
              ORDER BY hd.nam DESC, hd.thang DESC LIMIT 1";
$stmtLatest = $pdo->prepare($sqlLatest);
$stmtLatest->execute([$maKH]);
$latestInvoice = $stmtLatest->fetch(PDO::FETCH_ASSOC);

// 3. Lấy danh sách hóa đơn toàn bộ (Có áp dụng bộ lọc & tìm kiếm)
$conditions = ["h.maKH = ?", "hd.deleted_at IS NULL"];
$params = [$maKH];

if (!empty($searchTerm)) {
    $conditions[] = "hd.soHoaDon LIKE ?";
    $params[] = "%$searchTerm%";
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'Huy') {
        $conditions[] = "hd.trangThai IN ('Huy', 'Void')";
    } else {
        $conditions[] = "hd.trangThai = ?";
        $params[] = $statusFilter;
    }
}

$whereClause = implode(" AND ", $conditions);
$sqlList = "SELECT hd.*, h.maKH 
            FROM HOA_DON hd 
            JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong 
            WHERE $whereClause 
            ORDER BY hd.nam DESC, hd.thang DESC";

$stmtList = $pdo->prepare($sqlList);
$stmtList->execute($params);
$hoaDonList = $stmtList->fetchAll(PDO::FETCH_ASSOC);

// Giao diện
include __DIR__ . '/../../includes/tenant/header.php';
?>

<style>
    :root {
        --navy-primary: #1e3a5f;
        --gold-accent: #c9a66b;
    }
    .text-navy { color: var(--navy-primary); }
    .bg-navy { background-color: var(--navy-primary) !important; color: white !important; }
    .border-gold { border-color: var(--gold-accent) !important; }
    .btn-gold { background-color: var(--gold-accent); color: white; border: none; }
    .btn-gold:hover { background-color: #b08d55; color: white; }
    
    .card-summary {
        border: none;
        border-right: 4px solid var(--gold-accent);
        transition: transform 0.2s;
        border-radius: 12px;
    }
    .card-summary:hover { transform: translateY(-5px); }
    
    .status-badge { font-weight: 500; font-size: 0.85rem; }
    
    .filter-bar {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
    }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-0">Quản lý Hóa đơn</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Tổng quan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Hóa đơn</li>
                </ol>
            </nav>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark p-2 border rounded-pill">Hôm nay: <?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- Tóm tắt (Top Cards) -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card card-summary shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                        <i class="bi bi-wallet2 text-danger fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold ls-1">Tổng nợ còn lại</small>
                        <h3 class="fw-bold text-navy mb-0"><?= formatTien($tongNo) ?> ₫</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-summary shadow-sm h-100" style="border-right-color: var(--navy-primary);">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-clock-history text-warning fs-3"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold ls-1">Hóa đơn mới nhất (Chưa đóng)</small>
                        <?php if ($latestInvoice): ?>
                            <h3 class="fw-bold text-navy mb-0"><?= e($latestInvoice['soHoaDon']) ?></h3>
                            <small class="text-danger">Kỳ <?= e($latestInvoice['kyThanhToan']) ?> - <?= formatTien($latestInvoice['soTienConNo']) ?> ₫</small>
                        <?php else: ?>
                            <h4 class="fw-bold text-success mb-0">Hoàn thành nghĩa vụ</h4>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc & Tìm kiếm -->
    <div class="filter-bar shadow-sm">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Tìm kiếm hóa đơn</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Nhập số hóa đơn..." value="<?= e($searchTerm) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ConNo" <?= $statusFilter === 'ConNo' ? 'selected' : '' ?>>Còn nợ</option>
                    <option value="DaThuMotPhan" <?= $statusFilter === 'DaThuMotPhan' ? 'selected' : '' ?>>Thu một phần</option>
                    <option value="DaThu" <?= $statusFilter === 'DaThu' ? 'selected' : '' ?>>Đã thu</option>
                    <option value="Huy" <?= $statusFilter === 'Huy' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-navy flex-grow-1 rounded-pill bg-navy">
                    <i class="bi bi-funnel me-2"></i>Lọc dữ liệu
                </button>
                <a href="hoa_don.php" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Làm mới
                </a>
            </div>
        </form>
    </div>

    <!-- Bảng dữ liệu hóa đơn -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom border-gold">
            <h5 class="mb-0 fw-bold text-navy"><i class="bi bi-list-ul me-2"></i>Lịch sử Hóa đơn</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Số Hóa Đơn</th>
                            <th class="py-3">Kỳ Thanh Toán</th>
                            <th class="py-3">Loại</th>
                            <th class="text-end py-3">Tổng Tiền</th>
                            <th class="text-end py-3">Đã Nộp</th>
                            <th class="text-end py-3">Còn Nợ</th>
                            <th class="text-center py-3">Trạng Thái</th>
                            <th class="text-center pe-4 py-3">Khiếu nại</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hoaDonList)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="opacity-50">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Không tìm thấy dữ liệu hóa đơn nào phù hợp.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($hoaDonList as $row): 
                                $statusClass = 'secondary';
                                $statusText = $row['trangThai'];
                                
                                switch($row['trangThai']) {
                                    case 'ConNo': 
                                        $statusClass = 'danger'; $statusText = 'Còn nợ'; break;
                                    case 'DaThuMotPhan': 
                                        $statusClass = 'warning'; $statusText = 'Thu một phần'; break;
                                    case 'DaThu': 
                                        $statusClass = 'success'; $statusText = 'Đã thu'; break;
                                    case 'Void': 
                                    case 'Huy': 
                                        $statusClass = 'secondary'; $statusText = 'Đã hủy'; break;
                                }
                            ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-navy"><?= e($row['soHoaDon']) ?></td>
                                    <td><?= e($row['kyThanhToan']) ?></td>
                                    <td>
                                        <span class="badge border text-dark bg-white font-monospace">
                                            <?= $row['loaiHoaDon'] === 'CreditNote' ? 'Credit Note' : 'Chính' ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold"><?= formatTien($row['tongTien']) ?> ₫</td>
                                    <td class="text-end text-success"><?= formatTien($row['soTienDaNop']) ?> ₫</td>
                                    <td class="text-end text-danger"><?= formatTien($row['soTienConNo']) ?> ₫</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-<?= $statusClass ?> status-badge">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <?php if (!in_array($row['trangThai'], ['Void', 'Huy'])): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger rounded-pill px-3 dispute-btn" 
                                                    data-id="<?= e($row['soHoaDon']) ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#disputeModal">
                                                <i class="bi bi-exclamation-octagon me-1"></i>Khiếu nại
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tranh Chấp (Dispute) -->
<div class="modal fade" id="disputeModal" tabindex="-1" aria-labelledby="disputeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="tranh_chap_submit.php" method="POST">
                <div class="modal-header bg-navy border-0 py-3">
                    <h5 class="modal-title text-white fw-bold" id="disputeModalLabel"><i class="bi bi-send-exclamation me-2"></i>Khiếu nại hóa đơn</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- CSRF Protection -->
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ls-1">Mã hóa đơn</label>
                        <input type="text" name="maHoaDon" id="modalMaHoaDon" class="form-control border-0 bg-light fw-bold" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small text-uppercase ls-1">Lý do khiếu nại <span class="text-danger">*</span></label>
                        <textarea name="noiDung" class="form-control border-opacity-50" rows="5" placeholder="Vui lòng mô tả chi tiết vấn đề bạn gặp phải..." required></textarea>
                    </div>
                    <div class="alert alert-warning py-2 small border-0 bg-warning bg-opacity-10 text-dark">
                        <i class="bi bi-info-circle me-2"></i>Yêu cầu của bạn sẽ được Bộ phận Kế toán tiếp nhận và phản hồi sớm nhất.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-navy rounded-pill px-4 fw-bold shadow-sm">Gửi khiếu nại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const disputeBtns = document.querySelectorAll('.dispute-btn');
    const modalInput = document.getElementById('modalMaHoaDon');
    
    disputeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modalInput.value = this.getAttribute('data-id');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/tenant/footer.php'; ?>
