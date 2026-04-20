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
$soHoaDon = $_GET['soHoaDon'] ?? ($_POST['soHoaDon'] ?? '');

if (empty($soHoaDon)) {
    $_SESSION['error_msg'] = "Vui lòng chọn hóa đơn cần khiếu nại.";
    header("Location: hoa_don.php");
    exit();
}

// Chống IDOR: Xác thực hóa đơn này có thuộc về khách hàng đang đăng nhập không
$stmtCheck = $pdo->prepare("
    SELECT hd.soHoaDon, hd.tongTien, hd.soTienConNo, hd.kyThanhToan 
    FROM HOA_DON hd
    JOIN HOP_DONG hp ON hd.soHopDong = hp.soHopDong
    WHERE hd.soHoaDon = ? AND hp.maKH = ? AND hp.deleted_at IS NULL
");
$stmtCheck->execute([$soHoaDon, $maKH]);
$hoaDonInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$hoaDonInfo) {
    $_SESSION['error_msg'] = "Hóa đơn không hợp lệ hoặc bạn không có quyền thao tác.";
    header("Location: hoa_don.php");
    exit();
}

// Xử lý khi Submit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noiDung = trim($_POST['noiDung'] ?? '');
    
    if (empty($noiDung)) {
        $_SESSION['error_msg'] = "Vui lòng nhập nội dung khiếu nại.";
    } else {
        try {
            $idTranhChap = sinhMaNgauNhien('TC-' . date('Ym') . '-', 6);
            $stmtInsert = $pdo->prepare("INSERT INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai) VALUES (?, ?, ?, 0)");
            $stmtInsert->execute([$idTranhChap, $soHoaDon, htmlspecialchars($noiDung)]);
            
            $_SESSION['success_msg'] = "Đã gửi yêu cầu kiểm tra lại hóa đơn thành công! Vui lòng chờ kế toán phản hồi.";
            header("Location: hoa_don.php");
            exit();
        } catch (PDOException $e) {
            error_log("Lỗi tạo Tranh chấp HĐ: " . $e->getMessage());
            $_SESSION['error_msg'] = "Có lỗi xảy ra, vui lòng thử lại sau.";
        }
    }
}

// Include Header
require_once __DIR__ . '/../../includes/tenant/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-navy text-decoration-none">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="hoa_don.php" class="text-navy text-decoration-none">Hóa đơn</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Khiếu nại hóa đơn</li>
                </ol>
            </nav>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-danger py-3 px-4">
                    <h5 class="mb-0 fw-bold text-white">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Yêu Cầu Kiểm Tra Lại Hóa Đơn
                    </h5>
                </div>
                <div class="card-body p-4 p-lg-5">
                    
                    <!-- Khối thông tin hóa đơn -->
                    <div class="alert alert-info border-0 rounded-4 mb-4 p-4 shadow-sm">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-file-invoice me-2"></i>Thông tin hóa đơn đang thanh chấp:</h6>
                                <div class="row g-2 small">
                                    <div class="col-6 text-muted">Số hóa đơn:</div>
                                    <div class="col-6 fw-bold text-navy"><?php echo e($hoaDonInfo['soHoaDon']); ?></div>
                                    <div class="col-6 text-muted">Kỳ thanh toán:</div>
                                    <div class="col-6 fw-bold text-navy"><?php echo e($hoaDonInfo['kyThanhToan']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-5 border-start-md ps-md-4 mt-3 mt-md-0">
                                <div class="mb-2">
                                    <span class="small text-muted d-block">Tổng tiền:</span>
                                    <span class="fs-5 fw-bold text-navy"><?php echo number_format($hoaDonInfo['tongTien'], 0, ',', '.'); ?> ₫</span>
                                </div>
                                <div>
                                    <span class="small text-muted d-block">Còn nợ:</span>
                                    <span class="fs-5 fw-bold text-danger"><?php echo number_format($hoaDonInfo['soTienConNo'], 0, ',', '.'); ?> ₫</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="soHoaDon" value="<?php echo e($soHoaDon); ?>">
                        
                        <div class="mb-4">
                            <label for="noiDung" class="form-label fw-bold text-navy mb-2">Nội dung khiếu nại <span class="text-danger">*</span></label>
                            <textarea 
                                class="form-control rounded-3 border-light-subtle shadow-sm" 
                                id="noiDung" 
                                name="noiDung" 
                                rows="6" 
                                required
                                placeholder="Vui lòng ghi rõ lý do bạn muốn kiểm tra lại hóa đơn này (ví dụ: Sai chỉ số điện, đã chuyển khoản nhưng chưa ghi nhận...)"
                            ></textarea>
                            <div class="form-text mt-2">
                                <i class="fa-solid fa-circle-info me-1"></i> Yêu cầu của bạn sẽ được gửi trực tiếp đến phòng kế toán tòa nhà Blue Sky Khối A.
                            </div>
                        </div>

                        <div class="d-flex gap-3 mt-5">
                            <button type="submit" class="btn btn-danger py-2 px-4 rounded-pill fw-bold shadow-sm flex-grow-1">
                                <i class="fa-solid fa-paper-plane me-2"></i>Gửi Yêu Cầu
                            </button>
                            <a href="hoa_don.php" class="btn btn-outline-secondary py-2 px-4 rounded-pill fw-bold">
                                <i class="fa-solid fa-xmark me-2"></i>Hủy bỏ / Quay lại
                            </a>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center mt-4 text-muted small">
                <p>Thời gian phản hồi dự kiến từ 24h - 48h làm việc. Trân trọng!</p>
            </div>
        </div>
    </div>
</div>

<style>
    @media (min-width: 768px) {
        .border-start-md { border-left: 1px solid rgba(0,0,0,0.1) !important; }
    }
    textarea:focus {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
    }
</style>

<?php require_once __DIR__ . '/../../includes/tenant/footer.php'; ?>
