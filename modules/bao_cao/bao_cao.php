<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';
kiemTraSession();
kiemTraRole([1, 2]); // Chỉ Admin và Quản lý nhà được xem báo cáo

$pdo = Database::getInstance()->getConnection();

// 1. Báo cáo Phòng Trống (Kèm giá thuê tự tính)
$stmt1 = $pdo->query("
    SELECT co.tenCaoOc, t.tenTang, t.heSoGia, p.maPhong, p.tenPhong, p.dienTich, p.donGiaM2, 
           ROUND(p.donGiaM2 * p.dienTich * t.heSoGia, 0) AS giaThueThang
    FROM PHONG p
    JOIN TANG t ON p.maTang = t.maTang
    JOIN CAO_OC co ON t.maCaoOc = co.maCaoOc
    WHERE p.trangThai = 1 AND p.deleted_at IS NULL
    ORDER BY co.maCaoOc, t.tenTang, p.maPhong
");
$dsPhongTrong = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// 2. Báo cáo Phòng Đang Thuê
$stmt2 = $pdo->query("
    SELECT co.tenCaoOc, t.tenTang, p.maPhong, p.tenPhong, p.dienTich
    FROM PHONG p
    JOIN TANG t ON p.maTang = t.maTang
    JOIN CAO_OC co ON t.maCaoOc = co.maCaoOc
    WHERE p.trangThai = 2 AND p.deleted_at IS NULL
    ORDER BY co.maCaoOc, t.tenTang, p.maPhong
");
$dsPhongThue = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// 3. Báo cáo HĐ Hết Hạn Trong Tháng
$stmt3 = $pdo->query("
    SELECT hd.soHopDong, kh.tenKH, kh.sdt, hd.ngayHetHanCuoiCung
    FROM HOP_DONG hd
    JOIN KHACH_HANG kh ON hd.maKH = kh.maKH
    WHERE hd.trangThai = 1 AND hd.deleted_at IS NULL
      AND MONTH(hd.ngayHetHanCuoiCung) = MONTH(CURRENT_DATE())
      AND YEAR(hd.ngayHetHanCuoiCung) = YEAR(CURRENT_DATE())
    ORDER BY hd.ngayHetHanCuoiCung ASC
");
$dsHopDong = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// 4. Báo cáo Nhân Sự (Đang làm việc)
$stmt4 = $pdo->query("
    SELECT maNV, tenNV, chucVu, sdt, email 
    FROM NHAN_VIEN 
    WHERE deleted_at IS NULL
    ORDER BY role_id ASC, tenNV ASC
");$dsNhanVien = $stmt4->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require_once __DIR__ . '/../../includes/admin/admin-header.php'; ?>
</head>
<body class="bg-light">
<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php require_once __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
        <!-- Header Trang -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h3 class="fw-bold text-navy mb-0"><i class="bi bi-graph-up-arrow me-2 text-warning"></i>Báo Cáo & Thống Kê</h3>
                <p class="text-muted small mb-0">Theo dõi vận hành hệ thống cao ốc Blue Sky</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <button onclick="window.print()" class="btn btn-secondary shadow-sm rounded-pill px-3">
                    <i class="bi bi-printer me-1"></i> In trang này
                </button>
                <div class="dropdown">
                    <button class="btn btn-navy bg-navy border-0 rounded-pill px-4 shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-excel me-2"></i>Xuất Excel
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="export_csv.php?type=phong_trong"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Phòng Trống</a></li>
                        <li><a class="dropdown-item" href="export_csv.php?type=phong_thue"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Phòng Đang Thuê</a></li>
                        <li><a class="dropdown-item" href="export_csv.php?type=hd_hethan"><i class="bi bi-file-earmark-spreadsheet me-2"></i>HĐ Hết Hạn</a></li>
                        <li><a class="dropdown-item" href="export_csv.php?type=nhan_su"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Nhân Sự</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TABS Điều Hướng -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-3 px-3">
                <ul class="nav nav-pills nav-fill" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold" id="phongtrong-tab" data-bs-toggle="tab" data-bs-target="#phongtrong" type="button" role="tab">
                            Phòng Đang Trống <span class="badge bg-danger ms-2"><?= count($dsPhongTrong) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="phongthue-tab" data-bs-toggle="tab" data-bs-target="#phongthue" type="button" role="tab">
                            Phòng Đang Thuê <span class="badge bg-primary ms-2"><?= count($dsPhongThue) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="hopdong-tab" data-bs-toggle="tab" data-bs-target="#hopdong" type="button" role="tab">
                            HĐ Hết Hạn <span class="badge bg-warning text-dark ms-2"><?= count($dsHopDong) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="nhansu-tab" data-bs-toggle="tab" data-bs-target="#nhansu" type="button" role="tab">
                            Nhân Sự <span class="badge bg-info ms-2"><?= count($dsNhanVien) ?></span>
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="reportTabsContent">
                    
                    <!-- TAB 1: PHÒNG ĐANG TRỐNG -->
                    <div class="tab-pane fade show active" id="phongtrong" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-list-check me-1"></i> Danh sách phòng trống</h5>
                            <a href="export_csv.php?type=phong_trong" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                <i class="bi bi-file-earmark-excel me-1"></i> Xuất CSV
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table id="tablePhongTrong" class="table table-hover table-striped align-middle border">
                                <thead class="bg-navy text-white">
                                    <tr>
                                        <th>Tòa Nhà</th>
                                        <th>Tầng</th>
                                        <th>Mã Phòng</th>
                                        <th>Tên Phòng</th>
                                        <th class="text-center">Diện Tích</th>
                                        <th class="text-end">Đơn Giá M2</th>
                                        <th class="text-end">Giá Thuê / Tháng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($dsPhongTrong)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Không có dữ liệu phù hợp</td></tr>
                                    <?php else: ?>
                                        <?php foreach($dsPhongTrong as $p): ?>
                                            <tr>
                                                <td><?= e($p['tenCaoOc']) ?></td>
                                                <td><?= e($p['tenTang']) ?></td>
                                                <td class="fw-bold"><?= e($p['maPhong']) ?></td>
                                                <td><?= e($p['tenPhong']) ?></td>
                                                <td class="text-center"><?= number_format($p['dienTich'], 1) ?> m²</td>
                                                <td class="text-end"><?= formatTien($p['donGiaM2']) ?></td>
                                                <td class="text-end fw-bold text-success"><?= formatTien($p['giaThueThang']) ?> VNĐ</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: PHÒNG ĐANG THUÊ -->
                    <div class="tab-pane fade" id="phongthue" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-house-door me-1"></i> Danh sách phòng đang thuê</h5>
                            <a href="export_csv.php?type=phong_thue" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                <i class="bi bi-file-earmark-excel me-1"></i> Xuất CSV
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table id="tablePhongThue" class="table table-hover table-striped align-middle border">
                                <thead class="bg-navy text-white">
                                    <tr>
                                        <th>Tòa Nhà</th>
                                        <th>Tầng</th>
                                        <th>Mã Phòng</th>
                                        <th>Tên Phòng</th>
                                        <th class="text-center">Diện Tích</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($dsPhongThue)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Không có dữ liệu phù hợp</td></tr>
                                    <?php else: ?>
                                        <?php foreach($dsPhongThue as $p): ?>
                                            <tr>
                                                <td><?= e($p['tenCaoOc']) ?></td>
                                                <td><?= e($p['tenTang']) ?></td>
                                                <td class="fw-bold text-navy"><?= e($p['maPhong']) ?></td>
                                                <td><?= e($p['tenPhong']) ?></td>
                                                <td class="text-center"><?= number_format($p['dienTich'], 1) ?> m²</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: HĐ SẮP HẾT HẠN -->
                    <div class="tab-pane fade" id="hopdong" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold text-danger mb-0"><i class="bi bi-clock-history me-1"></i> Hợp đồng sắp hết hạn</h5>
                            <a href="export_csv.php?type=hd_hethan" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                <i class="bi bi-file-earmark-excel me-1"></i> Xuất CSV
                            </a>
                        </div>
                        <div class="alert alert-warning border-0 rounded-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><b>Lưu ý:</b> Dưới đây là các Hợp đồng sẽ hết hạn trong <b>Tháng <?= date('m/Y') ?></b>. Vui lòng liên hệ khách hàng để làm thủ tục gia hạn.
                        </div>
                        <div class="table-responsive">
                            <table id="tableHDHetHan" class="table table-hover table-striped align-middle border">
                                <thead class="bg-danger text-white">
                                    <tr>
                                        <th>Số Hợp Đồng</th>
                                        <th>Tên Khách Hàng</th>
                                        <th>Số Điện Thoại</th>
                                        <th>Ngày Hết Hạn</th>
                                        <th class="text-center">Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($dsHopDong)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Không có dữ liệu phù hợp</td></tr>
                                    <?php else: ?>
                                        <?php foreach($dsHopDong as $h): ?>
                                            <tr class="table-warning">
                                                <td class="fw-bold"><?= e($h['soHopDong']) ?></td>
                                                <td><?= e($h['tenKH']) ?></td> <!-- XSS Protected via e() -->
                                                <td><?= e($h['sdt']) ?></td>
                                                <td class="text-danger fw-bold"><?= date('d/m/Y', strtotime($h['ngayHetHanCuoiCung'])) ?></td>
                                                <td class="text-center">
                                                    <a href="#" class="btn btn-sm btn-outline-danger rounded-pill">Liên hệ KH</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 4: NHÂN SỰ -->
                    <div class="tab-pane fade" id="nhansu" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-people me-1"></i> Danh sách nhân sự</h5>
                            <a href="export_csv.php?type=nhan_su" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                <i class="bi bi-file-earmark-excel me-1"></i> Xuất CSV
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table id="tableNhanSu" class="table table-hover table-striped align-middle border">
                                <thead class="bg-navy text-white">
                                    <tr>
                                        <th>Mã NV</th>
                                        <th>Họ Tên</th>
                                        <th>Chức Vụ</th>
                                        <th>Số Điện Thoại</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($dsNhanVien)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Không có dữ liệu phù hợp</td></tr>
                                    <?php else: ?>
                                        <?php foreach($dsNhanVien as $nv): ?>
                                            <tr>
                                                <td class="fw-bold"><?= e($nv['maNV']) ?></td>
                                                <td class="text-navy fw-bold"><?= e($nv['tenNV']) ?></td> <!-- XSS Protected via e() -->
                                                <td><span class="badge bg-light text-dark shadow-sm px-2"><?= e($nv['chucVu']) ?></span></td>
                                                <td><?= e($nv['sdt']) ?></td>
                                                <td><?= e($nv['email']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

<style>
    :root {
        --navy-primary: #1e3a5f;
        --gold-accent: #c9a66b;
    }
    .text-navy { color: var(--navy-primary); }
    .bg-navy { background-color: var(--navy-primary) !important; }
    .btn-navy { background-color: var(--navy-primary); color: white; }
    .btn-navy:hover { background-color: #122a46; color: white; }
    
    .nav-pills .nav-link {
        color: var(--navy-primary);
        border: 1px solid #e9ecef;
        margin: 0 5px;
        transition: all 0.3s;
    }
    .nav-pills .nav-link:hover {
        background-color: #f8f9fa;
    }
    .nav-pills .nav-link.active {
        background-color: var(--navy-primary) !important;
        border-color: var(--navy-primary) !important;
        box-shadow: 0 4px 10px rgba(30, 58, 95, 0.2);
    }
    
    .table thead th { border: none; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .table tbody td { font-size: 0.9rem; }
    
    .card { border: none; transition: transform 0.2s; }
    .rounded-4 { border-radius: 1rem !important; }

    /* --- Cấu hình riêng cho bản IN --- */
    @media print {
        /* Ẩn Sidebar, Topbar, Tabs chuyển trang, Footer và các nút bấm */
        .admin-sidebar, .admin-topbar, .nav-pills, .btn, .dropdown, footer, .alert, .admin-flash-messages {
            display: none !important;
        }
        
        /* Mở rộng không gian bảng in, xóa margin lề trái của sidebar */
        .admin-main-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        
        .admin-main-content {
            padding: 0 !important;
            max-width: 100% !important;
        }

        /* Đảm bảo bảng hiển thị rõ nét và không bị cắt ngang dòng quá xấu */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto;
            border: 1px solid #dee2e6 !important;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th {
            background-color: #1e3a5f !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
        }
        .bg-navy { background-color: #1e3a5f !important; color: white !important; -webkit-print-color-adjust: exact; }
        .bg-danger { background-color: #dc3545 !important; color: white !important; -webkit-print-color-adjust: exact; }
    }
</style>

        </main> <!-- End admin-main-content -->
        
        <?php require_once __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div> <!-- End admin-main-wrapper -->
</div> <!-- End admin-layout -->

</body>
</html>
