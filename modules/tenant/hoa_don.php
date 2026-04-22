<?php
require_once __DIR__ . '/../../config/app.php';
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
require_once __DIR__ . '/../../includes/common/jwt_helper.php';

// Task 9.2: SECRET_KEY đã được định nghĩa trong config/app.php


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
            WHERE h.maKH = ? AND hd.trangThai = 'ConNo' AND hd.loaiHoaDon = 'Chinh' AND hd.deleted_at IS NULL";
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

// --- Task 9.2: Tạo QR Token cho Hóa đơn mới nhất (Đã nâng cấp SapphireAuth) ---
$qrUrl = "";
if ($latestInvoice) {
    $payload = [
        'iat' => time(),
        'exp' => time() + 900,
        'data' => [
            'type' => 'invoice',
            'id' => $latestInvoice['soHoaDon'],
            'maKH' => $maKH
        ]
    ];
    $qrToken = SapphireAuth::encode($payload, JWT_SECRET_KEY);
    $qrUrl = BASE_URL . "modules/tenant_portal/index.php?token=" . $qrToken;
}


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

// 4. Lấy danh sách các khiếu nại (Tranh chấp) đã gửi để hiển thị (Fix-UI: Theo dõi tiến độ)
$sqlDisputes = "SELECT tc.*, hd.kyThanhToan, hd.tongTien
                FROM TRANH_CHAP_HOA_DON tc
                JOIN HOA_DON hd ON tc.maHoaDon = hd.soHoaDon
                JOIN HOP_DONG hp ON hd.soHopDong = hp.soHopDong
                WHERE hp.maKH = ? AND hp.deleted_at IS NULL
                ORDER BY tc.ngayTao DESC";
$stmtD = $pdo->prepare($sqlDisputes);
$stmtD->execute([$maKH]);
$myDisputes = $stmtD->fetchAll(PDO::FETCH_ASSOC);

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
                            
                            <!-- Task 9.2: Vùng hiển thị QR Code -->
                            <div class="mt-3 text-center border-top pt-3">
                                <div id="qrCode-Invoice" class="d-inline-block"></div>
                                <small class="text-danger d-block mt-2 fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-shield-lock me-1"></i>Mã QR xác thực (Hết hạn sau 15 phút)
                                </small>
                            </div>
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
                                            <a href="tranh_chap.php?soHoaDon=<?= e($row['soHoaDon']) ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                <i class="bi bi-exclamation-octagon me-1"></i>Khiếu nại
                                            </a>
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

    <!-- Danh sách Khiếu nại (Theo dõi tiến độ) -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mt-5">
        <div class="card-header bg-white py-3 border-bottom border-danger">
            <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-chat-dots me-2"></i>Khiếu nại & Yêu cầu kiểm tra lại</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Mã đơn</th>
                            <th class="py-3">Hóa đơn / Kỳ</th>
                            <th class="py-3">Nội dung khiếu nại</th>
                            <th class="py-3">Ngày gửi</th>
                            <th class="text-center pe-4 py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myDisputes)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Bạn chưa có khiếu nại nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($myDisputes as $tc): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-navy"><?= e($tc['id']) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= e($tc['maHoaDon']) ?></div>
                                        <div class="small text-muted">Kỳ: <?= e($tc['kyThanhToan']) ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="<?= e($tc['noiDung']) ?>">
                                            <?= e($tc['noiDung']) ?>
                                        </div>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($tc['ngayTao']) ) ?></td>
                                    <td class="text-center pe-4">
                                        <?php 
                                            $badge = match((int)$tc['trangThai']){
                                                0 => ['bg-warning text-dark', 'Mới tạo'],
                                                1 => ['bg-info text-dark', 'Đang xử lý'],
                                                2 => ['bg-success', 'Hoàn thành'],
                                                3 => ['bg-danger', 'Đã bác bỏ'],
                                                default => ['bg-secondary', 'N/A']
                                            };
                                        ?>
                                        <span class="badge rounded-pill <?= $badge[0] ?> px-3"><?= $badge[1] ?></span>
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



<?php include __DIR__ . '/../../includes/tenant/footer.php'; ?>

<!-- Scripts for QR Code (Task 9.2) -->
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/qrcode-init.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if ($qrUrl): ?>
            generateSecureQR("qrCode-Invoice", "<?= $qrUrl ?>");
        <?php endif; ?>
    });
</script>

