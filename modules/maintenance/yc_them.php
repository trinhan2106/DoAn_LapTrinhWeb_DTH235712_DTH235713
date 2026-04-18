<?php
// modules/maintenance/yc_them.php
/**
 * Module: Maintenance Request (Tenant side)
 * Chức năng: Giao diện form gửi yêu cầu kỹ thuật bảo trì.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

// Yêu cầu quyền Khách thuê
if ((int)($_SESSION['user_role'] ?? 0) !== ROLE_KHACH_HANG) {
    die("Access Denied: Chức năng dành riêng cho Khách hàng.");
}

$maKH = $_SESSION['user_id'] ?? '';
$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn danh sách phòng khách đang thuê thực tế dựa trên Hợp đồng còn hiệu lực
    $stmtPhong = $pdo->prepare("
        SELECT DISTINCT p.maPhong, p.tenPhong 
        FROM CHI_TIET_HOP_DONG c 
        JOIN HOP_DONG h ON c.soHopDong = h.soHopDong 
        JOIN PHONG p ON c.maPhong = p.maPhong 
        WHERE h.maKH = ? AND h.trangThai = 'DangHieuLuc'
    ");
    $stmtPhong->execute([$maKH]);
    $listPhong = $stmtPhong->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Maintenance Get Room Error: " . $e->getMessage());
    die("Lỗi hệ thống khi tải danh sách phòng.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Yêu Cầu Bảo Trì Kỹ Thuật</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 bg-white p-4 rounded shadow-sm">
            <h4 class="mb-4 text-primary border-bottom pb-2">Gửi Yêu Cầu Bảo Trì (Maintenance Request)</h4>

            <?php if(isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger px-3 py-2"><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?></div>
            <?php endif; ?>
            <?php if(isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success px-3 py-2"><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?></div>
            <?php endif; ?>

            <form action="yc_them_submit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Chọn Phòng Gặp Sự Cố *</label>
                    <select name="maPhong" class="form-select" required>
                        <option value="" disabled selected>-- Danh sách phòng đang thuê --</option>
                        <?php foreach($listPhong as $phong): ?>
                            <option value="<?= htmlspecialchars($phong['maPhong']) ?>">Phòng <?= htmlspecialchars($phong['tenPhong']) ?> (<?= htmlspecialchars($phong['maPhong']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(empty($listPhong)): ?>
                        <small class="text-danger">Không có phòng hợp lệ do Hợp đồng chưa có hiệu lực.</small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Mức Độ Ưu Tiên (SLA Impact) *</label>
                    <select name="mucDoUT" class="form-select" required>
                        <option value="1">1 - Thấp (Giải quyết trong 72 giờ)</option>
                        <option value="2" selected>2 - Trung Bình (Giải quyết trong 48 giờ)</option>
                        <option value="3">3 - Cao (Giải quyết trong 24 giờ)</option>
                        <option value="4">4 - Khẩn cấp cực độ (Giải quyết trong 4 giờ)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Mô Tả Nhanh Vấn Đề (Mô tả kỹ thuật) *</label>
                    <textarea name="moTa" class="form-control" rows="4" placeholder="Ví dụ: Rò rỉ ống nước khu vực máy lạnh, đèn chớp tắt liên tục..." required></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="../../modules/tenant/dashboard.php" class="btn btn-secondary">Về Cổng Khách Hàng</a>
                    <button type="submit" class="btn btn-primary" <?= empty($listPhong) ? 'disabled' : '' ?>>Gửi Lên Trạm Điều Hành</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
