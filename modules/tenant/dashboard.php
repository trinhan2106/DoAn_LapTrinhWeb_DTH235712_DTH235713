<?php
/**
 * modules/tenant/dashboard.php
 * Trang chủ Cổng Khách Hàng (Tenant Portal)
 * Chỉ cho phép ROLE_KHACH_HANG (role_id = 4)
 * Đã refactor dùng component chung và thương hiệu THE SAPPHIRE
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Chỉ khách hàng mới được vào đây
kiemTraSession();
kiemTraRole([ROLE_KHACH_HANG]);

$maKH = $_SESSION['user_id'];   
$tenKH = $_SESSION['ten_user'];
$pdo = Database::getInstance()->getConnection();

// 1. TRUY VẤN DỮ LIỆU (Giữ nguyên logic nghiệp vụ)
// [Logic truy vấn Hợp đồng, Hóa đơn, Bảo trì, Thông báo...]
$stmtHD = $pdo->prepare("SELECT hd.soHopDong, hd.ngayBatDau, hd.ngayKetThuc, hd.trangThai, GROUP_CONCAT(p.tenPhong ORDER BY p.tenPhong SEPARATOR ', ') AS danhSachPhong FROM HOP_DONG hd LEFT JOIN CHI_TIET_HOP_DONG cthd ON hd.soHopDong = cthd.soHopDong LEFT JOIN PHONG p ON cthd.maPhong = p.maPhong WHERE hd.maKH = ? AND hd.trangThai = 1 AND hd.deleted_at IS NULL GROUP BY hd.soHopDong ORDER BY hd.ngayKetThuc ASC LIMIT 5");
$stmtHD->execute([$maKH]);
$hopDongList = $stmtHD->fetchAll(PDO::FETCH_ASSOC);

$stmtHoaDon = $pdo->prepare("SELECT hd.soHoaDon, hd.kyThanhToan, hd.tongTien, hd.soTienConNo, hd.trangThai, hd.loaiHoaDon, hd.ngayLap FROM HOA_DON hd JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong WHERE h.maKH = ? AND hd.deleted_at IS NULL AND hd.loaiHoaDon = 'Chinh' AND hd.trangThai != 'Void' ORDER BY FIELD(hd.trangThai,'ConNo','DaThuMotPhan','DaThu') ASC, hd.ngayLap DESC LIMIT 6");
$stmtHoaDon->execute([$maKH]);
$hoaDonList = $stmtHoaDon->fetchAll(PDO::FETCH_ASSOC);

$stmtNo = $pdo->prepare("SELECT COALESCE(SUM(hd.soTienConNo), 0) as tong_no FROM HOA_DON hd JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong WHERE h.maKH = ? AND hd.trangThai IN ('ConNo','DaThuMotPhan') AND hd.deleted_at IS NULL");
$stmtNo->execute([$maKH]);
$tongNo = (float)$stmtNo->fetchColumn();

$stmtMR = $pdo->prepare("SELECT mr.id, mr.moTa, mr.trangThai, mr.mucDoUT, mr.created_at, p.tenPhong FROM MAINTENANCE_REQUEST mr JOIN PHONG p ON mr.maPhong = p.maPhong JOIN CHI_TIET_HOP_DONG cthd ON p.maPhong = cthd.maPhong JOIN HOP_DONG h ON cthd.soHopDong = h.soHopDong WHERE h.maKH = ? AND mr.deleted_at IS NULL ORDER BY mr.created_at DESC LIMIT 3");
$stmtMR->execute([$maKH]);
$maintenanceList = $stmtMR->fetchAll(PDO::FETCH_ASSOC);

$stmtTB = $pdo->prepare("SELECT tieuDe, noiDung, ngayGui, loaiThongBao, daDoc FROM THONG_BAO WHERE nguoiNhan = ? AND daDoc = 0 ORDER BY ngayGui DESC LIMIT 5");
$stmtTB->execute([$maKH]);
$thongBaoList = $stmtTB->fetchAll(PDO::FETCH_ASSOC);
$soTBChuaDoc = count($thongBaoList);

$trangThaiMR = [0=>'Chờ tiếp nhận',1=>'Đang xử lý',2=>'Hoàn thành',3=>'Đã hủy'];
$badgeMR     = [0=>'secondary',1=>'warning',2=>'success',3=>'danger'];

// Dùng header chung
include __DIR__ . '/../../includes/public/header.php';
?>

<style>
    /* Custom styles cho Tenant Portal để bổ trợ cho style.css chung */
    :root {
        --tenant-navy: #1e3a5f;
        --tenant-gold: #c9a66b;
    }
    .hero-banner-tenant {
        background: linear-gradient(135deg, var(--tenant-navy) 0%, #2c5282 100%);
        color: #fff;
        padding: 3rem 0;
        margin-bottom: 2rem;
    }
    .tenant-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        background: #fff;
    }
    .tenant-card-header {
        background: #fff;
        border-bottom: 2px solid var(--tenant-gold);
        padding: 1rem 1.5rem;
    }
    .kpi-box {
        background: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        border-right: 4px solid var(--tenant-navy);
        box-shadow: 0 4px 6px rgba(0,0,0,0.03);
    }
    .quick-link-item {
        display: flex; flex-direction: column; align-items: center;
        padding: 1.5rem; gap: 10px;
        border: 1px solid #edf2f7; border-radius: 12px;
        transition: all 0.3s ease; text-decoration: none; color: var(--tenant-navy);
    }
    .quick-link-item:hover {
        background: var(--tenant-navy); color: #fff; transform: translateY(-3px);
    }
    .quick-link-item i { font-size: 1.5rem; color: var(--tenant-gold); }
    .quick-link-item:hover i { color: #fff; }

    /* Animation pulse cho nút sắp hết hạn */
    @keyframes pulse-yellow {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
    }
    .pulse-expire {
        animation: pulse-yellow 2s infinite;
    }
</style>

<!-- Dùng Navbar chung -->
<?php include __DIR__ . '/../../includes/public/navbar.php'; ?>



<main>
    <!-- HERO -->
    <div class="hero-banner-tenant">
        <div class="container text-center text-md-start">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-2">Chào mừng bạn, <?php echo htmlspecialchars($tenKH); ?>!</h1>
                    <p class="lead opacity-75 mb-0">Cổng thông tin khách thuê tại <span class="text-warning fw-bold">THE SAPPHIRE</span></p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="badge bg-white text-dark p-3 rounded-pill shadow-sm">
                        <i class="fa-solid fa-clock me-2 text-primary"></i><?php echo date('d/m/Y'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <!-- Thông báo thành công/lỗi -->
        <?php if(isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
                <i class="fa-solid fa-circle-check me-2"></i><strong>Thành công!</strong> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert" style="background-color: #f8d7da; color: #842029;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Lỗi!</strong> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- KPI ROW -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-lg-3">
                <div class="kpi-box">
                    <div class="small text-muted text-uppercase fw-bold mb-2">Còn nợ</div>
                    <div class="h3 fw-bold text-danger"><?php echo number_format($tongNo,0,',','.'); ?> ₫</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-box">
                    <div class="small text-muted text-uppercase fw-bold mb-2">Hợp đồng</div>
                    <div class="h3 fw-bold text-primary"><?php echo count($hopDongList); ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-box">
                    <div class="small text-muted text-uppercase fw-bold mb-2">Thông báo</div>
                    <div class="h3 fw-bold text-warning"><?php echo $soTBChuaDoc; ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="kpi-box">
                    <div class="small text-muted text-uppercase fw-bold mb-2">Bảo trì</div>
                    <div class="h3 fw-bold text-success"><?php echo count($maintenanceList); ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- CỘT CHÍNH -->
            <div class="col-lg-8">
                <!-- Hợp đồng -->
                <div class="tenant-card mb-4">
                    <div class="tenant-card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 fw-bold text-navy"><i class="fa-solid fa-file-contract me-2 text-warning"></i>Hợp đồng hiện tại</h5>
                        <a href="hop_dong.php?soHopDong=<?php echo $hopDongList[0]['soHopDong'] ?? ''; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Chi tiết</a>
                    </div>
                    <div class="p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Số HĐ</th>
                                        <th class="border-0">Vị trí</th>
                                        <th class="border-0">Hết hạn</th>
                                        <th class="border-0 text-end">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hopDongList as $hd): 
                                        $ngayHetHan = strtotime($hd['ngayKetThuc']);
                                        $homNay = time();
                                        $soNgayConLai = round(($ngayHetHan - $homNay) / (60 * 60 * 24));
                                        $isSapHetHan = ($soNgayConLai <= 30 && $soNgayConLai >= 0);
                                    ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $hd['soHopDong']; ?></td>
                                            <td><span class="badge" style="background-color: #f0f4f8; color: #1e3a5f; border: 1px solid #d1d9e6;"><?php echo $hd['danhSachPhong']; ?></span></td>
                                            <td>
                                                <span class="text-danger fw-bold"><?php echo date('d/m/Y', $ngayHetHan); ?></span>
                                                <?php if($isSapHetHan): ?>
                                                    <br><span class="badge bg-danger mt-1 small">Sắp hết hạn</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <?php if($isSapHetHan): ?>
                                                        <a href="yeu_cau_giahan.php?soHopDong=<?php echo $hd['soHopDong']; ?>" 
                                                           class="btn btn-sm btn-warning shadow-sm pulse-expire px-3 rounded-pill fw-bold">
                                                            Gia hạn ngay
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="hop_dong.php?soHopDong=<?php echo $hd['soHopDong']; ?>" class="btn btn-sm btn-outline-navy rounded-pill px-3">Xem</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Hóa đơn -->
                <div class="tenant-card">
                    <div class="tenant-card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 fw-bold text-navy"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>Hóa đơn gần nhất</h5>
                        <a href="hoa_don.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Tất cả</a>
                    </div>
                    <div class="p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Số HD</th>
                                        <th class="border-0">Kỳ thanh toán</th>
                                        <th class="border-0 text-end">Tổng tiền</th>
                                        <th class="border-0 text-center">Trạng thái</th>
                                        <th class="border-0 text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hoaDonList as $hd): ?>
                                        <tr>
                                            <td><?php echo $hd['soHoaDon']; ?></td>
                                            <td><?php echo $hd['kyThanhToan']; ?></td>
                                            <td class="text-end fw-bold text-navy"><?php echo number_format($hd['tongTien'],0,',','.'); ?> ₫</td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill <?php echo $hd['trangThai']=='DaThu' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                    <?php echo $hd['trangThai']=='DaThu' ? 'Đã thu' : 'Chưa thu'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="tranh_chap.php?soHoaDon=<?php echo $hd['soHoaDon']; ?>" class="btn btn-sm btn-link text-danger text-decoration-none fw-bold p-0">
                                                    Khiếu nại
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CỘT PHỤ -->
            <div class="col-lg-4">
                <!-- Truy cập nhanh -->
                <div class="tenant-card mb-4 text-navy">
                    <div class="tenant-card-header">
                        <h5 class="m-0 fw-bold"><i class="fa-solid fa-bolt me-2 text-warning"></i>Thao tác nhanh</h5>
                    </div>
                    <div class="p-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="hop_dong.php?soHopDong=<?php echo $hopDongList[0]['soHopDong'] ?? ''; ?>" class="quick-link-item small">
                                    <i class="fa-solid fa-file-contract"></i>
                                    Xem hợp đồng
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="yeu_cau_giahan.php" class="quick-link-item small">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    Gia hạn HĐ
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="hoa_don.php" class="quick-link-item small">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                    Lịch sử hóa đơn
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="maintenance.php" class="quick-link-item small">
                                    <i class="fa-solid fa-screwdriver-wrench"></i>
                                    Báo hỏng
                                </a>
                            </div>
                            <div class="col-12 mt-2">
                                <a href="<?= BASE_URL ?>dangxuat.php" class="btn btn-outline-danger w-100 rounded-pill btn-sm py-2 fw-bold logout-link">
                                    <i class="fa-solid fa-power-off me-2"></i>Đăng xuất
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bảo trì -->
                <div class="tenant-card">
                    <div class="tenant-card-header">
                        <h5 class="m-0 fw-bold">Yêu cầu bảo trì</h5>
                    </div>
                    <div class="p-3">
                        <?php if (empty($maintenanceList)): ?>
                            <div class="text-center py-3 text-muted small">Không có yêu cầu nào.</div>
                        <?php else: ?>
                            <?php foreach ($maintenanceList as $mr): ?>
                                <div class="mb-3 pb-3 border-bottom last-child-noborder">
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span class="fw-bold"><?php echo htmlspecialchars($mr['tenPhong']); ?></span>
                                        <span class="badge bg-<?php echo $badgeMR[$mr['trangThai']]; ?>"><?php echo $trangThaiMR[$mr['trangThai']]; ?></span>
                                    </div>
                                    <div class="text-muted small text-truncate"><?php echo htmlspecialchars($mr['moTa']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <a href="maintenance.php" class="btn btn-navy w-100 mt-2 rounded-pill btn-sm" style="background-color: var(--tenant-navy); color: #fff;">Gửi yêu cầu mới</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Dùng Footer chung -->
<?php include __DIR__ . '/../../includes/public/footer.php'; ?>
