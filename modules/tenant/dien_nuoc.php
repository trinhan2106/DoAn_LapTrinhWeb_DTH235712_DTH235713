<?php
/**
 * modules/tenant/dien_nuoc.php
 * Lịch sử tiêu thụ Điện & Nước dành cho Khách hàng (Tenant Portal)
 * Phân quyền: ROLE_KHACH_HANG (4)
 *
 * Security: kiemTraSession(), kiemTraRole(), IDOR-protected JOIN, XSS-escaped output
 * Fix-06: Direct DB check for phai_doi_matkhau on every page load
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// =====================================================================
// P0 – BẢO MẬT: Xác thực phiên và quyền hạn
// =====================================================================
kiemTraSession();
kiemTraRole([ROLE_KHACH_HANG]);

$maKH      = $_SESSION['user_id'];      // maKH (không phải accountId)
$accountId = $_SESSION['accountId'] ?? null;
$tenKH     = $_SESSION['ten_user']  ?? 'Khách hàng';
$pdo       = Database::getInstance()->getConnection();

// Fix-06: Kiểm tra cờ phai_doi_matkhau trực tiếp từ CSDL mỗi lần tải trang
if ($accountId) {
    $stmtPDM = $pdo->prepare(
        "SELECT phai_doi_matkhau FROM KHACH_HANG_ACCOUNT
         WHERE accountId = ? AND deleted_at IS NULL LIMIT 1"
    );
    $stmtPDM->execute([$accountId]);
    $phai_doi = (int)($stmtPDM->fetchColumn() ?: 0);
    if ($phai_doi === 1) {
        header("Location: " . BASE_URL . "modules/ho_so/doi_mat_khau_batbuoc.php");
        exit();
    }
}

// =====================================================================
// BỘ LỌC: Đọc tham số GET (Server-side, IDOR-safe)
// =====================================================================
$filterRoom  = trim($_GET['maPhong']  ?? '');
$filterYear  = (int)($_GET['namGhi']  ?? 0);
$filterMonth = (int)($_GET['thangGhi'] ?? 0);

// Lấy danh sách phòng có dữ liệu điện nước của KH (cho dropdown)
$stmtRooms = $pdo->prepare("
    SELECT DISTINCT p.maPhong, p.tenPhong
    FROM PHONG p
    JOIN CHI_TIET_HOP_DONG ct ON ct.maPhong   = p.maPhong
    JOIN HOP_DONG hd          ON hd.soHopDong = ct.soHopDong
    JOIN CHI_SO_DIEN_NUOC cs  ON cs.maPhong   = p.maPhong
    WHERE hd.maKH = ? AND hd.deleted_at IS NULL AND cs.deleted_at IS NULL
    ORDER BY p.maPhong
");
$stmtRooms->execute([$maKH]);
$roomOptions = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách năm có dữ liệu điện nước của KH (cho dropdown)
$stmtYears = $pdo->prepare("
    SELECT DISTINCT cs.namGhi
    FROM CHI_SO_DIEN_NUOC cs
    JOIN CHI_TIET_HOP_DONG ct ON ct.maPhong   = cs.maPhong
    JOIN HOP_DONG hd          ON hd.soHopDong = ct.soHopDong
    WHERE hd.maKH = ? AND cs.deleted_at IS NULL
    ORDER BY cs.namGhi DESC
");
$stmtYears->execute([$maKH]);
$yearOptions = $stmtYears->fetchAll(PDO::FETCH_COLUMN);

// =====================================================================
// LOGIC DỮ LIỆU – Query đầy đủ (dùng cho KPI Cards + Chart.js)
// =====================================================================

/**
 * Truy vấn IDOR-safe (KHÔNG áp dụng bộ lọc)
 * CHI_SO_DIEN_NUOC -> CHI_TIET_HOP_DONG -> HOP_DONG (maKH = ?)
 */
