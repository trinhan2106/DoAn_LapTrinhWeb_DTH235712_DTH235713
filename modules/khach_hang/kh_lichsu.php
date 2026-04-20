<?php
/**
 * modules/khach_hang/kh_lichsu.php
 * Lịch sử giao dịch Khách hàng (Hợp đồng & Hóa đơn)
 */

require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([1, 2]); // Chỉ Admin (1) và Quản lý Nhà (2) được xem

// 1. Chống IDOR: Lấy id (maKH) từ GET, kiểm tra với dữ liệu không bị xóa
$maKH = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($maKH)) {
    $_SESSION['error_msg'] = "Mã khách hàng không hợp lệ.";
    header("Location: kh_hienthi.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

// Kiểm tra khách hàng tồn tại và chưa bị soft delete
$stmtCheck = $pdo->prepare("SELECT maKH, tenKH FROM KHACH_HANG WHERE maKH = ? AND deleted_at IS NULL");
$stmtCheck->execute([$maKH]);
$khachHang = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$khachHang) {
    $_SESSION['error_msg'] = "Khách hàng không tồn tại hoặc đã bị xóa.";
    header("Location: kh_hienthi.php");
    exit();
}

// 2. Tab 1 - Lịch sử Hợp đồng 
// JOIN HOP_DONG với KHACH_HANG dựa trên maKH để lấy dữ liệu. Chỉ lấy bản ghi chưa bị xóa.
$sqlHopDong = "
    SELECT hd.soHopDong, hd.ngayBatDau, hd.ngayKetThuc, hd.tienTienCoc, hd.trangThai
    FROM HOP_DONG hd
    INNER JOIN KHACH_HANG kh ON hd.maKH = kh.maKH
    WHERE kh.maKH = ? AND hd.deleted_at IS NULL
    ORDER BY hd.ngayBatDau DESC
";
$stmtHopDong = $pdo->prepare($sqlHopDong);
$stmtHopDong->execute([$maKH]);
$hopDongs = $stmtHopDong->fetchAll(PDO::FETCH_ASSOC);

// 3. Tab 2 - Lịch sử Hóa đơn 
// JOIN HOA_DON với HOP_DONG (thông qua soHopDong) để lấy các hóa đơn thuộc chuỗi hợp đồng của khách hàng
$sqlHoaDon = "
    SELECT hd.soHoaDon, hd.kyThanhToan, hd.tongTien, hd.soTienDaNop, hd.soTienConNo, hd.trangThai, hd.loaiHoaDon
    FROM HOA_DON hd
    INNER JOIN HOP_DONG hpd ON hd.soHopDong = hpd.soHopDong
    WHERE hpd.maKH = ? AND hd.loaiHoaDon IN ('Chinh', 'CreditNote')
    ORDER BY hd.kyThanhToan DESC
";
$stmtHoaDon = $pdo->prepare($sqlHoaDon);
$stmtHoaDon->execute([$maKH]);
$hoaDons = $stmtHoaDon->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require_once __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .table-navy thead th { background-color: #1e3a5f !important; color: #ffffff !important; }
        .text-navy { color: #1e3a5f !important; }
        .nav-tabs .nav-link { color: #1e3a5f; font-weight: 500; border-radius: 0; }
        .nav-tabs .nav-link.active { color: #c9a66b; border-color: #dee2e6 #dee2e6 #fff; font-weight: bold; border-top: 3px solid #c9a66b; }
        .btn-gold { background-color: #c9a66b !important; color: #ffffff !important; border: none; }
        .btn-gold:hover { background-color: #b5925a; }
        .card-header-navy { background-color: #1e3a5f; color: white; }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php require_once __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/dashboard/admin.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="kh_hienthi.php" class="text-decoration-none">Quản lý Khách hàng</a></li>
                    <li class="breadcrumb-item active">Lịch sử Giao dịch</li>
                </ol>
            </nav>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header card-header-navy p-4 d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0 fw-bold">
                        <i class="bi bi-clock-history me-2"></i>LỊCH SỬ GIAO DỊCH: <?= e($khachHang['tenKH']) ?> (<?= e($maKH) ?>)
                    </h2>
                    <a href="kh_hienthi.php" class="btn btn-light btn-sm fw-bold">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại
                    </a>
                </div>
                
                <div class="card-body p-4">
                    <!-- Bootstrap Tabs -->
                    <ul class="nav nav-tabs mb-4" id="historyTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active px-4" id="hopdong-tab" data-bs-toggle="tab" data-bs-target="#hopdong" type="button" role="tab" aria-controls="hopdong" aria-selected="true">
                                <i class="bi bi-file-earmark-text me-1"></i> Lịch sử Hợp đồng
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-4" id="hoadon-tab" data-bs-toggle="tab" data-bs-target="#hoadon" type="button" role="tab" aria-controls="hoadon" aria-selected="false">
                                <i class="bi bi-receipt me-1"></i> Lịch sử Hóa đơn
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="historyTabContent">
                        
                        <!-- Tab 1: Lịch sử Hợp đồng -->
                        <div class="tab-pane fade show active" id="hopdong" role="tabpanel" aria-labelledby="hopdong-tab">
                            <?php if (empty($hopDongs)): ?>
                                <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                                    <i class="bi bi-exclamation-circle-fill flex-shrink-0 me-2 border-0"></i>
                                    <div>Chưa có lịch sử giao dịch hợp đồng.</div>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-navy border mb-0">
                                        <thead>
                                            <tr>
                                                <th>Số Hợp Đồng</th>
                                                <th>Ngày Bắt Đầu</th>
                                                <th>Ngày Kết Thúc</th>
                                                <th class="text-end">Tiền Cọc (VNĐ)</th>
                                                <th class="text-center">Trạng Thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($hopDongs as $hd): ?>
                                            <tr>
                                                <!-- Chống XSS qua hàm e() -->
                                                <td class="fw-bold"><?= e($hd['soHopDong']) ?></td>
                                                <td><?= e($hd['ngayBatDau']) ? date('d/m/Y', strtotime($hd['ngayBatDau'])) : '' ?></td>
                                                <td><?= e($hd['ngayKetThuc']) ? date('d/m/Y', strtotime($hd['ngayKetThuc'])) : '' ?></td>
                                                <td class="text-end fw-semibold text-danger"><?= number_format($hd['tienTienCoc'], 0, ',', '.') ?></td>
                                                <td class="text-center">
                                                    <?php 
                                                        // Logic màu Badge: 1: Hiệu lực - xanh, 0: Kết thúc - xám, 2: Hủy - đỏ, 3: Chờ duyệt - cam
                                                        $trangThai = (int)$hd['trangThai'];
                                                        if ($trangThai === 1) {
                                                            echo '<span class="badge bg-success">Hiệu lực</span>';
                                                        } elseif ($trangThai === 0) {
                                                            echo '<span class="badge bg-secondary">Kết thúc</span>';
                                                        } elseif ($trangThai === 2) {
                                                            echo '<span class="badge bg-danger">Hủy</span>';
                                                        } elseif ($trangThai === 3) {
                                                            echo '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                                                        } else {
                                                            echo '<span class="badge bg-dark">N/A</span>';
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab 2: Lịch sử Hóa đơn -->
                        <div class="tab-pane fade" id="hoadon" role="tabpanel" aria-labelledby="hoadon-tab">
                            <?php if (empty($hoaDons)): ?>
                                <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                                    <i class="bi bi-exclamation-circle-fill flex-shrink-0 me-2"></i>
                                    <div>Chưa có lịch sử giao dịch hóa đơn.</div>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-navy border mb-0">
                                        <thead>
                                            <tr>
                                                <th>Số Hóa Đơn</th>
                                                <th>Kỳ Thanh Toán</th>
                                                <th>Loại</th>
                                                <th class="text-end">Tổng Tiền (VNĐ)</th>
                                                <th class="text-end">Đã Nộp (VNĐ)</th>
                                                <th class="text-end">Còn Nợ (VNĐ)</th>
                                                <th class="text-center">Trạng Thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($hoaDons as $hdn): ?>
                                            <tr>
                                                <!-- Chống XSS -->
                                                <td class="fw-bold"><?= e($hdn['soHoaDon']) ?></td>
                                                <td><?= e($hdn['kyThanhToan']) ?></td>
                                                <td>
                                                    <?php 
                                                        // Hiển thị loại hóa đơn
                                                        if ($hdn['loaiHoaDon'] === 'Chinh') {
                                                            echo '<span class="badge bg-info text-dark">Chính</span>';
                                                        } elseif ($hdn['loaiHoaDon'] === 'CreditNote') {
                                                            echo '<span class="badge bg-warning text-dark">Cấn Trừ</span>';
                                                        } else {
                                                            echo '<span class="badge bg-secondary">'.e($hdn['loaiHoaDon']).'</span>';
                                                        }
                                                    ?>
                                                </td>
                                                <td class="text-end fw-semibold"><?= number_format($hdn['tongTien'], 0, ',', '.') ?></td>
                                                <td class="text-end text-success"><?= number_format($hdn['soTienDaNop'], 0, ',', '.') ?></td>
                                                <td class="text-end text-danger"><?= number_format($hdn['soTienConNo'], 0, ',', '.') ?></td>
                                                <td class="text-center">
                                                    <?php 
                                                        // 'ConNo', 'DaThu', 'Void'
                                                        $ttHD = $hdn['trangThai'];
                                                        if ($ttHD === 'ConNo') {
                                                            echo '<span class="badge bg-danger">Còn Nợ</span>';
                                                        } elseif ($ttHD === 'DaThu') {
                                                            echo '<span class="badge bg-success">Đã Thu</span>';
                                                        } elseif ($ttHD === 'Void') {
                                                            echo '<span class="badge bg-secondary">Đã Hủy</span>';
                                                        } else {
                                                            echo '<span class="badge bg-dark">'.e($ttHD).'</span>';
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
            
        </main>
        
        <?php require_once __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

</body>
</html>
