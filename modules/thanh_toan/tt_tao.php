<?php
// modules/thanh_toan/tt_tao.php
/**
 * UI & LOGIC UC06: TRUNG TÂM PHIẾU THU TÀI CHÍNH BÙ TRỪ KẾ TOÁN
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

$pdo = Database::getInstance()->getConnection();

// Nhận param Search Truy Vết soHopDong
$soHD = trim($_GET['soHopDong'] ?? '');
$listHoaDon_No = [];
$tongNoCongDon = 0;
$khachHangInfo = null;

if (!empty($soHD)) {
    try {
        // Query Lấy File Khách Hàng
        $stmtKH = $pdo->prepare("
            SELECT k.tenKH, k.sdt, k.email 
            FROM HOP_DONG h 
            INNER JOIN KHACH_HANG k ON h.maKH = k.maKH 
            WHERE h.soHopDong = ? AND h.deleted_at IS NULL
        ");
        $stmtKH->execute([$soHD]);
        $khachHangInfo = $stmtKH->fetch(PDO::FETCH_ASSOC);

        if ($khachHangInfo) {
            // Truy vấn Cáp dữ liệu tất cả Invoice đang bị Treo Nợ Trạng thái 'ConNo'
            $stmtHD = $pdo->prepare("
                SELECT * FROM HOA_DON 
                WHERE soHopDong = ? AND trangThai = 'ConNo'
                ORDER BY kyThanhToan ASC, created_at ASC
            ");
            $stmtHD->execute([$soHD]);
            $listHoaDon_No = $stmtHD->fetchAll(PDO::FETCH_ASSOC);

            // Thuật Toán Bù Trừ Cộng Dồn Tài Chính (Tính Base Total Dư Nợ)
            foreach ($listHoaDon_No as $hd) {
                $tongNoCongDon += (float)$hd['soTienConNo'];
            }
        }
    } catch (PDOException $e) {
        die("Fail DB Nguồn gốc UC06: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thu Ngân Quầy Kế Toán Cao Ốc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .pos-box { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); max-width: 950px; margin: 30px auto; border-top: 5px solid #28a745; }
        .bill-card { background: #fafafa; border: 1px solid #e0e0e0; border-radius: 8px;}
        .bill-header { background: #1e3a5f; color: #fff; padding: 12px 20px; border-radius: 8px 8px 0 0;}
        .rb-green { background-color: #e8f5e9; border: 2px dashed #4caf50; color: #1b5e20; }
        .rb-orange { background-color: #fff3e0; border: 2px dashed #f57c00; color: #e65100; }
    </style>
</head>
<body class="p-4">

<div class="container pos-box">
    
    <h3 class="mb-4 text-uppercase fw-bold text-center" style="color: #28a745;">
        <i class="fa-solid fa-cash-register me-2"></i> TRUNG TÂM PHIẾU THU TÀI CHÍNH POS
    </h3>

    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
        <div class="alert alert-success fw-bold text-center shadow-sm">
            <i class="fa-solid fa-check-double me-2"></i> GIAO DỊCH LƯU SỔ THÀNH CÔNG! <br/>
            Hệ thống đã tự động đối trừ thác nước (Waterfall) và Phát lệnh bắn Biên lai điện tử vào Mail khách hàng.
        </div>
    <?php endif; ?>

    <!-- THANH TÌM KIẾM TRUY XUẤT HỢP ĐỒNG KHÁCH HÀNG -->
    <div class="card bg-light border-0 mb-4">
        <div class="card-body">
            <form action="tt_tao.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-9">
                    <label class="form-label fw-bold text-muted"><i class="fa-solid fa-barcode me-1"></i> Scan/Nhập Mã Số Hợp Đồng Tòa Nhà:</label>
                    <input type="text" class="form-control form-control-lg border-2" name="soHopDong" placeholder="VD: HD-2026-62A89" value="<?= htmlspecialchars($soHD) ?>" required>
                </div>
                <div class="col-md-3 mt-auto">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow"><i class="fa-solid fa-magnifying-glass me-2"></i> Truy Cập</button>
                </div>
            </form>
        </div>
    </div>


    <?php if(!empty($soHD) && !$khachHangInfo): ?>
        <div class="alert alert-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> Cảnh Báo: Truy vấn Hợp đồng [<?= htmlspecialchars($soHD) ?>] bị hụt. Không tồn tại Data hoặc đã bị Hủy Giấy vĩnh viễn.</div>
    
    <?php elseif(!empty($soHD) && $khachHangInfo): ?>

        <div class="row g-4 mt-2">
            
            <!-- CỘT BÊN TRÁI: THÔNG TIN KHÁCH VÀ LIST BILL CHƯA TRẢ -->
            <div class="col-md-7">
                <div class="bill-card h-100 shadow-sm border-0">
                    <div class="bill-header fw-bold">
                        <i class="fa-solid fa-user-check me-2"></i> HỒ SƠ ĐẠI DIỆN HỢP ĐỒNG: <?= htmlspecialchars($soHD) ?>
                    </div>
                    <div class="p-3 bg-white">
                        <p class="mb-1"><strong>👤 Doanh Nghiệp:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($khachHangInfo['tenKH']) ?></span></p>
                        <p class="mb-1"><strong><i class="fa-solid fa-phone me-1"></i> Điện thoại:</strong> <?= htmlspecialchars($khachHangInfo['sdt']) ?></p>
                        <p class="mb-1"><strong><i class="fa-solid fa-envelope me-1"></i> Electronic Mail:</strong> <?= htmlspecialchars($khachHangInfo['email']) ?></p>
                    </div>

                    <div class="bg-light p-3 border-top">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-file-invoice me-1"></i> BIÊN LAI NỢ XẤU TỒN ĐỌNG KỲ TÀI TÀI CHÍNH</h6>
                        <ul class="list-group list-group-flush">
                            <?php if(count($listHoaDon_No) === 0): ?>
                                <div class="alert alert-success mt-2 mb-0 fw-bold"><i class="fa-solid fa-shield-cat me-2"></i> Quý Khách Đã Hoàn Tất Xóa Nợ. Không còn chứng từ Nợ Hóa Đơn Trống Nào.</div>
                            <?php else: ?>
                                <?php foreach($listHoaDon_No as $hd): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-bottom">
                                        <div>
                                            <span class="badge bg-danger me-2">Bill Code: <?= htmlspecialchars($hd['soHoaDon']) ?></span>
                                            <br/><small class="text-muted"><i class="fa-regular fa-clock me-1"></i> Kỳ Cước: <?= htmlspecialchars($hd['kyThanhToan']) ?></small>
                                            <br/><small class="fw-bold text-dark">Lý Do: <?= htmlspecialchars($hd['lyDo']) ?></small>
                                        </div>
                                        <span class="fw-bold fs-6 text-danger"><?= number_format($hd['soTienConNo'], 0) ?> đ</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- CỘT BÊN PHẢI: BÀN TÍNH POS NHẬP TIỀN THU BÙ TRỪ KẾ TOÁN (RB-06.1 VÀ NFR-06.2) -->
            <div class="col-md-5">
                <form action="tt_tao_submit.php" method="POST" id="frmPOS">
                    <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">
                    <input type="hidden" name="soHopDong" value="<?= htmlspecialchars($soHD) ?>">
                    <!-- Dữ liệu mồi cho Javascript Cân Bằng Phương Trình Kế Toán (Base_NFR) -->
                    <input type="hidden" id="tongNoThucTe" value="<?= $tongNoCongDon ?>">

                    <!-- MÀN HÌNH RB-06.1 BÙ TRỪ NỢ (CREDIT/DEBT) TRỰC QUAN -->
                    <?php if($tongNoCongDon > 0): ?>
                        <div class="p-3 mb-4 rounded rb-orange text-center shadow-sm">
                            <i class="fa-solid fa-triangle-exclamation mb-1 text-danger d-block fs-3"></i>
                            <h6 class="fw-bold">KHÁCH HÀNG ĐANG ÂM TIỀN - MANG NỢ</h6>
                            <h2 class="fw-bold mb-0"><?= number_format($tongNoCongDon, 0) ?> <span class="fs-5">VNĐ</span></h2>
                        </div>
                    <?php elseif($tongNoCongDon < 0): ?>
                        <div class="p-3 mb-4 rounded rb-green text-center shadow-sm">
                            <i class="fa-solid fa-piggy-bank mb-1 text-success d-block fs-3"></i>
                            <h6 class="fw-bold">HỆ THỐNG GHI NHẬN TÀI KHOẢN DƯ TÍN DỤNG (TIỀN NỘP DƯ KỲ TRƯỚC)</h6>
                            <!-- Âm Tiền tức là Khách Nộp Lố, được quyền trừ bớt tiền cước phí tháng này -->
                            <h2 class="fw-bold mb-0">Dư Cấn: +<?= number_format(abs($tongNoCongDon), 0) ?> <span class="fs-5">VNĐ</span></h2>
                        </div>
                    <?php else: ?>
                        <!-- Không Nợ cũng Không Dư -->
                    <?php endif; ?>


                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fa-solid fa-wallet text-secondary me-1"></i> Tiền Thu Vào Két Bắt Buộc <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg text-end fw-bold text-success border-3 border-success" 
                               name="soTienDaNop_POST" id="inpTienNop" value="0" required oninput="thuatToanLiveBCC()">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">Cấu Trúc Thanh Khoản Phương Thức</label>
                        <select name="phuongThucM" class="form-select border-2 bg-light fw-bold" required>
                            <option value="Tien_Mat">💰 Giao Dịch Tiền Mặt Phân Thể (Cash)</option>
                            <option value="Chuyen_Khoan" selected>🏦 Lệnh Chuyển Khoản Ngân Hàng KTS</option>
                            <option value="Vi_Dien_Tu">📱 Quẹt Mã Momo/VnPay Code QR</option>
                        </select>
                    </div>

                    <!-- NFR-06.2: Thuật Toán Tính Số Dư Nợ Tương Lai  -->
                    <div class="mb-4 p-3 bg-light border rounded text-center">
                        <span class="d-block text-secondary fw-bold small mb-1">Dư Nợ Còn Lại Nhảy Live: </span>
                        <!-- Nếu < 0 thì biến thành Credit, nếu > 0 là Nợ gối đầu kì sau -->
                        <h4 class="m-0 fw-bold" id="lblLiveDuNo">
                            <?= number_format($tongNoCongDon, 0) ?> đ 
                        </h4>
                    </div>

                    <?php if(count($listHoaDon_No) > 0): ?>
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow mt-2 pt-3 pb-3" onclick="return confirm('Hệ thống sẽ chạy Giao Dịch Kế toán ACID phân bổ Thác Nước ngầm. \nBạn cam đoan đã nhận đủ tiền thủ quỹ?')">
                            <i class="fa-solid fa-stamp me-2"></i> THU TIỀN VÀ TRỪ NỢ BILLING
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg w-100 fw-bold mt-2" disabled>
                            <i class="fa-solid fa-lock me-2"></i> HIỆN KHÔNG CÓ PHIẾU CẦN THU
                        </button>
                    <?php endif; ?>

                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    /**
     * NFR-06.2 Giao Thức JS DOM Bù Trừ Đối Chiếu Live Không Trễ Milliseconds
     */
    function thuatToanLiveBCC() {
        const inputNopNode = document.getElementById('inpTienNop');
        const tienKhachBopRa = parseFloat(inputNopNode.value) || 0;
        const noMacDinh = parseFloat(document.getElementById('tongNoThucTe').value) || 0;
        
        const theHienThiDom = document.getElementById('lblLiveDuNo');

        // Công Thức Vàng Cụ Của Phân Số: Dư Nợ Mới = Cảnh Nợ Cũ - Lượng Nộp Vô
        let balanceMoiThieuGia = noMacDinh - tienKhachBopRa;

        // Định dạng tiền tệ cho đẹp mắt
        let f = new Intl.NumberFormat('vi-VN', { style: 'decimal', maximumFractionDigits: 0 }).format(Math.abs(balanceMoiThieuGia));

        if (balanceMoiThieuGia > 0) {
            // Còn Dính Nợ Chút Điểm Trầm (Gối đầu rải tiền tháng sau trả bù tiếp)
            theHienThiDom.innerHTML = `<span class="text-danger"><i class="fa-solid fa-caret-down me-1"></i> Âm Quỹ Kế Tiếp: ${f} đ</span>`;
        } else if (balanceMoiThieuGia < 0) {
            // Khách Trả dư Lố (Ví dụ Nợ 9 triệu, đóng luôn 10 triệu chẳn -> Tính Dương 1 trịu cho tháng sau)
            theHienThiDom.innerHTML = `<span class="text-success"><i class="fa-solid fa-gift me-1"></i> Tín Dụng Lữu Chữ (Plus Cấn Trừ Cho Vòng Kế): +${f} đ</span>`;
        } else {
            // Khớp Sóng Đều Tam Thể Balance Zero
            theHienThiDom.innerHTML = `<span class="text-primary"><i class="fa-solid fa-check-double me-1"></i> Cân Bằng Tuyệt Đối Sạch Nợ Sổ 0</span>`;
        }
    }
    
    // Gọi kích hoạt vòng lặp chớp nhoáng khi load form lên (Để tính Credit cũ nếu có)
    window.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('inpTienNop')) { thuatToanLiveBCC(); }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