$sqlHistory = "
    SELECT
        cs.maChiSo,
        cs.maPhong,
        cs.thangGhi,
        cs.namGhi,
        cs.chiSoDienCu,
        cs.chiSoDienMoi,
        cs.chiSoNuocCu,
        cs.chiSoNuocMoi,
        CAST(cs.chiSoDienMoi - cs.chiSoDienCu AS DECIMAL(15,2)) AS tieuThuDien,
        CAST(cs.chiSoNuocMoi - cs.chiSoNuocCu AS DECIMAL(15,2)) AS tieuThuNuoc,
        cs.thanhTienDien,
        cs.thanhTienNuoc,
        p.tenPhong
    FROM CHI_SO_DIEN_NUOC cs
    JOIN PHONG p              ON cs.maPhong    = p.maPhong
    JOIN CHI_TIET_HOP_DONG ct ON ct.maPhong    = cs.maPhong
    JOIN HOP_DONG hd          ON hd.soHopDong  = ct.soHopDong
    WHERE hd.maKH = ?
      AND hd.deleted_at IS NULL
      AND cs.deleted_at IS NULL
    ORDER BY cs.namGhi DESC, cs.thangGhi DESC
";

$stmtHist = $pdo->prepare($sqlHistory);
$stmtHist->execute([$maKH]);
$allRecords = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

// =====================================================================
// Query CÓ BỘ LỌC – chỉ dùng cho bảng chi tiết
// =====================================================================
$fConditions = ['hd.maKH = ?', 'hd.deleted_at IS NULL', 'cs.deleted_at IS NULL'];
$fParams     = [$maKH];

if ($filterRoom !== '') {
    $fConditions[] = 'cs.maPhong = ?';
    $fParams[]     = $filterRoom;
}
if ($filterYear > 0) {
    $fConditions[] = 'cs.namGhi = ?';
    $fParams[]     = $filterYear;
}
if ($filterMonth > 0) {
    $fConditions[] = 'cs.thangGhi = ?';
    $fParams[]     = $filterMonth;
}

$whereFilter = implode(' AND ', $fConditions);
$sqlFiltered = "
    SELECT
        cs.maChiSo, cs.maPhong, cs.thangGhi, cs.namGhi,
        cs.chiSoDienCu, cs.chiSoDienMoi, cs.chiSoNuocCu, cs.chiSoNuocMoi,
        CAST(cs.chiSoDienMoi - cs.chiSoDienCu AS DECIMAL(15,2)) AS tieuThuDien,
        CAST(cs.chiSoNuocMoi - cs.chiSoNuocCu AS DECIMAL(15,2)) AS tieuThuNuoc,
        cs.thanhTienDien, cs.thanhTienNuoc, p.tenPhong
    FROM CHI_SO_DIEN_NUOC cs
    JOIN PHONG p              ON cs.maPhong   = p.maPhong
    JOIN CHI_TIET_HOP_DONG ct ON ct.maPhong   = cs.maPhong
    JOIN HOP_DONG hd          ON hd.soHopDong = ct.soHopDong
    WHERE $whereFilter
    ORDER BY cs.namGhi DESC, cs.thangGhi DESC
";

$stmtFiltered = $pdo->prepare($sqlFiltered);
$stmtFiltered->execute($fParams);
$filteredRecords = $stmtFiltered->fetchAll(PDO::FETCH_ASSOC);
$isFiltered = ($filterRoom !== '' || $filterYear > 0 || $filterMonth > 0);

// =====================================================================
// Chuẩn bị dữ liệu JSON cho Chart.js (12 tháng gần nhất – độc nhất)
// =====================================================================
$chartLabels  = [];
$chartDien    = [];
$chartNuoc    = [];

// Tạo map: key = "namGhi-thangGhi" → tổng tiêu thụ (nhiều phòng cùng tháng)
$monthlyMap = [];
foreach ($allRecords as $row) {
    $key = $row['namGhi'] . '-' . str_pad($row['thangGhi'], 2, '0', STR_PAD_LEFT);
    if (!isset($monthlyMap[$key])) {
        $monthlyMap[$key] = ['dien' => 0.0, 'nuoc' => 0.0, 'label' => $row['thangGhi'] . '/' . $row['namGhi']];
    }
    $monthlyMap[$key]['dien'] += (float)$row['tieuThuDien'];
    $monthlyMap[$key]['nuoc'] += (float)$row['tieuThuNuoc'];
}

// Sắp xếp tháng tăng dần và lấy tối đa 12 tháng gần nhất
krsort($monthlyMap);                          // mới nhất lên đầu
$top12 = array_slice($monthlyMap, 0, 12, true);
krsort($top12);                               // đảo ngược để biểu đồ từ cũ → mới

