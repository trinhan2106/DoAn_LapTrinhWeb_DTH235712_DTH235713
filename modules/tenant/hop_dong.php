<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';
kiemTraSession();

// Kiểm tra quyền Khách hàng (Role 4)
if ((int)$_SESSION['user_role'] !== 4) {
    header("Location: " . BASE_URL . "dangnhap.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();
$maKH = $_SESSION['user_id'];
$soHopDong = $_GET['soHopDong'] ?? '';

if (empty($soHopDong)) {
    $_SESSION['error_msg'] = "Vui lòng chọn một hợp đồng để xem.";
    header("Location: dashboard.php");
    exit();
}

// Lấy thông tin Hợp Đồng (Bảo vệ IDOR: chỉ lấy của đúng maKH)
$stmtHD = $pdo->prepare("SELECT * FROM HOP_DONG WHERE soHopDong = ? AND maKH = ? AND deleted_at IS NULL");
$stmtHD->execute([$soHopDong, $maKH]);
$hopDong = $stmtHD->fetch(PDO::FETCH_ASSOC);

if (!$hopDong) {
    $_SESSION['error_msg'] = "Hợp đồng không tồn tại hoặc bạn không có quyền xem.";
    header("Location: dashboard.php");
    exit();
}

// Lấy danh sách chi tiết phòng và tên phòng
$stmtPhong = $pdo->prepare("
    SELECT c.*, p.tenPhong, p.moTaViTri 
    FROM CHI_TIET_HOP_DONG c 
    JOIN PHONG p ON c.maPhong = p.maPhong 
    WHERE c.soHopDong = ?
");
$stmtPhong->execute([$soHopDong]);
$danhSachPhong = $stmtPhong->fetchAll(PDO::FETCH_ASSOC);

// Include Header của Tenant Portal
require_once __DIR__ . '/../../includes/tenant/header.php';
?>

<div class="container py-5" style="max-width: 1200px;">
    <!-- Head Breadcrumb & Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-navy">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết hợp đồng</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-navy mb-0">Hợp Đồng: <?php echo e($soHopDong); ?></h2>
        </div>
        <a href="dashboard.php" class="btn btn-outline-navy rounded-pill px-4" style="border-color: #1e3a5f; color: #1e3a5f;">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <!-- KHỐI 1: THÔNG TIN CHUNG -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 rounded-top-4">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-navy"><i class="fa-solid fa-circle-info me-2 text-warning"></i>Thông Tin Chung</h5>
                <?php
                    $statusBadge = match((int)$hopDong['trangThai']) {
                        1 => '<span class="badge bg-success px-3 py-2 rounded-pill">Đang hiệu lực</span>',
                        3 => '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Chờ duyệt</span>',
                        2 => '<span class="badge bg-danger px-3 py-2 rounded-pill">Đã hủy</span>',
                        0 => '<span class="badge bg-secondary px-3 py-2 rounded-pill">Đã kết thúc</span>',
                        default => '<span class="badge bg-dark px-3 py-2 rounded-pill">Không rõ</span>'
                    };
                    echo $statusBadge;
                ?>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-light rounded-circle"><i class="fa-solid fa-calendar-check text-navy fs-4"></i></div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">Ngày hiệu lực</div>
                            <div class="fw-bold text-navy fs-5"><?php echo date('d/m/Y', strtotime($hopDong['ngayBatDau'])); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-light rounded-circle"><i class="fa-solid fa-money-bill-transfer text-navy fs-4"></i></div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">Thanh toán đầu</div>
                            <div class="fw-bold text-navy fs-5"><?php echo $hopDong['ngayThanhToanDauTien'] ? date('d/m/Y', strtotime($hopDong['ngayThanhToanDauTien'])) : '—'; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-light rounded-circle"><i class="fa-solid fa-calendar-xmark text-navy fs-4"></i></div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold">Ngày hết hạn</div>
                            <div class="fw-bold text-danger fs-5"><?php echo date('d/m/Y', strtotime($hopDong['ngayHetHanCuoiCung'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHỐI 2: DANH SÁCH PHÒNG THUÊ -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4 rounded-top-4">
            <h5 class="mb-0 fw-bold text-navy"><i class="fa-solid fa-door-open me-2 text-primary"></i>Danh Sách Mặt Bằng / Phòng Thuê</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-navy fw-bold">
                        <tr>
                            <th class="ps-4 border-0 py-3">Mã Phòng</th>
                            <th class="border-0 py-3">Tên Phòng</th>
                            <th class="border-0 py-3">Vị Trí</th>
                            <th class="border-0 py-3">Bắt Đầu</th>
                            <th class="border-0 py-3">Hết Hạn</th>
                            <th class="border-0 py-3 text-end">Giá Thuê / Tháng</th>
                            <th class="border-0 py-3 text-center pe-4">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danhSachPhong)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Không tìm thấy phòng thuê trong hợp đồng này.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSachPhong as $phong): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-navy"><?php echo e($phong['maPhong']); ?></td>
                                    <td><?php echo e($phong['tenPhong']); ?></td>
                                    <td class="text-muted"><small><?php echo e($phong['moTaViTri']); ?></small></td>
                                    <td><small><?php echo date('d/m/Y', strtotime($phong['ngayBatDau'])); ?></small></td>
                                    <td><small class="text-danger"><?php echo date('d/m/Y', strtotime($phong['ngayHetHan'])); ?></small></td>
                                    <td class="text-end fw-bold text-navy"><?php echo number_format($phong['giaThue'], 0, ',', '.'); ?> ₫</td>
                                    <td class="text-center pe-4">
                                        <?php if ($phong['trangThai'] === 'DangThue'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3">Đang thuê</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary rounded-pill px-3">Đã kết thúc</span>
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

    <!-- Quick Footer Action -->
    <div class="mt-4 text-center">
        <a href="dashboard.php" class="btn btn-link text-decoration-none text-muted">
            <i class="fa-solid fa-chevron-left me-1"></i> Trở về Dashboard
        </a>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/tenant/footer.php';
?>
