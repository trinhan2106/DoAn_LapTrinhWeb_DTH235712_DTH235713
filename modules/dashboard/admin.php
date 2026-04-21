<?php
/**
 * modules/dashboard/admin.php
 * Trang Admin Dashboard - Tổng quan vận hành
 * Tuân thủ chuẩn 28 bảng và hệ màu thương hiệu Navy/Gold
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Xác thực Session & Role (Admin=1, Quản lý nhà=2, Kế toán=3 được xem tổng quan)
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA, ROLE_KE_TOAN]);

// Kết nối CSDL Singleton PDO
$db = Database::getInstance()->getConnection();

// ============================================================================
// 2. TRUY VẤN DỮ LIỆU KPI (CHỈ SỐ NHANH)
// ============================================================================

// A. Thống kê Phòng (Tổng, Trống, Đang thuê)
$sqlRoomKPI = "
    SELECT 
        COUNT(*) as tong_phong,
        SUM(CASE WHEN trangThai = 1 THEN 1 ELSE 0 END) as phong_trong,
        SUM(CASE WHEN trangThai = 2 THEN 1 ELSE 0 END) as phong_dang_thue,
        SUM(dienTich) as tong_dien_tich
    FROM PHONG 
    WHERE deleted_at IS NULL
      AND maPhong NOT LIKE '%-V%'
";
$roomKPI = $db->query($sqlRoomKPI)->fetch();
$totalRooms = (int)$roomKPI['tong_phong'];
$availableRooms = (int)$roomKPI['phong_trong'];
$rentedRooms = (int)$roomKPI['phong_dang_thue'];
$totalArea = (float)$roomKPI['tong_dien_tich'];
$occupancyRate = ($totalRooms > 0) ? round(($rentedRooms / $totalRooms) * 100, 1) : 0;

// B. Thống kê Khách hàng nợ (Số lượng KH có ít nhất 1 hóa đơn chính còn nợ)
$sqlDebtCount = "
    SELECT COUNT(DISTINCT h.maKH) as count_debtor
    FROM HOA_DON hd
    JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong
    WHERE hd.trangThai = 'ConNo' 
      AND hd.loaiHoaDon = 'Chinh'
      AND hd.deleted_at IS NULL
";
$debtorCount = $db->query($sqlDebtCount)->fetch()['count_debtor'] ?: 0;

// C. Doanh thu dự kiến tháng hiện tại (Hoa don MM/YYYY)
$currentMonthYear = date('m/Y');
$sqlRevenue = "
    SELECT SUM(tongTien) as projected
    FROM HOA_DON 
    WHERE kyThanhToan = :period 
      AND deleted_at IS NULL 
      AND loaiHoaDon = 'Chinh'
";
$stmtRev = $db->prepare($sqlRevenue);
$stmtRev->execute([':period' => $currentMonthYear]);
$projectedRevenue = $stmtRev->fetch()['projected'] ?: 0;

// D. Tổng nợ quá hạn (Tổng soTienConNo)
$sqlTotalDebt = "
    SELECT SUM(soTienConNo) as total_debt 
    FROM HOA_DON 
    WHERE trangThai = 'ConNo' 
      AND deleted_at IS NULL
";
$totalDebt = $db->query($sqlTotalDebt)->fetch()['total_debt'] ?: 0;

// ============================================================================
// 3. TRUY VẤN BẢN ĐỒ MẶT BẰNG (FLOOR MAP)
// ============================================================================
// Join để lấy Tên khách hàng nếu phòng đang được thuê (HD hieu luc)
$sqlFloorMap = "
    SELECT 
        p.maPhong,
        p.tenPhong,
        p.trangThai, 
        p.giaThue,
        co.tenCaoOc,
        t.tenTang,
        kh.tenKH
    FROM PHONG p
    JOIN TANG t ON p.maTang = t.maTang
    JOIN CAO_OC co ON t.maCaoOc = co.maCaoOc
    LEFT JOIN (
        SELECT cthd.maPhong, h.maKH
        FROM CHI_TIET_HOP_DONG cthd
        JOIN HOP_DONG h ON cthd.soHopDong = h.soHopDong
        WHERE h.trangThai = 1
          AND h.deleted_at IS NULL
          AND cthd.trangThai = 'DangThue'
        GROUP BY cthd.maPhong
    ) active_hd ON p.maPhong = active_hd.maPhong
    LEFT JOIN KHACH_HANG kh ON active_hd.maKH = kh.maKH
        AND kh.deleted_at IS NULL
    WHERE p.deleted_at IS NULL
      AND p.maPhong NOT LIKE '%-V%'
    ORDER BY co.tenCaoOc ASC, t.maTang ASC, p.maPhong ASC
";
$roomsRaw = $db->query($sqlFloorMap)->fetchAll();
$roomsByBuilding = [];
$buildingsList = [];
foreach ($roomsRaw as $r) {
    // Logic đồng bộ trạng thái
    if (!empty($r['tenKH'])) {
        $r['effective_status'] = 2;
    } else {
        $r['effective_status'] = ($r['trangThai'] == 2) ? 1 : $r['trangThai'];
    }

    $roomsByBuilding[$r['tenCaoOc']][$r['tenTang']][] = $r;
    if (!in_array($r['tenCaoOc'], $buildingsList)) $buildingsList[] = $r['tenCaoOc'];
}

// ============================================================================
// 4. BỔ SUNG: DANH SÁCH NỢ XẤU (TOP 10)
// ============================================================================
$sqlBadDebt = "
    SELECT 
        kh.tenKH, 
        h.soHopDong, 
        SUM(hd.soTienConNo) as total_debt_amount 
    FROM HOA_DON hd 
    JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong 
    JOIN KHACH_HANG kh ON h.maKH = kh.maKH 
    WHERE hd.trangThai = 'ConNo' 
      AND hd.loaiHoaDon = 'Chinh'
      AND hd.deleted_at IS NULL 
      AND h.deleted_at IS NULL 
      AND kh.deleted_at IS NULL
    GROUP BY kh.maKH, h.soHopDong
    ORDER BY total_debt_amount DESC 
    LIMIT 10
";
$badDebtList = $db->query($sqlBadDebt)->fetchAll();

// ============================================================================
// HỢP ĐỒNG SẮP HẾT HẠN (30 NGÀY TỚI)
// ============================================================================
$sqlExpiring = "
    SELECT 
        HD.soHopDong, 
        KH.tenKH, 
        HD.ngayKetThuc, 
        (SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE soHopDong = HD.soHopDong LIMIT 1) as maPhong
    FROM HOP_DONG HD
    JOIN KHACH_HANG KH ON HD.maKH = KH.maKH
    WHERE HD.ngayKetThuc BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)
      AND HD.deleted_at IS NULL 
      AND HD.trangThai = 1
      AND KH.deleted_at IS NULL
    ORDER BY HD.ngayKetThuc ASC
    LIMIT 10
";
$expiringContracts = $db->query($sqlExpiring)->fetchAll();

// ============================================================================
// 5. TRUY VẤN BIỂU ĐỒ DOANH THU (6 THÁNG GẦN NHẤT)
// ============================================================================
$sqlChart = "
    SELECT kyThanhToan, SUM(tongTien) as total_revenue
    FROM HOA_DON
    WHERE STR_TO_DATE(CONCAT('01/', kyThanhToan), '%d/%m/%Y') >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 6 MONTH)
      AND deleted_at IS NULL 
      AND loaiHoaDon = 'Chinh'
    GROUP BY kyThanhToan
    ORDER BY STR_TO_DATE(CONCAT('01/', kyThanhToan), '%d/%m/%Y') ASC
";
$chartData = $db->query($sqlChart)->fetchAll();
$chartLabels = []; $chartValues = [];
foreach ($chartData as $row) {
    $chartLabels[] = 'T' . $row['kyThanhToan'];
    $chartValues[] = (float)$row['total_revenue'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        :root {
            --navy-primary: #1e3a5f;
            --gold-accent: #c9a66b;
            --bg-neutral: #f4f7f9;
        }
        
        .kpi-card {
            border: none;
            border-radius: 12px;
            background: #fff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid var(--navy-primary);
        }
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(30, 58, 95, 0.12);
        }
        .kpi-card--gold { border-bottom-color: var(--gold-accent); }
        .kpi-card--danger { border-bottom-color: #e74c3c; }
        
        .kpi-icon-bg {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            color: rgba(30, 58, 95, 0.04);
            z-index: 0;
            transform: rotate(-15deg);
        }
        
        .kpi-content { position: relative; z-index: 1; }
        .kpi-title { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-value { font-size: 1.75rem; font-weight: 800; color: var(--navy-primary); margin: 0.25rem 0; }
        
        .room-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 85px;
            padding: 8px;
            border-radius: 10px;
            color: #fff;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.06);
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .room-box:hover { transform: scale(1.03); filter: brightness(1.1); border-color: var(--gold-accent); }
        .room-code { font-weight: 800; font-size: 1rem; line-height: 1.2; }
        .room-tenant { font-size: 0.7rem; font-weight: 400; margin-top: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; opacity: 0.9; }
        
        /* Status Colors */
        .room--available { background-color: #27ae60; }
        .room--rented { background-color: #1e3a5f; } /* Navy for rented to match theme */
        .room--maintenance { background-color: #f39c12; }
        
        .table-custom thead th {
            background-color: var(--navy-primary);
            color: #fff;
            font-weight: 600;
            border-bottom: none;
        }
        .card-header-gold {
            background-color: #fff;
            border-bottom: 2px solid var(--gold-accent);
        }
        .card-title-navy { color: var(--navy-primary); font-weight: 700; }
        
        /* Dashboard Density Fix: 70-80% view */
        .admin-main-content {
            max-width: 1400px;
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
            <!-- HEADER -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="h3 card-title-navy mb-1">Hệ Thống Quản Lý Vận Hành</h2>
                    <p class="text-muted small mb-0">Chào mừng bạn trở lại, hệ thống đang vận hành ổn định.</p>
                </div>
                <div class="d-flex gap-2 shadow-sm">
                    <a href="<?= BASE_URL ?>modules/dashboard/admin.php" class="btn btn-white border active">
                        <i class="bi bi-speedometer2 me-2 text-brand-primary"></i>Tổng quan Admin
                    </a>
                    <button class="btn btn-white border d-none d-md-inline-block">
                        <i class="bi bi-calendar3 me-2"></i><?php echo date('d/m/Y'); ?>
                    </button>
                    <button class="btn btn-navy text-white px-3" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <!-- ROW 1: KPI CARDS -->
            <div class="row g-4 mb-4">
                <!-- KPI 1: Phóng trống -->
                <div class="col-6 col-md-3">
                    <div class="card kpi-card shadow-sm h-100">
                        <div class="card-body p-4 kpi-content">
                            <div class="kpi-title">Phòng Trống</div>
                            <div class="kpi-value"><?php echo e($availableRooms); ?></div>
                            <div class="text-success small fw-bold"><i class="bi bi-door-open me-1"></i>Sẵn sàng khai thác</div>
                            <i class="bi bi-house-check kpi-icon-bg"></i>
                        </div>
                    </div>
                </div>
                <!-- KPI 2: Tỷ lệ lấp đầy -->
                <div class="col-6 col-md-3">
                    <div class="card kpi-card kpi-card--gold shadow-sm h-100">
                        <div class="card-body p-4 kpi-content">
                            <div class="kpi-title">Tỷ Lệ Lấp Đầy</div>
                            <div class="kpi-value"><?php echo e($occupancyRate); ?>%</div>
                            <div class="text-navy small fw-bold"><i class="bi bi-people me-1"></i><?php echo e($rentedRooms); ?>/<?php echo e($totalRooms); ?> Phòng</div>
                            <i class="bi bi-buildings kpi-icon-bg"></i>
                        </div>
                    </div>
                </div>
                <!-- KPI 3: Khách hàng nợ -->
                <div class="col-6 col-md-3">
                    <div class="card kpi-card shadow-sm h-100">
                        <div class="card-body p-4 kpi-content">
                            <div class="kpi-title">Khách Hàng Nợ</div>
                            <div class="kpi-value"><?php echo e($debtorCount); ?></div>
                            <div class="text-warning small fw-bold"><i class="bi bi-exclamation-circle me-1"></i>Cần đôn đốc thu hồi</div>
                            <i class="bi bi-person-exclamation kpi-icon-bg"></i>
                        </div>
                    </div>
                </div>
                <!-- KPI 4: Doanh thu tháng -->
                <div class="col-6 col-md-3">
                    <div class="card kpi-card kpi-card--gold shadow-sm h-100">
                        <div class="card-body p-4 kpi-content">
                            <div class="kpi-title">Doanh Thu T<?php echo date('m'); ?></div>
                            <div class="kpi-value"><?php echo formatTien($projectedRevenue); ?></div>
                            <div class="text-navy small fw-bold"><i class="bi bi-cash-coin me-1"></i>Dự kiến thu</div>
                            <i class="bi bi-wallet2 kpi-icon-bg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- COLUMN LEFT: CHART -->
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header card-header-gold p-3">
                            <h5 class="m-0 card-title-navy">Doanh thu 6 tháng gần nhất</h5>
                        </div>
                        <div class="card-body py-4">
                            <div style="height: 350px;">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- COLUMN RIGHT: BAD DEBT LIST -->
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header card-header-gold p-3">
                            <h5 class="m-0 text-danger fw-bold"><i class="bi bi-shield-exclamation me-2"></i>Danh Sách Nợ Xấu</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 py-3">Khách hàng / HĐ</th>
                                            <th class="text-end pe-3">Số tiền nợ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($badDebtList) > 0): ?>
                                            <?php foreach ($badDebtList as $debt): ?>
                                                <tr>
                                                    <td class="ps-3 py-3">
                                                        <div class="fw-bold text-navy"><?php echo e($debt['tenKH']); ?></div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">HĐ: <?php echo e($debt['soHopDong']); ?></div>
                                                    </td>
                                                    <td class="text-end pe-3 fw-bold text-danger">
                                                        <?php echo number_format($debt['total_debt_amount'], 0, ',', '.'); ?> đ
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center py-5 text-muted">Không có dữ liệu nợ xấu.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 text-center py-3">
                            <a href="../thanh_toan/bao_cao_no.php" class="small text-navy fw-bold text-decoration-none">Xem báo cáo chi tiết <i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>

                    <!-- BẢNG 2: HỢP ĐỒNG SẮP HẾT HẠN -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header card-header-gold p-3">
                            <h5 class="m-0 text-warning fw-bold"><i class="bi bi-clock-history me-2"></i>Hợp Đồng Sắp Hết Hạn</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 py-3">Khách hàng / Phòng</th>
                                            <th class="text-end pe-3">Ngày hết hạn</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($expiringContracts) > 0): ?>
                                            <?php foreach ($expiringContracts as $ex): ?>
                                                <tr>
                                                    <td class="ps-3 py-3">
                                                        <div class="fw-bold text-navy"><?php echo e($ex['tenKH']); ?></div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">P: <?php echo e($ex['maPhong'] ?? 'N/A'); ?> - HĐ: <?php echo e($ex['soHopDong']); ?></div>
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <span class="badge bg-light text-danger border border-danger-subtle">
                                                            <?php echo date('d/m/Y', strtotime($ex['ngayKetThuc'])); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center py-5 text-muted">Không có hợp đồng sắp hết hạn.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FLOOR MAP SECTION -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header p-3 d-flex justify-content-between align-items-center flex-wrap gap-2 card-header-gold">
                    <h5 class="m-0 card-title-navy"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Sơ Đồ Mặt Bằng Khai Thác</h5>
                    <div class="d-flex gap-3 small fw-bold">
                        <span class="d-flex align-items-center"><span class="badge rounded-circle p-1 me-1 room--available" style="width: 12px; height: 12px;">&nbsp;</span>Trống</span>
                        <span class="d-flex align-items-center"><span class="badge rounded-circle p-1 me-1 room--rented" style="width: 12px; height: 12px;">&nbsp;</span>Đang thuê</span>
                        <span class="d-flex align-items-center"><span class="badge rounded-circle p-1 me-1 room--maintenance" style="width: 12px; height: 12px;">&nbsp;</span>Bảo trì</span>
                    </div>
                </div>

                <!-- Interactive Filters -->
                <div class="bg-light px-4 py-3 border-bottom d-flex align-items-center gap-4 flex-nowrap overflow-auto">
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <label class="small fw-bold text-muted text-uppercase mb-0" style="white-space: nowrap;">Cao ốc:</label>
                        <select class="form-select form-select-sm border-0 shadow-sm" id="filterBuilding" style="min-width: 200px;">
                            <option value="all">Tất cả Cao ốc</option>
                            <?php foreach($buildingsList as $b): ?>
                                <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <label class="small fw-bold text-muted text-uppercase mb-0" style="white-space: nowrap;">Trạng thái:</label>
                        <select class="form-select form-select-sm border-0 shadow-sm" id="filterStatus" style="min-width: 150px;">
                            <option value="all">Tất cả</option>
                            <option value="status-1">Trống</option>
                            <option value="status-2">Đang thuê</option>
                            <option value="status-3">Bảo trì</option>
                        </select>
                    </div>
                    <div class="ms-auto small text-muted text-nowrap">
                        <span id="filteredCount" class="fw-bold text-navy">---</span> mặt bằng
                    </div>
                </div>

                <div class="card-body p-4">
                    <?php if (empty($roomsByBuilding)): ?>
                        <div class="text-center py-5 text-muted">Vui lòng khởi tạo dữ liệu Cao ốc và Tầng.</div>
                    <?php else: ?>
                        <?php foreach ($roomsByBuilding as $buildingName => $floors): ?>
                            <div class="building-section mb-5" data-building="<?= htmlspecialchars($buildingName) ?>">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="rounded-3 px-3 py-2 me-3 text-white fw-bold" style="background:#1e3a5f; font-size:0.95rem; white-space:nowrap;">
                                        <i class="bi bi-buildings me-2"></i><?php echo e($buildingName); ?>
                                    </div>
                                    <hr class="flex-grow-1 opacity-10">
                                </div>

                                <?php foreach ($floors as $floorName => $floorRooms): ?>
                                    <div class="mb-4 ps-2 floor-wrapper">
                                        <h6 class="fw-semibold mb-3 d-flex align-items-center" style="color:#c9a66b;">
                                            <i class="bi bi-layers me-2"></i><?php echo e($floorName); ?>
                                            <span class="ms-2 text-muted fw-normal small">(<?php echo count($floorRooms); ?> phòng)</span>
                                        </h6>
                                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3">
                                            <?php foreach ($floorRooms as $room): 
                                                $statusClass = 'room--available';
                                                if ($room['effective_status'] == 2) $statusClass = 'room--rented';
                                                elseif ($room['effective_status'] == 3) $statusClass = 'room--maintenance';
                                            ?>
                                                <div class="col room-item" data-status="status-<?= $room['effective_status'] ?>">
                                                    <div class="room-box <?php echo $statusClass; ?>" 
                                                         title="<?php echo e($buildingName); ?> - <?php echo e($floorName); ?>&#10;<?php echo e($room['tenPhong']); ?>&#10;Giá: <?php echo formatTien($room['giaThue']); ?> đ">
                                                        <div class="room-code"><?php echo e($room['maPhong']); ?></div>
                                                        <div class="room-tenant"><?php echo e($room['tenKH'] ?: 'TRỐNG'); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<!-- SCRIPTS: CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('revenueChart').getContext('2d');
    var chartLabels = <?php echo json_encode($chartLabels); ?>;
    var chartValues = <?php echo json_encode($chartValues); ?>;
    
    // Gradient cho biểu đồ
    var gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, '#1e3a5f');
    gradient.addColorStop(1, '#2c5282');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Doanh thu',
                data: chartValues,
                backgroundColor: gradient,
                hoverBackgroundColor: '#c9a66b',
                borderRadius: 6,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(value) {
                            return (value / 1000000).toLocaleString('vi-VN') + ' Tr';
                        }
                    }
                },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 15,
                    backgroundColor: 'rgba(30, 58, 95, 0.9)',
                    callbacks: {
                        label: function(context) {
                            return ' Doanh thu: ' + context.parsed.y.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            }
        }
    });

    // ── BIÊN TẬP BỘ LỌC MẶT BẰNG ──
    const filterBuilding = document.getElementById('filterBuilding');
    const filterStatus = document.getElementById('filterStatus');
    const buildingSections = document.querySelectorAll('.building-section');
    const filteredCount = document.getElementById('filteredCount');

    function applyFilters() {
        const building = filterBuilding.value;
        const status = filterStatus.value;
        let count = 0;

        buildingSections.forEach(section => {
            // Lọc theo Cao ốc: Nếu chọn 'all' hoặc tên cao ốc khớp bản ghi
            const matchBuilding = (building === 'all' || section.dataset.building === building);
            
            if (matchBuilding) {
                section.classList.remove('d-none');
                
                // Lọc theo Trạng thái bên trong cao ốc đó
                const roomItems = section.querySelectorAll('.room-item');
                let visibleRoomsInSection = 0;

                roomItems.forEach(room => {
                    const matchStatus = (status === 'all' || room.dataset.status === status);
                    if (matchStatus) {
                        room.classList.remove('d-none');
                        visibleRoomsInSection++;
                        count++;
                    } else {
                        room.classList.add('d-none');
                    }
                });

                // Ẩn tiêu đề Tầng nếu không còn phòng nào
                section.querySelectorAll('.floor-wrapper').forEach(floor => {
                    const visibleRoomsInFloor = floor.querySelectorAll('.room-item:not(.d-none)').length;
                    floor.classList.toggle('d-none', visibleRoomsInFloor === 0);
                });

                // Nếu cao ốc không còn phòng nào khớp trạng thái -> ẩn luôn cả cao ốc
                section.classList.toggle('d-none', visibleRoomsInSection === 0);
            } else {
                section.classList.add('d-none');
            }
        });

        filteredCount.innerText = count;
    }

    filterBuilding.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    applyFilters();
});
</script>

</body>
</html>