foreach ($top12 as $map) {
    $chartLabels[] = $map['label'];
    $chartDien[]   = $map['dien'];
    $chartNuoc[]   = $map['nuoc'];
}

$chartLabelsJson = json_encode($chartLabels, JSON_UNESCAPED_UNICODE);
$chartDienJson   = json_encode($chartDien);
$chartNuocJson   = json_encode($chartNuoc);

// KPI: Tháng mới nhất (hàng đầu tiên sau ORDER BY DESC)
$latestDien  = 0.0;
$latestNuoc  = 0.0;
$latestLabel = null;
if (!empty($allRecords)) {
    $latestLabel = $allRecords[0]['thangGhi'] . '/' . $allRecords[0]['namGhi'];
    // Tổng tất cả phòng trong tháng mới nhất
    foreach ($allRecords as $r) {
        if ($r['thangGhi'] === $allRecords[0]['thangGhi'] && $r['namGhi'] === $allRecords[0]['namGhi']) {
            $latestDien += (float)$r['tieuThuDien'];
            $latestNuoc += (float)$r['tieuThuNuoc'];
        }
    }
}

$hasData = !empty($allRecords);

// =====================================================================
// RENDER HTML
// =====================================================================
include __DIR__ . '/../../includes/tenant/header.php';
?>

