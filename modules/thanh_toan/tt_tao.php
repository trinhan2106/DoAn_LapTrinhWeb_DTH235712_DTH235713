<?php
/**
 * modules/thanh_toan/tt_tao.php
 * UI UC06: Form thu tiền và hiển thị danh sách hóa đơn còn nợ theo hợp đồng.
 * Tích hợp Admin Layout chuẩn hệ thống
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Bảo mật: Kiểm tra Session và Quyền hạn
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN]);

$pdo = Database::getInstance()->getConnection();

// Nhận param tìm kiếm hợp đồng
$soHD = trim($_GET['soHopDong'] ?? '');
$listHoaDon_No = [];
$tongNoCongDon = 0;
$khachHangInfo = null;

if (!empty($soHD)) {
    try {
        // Query lấy thông tin khách hàng theo hợp đồng
        $stmtKH = $pdo->prepare("
            SELECT k.tenKH, k.sdt, k.email 
            FROM HOP_DONG h 
            INNER JOIN KHACH_HANG k ON h.maKH = k.maKH 
            WHERE h.soHopDong = ? AND h.deleted_at IS NULL
        ");
        $stmtKH->execute([$soHD]);
        $khachHangInfo = $stmtKH->fetch(PDO::FETCH_ASSOC);

        if ($khachHangInfo) {
            // Lấy danh sách hóa đơn còn nợ (Chỉ loại Chinh)
            $stmtHD = $pdo->prepare("
                SELECT * FROM HOA_DON 
                WHERE soHopDong = ? AND trangThai = 'ConNo' AND loaiHoaDon = 'Chinh'
                ORDER BY kyThanhToan ASC, created_at ASC
            ");
            $stmtHD->execute([$soHD]);
            $listHoaDon_No = $stmtHD->fetchAll(PDO::FETCH_ASSOC);

            // Tính tổng nợ cộng dồn
            foreach ($listHoaDon_No as $hd) {
                $tongNoCongDon += (float)$hd['soTienConNo'];
            }
        }
    } catch (PDOException $e) {
        error_log("[tt_tao.php] PDO error: " . $e->getMessage());
        $_SESSION['error_msg'] = "Lỗi hệ thống khi truy vấn dữ liệu.";
    }
}

$pageTitle = "Thu Tiền & Đối Soát - Admin";
include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<style>
    .pos-box { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); max-width: 1000px; margin: auto; border-top: 5px solid #28a745; }
    .bill-card { background: #fafafa; border: 1px solid #e0e0e0; border-radius: 8px;}
    .bill-header { background: #1e3a5f; color: #fff; padding: 12px 20px; border-radius: 8px 8px 0 0;}
    .rb-green { background-color: #e8f5e9; border: 2px dashed #4caf50; color: #1b5e20; }
    .rb-orange { background-color: #fff3e0; border: 2px dashed #f57c00; color: #e65100; }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <!-- Header Page -->
                <div class="mb-4">
                    <h2 class="h3 fw-bold text-navy"><i class="bi bi-cash-stack me-2"></i> TRUNG TÂM THANH TOÁN</h2>
                    <p class="text-muted small">Thu tiền mặt, chuyển khoản và đối soát gạch nợ tự động (Waterfall).</p>
                </div>

                <div class="pos-box">
                    <?php if (isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger fw-bold"><i class="bi bi-exclamation-triangle me-2"></i> <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_msg'])): ?>
                        <div class="alert alert-success fw-bold text-center shadow-sm"><i class="bi bi-check-all me-2"></i> <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                    <?php endif; ?>

                    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                        <div class="alert alert-success fw-bold text-center shadow-sm">
                            <i class="bi bi-check-circle-fill me-2"></i> Giao dịch thanh toán đã được ghi nhận thành công.<br/>
                            <small class="fw-normal">Hệ thống đã tự động phân bổ nợ và gửi biên lai điện tử cho khách hàng.</small>
                        </div>
                    <?php endif; ?>

                    <!-- THANH TÌM KIẾM HỢP ĐỒNG -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <form action="tt_tao.php" method="GET" class="row g-3 align-items-center">
                                <div class="col-md-9">
                                    <label class="form-label fw-bold text-muted small"><i class="bi bi-search me-1"></i> NHẬP MÃ SỐ HỢP ĐỒNG:</label>
                                    <input type="text" class="form-control form-control-lg border-2" name="soHopDong" placeholder="Ví dụ: HD-2025-001" value="<?= htmlspecialchars($soHD) ?>" required>
                                </div>
                                <div class="col-md-3 mt-auto">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow"><i class="bi bi-search me-2"></i> TÌM KIẾM</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if(!empty($soHD) && !$khachHangInfo): ?>
                        <div class="alert alert-warning fw-bold"><i class="bi bi-info-circle me-2"></i> Không tìm thấy hợp đồng [<?= htmlspecialchars($soHD) ?>]. Vui lòng kiểm tra lại mã số.</div>
                    
                    <?php elseif(!empty($soHD) && $khachHangInfo): ?>
                        <div class="row g-4 mt-2">
                            <!-- CỘT BÊN TRÁI: THÔNG TIN KHÁCH HÀNG & HÓA ĐƠN -->
                            <div class="col-md-7">
                                <div class="bill-card h-100 shadow-sm border-0">
                                    <div class="bill-header fw-bold">
                                        <i class="bi bi-person-badge-fill me-2"></i> HỒ SƠ: <?= htmlspecialchars($soHD) ?>
                                    </div>
                                    <div class="p-3 bg-white">
                                        <p class="mb-1"><strong>Khách hàng:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($khachHangInfo['tenKH']) ?></span></p>
                                        <p class="mb-0 text-muted small"><i class="bi bi-phone me-1"></i> <?= htmlspecialchars($khachHangInfo['sdt']) ?> | <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($khachHangInfo['email']) ?></p>
                                    </div>

                                    <div class="bg-light p-3 border-top">
                                        <h6 class="fw-bold text-secondary mb-3 small text-uppercase">Danh sách hóa đơn còn nợ</h6>
                                        <div class="list-group list-group-flush rounded-3">
                                            <?php if(count($listHoaDon_No) === 0): ?>
                                                <div class="alert alert-success mt-2 mb-0 fw-bold py-2"><i class="bi bi-check-circle me-2"></i> Khách hàng đã thanh toán đủ 100%.</div>
                                            <?php else: ?>
                                                <?php foreach($listHoaDon_No as $hd): ?>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                                        <div>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">Mã: <?= htmlspecialchars($hd['soHoaDon']) ?></span>
                                                            <br/><small class="text-muted">Kỳ: <?= htmlspecialchars($hd['kyThanhToan']) ?> | <?= htmlspecialchars($hd['lyDo']) ?></small>
                                                        </div>
                                                        <span class="fw-bold text-danger"><?= number_format((float)$hd['soTienConNo'], 0) ?> đ</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CỘT BÊN PHẢI: FORM THU TIỀN -->
                            <div class="col-md-5">
                                <form action="tt_tao_submit.php" method="POST" id="frmPOS">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="soHopDong" value="<?= htmlspecialchars($soHD) ?>">
                                    <input type="hidden" id="tongNoThucTe" value="<?= $tongNoCongDon ?>">

                                    <?php if($tongNoCongDon > 0): ?>
                                        <div class="p-3 mb-4 rounded rb-orange text-center shadow-sm">
                                            <h6 class="fw-bold small mb-1">TỔNG NỢ HIỆN TẠI</h6>
                                            <h2 class="fw-bold mb-0"><?= number_format($tongNoCongDon, 0) ?> <small class="fs-6">VND</small></h2>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Số tiền khách nộp <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control form-control-lg text-end fw-bold text-success border-success" 
                                                   name="soTienDaNop_POST" id="inpTienNop" value="<?= $tongNoCongDon ?>" min="1" required oninput="tinhDuNoLive()">
                                            <span class="input-group-text bg-success text-white fw-bold">đ</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small">Phương thức thanh toán <span class="text-danger">*</span></label>
                                        <select name="phuongThucM" class="form-select border-2 bg-light fw-bold" required>
                                            <option value="TienMat">Tiền mặt</option>
                                            <option value="ChuyenKhoan" selected>Chuyển khoản / Quẹt thẻ</option>
                                            <option value="Vi">Ví điện tử (Momo/VNPay)</option>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-muted small">Mã giao dịch (Nếu có)</label>
                                        <input type="text" name="maGiaoDich" class="form-control border-2 bg-light" placeholder="Ví dụ: VCB-12345678">
                                    </div>

                                    <div class="mb-4 p-3 bg-light border rounded text-center">
                                        <span class="d-block text-secondary fw-bold small mb-1">Dư nợ sau thanh toán:</span>
                                        <h4 class="m-0 fw-bold" id="lblLiveDuNo"><?= number_format($tongNoCongDon, 0) ?> đ</h4>
                                    </div>

                                    <?php if(count($listHoaDon_No) > 0): ?>
                                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-lg py-3" onclick="return confirm('Xác nhận thu tiền và cập nhật gạch nợ hệ thống?')">
                                            <i class="bi bi-check2-circle me-2"></i> THỰC THI THU TIỀN
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-lg w-100 fw-bold py-3" disabled>
                                            <i class="bi bi-lock me-2"></i> KHÔNG CÒN NỢ CẦN THU
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<script>
    function tinhDuNoLive() {
        const inputNop = document.getElementById('inpTienNop');
        const tienNop = parseFloat(inputNop.value) || 0;
        const tongNo = parseFloat(document.getElementById('tongNoThucTe').value) || 0;
        const lblDuNo = document.getElementById('lblLiveDuNo');
        
        let duNoMoi = tongNo - tienNop;
        let formatted = new Intl.NumberFormat('vi-VN').format(Math.abs(duNoMoi));

        if (duNoMoi > 0) {
            lblDuNo.innerHTML = `<span class="text-danger"><i class="bi bi-caret-down-fill me-1"></i> Còn nợ: ${formatted} đ</span>`;
        } else if (duNoMoi < 0) {
            lblDuNo.innerHTML = `<span class="text-success"><i class="bi bi-plus-circle-fill me-1"></i> Dư tín dụng: ${formatted} đ</span>`;
        } else {
            lblDuNo.innerHTML = `<span class="text-primary"><i class="bi bi-check-all me-1"></i> Đã sạch nợ (0 đ)</span>`;
        }
    }
    
    window.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('inpTienNop')) { tinhDuNoLive(); }
    });
</script>

</body>
</html>
