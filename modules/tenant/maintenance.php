<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php'; // Gọi sinhMaNgauNhien
kiemTraSession();

if ((int)$_SESSION['user_role'] !== 4) {
    header("Location: " . BASE_URL . "dangnhap.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();
$maKH = $_SESSION['user_id'];

// Xử lý Gửi Yêu cầu Sửa chữa mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $maPhong = $_POST['maPhong'] ?? '';
    $mucDoUT = (int)($_POST['mucDoUT'] ?? 1);
    $moTa = trim($_POST['moTa'] ?? '');

    // Chống IDOR: Xác thực maPhong có đang được thuê bởi maKH này không
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM CHI_TIET_HOP_DONG c
        JOIN HOP_DONG h ON c.soHopDong = h.soHopDong
        WHERE c.maPhong = ? AND h.maKH = ? AND c.trangThai = 'DangThue' AND h.trangThai = 1 AND h.deleted_at IS NULL
    ");
    $stmtCheck->execute([$maPhong, $maKH]);
    if ($stmtCheck->fetchColumn() == 0) {
        $_SESSION['error_msg'] = "Phòng không hợp lệ hoặc bạn không có quyền báo lỗi cho phòng này.";
        header("Location: maintenance.php");
        exit();
    }

    if (empty($moTa)) {
        $_SESSION['error_msg'] = "Vui lòng nhập mô tả sự cố.";
    } else {
        try {
            $pdo->beginTransaction();
            $idReq = sinhMaNgauNhien('MNT-' . date('Ym') . '-', 6);
            
            // Insert Request
            $stmtInsert = $pdo->prepare("INSERT INTO MAINTENANCE_REQUEST (id, maPhong, moTa, nguoiYeuCau, trangThai, mucDoUT) VALUES (?, ?, ?, ?, 0, ?)");
            $stmtInsert->execute([$idReq, $maPhong, htmlspecialchars($moTa), $maKH, $mucDoUT]);
            
            // Insert Log khởi tạo
            $stmtLog = $pdo->prepare("INSERT INTO MAINTENANCE_STATUS_LOG (request_id, trangThaiMoi, nguoiCapNhat) VALUES (?, 0, ?)");
            $stmtLog->execute([$idReq, $maKH]);

            $pdo->commit();
            $_SESSION['success_msg'] = "Đã gửi yêu cầu bảo trì! Kỹ thuật viên sẽ tiếp nhận sớm nhất.";
            header("Location: maintenance.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Lỗi tạo YC Bảo trì: " . $e->getMessage());
            $_SESSION['error_msg'] = "Có lỗi xảy ra, vui lòng thử lại sau.";
        }
    }
}

// Lấy danh sách phòng Đang Thuê của khách hàng để đưa vào Select Form
$stmtPhong = $pdo->prepare("
    SELECT p.maPhong, p.tenPhong 
    FROM PHONG p
    JOIN CHI_TIET_HOP_DONG c ON p.maPhong = c.maPhong
    JOIN HOP_DONG h ON c.soHopDong = h.soHopDong
    WHERE h.maKH = ? AND c.trangThai = 'DangThue' AND h.trangThai = 1
");
$stmtPhong->execute([$maKH]);
$dsPhong = $stmtPhong->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách yêu cầu bảo trì đã gửi của KH
$stmtReq = $pdo->prepare("
    SELECT m.*, p.tenPhong 
    FROM MAINTENANCE_REQUEST m
    JOIN PHONG p ON m.maPhong = p.maPhong
    WHERE m.nguoiYeuCau = ? AND m.deleted_at IS NULL
    ORDER BY m.created_at DESC
");
$stmtReq->execute([$maKH]);
$dsYeuCau = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

// Include Header
require_once __DIR__ . '/../../includes/tenant/header.php';
?>

<style>
    .text-navy { color: #1e3a5f; }
    .bg-navy { background-color: #1e3a5f; }
    
    /* Progress Stepper */
    .stepper {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin-bottom: 2rem;
        padding: 0 1rem;
    }
    .stepper::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 10%;
        right: 10%;
        height: 3px;
        background-color: #e9ecef;
        z-index: 1;
    }
    .step-item {
        position: relative;
        z-index: 2;
        text-align: center;
        width: 33%;
    }
    .step-circle {
        width: 34px;
        height: 34px;
        background-color: #fff;
        border: 3px solid #e9ecef;
        border-radius: 50%;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #adb5bd;
        transition: all 0.3s;
    }
    .step-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #adb5bd;
        text-transform: uppercase;
    }
    
    /* States */
    .step-item.active .step-circle { border-color: #ffc107; color: #ffc107; box-shadow: 0 0 10px rgba(255, 193, 7, 0.3); }
    .step-item.active .step-label { color: #856404; }
    
    .step-item.completed .step-circle { border-color: #28a745; background-color: #28a745; color: #fff; }
    .step-item.completed .step-label { color: #155724; }

    .stepper.progress-1 .step-item:nth-child(1) .step-circle { border-color: #ffc107; color: #ffc107; }
    .stepper.progress-1 .step-item:nth-child(1) .step-label { color: #856404; }
    
    .stepper.progress-2 .step-item:nth-child(1) .step-circle { border-color: #28a745; background-color: #28a745; color: #fff; }
    .stepper.progress-2 .step-item:nth-child(2) .step-circle { border-color: #ffc107; color: #ffc107; }
    
    .stepper.progress-3 .step-item:nth-child(1) .step-circle,
    .stepper.progress-3 .step-item:nth-child(2) .step-circle,
    .stepper.progress-3 .step-item:nth-child(3) .step-circle { border-color: #28a745; background-color: #28a745; color: #fff; }

    .priority-4 { color: #dc3545 !important; font-weight: bold; }
    .maintenance-item { transition: all 0.3s; border-left: 5px solid #e9ecef; }
    .maintenance-item:hover { transform: translateX(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important; }
    .status-0 { border-left-color: #ffc107; }
    .status-1 { border-left-color: #0dcaf0; }
    .status-2 { border-left-color: #198754; }
    .status-3 { border-left-color: #dc3545; }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-navy mb-0"><i class="fa-solid fa-screwdriver-wrench me-2 text-warning"></i>Báo hỏng & Sửa chữa</h2>
        <a href="dashboard.php" class="btn btn-outline-navy rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <div class="row g-4">
        <!-- KHỐI 1: FORM GỬI YÊU CẦU -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-navy mb-0">Gửi yêu cầu mới</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($dsPhong)): ?>
                        <div class="alert alert-warning rounded-4 border-0 shadow-sm" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> Bạn hiện không có hợp đồng thuê phòng nào còn hiệu lực để báo hỏng.
                        </div>
                        <fieldset disabled>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Chọn phòng gặp sự cố</label>
                            <select name="maPhong" class="form-select rounded-3 border-light-subtle shadow-sm" required>
                                <option value="">-- Chọn phòng --</option>
                                <?php foreach ($dsPhong as $p): ?>
                                    <option value="<?php echo $p['maPhong']; ?>"><?php echo e($p['maPhong'] . ' - ' . $p['tenPhong']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Mức độ ưu tiên</label>
                            <select name="mucDoUT" class="form-select rounded-3 border-light-subtle shadow-sm" required>
                                <option value="1">Thấp</option>
                                <option value="2">Trung bình</option>
                                <option value="3">Cao</option>
                                <option value="4" class="priority-4">Khẩn cấp</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Mô tả sự cố</label>
                            <textarea name="moTa" class="form-control rounded-3 border-light-subtle shadow-sm" rows="5" placeholder="Ví dụ: Máy lạnh phòng 201 chảy nước, bóng đèn ban công bị cháy..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 py-2 rounded-pill fw-bold shadow-sm text-navy">
                            <i class="fa-solid fa-paper-plane me-2"></i>Gửi Yêu Cầu
                        </button>
                    </form>

                    <?php if (empty($dsPhong)): ?>
                        </fieldset>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-light rounded-4 text-muted small border-0">
                <i class="fa-solid fa-circle-info me-2 text-primary"></i> 
                Yêu cầu của bạn sẽ được chuyển đến bộ phận kỹ thuật ngay lập tức. Bạn có thể theo dõi tiến độ xử lý ở danh sách bên cạnh.
            </div>
        </div>

        <!-- KHỐI 2: DANH SÁCH & TIẾN ĐỘ -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-navy mb-0">Lịch sử yêu cầu & Tiến độ</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($dsYeuCau)): ?>
                        <div class="text-center py-5">
                            <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" alt="Empty" style="width: 200px; opacity: 0.5;">
                            <p class="text-muted mt-3">Bạn chưa có yêu cầu bảo trì nào.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($dsYeuCau as $yc): ?>
                                <div class="col-12">
                                    <div class="card rounded-4 border-0 shadow-sm maintenance-item status-<?php echo $yc['trangThai']; ?> mb-3">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <span class="badge bg-light text-navy border mb-2 small fw-bold px-3">#<?php echo $yc['id']; ?></span>
                                                    <h6 class="fw-bold text-navy mb-1">Phòng: <?php echo e($yc['tenPhong']); ?></h6>
                                                    <p class="mb-0 text-muted small"><?php echo nl2br(e($yc['moTa'])); ?></p>
                                                </div>
                                                <div class="text-end">
                                                    <?php
                                                        $pLabel = match((int)$yc['mucDoUT']) {
                                                            1 => '<span class="badge bg-success-subtle text-success border-success-subtle px-2">Ưu tiên: Thấp</span>',
                                                            2 => '<span class="badge bg-info-subtle text-info border-info-subtle px-2">Ưu tiên: TB</span>',
                                                            3 => '<span class="badge bg-warning-subtle text-warning border-warning-subtle px-2">Ưu tiên: Cao</span>',
                                                            4 => '<span class="badge bg-danger px-2">Ưu tiên: Khẩn cấp</span>',
                                                            default => ''
                                                        };
                                                        echo $pLabel;
                                                    ?>
                                                    <div class="text-muted mt-2" style="font-size: 0.7rem;">Gửi lúc: <?php echo date('d/m/Y H:i', strtotime($yc['created_at'])); ?></div>
                                                </div>
                                            </div>

                                            <?php if ($yc['trangThai'] == 3): ?>
                                                <div class="alert alert-danger mb-0 py-2 rounded-3 small border-0">
                                                    <i class="fa-solid fa-circle-xmark me-2"></i> Yêu cầu này đã bị hủy.
                                                </div>
                                            <?php else: ?>
                                                <div class="stepper progress-<?php echo $yc['trangThai'] + 1; ?> mt-4 px-0">
                                                    <div class="step-item active">
                                                        <div class="step-circle"><i class="fa-solid fa-clock"></i></div>
                                                        <div class="step-label">Chờ tiếp nhận</div>
                                                    </div>
                                                    <div class="step-item <?php echo $yc['trangThai'] >= 1 ? 'active' : ''; ?>">
                                                        <div class="step-circle"><i class="fa-solid fa-wrench"></i></div>
                                                        <div class="step-label">Đang xử lý</div>
                                                    </div>
                                                    <div class="step-item <?php echo $yc['trangThai'] == 2 ? 'completed' : ''; ?>">
                                                        <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                                                        <div class="step-label">Hoàn thành</div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/tenant/footer.php'; ?>