<style>
    /* ===================== DESIGN TOKENS ===================== */
    :root {
        --navy:  #1e3a5f;
        --gold:  #c9a66b;
        --gold2: #b08d55;
        --bg:    #f4f7f9;
    }

    body { background-color: var(--bg); }

    /* Utility classes */
    .text-navy  { color: var(--navy)  !important; }
    .bg-navy    { background-color: var(--navy) !important; }
    .border-gold{ border-color: var(--gold) !important; }
    .btn-gold   { background-color: var(--gold); color: #fff; border: none; font-weight: 600; }
    .btn-gold:hover { background-color: var(--gold2); color: #fff; }

    /* ===================== PAGE HEADER ===================== */
    .page-hero {
        background: linear-gradient(135deg, var(--navy) 0%, #2c5282 100%);
        color: #fff;
        padding: 2.2rem 0 2.5rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .page-hero::after {
        content: '';
        position: absolute; inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        pointer-events: none;
    }
    .page-hero h1 { position: relative; z-index: 1; }

    /* ===================== KPI CARDS ===================== */
    .kpi-card {
        background: #fff;
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: transform .25s, box-shadow .25s;
        overflow: hidden;
    }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
    .kpi-card .kpi-icon {
        width: 56px; height: 56px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.55rem; flex-shrink: 0;
    }
    .kpi-card .kpi-label {
        font-size: 0.72rem; letter-spacing: 0.08em;
        text-transform: uppercase; font-weight: 700;
    }
    .kpi-card .kpi-value { font-size: 1.85rem; font-weight: 800; line-height: 1.15; }
    .kpi-card .kpi-sub   { font-size: 0.8rem; }
    .kpi-stripe { height: 5px; }

    /* ===================== CHART CARD ===================== */
    .chart-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: none;
        overflow: hidden;
    }
    .chart-card .card-header {
        background: #fff;
        border-bottom: 2px solid var(--gold);
        padding: 1.1rem 1.5rem;
    }
    .chart-container { position: relative; height: 320px; }

    /* ===================== TABLE CARD ===================== */
    .table-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: none;
        overflow: hidden;
    }
    .table-card .card-header {
        background: #fff;
        border-bottom: 2px solid var(--gold);
        padding: 1.1rem 1.5rem;
    }
    .table thead th {
        background: #f8fafc;
        color: var(--navy);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border-bottom: 2px solid #eef2f7;
        white-space: nowrap;
        padding: 14px 16px;
    }
    .table tbody td { padding: 13px 16px; vertical-align: middle; border-color: #f0f4f9; white-space: nowrap; }
    .table tbody tr:hover { background: #f9fbfc; }
    .badge-room {
        background: #eef2fa; color: var(--navy);
        font-size: 0.78rem; border-radius: 6px; padding: 4px 10px;
        font-weight: 600;
    }

    /* ===================== DATATABLE OVERRIDES ===================== */
    div.dataTables_wrapper div.dataTables_filter input { border-radius: 8px; }
    div.dataTables_wrapper div.dataTables_length select { border-radius: 8px; }
    div.dataTables_paginate span .paginate_button.current,
    div.dataTables_paginate span .paginate_button.current:hover {
        background: var(--navy) !important;
        border-color: var(--navy) !important;
        color: #fff !important;
        border-radius: 6px;
    }

    /* ===================== FILTER BAR ===================== */
    .filter-bar {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e9eef5;
        padding: 1.2rem 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        margin-bottom: 1.5rem;
    }
    .filter-bar .filter-label {
        font-size: 0.72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.07em;
        color: #64748b; margin-bottom: 6px; display: block;
    }
    .filter-bar .form-select,
    .filter-bar .form-control {
        border-radius: 9px;
        border-color: #d9e2ec;
        font-size: 0.9rem;
        transition: border-color .2s, box-shadow .2s;
    }
    .filter-bar .form-select:focus,
    .filter-bar .form-control:focus {
        border-color: var(--navy);
        box-shadow: 0 0 0 3px rgba(30,58,95,.12);
    }
    .filter-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(201,166,107,.15); color: var(--navy);
        border: 1px solid rgba(201,166,107,.4);
        border-radius: 999px; padding: 4px 12px;
        font-size: 0.78rem; font-weight: 600; margin: 2px;
    }
    .filter-badge .btn-close { font-size: 0.6rem; }

    /* ===================== EMPTY STATE ===================== */
    .empty-state {
        padding: 4rem 2rem; text-align: center;
        background: #fff; border-radius: 16px;
    }
    .empty-state .empty-icon {
        font-size: 4rem; margin-bottom: 1rem;
        color: var(--gold); opacity: .7;
    }

    /* ===================== RESPONSIVE ===================== */
    @media (max-width: 767.98px) {
        .chart-container { height: 240px; }
        .kpi-value { font-size: 1.4rem !important; }
        table.dataTable thead th, table.dataTable tbody td { padding: 10px 10px; font-size: 0.82rem; }
    }
</style>

<!-- =====================================================================
     PAGE HERO BANNER
======================================================================= -->
<div class="page-hero mb-0">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color: rgba(255,255,255,.5);">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="text-warning text-decoration-none fw-semibold">Tổng quan</a>
                        </li>
                        <li class="breadcrumb-item active text-white-50">Điện &amp; Nước</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold mb-1">
                    <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Lịch sử tiêu thụ Điện &amp; Nước
                </h1>
                <p class="mb-0 opacity-75 small">Theo dõi chỉ số và xu hướng tiêu thụ theo từng tháng</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0" style="position: relative; z-index: 1;">
                <span class="badge bg-white text-dark px-3 py-2 rounded-pill shadow-sm fw-semibold">
                    <i class="bi bi-calendar3 me-2 text-primary"></i><?= date('d/m/Y') ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">

<?php if (!$hasData): ?>
    <!-- ===================== EMPTY STATE ===================== -->
    <div class="empty-state shadow-sm">
        <div class="empty-icon"><i class="bi bi-droplet-half"></i></div>
        <h4 class="fw-bold text-navy mb-2">Không có dữ liệu</h4>
        <div class="alert alert-info border-0 d-inline-block px-4 py-3 rounded-4 mt-1 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            Chưa có dữ liệu tiêu thụ cho các phòng đang thuê.
        </div>
        <p class="text-muted mt-3 small">Dữ liệu điện nước sẽ xuất hiện sau khi nhân viên ghi chỉ số hàng tháng.</p>
        <a href="dashboard.php" class="btn btn-navy mt-2 rounded-pill px-4" style="background:var(--navy);color:#fff;">
            <i class="bi bi-arrow-left me-2"></i>Về Trang chủ
        </a>
    </div>

<?php else: ?>
    <!-- ===================== KPI CARDS ===================== -->
    <div class="row g-4 mb-4">
        <!-- KPI Điện -->
        <div class="col-md-6">
            <div class="kpi-card shadow-sm">
                <div class="kpi-stripe" style="background: linear-gradient(90deg,#f8c118,#f6a800);"></div>
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="kpi-icon" style="background: rgba(248,193,24,0.15);">
                        <i class="bi bi-lightning-charge-fill" style="color:#f8c118;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="kpi-label text-muted">Tiêu thụ Điện – Tháng <?= e($latestLabel ?? '—') ?></div>
                        <div class="kpi-value text-navy">
                            <?= number_format($latestDien, 2, ',', '.') ?>
                            <small class="fs-6 fw-normal text-muted ms-1">kWh</small>
                        </div>
                        <div class="kpi-sub text-muted mt-1">
                            <i class="bi bi-arrow-up-right text-success"></i> Tổng tiêu thụ tất cả phòng trong tháng
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Nước -->
        <div class="col-md-6">
            <div class="kpi-card shadow-sm">
                <div class="kpi-stripe" style="background: linear-gradient(90deg,#36b4f5,#1a8dca);"></div>
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="kpi-icon" style="background: rgba(54,180,245,0.15);">
                        <i class="bi bi-droplet-fill" style="color:#36b4f5;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="kpi-label text-muted">Tiêu thụ Nước – Tháng <?= e($latestLabel ?? '—') ?></div>
                        <div class="kpi-value text-navy">
                            <?= number_format($latestNuoc, 2, ',', '.') ?>
                            <small class="fs-6 fw-normal text-muted ms-1">m³</small>
                        </div>
                        <div class="kpi-sub text-muted mt-1">
                            <i class="bi bi-arrow-up-right text-success"></i> Tổng tiêu thụ tất cả phòng trong tháng
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== BIỂU ĐỒ CHART.JS ===================== -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold text-navy">
                        <i class="bi bi-bar-chart-line me-2" style="color: var(--gold);"></i>
                        Xu hướng tiêu thụ Điện &amp; Nước (12 tháng gần nhất)
                    </h5>
                    <div class="d-flex gap-3 align-items-center small">
                        <span class="d-flex align-items-center gap-1">
                            <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#f8c118;"></span>
                            <span class="text-muted">Điện (kWh)</span>
                        </span>
                        <span class="d-flex align-items-center gap-1">
                            <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#36b4f5;"></span>
                            <span class="text-muted">Nước (m³)</span>
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container">
                        <canvas id="dienuocChart" aria-label="Biểu đồ tiêu thụ điện nước" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== BỘ LỌC CHI TIẾT ===================== -->
    <div class="filter-bar">
        <form method="GET" action="" id="filterForm">
            <div class="row g-3 align-items-end">
                <!-- Lọc theo phòng -->
                <div class="col-sm-6 col-lg-3">
                    <label class="filter-label"><i class="bi bi-door-open me-1"></i>Phòng</label>
                    <select name="maPhong" id="filterRoom" class="form-select">
                        <option value="">— Tất cả phòng —</option>
                        <?php foreach ($roomOptions as $room): ?>
                        <option value="<?= e($room['maPhong']) ?>"
                            <?= ($filterRoom === $room['maPhong']) ? 'selected' : '' ?>>
                            <?= e($room['maPhong']) ?> – <?= e($room['tenPhong']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Lọc theo năm -->
                <div class="col-sm-6 col-lg-3">
                    <label class="filter-label"><i class="bi bi-calendar-year me-1"></i>Năm</label>
                    <select name="namGhi" id="filterYear" class="form-select">
                        <option value="0">— Tất cả năm —</option>
                        <?php foreach ($yearOptions as $y): ?>
                        <option value="<?= (int)$y ?>" <?= ($filterYear === (int)$y) ? 'selected' : '' ?>>
                            <?= (int)$y ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Lọc theo tháng -->
                <div class="col-sm-6 col-lg-3">
                    <label class="filter-label"><i class="bi bi-calendar-month me-1"></i>Tháng</label>
                    <select name="thangGhi" id="filterMonth" class="form-select">
                        <option value="0">— Tất cả tháng —</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($filterMonth === $m) ? 'selected' : '' ?>>
                            Tháng <?= $m ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <!-- Nút thao tác -->
                <div class="col-sm-6 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-gold flex-grow-1 rounded-pill fw-semibold" id="btnFilter">
                        <i class="bi bi-funnel-fill me-1"></i>Lọc dữ liệu
                    </button>
                    <a href="dien_nuoc.php" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" title="Xóa bộ lọc">
                        <i class="bi bi-x-circle me-1"></i>Đặt lại
                    </a>
                </div>
                <!-- Badge hiển thị bộ lọc đang áp dụng -->
                <?php if ($isFiltered): ?>
                <div class="col-12">
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                        <small class="text-muted me-1">Đang lọc:</small>
                        <?php if ($filterRoom !== ''): ?>
                        <span class="filter-badge">
                            <i class="bi bi-door-open"></i>
                            Phòng: <?= e($filterRoom) ?>
                            <a href="?<?= http_build_query(array_filter(['namGhi' => $filterYear ?: null, 'thangGhi' => $filterMonth ?: null])) ?>" class="btn-close" title="Bỏ lọc phòng"></a>
                        </span>
                        <?php endif; ?>
                        <?php if ($filterYear > 0): ?>
                        <span class="filter-badge">
                            <i class="bi bi-calendar-year"></i>
                            Năm: <?= $filterYear ?>
                            <a href="?<?= http_build_query(array_filter(['maPhong' => $filterRoom ?: null, 'thangGhi' => $filterMonth ?: null])) ?>" class="btn-close" title="Bỏ lọc năm"></a>
                        </span>
                        <?php endif; ?>
                        <?php if ($filterMonth > 0): ?>
                        <span class="filter-badge">
                            <i class="bi bi-calendar-month"></i>
                            Tháng: <?= $filterMonth ?>
                            <a href="?<?= http_build_query(array_filter(['maPhong' => $filterRoom ?: null, 'namGhi' => $filterYear ?: null])) ?>" class="btn-close" title="Bỏ lọc tháng"></a>
                        </span>
                        <?php endif; ?>
                        <span class="text-muted small ms-2">
                            → Tìm thấy <strong><?= count($filteredRecords) ?></strong> bản ghi
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ===================== BẢNG CHI TIẾT – DataTables ===================== -->
    <div class="row g-4">
        <div class="col-12">
            <div class="table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold text-navy">
                        <i class="bi bi-table me-2" style="color: var(--gold);"></i>
                        Chi tiết chỉ số theo tháng
                        <?php if ($isFiltered): ?>
                        <span class="badge bg-warning text-dark ms-2" style="font-size:.72rem;">Đã lọc</span>
                        <?php endif; ?>
                    </h5>
                    <span class="badge rounded-pill" style="background:var(--gold);color:#fff;font-size:.78rem;padding:6px 14px;">
                        <?= count($filteredRecords) ?> bản ghi
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($filteredRecords)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                        Không tìm thấy dữ liệu phù hợp với bộ lọc đã chọn.
                        <br><a href="dien_nuoc.php" class="btn btn-sm btn-outline-secondary mt-3 rounded-pill px-4">Xóa bộ lọc</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table id="dienuocTable" class="table table-hover align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th class="ps-4">Phòng</th>
                                    <th>Tháng/Năm</th>
                                    <th class="text-end">CS Điện Cũ</th>
                                    <th class="text-end">CS Điện Mới</th>
                                    <th class="text-end">Tiêu thụ Điện</th>
                                    <th class="text-end">CS Nước Cũ</th>
                                    <th class="text-end">CS Nước Mới</th>
                                    <th class="text-end">Tiêu thụ Nước</th>
                                    <th class="text-end">Tiền Điện</th>
                                    <th class="text-end pe-4">Tiền Nước</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredRecords as $r): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge-room">
                                            <i class="bi bi-door-open me-1"></i><?= e($r['maPhong']) ?>
                                        </span>
                                        <div class="text-muted small mt-1"><?= e($r['tenPhong']) ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-navy"><?= e($r['thangGhi']) ?>/<?= e($r['namGhi']) ?></span>
                                    </td>
                                    <td class="text-end text-muted font-monospace">
                                        <?= number_format((float)$r['chiSoDienCu'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?= number_format((float)$r['chiSoDienMoi'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold" style="color:#c58e00;">
                                            <?= number_format((float)$r['tieuThuDien'], 2, ',', '.') ?>
                                            <small class="text-muted fw-normal"> kWh</small>
                                        </span>
                                    </td>
                                    <td class="text-end text-muted font-monospace">
                                        <?= number_format((float)$r['chiSoNuocCu'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?= number_format((float)$r['chiSoNuocMoi'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold" style="color:#1a8dca;">
                                            <?= number_format((float)$r['tieuThuNuoc'], 2, ',', '.') ?>
                                            <small class="text-muted fw-normal"> m³</small>
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold text-navy text-nowrap">
                                        <?= number_format((float)$r['thanhTienDien'], 0, ',', '.') ?> ₫
                                    </td>
                                    <td class="text-end fw-semibold text-navy pe-4 text-nowrap">
                                        <?= number_format((float)$r['thanhTienNuoc'], 0, ',', '.') ?> ₫
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold" style="background:#f8fafc;">
                                    <td colspan="8" class="ps-4 py-3">
                                        <i class="bi bi-sigma me-2 text-navy"></i>
                                        Tổng cộng <?= $isFiltered ? '(đã lọc)' : '' ?>
                                    </td>
                                    <td class="text-end py-3 text-navy text-nowrap">
                                        <?= number_format(array_sum(array_column($filteredRecords, 'thanhTienDien')), 0, ',', '.') ?> ₫
                                    </td>
                                    <td class="text-end py-3 text-navy pe-4 text-nowrap">
                                        <?= number_format(array_sum(array_column($filteredRecords, 'thanhTienNuoc')), 0, ',', '.') ?> ₫
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>
</div><!-- /.container -->

<!-- =====================================================================
     SCRIPTS: Chart.js + DataTables
======================================================================= -->
<!-- DataTables CSS (loaded before footer to avoid FOUC) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<?php include __DIR__ . '/../../includes/tenant/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
       1. CHART.JS – LINE CHART: Điện & Nước theo tháng
    ========================================================= */
    const ctxChart = document.getElementById('dienuocChart');
    if (ctxChart) {
        const labels = <?= $chartLabelsJson ?>;
        const dataDien = <?= $chartDienJson ?>;
        const dataNuoc = <?= $chartNuocJson ?>;

        new Chart(ctxChart, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Tiêu thụ Điện (kWh)',
                        data: dataDien,
                        borderColor: '#f8c118',
                        backgroundColor: 'rgba(248,193,24,0.10)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#f8c118',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.35,
                    },
                    {
                        label: 'Tiêu thụ Nước (m³)',
                        data: dataNuoc,
                        borderColor: '#36b4f5',
                        backgroundColor: 'rgba(54,180,245,0.10)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#36b4f5',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.35,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: false   /* dùng custom legend HTML ở trên */
                    },
                    tooltip: {
                        backgroundColor: '#1e3a5f',
                        titleColor: '#c9a66b',
                        bodyColor: '#fff',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(ctx) {
                                const unit = ctx.datasetIndex === 0 ? ' kWh' : ' m³';
                                return ' ' + ctx.dataset.label.split('(')[0].trim()
                                     + ': ' + ctx.parsed.y.toLocaleString('vi-VN', {minimumFractionDigits: 2}) + unit;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 12 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: {
                            font: { size: 12 }, color: '#64748b',
                            callback: v => v.toLocaleString('vi-VN')
                        }
                    }
                }
            }
        });
    }

    /* =========================================================
       2. DATATABLE – Chi tiết chỉ số
    ========================================================= */
    if ($.fn.DataTable && document.getElementById('dienuocTable')) {
        $('#dienuocTable').DataTable({
            responsive: true,
            pageLength: 12,
            dom: '<"row mb-3"<"col-sm-6"l><"col-sm-6"f>>t<"row mt-3"<"col-sm-6"i><"col-sm-6"p>>',
            order: [[1, 'desc']],      // Sắp xếp theo Tháng/Năm giảm dần
            language: {
                search:         '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Tìm kiếm…',
                lengthMenu:     'Hiển thị _MENU_ dòng',
                info:           'Đang xem _START_–_END_ / _TOTAL_ bản ghi',
                infoEmpty:      'Không có dữ liệu',
                infoFiltered:   '(lọc từ _MAX_ bản ghi)',
                zeroRecords:    'Không tìm thấy dữ liệu phù hợp',
                paginate: {
                    next:     '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                },
                emptyTable:     'Không có dữ liệu trong bảng'
            },
            columnDefs: [
                { targets: [2,3,4,5,6,7,8,9], className: 'dt-right' }
            ]
        });
    }

}); /* end DOMContentLoaded */
</script>
