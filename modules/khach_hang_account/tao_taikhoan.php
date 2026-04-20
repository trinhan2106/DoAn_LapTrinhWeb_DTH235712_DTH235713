<?php
/**
 * modules/khach_hang_account/tao_taikhoan.php
 * Giao diện Cấp Tài khoản Khách hàng
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

$pdo = Database::getInstance()->getConnection();

// Nhận mã KH từ URL (nếu có) để auto-select
$targetMaKH = trim($_GET['maKH'] ?? '');

try {
    // Truy vấn Khách Hàng chưa được cấp Account
    $sql = "
        SELECT maKH, tenKH, sdt, email 
        FROM KHACH_HANG 
        WHERE deleted_at IS NULL 
          AND maKH NOT IN (SELECT maKH FROM KHACH_HANG_ACCOUNT)
    ";
    
    // Nếu có targetMaKH, kiểm tra xem họ có thực sự chưa có account không
    if (!empty($targetMaKH)) {
        // Ta vẫn lấy list full nhưng ưu tiên logic hiển thị
    }

    $stmt = $pdo->query($sql);
    $listKH = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Lỗi DB: " . $e->getMessage());
    $listKH = [];
}

$pageTitle = "Cấp Tài Khoản Mới - Admin";
include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <!-- Header -->
                        <div class="mb-4">
                            <h2 class="h3 fw-bold text-navy"><i class="bi bi-person-plus-fill me-2"></i>CẤP TÀI KHOẢN TRUY CẬP</h2>
                            <p class="text-muted small">Cấp tên đăng nhập cho khách hàng để họ có thể xem công nợ và tiện ích.</p>
                        </div>

                        <div class="card shadow border-0 rounded-3">
                            <div class="card-body p-4 p-md-5">
                                <?php if(isset($_SESSION['error_msg'])): ?>
                                    <div class="alert alert-danger fw-bold"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?></div>
                                <?php endif; ?>

                                <form action="tao_taikhoan_submit.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Chọn Khách Hàng <span class="text-danger">*</span></label>
                                        <select name="maKH" class="form-select form-select-lg border-2" required>
                                            <option value="" disabled selected>-- Chọn khách hàng chưa có tài khoản --</option>
                                            <?php foreach($listKH as $kh): ?>
                                                <option value="<?= htmlspecialchars($kh['maKH']) ?>" <?= $kh['maKH'] === $targetMaKH ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($kh['maKH']) ?> - <?= htmlspecialchars($kh['tenKH']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if(empty($listKH) && empty($targetMaKH)): ?>
                                            <div class="form-text text-danger mt-2 fw-bold">Tất cả khách hàng hiện tại đều đã được cấp tài khoản!</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Tên Đăng Nhập (Username) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-person-circle"></i></span>
                                            <input type="text" name="username" class="form-control form-control-lg border-2" 
                                                   placeholder="Ví dụ: khachhang01" required pattern="[a-zA-Z0-9_]{3,}" title="Tối thiểu 3 ký tự, chỉ chữ cái, số và dấu gạch dưới.">
                                        </div>
                                        <div class="form-text mt-2">
                                            <i class="bi bi-info-circle me-1"></i> Mật khẩu mặc định sau khi tạo là: <strong class="text-primary">123456</strong>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-warning-subtle text-warning border border-warning-subtle rounded mb-4 small fw-semibold">
                                        <i class="bi bi-shield-lock me-2"></i> Khách hàng sẽ được yêu cầu đổi mật khẩu ngay trong lần đầu tiên đăng nhập vào cổng thông tin (Tenant Portal).
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 border-top pt-4 mt-2">
                                        <a href="index.php" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
                                        <button type="submit" class="btn btn-primary px-5 fw-bold shadow">
                                            <i class="bi bi-plus-lg me-2"></i>Xác nhận Cấp Tài Khoản
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

</body>
</html>
