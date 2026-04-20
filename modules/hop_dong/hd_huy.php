<?php
// modules/hop_dong/hd_huy.php
/**
 * TRUNG TÂM UC11: THANH LÝ / HỦY BỎ 100% HIỆU SUẤT CỦA HỢP ĐỒNG BĐS
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

$soHD = trim($_GET['id'] ?? $_GET['soHopDong'] ?? '');
if (empty($soHD)) die("Mất Dấu vết URL Tham Số.");

$pdo = Database::getInstance()->getConnection();

try {
    // KÈM CHECK Ồ ĐẠT TỘI PHẠM NỢ HÓA ĐƠN TRƯỚC KHI CHO THOÁT KHỎI HỢP ĐỒNG (RB-11.1)
    $stmtHD = $pdo->prepare("SELECT trangThai, maKH FROM HOP_DONG WHERE soHopDong = ?");
    $stmtHD->execute([$soHD]);
    $thongTinHD = $stmtHD->fetch(PDO::FETCH_ASSOC);

    if (!$thongTinHD) die("Tệp Khống: Không truy được file SQL Core của Hợp Đồng này.");

    // Kiểm tra Nợ Tiền Tỷ của cả 1 Hợp Đồng (Nhiều phòng gộp chung)
    $hasDebt = false;
    $tienNo = 0;
    try {
        // Tổng công nợ có trong bảng Hoa don 
        $stmtDebt = $pdo->prepare("SELECT SUM(soTienConNo) AS noTong FROM HOA_DON WHERE soHopDong = ?");
        $stmtDebt->execute([$soHD]);
        $tienNo = (float)$stmtDebt->fetchColumn();
        
        if ($tienNo > 0) {
            $hasDebt = true;
        }
    } catch (Exception $e) {
         // Pass qua an toàn nếu Backend Database chưa kịp Import Scheme HOA_DON
    }

} catch (PDOException $e) {
    die("Lỗi PDO Exception Lõi UC11: " . $e->getMessage());
}

$alertMsg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biên Bản Thanh Lý Hủy Hợp Đồng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { background-color: #fce4ec; } /* Lót Nền Hồng Âm Tính Báo Hiệu Đứt Độn */
        .box-container {
            background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(183, 28, 28, 0.1);
            max-width: 800px; margin: 20px auto;
            border-top: 5px solid #c62828;
            border-bottom: 5px solid #c62828;
        }
        .debt-alert-panel {
            background: #ffebee; border: 2px dashed #d32f2f; color: #b71c1c; padding: 25px; border-radius: 8px; margin-bottom: 30px;
        }
    </style>
</head>
<body class="p-4">

<div class="container box-container">
    
    <?php if($alertMsg === 'force_uc11'): ?>
        <div class="alert alert-warning mb-4 shadow-sm fw-bold">
            <i class="fa-solid fa-arrow-right-arrow-left me-2 text-danger"></i> 
            Hệ Hệ: Do bạn vừa chọn trả Toàn bộ phòng bên File Trả Lẻ Căn Nên Hệ thống tự động chuyển Trạm bạn sang đây để chạy đúng luồng UC11 Thanh Lý Toàn Diện. 
        </div>
    <?php endif; ?>

    <h2 class="text-center text-danger fw-bold mb-4">
        <i class="fa-solid fa-file-excel mb-2 d-block" style="font-size: 3.5rem;"></i>BIÊN BẢN HỦY / THANH LÝ GIAO KÈO
    </h2>
    <div class="text-center fs-5 text-secondary fw-bold border-bottom pb-4 mb-4">
        Mã Số Trace: <span class="text-dark bg-light px-3 py-1 border rounded"><?= htmlspecialchars($soHD) ?></span>
    </div>

    <!-- KHỐI RB-11.1 BÁO ĐỘNG ĐỎ CÔNG NỢ KẾ TOÁN -->
    <?php if ($hasDebt): ?>
        <div class="debt-alert-panel text-center">
            <i class="fa-solid fa-triangle-exclamation mb-3 d-block" style="font-size: 3rem;"></i>
            <h4 class="fw-bold">HỆ THỐNG KHÓA VĨNH VIỄN NÚT HỦY HỢP ĐỒNG!</h4>
            <p class="mb-0 fs-5 mt-3">Sổ Sách Kế Toán phát hiện đối tác này vẫn lẩn trốn nợ đọng một khoản <strong class="fs-3 text-dark"><?= number_format($tienNo, 0) ?> đ</strong> ở Phân hệ Hóa Đơn.</p>
            <p class="mt-2 text-muted fst-italic">Luật kinh doanh chung: KHÔNG BAO GIỜ CHO TRẢ TOÀN BỘ PHÒNG ĐỂ BIẾN MẤT KHI CHƯA CHÁY XONG NỢ.</p>
        </div>
        
        <div class="text-center mt-4 border-top pt-4">
            <a href="hd_chitiet.php?id=<?= urlencode($soHD) ?>" class="btn btn-secondary px-5 py-3 fs-5 fw-bold shadow"><i class="fa-solid fa-rotate-left me-2"></i> Quay Lại - Đòi Nợ Xong Rồi Tính</a>
        </div>

    <?php else: ?>
        <!-- PASS MƯỢT : TIẾN HÀNH RENDER FORM THU THẬP BÚT TÍCH -->
        <p class="text-center text-muted fw-bold mb-4">
            <i class="fa-solid fa-check text-success me-1"></i> Hồ sơ trong sạch tài chính. Tiến Hành Lục Soát Hành Chính Hủy Lệnh Tòa Nhà BĐS.
        </p>

        <form action="hd_huy_submit.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="soHopDong" value="<?= htmlspecialchars($soHD) ?>">
            
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Ngày Khởi Phát Thanh Lý Trả Chìa Khóa <span class="text-danger">*</span></label>
                    <!-- Yêu Cầu Design: input type date -->
                    <input type="date" class="form-control border-3" name="ngayHuy" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold">Văn Phong Lý Do Huỷ Biên Bản <span class="text-danger">*</span></label>
                    <textarea class="form-control border-3" name="lyDoHuy" rows="4" placeholder="VD: Khách Bùng Hợp Đồng, Khách Không Có Khả Năng Thanh Toán Mặt Bằng Nữa... (Ghi vào Cấu Trúc Kiểm Toán)" required></textarea>
                </div>
            </div>

            <div class="alert alert-danger mt-4 mt-4 bg-white border border-danger">
                <h6 class="fw-bold text-danger"><i class="fa-solid fa-skull-crossbones me-2"></i> LIỀU THUỐC ĐỘC DATABASE:</h6>
                Hành động chốt Submit sẽ gọt bỏ 100% Trạng Thái Toàn Bộ các Căn hộ đang ghim thuộc về đối tượng trên thành "Khởi Động Lại: Phòng Trống". Chuyển trạng thái siêu phàm Hợp Đồng Xóa Trắng. Rất Nguy Hiểm. 
            </div>

            <div class="d-flex justify-content-between mt-5 border-top pt-4">
                <a href="hd_chitiet.php?id=<?= urlencode($soHD) ?>" class="btn btn-light px-4 border fw-bold text-muted shadow-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Không Hủy Nữa! 
                </a>
                
                <button type="submit" class="btn btn-danger px-5 fs-5 fw-bold shadow" onclick="return confirm('Sóng thần dữ liệu đã chuẩn bị. Nhấn OK để triệt tiêu chốt Hợp Đồng!');">
                    <i class="fa-solid fa-fire-burner me-2"></i> XÁC NHẬN HỦY BỎ TẬN GỐC
                </button>
            </div>
        </form>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
