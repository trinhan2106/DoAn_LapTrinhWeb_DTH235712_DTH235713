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
        p.trangThai, 
        p.giaThue,
        t.tenTang,
        kh.tenKH
    FROM PHONG p
    JOIN TANG t ON p.maTang = t.maTang
    LEFT JOIN CHI_TIET_HOP_DONG cthd ON p.maPhong = cthd.maPhong
    LEFT JOIN HOP_DONG h ON cthd.soHopDong = h.soHopDong 
        AND h.trangThai = 1 
        AND h.deleted_at IS NULL
    LEFT JOIN KHACH_HANG kh ON h.maKH = kh.maKH 
        AND kh.deleted_at IS NULL
    WHERE p.deleted_at IS NULL
    ORDER BY t.maTang ASC, p.maPhong ASC
";
$roomsRaw = $db->query($sqlFloorMap)->fetchAll();
$roomsByFloor = [];
foreach ($roomsRaw as $r) {
    $roomsByFloor[$r['tenTang']][] = $r;
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
        <?php include __DIR__ . '/../../includes/admin/notifications.php'; ?>
        
        <main class="admin-main-content p-4">
            <!-- HEADER -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="h3 card-title-navy mb-1">Hệ Thống Quản Lý Vận Hành</h2>
                    <p class="text-muted small mb-0">Chào mừng bạn trở lại, hệ thống đang vận hành ổn định.</p>
                </div>
                <div class="btn-group shadow-sm">
                    <button class="btn btn-white border"><i class="bi bi-calendar3 me-2"></i><?php echo date('d/m/Y'); ?></button>
                    <button class="btn btn-navy text-white" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i></button>
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
                <div class="card-body p-4">
                    <?php if (empty($roomsByFloor)): ?>
                        <div class="text-center py-5 text-muted">Vui lòng khởi tạo dữ liệu Tầng và Phòng.</div>
                    <?php else: ?>
                        <?php foreach ($roomsByFloor as $floorName => $floorRooms): ?>
                            <div class="mb-5">
                                <h6 class="text-navy fw-bold mb-4 d-flex align-items-center">
                                    <span class="badge bg-navy me-2 px-3 py-2"><?php echo e($floorName); ?></span>
                                    <hr class="flex-grow-1 opacity-10">
                                </h6>
                                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3">
                                    <?php foreach ($floorRooms as $room): 
                                        $statusClass = 'room--available';
                                        if ($room['trangThai'] == 2) $statusClass = 'room--rented';
                                        elseif ($room['trangThai'] == 3) $statusClass = 'room--maintenance';
                                    ?>
                                        <div class="col">
                                            <div class="room-box <?php echo $statusClass; ?>" 
                                                 title="Giá thuê: <?php echo formatTien($room['giaThue']); ?> đ">
                                                <div class="room-code"><?php echo e($room['maPhong']); ?></div>
                                                <div class="room-tenant"><?php echo e($room['tenKH'] ?: 'TRỐNG'); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
});
</script>

</body>
</html>
