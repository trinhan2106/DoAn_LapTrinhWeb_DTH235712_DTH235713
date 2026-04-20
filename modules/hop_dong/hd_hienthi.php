<?php
// modules/hop_dong/hd_hienthi.php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn lõi Danh sách Hợp Đồng JOIN Khách Hàng
    $sql = "
        SELECT h.soHopDong, h.ngayLap, h.ngayBatDau, h.ngayKetThuc, h.trangThai, h.tienTienCoc,
               k.tenKH, k.sdt
        FROM HOP_DONG h
        INNER JOIN KHACH_HANG k ON h.maKH = k.maKH
        WHERE h.deleted_at IS NULL
        ORDER BY h.ngayLap DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $danhSachHD = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi tải danh sách hợp đồng: " . $e->getMessage());
}

// Map Trạng Thái thành Badge Bootstrap (Theme Mới)
function formatTrangThaiHD($tt) {
    switch ((int)$tt) {
        case 3: return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half me-1"></i> Chờ Duyệt Ký</span>';
        case 1: return '<span class="badge bg-success"><i class="fa-solid fa-file-contract me-1"></i> Đang Hiệu Lực</span>';
        case 4: return '<span class="badge bg-info text-dark"><i class="fa-solid fa-arrow-up-right-dots me-1"></i> Gia Hạn</span>';
        case 0: return '<span class="badge bg-secondary"><i class="fa-solid fa-flag-checkered me-1"></i> Đã Kết Thúc</span>';
        case 2: return '<span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i> Đã Hủy</span>';
        default: return '<span class="badge bg-light text-dark">Lỗi Status</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .header-box { border-left: 5px solid var(--color-primary); }
        .table-custom thead th { background: var(--color-primary) !important; color: white !important; }
    </style>
</head>
<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content">
            <div class="container-fluid">
    
        <div class="header-box bg-white shadow-sm p-4 rounded-3 d-flex justify-content-between align-items-center mb-4">
            <h4 class="m-0 fw-bold text-navy">
                <i class="fa-solid fa-folder-open me-2"></i> QUẢN TRỊ HỒ SƠ HỢP ĐỒNG
            </h4>
            <div class="mb-3">
                <a href="<?php echo BASE_URL; ?>modules/hop_dong/hd_them.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i>  Lập hợp đồng mới
                </a>
            </div>
        </div>

    <!-- Alert Block -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'giahan_success'): ?>
        <div class="alert alert-info alert-dismissible fade show fw-bold text-dark border-info">
            <i class="fa-solid fa-circle-check me-2 text-info"></i> Quét luồng Database gia hạn Thành Công (UC08). Dữ liệu đã thay đổi!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0 table-datatable">
                    <thead>
                        <tr>
                            <th width="15%">Số Đăng Ký (ID)</th>
                            <th width="20%">Bên B (Khách Hàng)</th>
                            <th width="15%">Thời Gian Chốt</th>
                            <th width="15%">Đáo Hạn Cuối</th>
                            <th width="15%">Trạng Thái DB</th>
                            <th width="20%" class="text-center">Thao Tác Tương Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($danhSachHD) > 0): ?>
                            <?php foreach ($danhSachHD as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($row['soHopDong']) ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-money-bill-transfer"></i> Cọc: <?= number_format($row['tienTienCoc'],0) ?>đ</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['tenKH']) ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($row['sdt']) ?></small>
                                    </td>
                                    <td class="fw-bold text-secondary">
                                        <?= date('d/m/Y', strtotime($row['ngayBatDau'])) ?>
                                    </td>
                                    <td class="fw-bold text-danger">
                                        <!-- Cấu trúc ngayKetThuc đại diện cho Đáo hạn cuối cùng (ngayHetHanCuoiCung) -->
                                        <?= date('d/m/Y', strtotime($row['ngayKetThuc'])) ?>
                                    </td>
                                    <td>
                                        <?= formatTrangThaiHD($row['trangThai']) ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="hd_chitiet.php?id=<?= urlencode($row['soHopDong']) ?>" class="btn btn-secondary btn-sm px-2 text-white fw-bold shadow-sm me-1" style="border-radius: 4px;" title="Xem Chi Tiết">
                                            <i class="fa-regular fa-eye"></i> Xem
                                        </a>
                                        
                                        <!-- Task 5.6: Nút Điều Hướng Sang Gia Hạn -->
                                        <a href="hd_gia_han.php?soHopDong=<?= urlencode($row['soHopDong']) ?>" class="btn btn-info btn-sm px-2 text-dark fw-bold shadow-sm me-1" style="border-radius: 4px;" title="Tiến hành thủ tục Gia Hạn">
                                            <i class="fa-solid fa-timeline"></i> Gia Hạn
                                        </a>

                                        <?php if((int)$row['trangThai'] === 3): ?>
                                            <!-- Chờ Ký -->
                                            <a href="hd_ky.php?id=<?= urlencode($row['soHopDong']) ?>" class="btn btn-success btn-sm px-2 text-white fw-bold shadow-sm" style="border-radius: 4px;">
                                                <i class="fa-solid fa-file-signature"></i> Ký Duyệt
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Hệ thống chưa ghi nhận dòng CSDL Hợp đồng nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>
</body>
</html>
