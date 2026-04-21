<?php
/**
 * modules/tenant/tranh_chap.php
 * Chế độ "Hai Mang": Lịch sử khiếu nại (List Mode) & Tạo khiếu nại mới (Form Mode)
 */
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
if ((int)$_SESSION['user_role'] !== 4) {
    header("Location: " . BASE_URL . "dangnhap.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();
$maKH = $_SESSION['user_id'];
$soHoaDon = $_GET['soHoaDon'] ?? ($_POST['soHoaDon'] ?? '');
$mode = 'list';
$hoaDonInfo = null;

// 1. KIỂM TRA CHẾ ĐỘ (MODE)
if (!empty($soHoaDon)) {
    // Nếu có soHoaDon, thực hiện kiểm tra IDOR để đảm bảo bill thuộc về KH này
    $stmtCheck = $pdo->prepare("
        SELECT hd.soHoaDon, hd.tongTien, hd.soTienConNo, hd.kyThanhToan 
        FROM HOA_DON hd
        JOIN HOP_DONG hp ON hd.soHopDong = hp.soHopDong
        WHERE hd.soHoaDon = ? AND hp.maKH = ? AND hp.deleted_at IS NULL
    ");
    $stmtCheck->execute([$soHoaDon, $maKH]);
    $hoaDonInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($hoaDonInfo) {
        $mode = 'form';
    } else {
        $_SESSION['error_msg'] = "Hóa đơn không hợp lệ hoặc bạn không có quyền truy cập.";
        header("Location: tranh_chap.php"); // Quay về List Mode
        exit();
    }
}

// 2. XỬ LÝ SUBMIT FORM (Chỉ dành cho Form Mode)
if ($mode === 'form' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_dispute'])) {
    $noiDung = trim($_POST['noiDung'] ?? '');
    
    if (empty($noiDung)) {
        $_SESSION['error_msg'] = "Vui lòng nhập nội dung khiếu nại.";
    } else {
        try {
            $idTranhChap = sinhMaNgauNhien('TC-' . date('Ym') . '-', 6);
            $stmtInsert = $pdo->prepare("INSERT INTO TRANH_CHAP_HOA_DON (id, maHoaDon, noiDung, trangThai) VALUES (?, ?, ?, 0)");
            $stmtInsert->execute([$idTranhChap, $soHoaDon, htmlspecialchars($noiDung)]);
            
            $_SESSION['success_msg'] = "Đã gửi yêu cầu kiểm tra lại hóa đơn thành công! Vui lòng chờ phản hồi.";
            header("Location: tranh_chap.php"); // Quay về List Mode sau khi xong
            exit();
        } catch (PDOException $e) {
            error_log("Lỗi tạo Tranh chấp HĐ: " . $e->getMessage());
            $_SESSION['error_msg'] = "Có lỗi xảy ra, vui lòng thử lại sau.";
        }
    }
}

// 3. TRUY VẤN DANH SÁCH (Dành cho List Mode)
$historyList = [];
if ($mode === 'list') {
    $stmtList = $pdo->prepare("
        SELECT tc.*, hd.kyThanhToan, hd.tongTien 
        FROM TRANH_CHAP_HOA_DON tc
        JOIN HOA_DON hd ON tc.maHoaDon = hd.soHoaDon
        JOIN HOP_DONG hp ON hd.soHopDong = hp.soHopDong
        WHERE hp.maKH = ? 
        ORDER BY tc.ngayTao DESC
    ");
    $stmtList->execute([$maKH]);
    $historyList = $stmtList->fetchAll(PDO::FETCH_ASSOC);
}

$trangThaiTxt = [0 => 'Mới tạo', 1 => 'Đang xử lý', 2 => 'Hoàn thành', 3 => 'Từ chối'];
$trangThaiColor = [0 => 'secondary', 1 => 'warning', 2 => 'success', 3 => 'danger'];

require_once __DIR__ . '/../../includes/tenant/header.php';
?>

<div class="container py-5">
    <!-- BREADCRUMB HOẠT ĐỘNG LINH HOẠT -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-navy text-decoration-none">Trang chủ</a></li>
            <?php if ($mode === 'form'): ?>
                <li class="breadcrumb-item"><a href="tranh_chap.php" class="text-navy text-decoration-none">Khiếu nại</a></li>
                <li class="breadcrumb-item active">Gửi yêu cầu mới</li>
            <?php else: ?>
                <li class="breadcrumb-item active">Lịch sử khiếu nại</li>
            <?php endif; ?>
        </ol>
    </nav>

    <?php if ($mode === 'list'): ?>
        <!-- ============================================== -->
        <!-- CHẾ ĐỘ 1: DANH SÁCH LỊCH SỬ KHIẾU NẠI           -->
        <!-- ============================================== -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-navy mb-0">Lịch sử khiếu nại & Tranh chấp</h3>
                    <div class="badge bg-navy p-2 px-3 rounded-pill shadow-sm">
                        Tổng cộng: <?php echo count($historyList); ?> Ticket
                    </div>
                </div>

                <!-- Mẹo UX theo chỉ đạo sếp -->
                <div class="alert alert-light border border-warning-subtle shadow-sm rounded-4 mb-4" style="border-left: 5px solid #ffc107 !important;">
                    <div class="d-flex align-items-center">
                        <span class="fs-4 me-3">💡</span>
                        <div>
                            <strong>Hướng dẫn tạo mới:</strong> Để tạo khiếu nại mới, vui lòng truy cập mục 
                            <a href="hoa_don.php" class="fw-bold text-decoration-underline" style="color: #1e3a5f;">Hóa đơn</a> 
                            và nhấn nút "Khiếu nại" tại hóa đơn bạn cần hỗ trợ.
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-navy text-white">
                                <tr>
                                    <th class="ps-4">Mã ticket</th>
                                    <th>Hóa đơn / Kỳ hạn</th>
                                    <th>Nội dung gửi</th>
                                    <th>Phản hồi kế toán</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="pe-4 text-end">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historyList)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted italic">
                                            Bạn chưa có khiếu nại nào được gửi đi.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($historyList as $tc): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-navy"><?php echo $tc['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo $tc['maHoaDon']; ?></div>
                                                <div class="small text-muted">Kỳ: <?php echo $tc['kyThanhToan']; ?></div>
                                            </td>
                                            <td style="max-width: 250px;">
                                                <p class="mb-0 small text-truncate-2" title="<?php echo htmlspecialchars($tc['noiDung']); ?>">
                                                    <?php echo htmlspecialchars($tc['noiDung']); ?>
                                                </p>
                                            </td>
                                            <td style="max-width: 250px;">
                                                <?php if (!empty($tc['phanHoi'])): ?>
                                                    <div class="bg-light p-2 rounded small border-start border-3 border-success">
                                                        <?php echo htmlspecialchars($tc['phanHoi']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small italic">Đang chờ phản hồi...</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-<?php echo $trangThaiColor[$tc['trangThai']]; ?>">
                                                    <?php echo $trangThaiTxt[$tc['trangThai']]; ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 text-end small text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($tc['ngayTao'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================== -->
        <!-- CHẾ ĐỘ 2: FORM TẠO KHIẾU NẠI MỚI (FORM MODE)  -->
        <!-- ============================================== -->
        <div class="row">
            <div class="col-md-9 mx-auto">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-danger py-3 px-4">
                        <h5 class="mb-0 fw-bold text-white">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>Gửi khiếu nại hóa đơn: <?php echo $soHoaDon; ?>
                        </h5>
                    </div>
                    <div class="card-body p-4 p-lg-5">
                        
                        <!-- Box Alert thông tin bill -->
                        <div class="alert alert-info border-0 rounded-4 mb-4 p-4 shadow-sm" style="background: #f0f7ff;">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h6 class="fw-bold text-navy mb-3"><i class="fa-solid fa-file-invoice me-2"></i>Dữ liệu hóa đơn:</h6>
                                    <div class="row g-2 small">
                                        <div class="col-6 text-muted">Số hóa đơn:</div>
                                        <div class="col-6 fw-bold text-navy"><?php echo $hoaDonInfo['soHoaDon']; ?></div>
                                        <div class="col-6 text-muted">Kỳ thanh toán:</div>
                                        <div class="col-6 fw-bold text-navy"><?php echo $hoaDonInfo['kyThanhToan']; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-5 border-start ps-md-4 mt-3 mt-md-0">
                                    <div class="mb-2">
                                        <span class="small text-muted d-block">Tổng tiền cần đóng:</span>
                                        <span class="fs-5 fw-bold text-navy"><?php echo number_format($hoaDonInfo['tongTien'], 0, ',', '.'); ?> ₫</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form action="" method="POST">
                            <input type="hidden" name="soHoaDon" value="<?php echo htmlspecialchars($soHoaDon); ?>">
                            <div class="mb-4">
                                <label for="noiDung" class="form-label fw-bold text-navy mb-2">Mô tả lý do khiếu nại <span class="text-danger">*</span></label>
                                <textarea class="form-control rounded-3 border-light-subtle shadow-sm" id="noiDung" name="noiDung" rows="8" required placeholder="Vui lòng mô tả chi tiết lý do bạn cho rằng hóa đơn này chưa chính xác (Ví dụ: sai số điện, đã đóng tiền nhưng chưa cập nhật...)"></textarea>
                                <div class="form-text mt-2 italic text-muted">
                                    <i class="fa-solid fa-circle-info me-1"></i> Ticket này sẽ được chuyển trực tiếp đến bộ phận Kế toán xử lý.
                                </div>
                            </div>

                            <div class="d-flex gap-3 pt-3">
                                <button type="submit" name="submit_dispute" class="btn btn-danger py-2 px-5 rounded-pill fw-bold shadow-sm flex-grow-1">
                                    Xác nhận gửi ticket
                                </button>
                                <a href="tranh_chap.php" class="btn btn-outline-secondary py-2 px-4 rounded-pill fw-bold">
                                    Quay lại danh sách
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .text-navy { color: #1e3a5f; }
    .bg-navy { background-color: #1e3a5f; }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .card { transition: transform 0.2s; }
    .italic { font-style: italic; }
</style>

<?php require_once __DIR__ . '/../../includes/tenant/footer.php'; ?>px solid rgba(0,0,0,0.1) !important; }
    }
    textarea:focus {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
    }
</style>

<?php require_once __DIR__ . '/../../includes/tenant/footer.php'; ?>
