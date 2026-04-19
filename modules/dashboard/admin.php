<?php
// Tích hợp Core PHP hệ thống (Lùi để móc nối)
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Kiểm tra quyền Session (Bắt buộc phải qua cửa Verify Đăng nhập)
// Lưu ý FIX-03: Hàm này sẽ tự động kiểm tra flag phai_doi_matkhau từ DB để buộc user đổi mật khẩu nếu cần
kiemTraSession();

// Khởi tạo kết nối CSDL chuẩn Singleton PDO
$db = Database::getInstance()->getConnection();

// ==========================================
// 1. TRUY VẤN DỮ LIỆU KPI (Chuẩn Schema)
// ==========================================

// Tổng diện tích & Tỷ lệ lấp đầy
$stmt = $db->prepare("SELECT 
    COUNT(*) as total_rooms,
    SUM(CASE WHEN trangThai = 2 THEN 1 ELSE 0 END) as rented_rooms,
    SUM(dienTich) as total_area
FROM PHONG WHERE deleted_at IS NULL");
$stmt->execute();
$roomStats = $stmt->fetch();

$totalRooms = $roomStats['total_rooms'] ?: 0;
$rentedRooms = $roomStats['rented_rooms'] ?: 0;
$totalArea = $roomStats['total_area'] ?: 0;
$occupancyRate = ($totalRooms > 0) ? round(($rentedRooms / $totalRooms) * 100, 1) : 0;

// Doanh thu dự kiến tháng hiện tại
// kyThanhToan có định dạng MM/YYYY
$currentMonthYear = date('m/Y');
$stmt = $db->prepare("SELECT SUM(tongTien) as projected_revenue 
FROM HOA_DON 
WHERE kyThanhToan = :currentMonthYear 
AND deleted_at IS NULL AND trangThai != 'Huy' AND trangThai != 'Void'");
$stmt->execute([':currentMonthYear' => $currentMonthYear]);
$projectedRevenue = $stmt->fetch()['projected_revenue'] ?: 0;

// Tổng nợ quá hạn
$stmt = $db->prepare("SELECT SUM(soTienConNo) as total_debt 
FROM HOA_DON 
WHERE trangThai = 'ConNo' AND deleted_at IS NULL");
$stmt->execute();
$totalDebt = $stmt->fetch()['total_debt'] ?: 0;

// ==========================================
// 2. TRUY VẤN DỮ LIỆU BIỂU ĐỒ DOANH THU
// ==========================================
// Lấy 6 tháng gần nhất (sắp xếp tăng dần theo thời gian)
$sqlChart = "
    SELECT kyThanhToan, SUM(tongTien) as total_revenue
    FROM HOA_DON
    WHERE STR_TO_DATE(CONCAT('01/', kyThanhToan), '%d/%m/%Y') >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 6 MONTH)
      AND deleted_at IS NULL AND trangThai != 'Huy' AND trangThai != 'Void'
    GROUP BY kyThanhToan
    ORDER BY STR_TO_DATE(CONCAT('01/', kyThanhToan), '%d/%m/%Y') ASC
";
$stmt = $db->prepare($sqlChart);
$stmt->execute();
$chartData = $stmt->fetchAll();

$chartLabels = [];
$chartValues = [];
foreach ($chartData as $row) {
    $chartLabels[] = 'T' . $row['kyThanhToan'];
    $chartValues[] = (float) $row['total_revenue'];
}

// ==========================================
// 3. HỢP ĐỒNG SẮP HẾT HẠN (30 Ngày Tới)
// ==========================================
// Gọi "ngayKetThuc" là "ngayHetHanCuoiCung" như theo logic CSDL
$sqlExpiring = "
    SELECT HD.soHopDong, KH.tenKH, HD.ngayKetThuc as ngayHetHanCuoiCung, P.maPhong
    FROM HOP_DONG HD
    JOIN KHACH_HANG KH ON HD.maKH = KH.maKH
    LEFT JOIN CHI_TIET_HOP_DONG CTHD ON HD.soHopDong = CTHD.soHopDong
    LEFT JOIN PHONG P ON CTHD.maPhong = P.maPhong
    WHERE HD.ngayKetThuc BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)
      AND HD.deleted_at IS NULL 
      AND HD.trangThai = 1
    ORDER BY HD.ngayKetThuc ASC
    LIMIT 10
";
$stmt = $db->prepare($sqlExpiring);
$stmt->execute();
$expiringContracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 4. BẢN ĐỒ MẶT BẰNG PHÒNG
// ==========================================
$sqlRooms = "
    SELECT P.maPhong, P.giaThue, P.trangThai, T.tenTang
    FROM PHONG P
    JOIN TANG T ON P.maTang = T.maTang
    WHERE P.deleted_at IS NULL
    ORDER BY T.tenTang ASC, P.maPhong ASC
";
$stmt = $db->prepare($sqlRooms);
$stmt->execute();
$roomsRaw = $stmt->fetchAll();

$roomsByFloor = [];
foreach ($roomsRaw as $r) {
    $roomsByFloor[$r['tenTang']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .kpi-card {
            border-left: 5px solid var(--color-primary);
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        }
        .kpi-card.is-danger {
            border-left-color: var(--color-danger);
        }
        .kpi-icon {
            font-size: 2.8rem;
            color: rgba(30, 58, 95, 0.1); 
        }
        [data-bs-theme="dark"] .kpi-icon {
            color: rgba(255, 255, 255, 0.1); 
        }
        .kpi-card.is-danger .kpi-icon {
            color: rgba(231, 76, 60, 0.15); 
        }
        
        .room-box {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 60px;
            border-radius: 8px;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            cursor: pointer;
        }
        .room-box:hover {
            transform: scale(1.05);
        }
        
        /* BEM Modifier cho Badge Brand */
        .badge-brand--success { background-color: #2ecc71 !important; color: #fff; } 
        .badge-brand--danger { background-color: #e74c3c !important; color: #fff; } 
        .badge-brand--warning { background-color: #f39c12 !important; color: #fff; }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        <?php include __DIR__ . '/../../includes/admin/notifications.php'; ?>
        
        <main class="admin-main-content p-4">
            <!-- HEADER DASHBOARD UX 3-Click Rule -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 text-brand-primary fw-bold mb-0">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard Tổng Quan
                </h2>
                <div class="d-flex gap-2">
                    <a href="../hop_dong/hd_them.php" class="btn btn-brand-primary shadow-sm text-white">
                        <i class="bi bi-plus-circle me-1"></i> Lập hợp đồng mới
                    </a>
                    <a href="#" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-bar-chart-fill me-1"></i> Xem báo cáo
                    </a>
                </div>
            </div>

            <!-- HÀNG 1: 4 KPI CARDS -->
            <div class="row g-4 mb-4">
                
                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-card shadow-sm h-100 border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem;">Tổng Diện Tích</p>
                                <h3 class="fw-bold mb-0 text-brand-primary"><?php echo formatTien($totalArea); ?> m²</h3>
                            </div>
                            <i class="bi bi-arrows-fullscreen kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-card shadow-sm h-100 border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem;">Tỷ Lệ Lấp Đầy</p>
                                <h3 class="fw-bold mb-0 text-brand-primary"><?php echo e($occupancyRate); ?>%</h3>
                                <small class="text-muted"><?php echo e($rentedRooms); ?>/<?php echo e($totalRooms); ?> phòng</small>
                            </div>
                            <i class="bi bi-buildings-fill kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-card shadow-sm h-100 border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem;">Doanh Thu Tháng Này</p>
                                <h3 class="fw-bold mb-0 text-brand-primary"><?php echo formatTien($projectedRevenue); ?> ₫</h3>
                            </div>
                            <i class="bi bi-safe-fill kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-card is-danger shadow-sm h-100 border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem;">Tổng Nợ Quá Hạn</p>
                                <h3 class="fw-bold mb-0 text-danger"><?php echo formatTien($totalDebt); ?> ₫</h3>
                            </div>
                            <i class="bi bi-exclamation-triangle-fill kpi-icon"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- HÀNG 2: BIỂU ĐỒ & HỢP ĐỒNG SẮP HẾT HẠN -->
            <div class="row g-4 mb-4">
                
                <!-- Chart.js Doanh Thu -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom border-light p-3">
                            <h5 class="m-0 fw-bold text-brand-primary">
                                <i class="bi bi-graph-up me-2 text-warning"></i> Doanh thu 6 tháng gần nhất
                            </h5>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-center">
                            <?php if (empty($chartValues)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-bar-chart text-secondary opacity-50 mb-3" style="font-size: 4rem;"></i>
                                    <p>Chưa có dữ liệu thanh toán trong 6 tháng qua.</p>
                                </div>
                            <?php else: ?>
                                <div style="height: 350px;">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Hợp đồng sắp hết hạn -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom border-light p-3">
                            <h5 class="m-0 fw-bold text-danger">
                                <i class="bi bi-stopwatch-fill me-2"></i> Hợp đồng sắp hết hẹn (30 ngày)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (count($expiringContracts) > 0): ?>
                                    <?php foreach ($expiringContracts as $contract): ?>
                                        <li class="list-group-item p-3 border-light list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1 fw-bold text-brand-primary"><?php echo e($contract['tenKH']); ?></h6>
                                                    <span class="badge bg-secondary">HĐ: <?php echo e($contract['soHopDong']); ?></span>
                                                    <span class="badge bg-info text-dark">P: <?php echo e($contract['maPhong'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted d-block">Đáo hạn cuối</small>
                                                    <strong class="text-danger">
                                                        <?php echo date('d/m/Y', strtotime($contract['ngayHetHanCuoiCung'])); ?>
                                                    </strong>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item p-5 text-center text-muted">
                                        <i class="bi bi-check-circle-fill text-success fs-3 mb-2 d-block"></i>
                                        Không có hợp đồng nào đáo hạn.
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- HÀNG 3: BẢN ĐỒ MẶT BẰNG PHÒNG -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom border-light p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="m-0 fw-bold text-brand-primary">
                        <i class="bi bi-grid-3x3-gap-fill me-2 text-warning"></i> Sơ Đồ Mặt Bằng Khai Thác
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="badge badge-brand--success px-3 py-2"><i class="bi bi-door-open me-1"></i> Trống</span>
                        <span class="badge badge-brand--danger px-3 py-2"><i class="bi bi-people-fill me-1"></i> Đang thuê</span>
                        <span class="badge badge-brand--warning px-3 py-2"><i class="bi bi-tools me-1"></i> Bảo trì</span>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (empty($roomsByFloor)): ?>
                        <div class="text-center text-muted py-4">Chưa có thông tin phòng trên hệ thống.</div>
                    <?php else: ?>
                        <?php foreach ($roomsByFloor as $floorName => $floorRooms): ?>
                            <div class="mb-4">
                                <h6 class="text-uppercase fw-bold text-muted mb-3 border-bottom pb-2">
                                    <i class="bi bi-layers text-brand-accent me-1"></i> <?php echo e($floorName); ?>
                                </h6>
                                <div class="row g-3">
                                    <?php foreach ($floorRooms as $room): 
                                        // 1: Trống, 2: Đang thuê, 3: Bảo trì (Hoặc theo chuẩn CSS required)
                                        $bgClass = 'badge-brand--success';
                                        $icon = 'bi-door-open';
                                        
                                        if ($room['trangThai'] == 2) {
                                            $bgClass = 'badge-brand--danger';
                                            $icon = 'bi-check-circle';
                                        } else if ($room['trangThai'] == 3) {
                                            $bgClass = 'badge-brand--warning';
                                            $icon = 'bi-tools';
                                        }
                                    ?>
                                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                            <div class="room-box <?php echo $bgClass; ?>" 
                                                 title="Phòng: <?php echo e($room['maPhong']); ?> &#10;Giá Thuê: <?php echo formatTien($room['giaThue']); ?> đ">
                                                <i class="bi <?php echo $icon; ?> me-2 d-none d-md-inline"></i> 
                                                <?php echo e($room['maPhong']); ?>
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

<!-- Kịch bản Javascript Chart.js -->
<?php if (!empty($chartValues)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('revenueChart').getContext('2d');
    
    // PHP đẩy data xuống Array JS
    var labels = <?php echo json_encode($chartLabels); ?>;
    var dataValues = <?php echo json_encode($chartValues); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tổng Doanh Thu (VNĐ)',
                data: dataValues,
                backgroundColor: '#1e3a5f', /* Màu Navy - Primary */
                hoverBackgroundColor: '#c9a66b', /* Màu Gold Highlight */
                borderRadius: 4,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000) + ' Tr';
                            }
                            return value.toLocaleString('vi-VN');
                        }
                    },
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(30, 58, 95, 0.9)',
                    titleFont: { size: 14 },
                    bodyFont: { size: 14 },
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            var value = context.parsed.y;
                            return 'Doanh Thu: ' + value.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

</body>
</html>
