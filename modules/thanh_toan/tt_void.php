<?php
// modules/thanh_toan/tt_void.php
/**
 * TRUNG TÂM PHÂN QUYỀN HỦY BỎ BIÊN LAI KẾ TOÁN (VOID INVOICE)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

// ---------------------------------------------------------------------
// BỨC TƯỜNG LỬA CHẶN ROLE CẤP THẤP
// Chỉ Quyền Admin Lõi (1) Hoặc Trưởng Phòng (2) Mới Được Void Hóa Đơn Tránh Thất Thoát Biển Thủ
// ---------------------------------------------------------------------
$role = (int)($_SESSION['role_id'] ?? 4);
if (!in_array($role, [1, 2])) {
    die("
    <div style='background:#fce4ec; border:3px solid #c62828; padding:50px; font-family:sans-serif; text-align:center;'>
        <h1 style='color:#c62828;'>🚫 ACCESS DENIED - QUYỀN TRUY CẬP ĐÃ BỊ TỪ CHỐI</h1>
        <h3>Tài khoản của bạn [Role ID: $role] là Nhân Viên Thường / Khách Hàng. Mọi hành vi Hủy Hóa Đơn Kế Toán đều bị Chặn Đứng theo chuẩn ERP!</h3>
        <p>Hành vi xâm nhập trái phép vào Route nhạy cảm này của bạn đã được ghi LOG báo Đội Ngũ Thanh Tra.</p>
        <a href='../../index.php' style='padding: 10px 20px; text-decoration:none; color:white; background:#1e3a5f;'>Rút Lui Về Trang Chủ An Toàn</a>
    </div>
    ");
}

$idBill = trim($_GET['id'] ?? '');
if (empty($idBill)) die("Loss Access: Trống Mã Bill Kế Toán Tồn Tương.");

$pdo = Database::getInstance()->getConnection();

try {
    // ----------------------------------------------------------------------------------
    // TRÍCH XUẤT CĂN CƯỚC HÓA ĐƠN TRƯỚC KHI THI HÀNH ÁN VOID
    // ----------------------------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT h.soHoaDon, h.soHopDong, h.tongTien, h.soTienDaNop, h.soTienConNo, h.trangThai, h.kyThanhToan, h.lyDo, k.tenKH
        FROM HOA_DON h
        LEFT JOIN HOP_DONG hd ON h.soHopDong = hd.soHopDong
        LEFT JOIN KHACH_HANG k ON hd.maKH = k.maKH
        WHERE h.soHoaDon = ?
    ");
    $stmt->execute([$idBill]);
    $billInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$billInfo) die("Hóa Đơn Sóng Chết: Không tìm thấy mảnh CSDL liên quan tới cái Tên Bill này.");
    if ($billInfo['trangThai'] === 'Void') {
         die("Tệp Bill Này Đã Bị Vãi Xương (Void) Trước Đó. Mọi tác động sau là Thừa thãi!");
    }

} catch (PDOException $e) {
    die("Xung đột CSDL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thẩm Vấn Hủy Biên Lai (Void Invoice)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { background-color: #ECEFF1; }
        .danger-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 15px 40px rgba(183, 28, 28, 0.15); max-width: 850px; margin: 40px auto; border-top: 5px solid #d32f2f; border-bottom: 5px solid #d32f2f;}
        .bill-ticket { background-color: #fff9c4; border: 2px dashed #fbc02d; border-radius: 8px; padding: 20px;}
        .alert-strip { background-color: #ffebee; color: #b71c1c; border-left: 6px solid #d32f2f; padding: 15px; border-radius: 4px; font-weight: bold;}
    </style>
</head>
<body class="p-4">

<div class="container danger-box">
    
    <h2 class="text-center fw-bold mb-4" style="color: #c62828;">
        <i class="fa-solid fa-skull-crossbones me-2 d-block mb-2" style="font-size: 3.5rem;"></i> TRẠM KẾ TOÁN ÂM CHIỀU - VOID HÓA ĐƠN
    </h2>
    <div class="alert-strip mb-4 text-center">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> RÀO CHẮN QUY TRÌNH: Tác vụ của bạn đang phá hủy cấu trúc dòng tiền. Không Thể Đảo Ngược Hậu Quả.
    </div>

    <div class="bill-ticket mb-4 shadow-sm">
        <h5 class="fw-bold text-dark border-bottom border-warning pb-2 mb-3"><i class="fa-solid fa-file-contract me-1 text-danger"></i> HỒ SƠ BIÊN BẢN CHỜ HỦY (TARGET INVOICE)</h5>
        
        <div class="row g-3 fs-5">
            <div class="col-md-6">
                <span class="text-muted small d-block">Mã Serial Code Bill:</span>
                <strong class="text-danger"><?= htmlspecialchars($billInfo['soHoaDon']) ?></strong>
            </div>
            <div class="col-md-6">
                <span class="text-muted small d-block">Khách Hàng Quy Chiếu:</span>
                <strong class="text-primary"><?= htmlspecialchars($billInfo['tenKH'] ?: 'Khách Trống Vô Danh') ?></strong>
            </div>
            <div class="col-md-6">
                <span class="text-muted small d-block">Hộp Đồng Chủ Gọn:</span>
                <strong class="text-dark"><?= htmlspecialchars($billInfo['soHopDong']) ?></strong>
            </div>
            <div class="col-md-6">
                <span class="text-muted small d-block">Trạng Thái Kho Bill Thấy Tạm:</span>
                <span class="badge bg-secondary"><?= htmlspecialchars($billInfo['trangThai']) ?></span>
            </div>
            <div class="col-md-12 border-top border-warning pt-3 mt-3">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <small class="text-muted fw-bold d-block">Tổng Tiền Gốc Billing</small>
                        <h4 class="fw-bold mb-0"><?= number_format($billInfo['tongTien'], 0) ?> đ</h4>
                    </div>
                    <div class="col-md-4 text-center">
                        <small class="text-secondary fw-bold d-block">Tài Sản Khách Đã Nộp</small>
                        <h4 class="fw-bold mb-0 text-success"><?= number_format($billInfo['soTienDaNop'], 0) ?> đ</h4>
                    </div>
                    <div class="col-md-4 text-center border-start border-warning">
                        <small class="text-danger fw-bold d-block">Cảnh Báo Thuật Toán Đền Tiền</small>
                        <?php if($billInfo['soTienDaNop'] > 0): ?>
                            <span class="badge bg-danger mt-1 fs-6">Sẽ Sinh Credit Cấn <?= number_format($billInfo['soTienDaNop'], 0) ?> đ Lại Cho Khách</span>
                        <?php else: ?>
                            <span class="badge bg-success mt-1 fs-6">Sát Nhập An Toàn (Không Gắn Lệ)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <form action="tt_void_submit.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">
        <input type="hidden" name="soHoaDon" value="<?= htmlspecialchars($billInfo['soHoaDon']) ?>">

        <div class="mb-4">
            <label class="form-label fw-bold text-dark fs-5"><i class="fa-solid fa-pen-nib me-2"></i> Trình Bày Rõ Ràng Biên Bản Hủy Hóa Đơn <span class="text-danger">*</span></label>
            <textarea class="form-control border-3 shadow-sm bg-light" name="lyDoVoid" rows="4" placeholder="Ví dụ: Ghi sai chỉ số điện tháng; Kế toán trưởng yêu cầu hủy để dòm mã khác; Sai lệch đơn giá hợp đồng..." required></textarea>
            <div class="form-text text-muted mt-2 fst-italic">Nội dung này sẽ đóng chết vào [Bảo Tàng Log CSDL] làm cơ sở cãi nhau với Cục Thuế.</div>
        </div>

        <div class="d-flex justify-content-between mt-5 border-top pt-4">
            <button type="button" class="btn btn-secondary px-5 fs-5 fw-bold shadow-sm" onclick="window.history.back()">
                <i class="fa-solid fa-rotate-left me-2"></i> Chạy Thoát (Cancel)
            </button>
            <button type="submit" class="btn btn-danger px-5 fs-5 fw-bold shadow-lg" onclick="return confirm('Toàn bộ Cơ Chế Máy Chém sẽ diễn ra. Gồm cả Bù Trừ Điểm TÍN DỤNG. Quyết Định Thực thi?');">
                <i class="fa-solid fa-radiation me-2"></i> TIẾN HÀNH KẾT LIỄU BILL
            </button>
        </div>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
