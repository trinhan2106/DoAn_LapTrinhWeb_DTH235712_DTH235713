<?php
// modules/tien_coc/tc_xuly.php
/**
 * FORM HÀNH KHẢO QUYẾT ĐỊNH XỬ LÝ TIỀN CỌC (HOÀN/TỊCH THU)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

// Ràng buộc quy trình: Chỉ QLN/Admin
$role = (int)($_SESSION['user_role'] ?? 4);
if (!in_array($role, [1, 2])) {
    die("<h2 style='color:red;'>Truy cập bị từ chối: Nút Tòa Án Cọc chỉ cấp cho Quản trị khu vực.</h2>");
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
    die("Lỗi: Mất định hướng Mã Tiền Cọc.");
}

$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->prepare("
        SELECT tc.*, hd.maKH, kh.tenKH, hd.trangThai as sttHopDong
        FROM TIEN_COC tc
        JOIN HOP_DONG hd ON tc.soHopDong = hd.soHopDong
        JOIN KHACH_HANG kh ON hd.maKH = kh.maKH
        WHERE tc.maTienCoc = ? AND tc.trangThai = 1
    ");
    $stmt->execute([$id]);
    $coc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coc) {
        $_SESSION['error_msg'] = "Giao dịch không tồn tại hoặc đã được xử lý (Khóa Chốt Hành Động).";
        header("Location: tc_hienthi.php");
        exit();
    }
} catch (PDOException $e) {
    die("Lỗi DB: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tòa Án Phán Quyết: Tiền Cọc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .card-header-court { background: #1e3a5f; color: white; border-bottom: 4px solid #c9a66b; }
        .amount-display { font-size: 2rem; color: #198754; font-weight: 900; letter-spacing: 2px; }
    </style>
</head>
<body class="p-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header card-header-court p-4 text-center">
                    <h2 class="mb-0 text-uppercase fw-bold"><i class="fa-solid fa-gavel me-2 text-warning"></i>Chấp Hành Xử Lý Cọc</h2>
                </div>
                
                <div class="card-body p-5">
                    <div class="alert alert-info border-info text-center mb-4">
                        Tài Khoản Trung Gian Đang Khóa Số Tiền <br>
                        <span class="amount-display"><?= number_format($coc['soTien'], 0, ',', '.') ?> VNĐ</span>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Mã Phiếu Cọc</p>
                            <h5 class="fw-bold"><code><?= htmlspecialchars($coc['maTienCoc']) ?></code></h5>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted mb-1">Số Hợp Đồng</p>
                            <h5 class="fw-bold text-primary"><?= htmlspecialchars($coc['soHopDong']) ?></h5>
                        </div>
                    </div>
                    <div class="mb-4 pb-4 border-bottom">
                        <p class="text-muted mb-1">Bên Nộp Cọc (Khách Hàng)</p>
                        <h5 class="fw-bold"><?= htmlspecialchars($coc['tenKH']) ?> (Mã: <?= htmlspecialchars($coc['maKH']) ?>)</h5>
                    </div>

                    <!-- THỰC THI PHÁN QUYẾT TÀI CHÍNH -->
                    <form action="tc_xuly_submit.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="maTienCoc" value="<?= htmlspecialchars($coc['maTienCoc']) ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fa-solid fa-scale-unbalanced text-danger me-1"></i> Quyết định chế tài</label>
                            <select name="actionStatus" class="form-select form-select-lg shadow-sm" required>
                                <option value="" disabled selected>-- Ra Lệnh Hành Động Trạng Thái --</option>
                                <option value="2">⚖️ KHÁCH TRẢ PHÒNG ĐÚNG HẠN: Hoàn trả 100% Tiền Cọc</option>
                                <option value="3">🚨 KHÁCH VI PHẠM HỢP ĐỒNG: Tịch thu toàn bộ Tiền Cọc</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fa-solid fa-signature text-secondary me-1"></i> Ghi chú hành vi / Biên bản (Sẽ vào Audit Log)</label>
                            <textarea name="ghiChu" class="form-control" rows="3" placeholder="Ghi rõ lý do tại sao hoàn hoặc tại sao tịch thu (Bắt buộc)..." required></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg fw-bold shadow-sm" onclick="return confirm('SAU KHI QUÁT BÚA, SỐ TIỀN MẶT SẼ BỊ LƯU CHẾT VÀ GHI LOG KIỂM TOÁN. XÁC NHẬN CHỨ?');">
                                <i class="fa-solid fa-stamp me-2"></i>CHỐT HỒ SƠ TÀI CHÍNH
                            </button>
                            <a href="tc_hienthi.php" class="btn btn-light mt-2 text-secondary fw-bold">Hủy bỏ / Quay Lại</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
