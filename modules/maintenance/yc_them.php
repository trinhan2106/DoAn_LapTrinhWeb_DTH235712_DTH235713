<?php
/**
 * modules/maintenance/yc_them.php
 * UI: Form gửi yêu cầu sửa chữa/bảo trì (Task 9.3)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Bảo mật: Admin & Quản lý tòa nhà mới được tạo yêu cầu kỹ thuật
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

$db = Database::getInstance()->getConnection();

// Lấy danh sách phòng đang có người thuê (Trạng thái = 2) để gán yêu cầu
try {
    $stmtRooms = $db->query("SELECT maPhong, tenPhong FROM PHONG WHERE trangThai = 2 AND deleted_at IS NULL ORDER BY maPhong ASC");
    $rentedRooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("[yc_them.php] Error: " . $e->getMessage());
    $rentedRooms = [];
}

$pageTitle = "Gửi Yêu Cầu Bảo Trì";
include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h3 fw-bold text-navy mb-0"><i class="bi bi-tools me-2 text-gold-accent"></i> TẠO YÊU CẦU BẢO TRÌ</h2>
                        <p class="text-muted small mb-0">Gửi yêu cầu sửa chữa kỹ thuật cho các mặt bằng đang khai thác.</p>
                    </div>
                    <a href="yc_quan_ly.php" class="btn btn-outline-secondary btn-sm shadow-sm ring-0">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-navy text-white py-3 px-4">
                                <h5 class="card-title mb-0 small text-uppercase fw-bold"><i class="bi bi-pencil-square me-2"></i> Nội dung yêu cầu</h5>
                            </div>
                            <div class="card-body p-4 p-md-5">
                                
                                <!-- Alert thông báo lỗi (nếu có) -->
                                <?php if (isset($_SESSION['error_msg'])): ?>
                                    <div class="alert alert-danger shadow-sm border-0 mb-4">
                                        <i class="bi bi-exclamation-octagon-fill me-2"></i> <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                                    </div>
                                <?php endif; ?>

                                <form action="yc_them_submit.php" method="POST" id="formMaintenance">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

                                    <div class="row g-4">
                                        <!-- Chọn phòng -->
                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <select class="form-select border-focus-navy shadow-none" id="maPhong" name="maPhong" required>
                                                    <option value="" selected disabled>--- Chọn phòng cần sửa ---</option>
                                                    <?php foreach ($rentedRooms as $room): ?>
                                                        <option value="<?= htmlspecialchars($room['maPhong']) ?>">
                                                            <?= htmlspecialchars($room['maPhong']) ?> - <?= htmlspecialchars($room['tenPhong']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="maPhong" class="fw-bold"><i class="bi bi-door-open me-2"></i>Phòng / Mặt bằng</label>
                                            </div>
                                        </div>

                                        <!-- Mức độ ưu tiên -->
                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <select class="form-select border-focus-navy shadow-none" id="mucDoUT" name="mucDoUT" required>
                                                    <option value="1">1. Thấp (Bình thường)</option>
                                                    <option value="2" selected>2. Trung Bình</option>
                                                    <option value="3">3. Cao (Cần xử lý sớm)</option>
                                                    <option value="4">4. Khẩn cấp (Nguy hiểm/Hỏng hóc nặng)</option>
                                                </select>
                                                <label for="mucDoUT" class="fw-bold"><i class="bi bi-flag-fill me-2"></i>Mức độ ưu tiên</label>
                                            </div>
                                        </div>

                                        <!-- Tiêu đề yêu cầu -->
                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <input type="text" class="form-control border-focus-navy shadow-none" id="tieuDe" name="tieuDe" placeholder="Ví dụ: Hỏng vòi nước, Điều hòa không mát..." required>
                                                <label for="tieuDe" class="fw-bold"><i class="bi bi-chat-left-text me-2"></i>Tiêu đề ngắn gọn</label>
                                            </div>
                                        </div>

                                        <!-- Mô tả chi tiết -->
                                        <div class="col-12">
                                            <div class="form-group mb-4">
                                                <label for="moTa" class="form-label fw-bold small text-muted text-uppercase mb-2"><i class="bi bi-info-circle me-1"></i> Mô tả tình trạng chi tiết</label>
                                                <textarea class="form-control border-focus-navy shadow-none" id="moTa" name="moTa" rows="5" placeholder="Mô tả cụ thể vị trí hỏng, hiện tượng xảy ra và các yêu cầu kỹ thuật đi kèm nếu có..." required></textarea>
                                            </div>
                                        </div>

                                        <div class="col-12 text-center">
                                            <hr class="my-4 opacity-10">
                                            <button type="submit" class="btn btn-navy btn-lg px-5 py-3 fw-bold shadow-sm rounded-3">
                                                <i class="bi bi-send-fill me-2"></i> GỬI YÊU CẦU KỸ THUẬT
                                            </button>
                                        </div>
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

<style>
    .bg-navy { background-color: #1e3a5f !important; }
    .text-navy { color: #1e3a5f !important; }
    .btn-navy {
        background-color: #1e3a5f;
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-navy:hover {
        background-color: #152943;
        color: #c9a66b;
        transform: translateY(-2px);
    }
    .form-control:focus, .form-select:focus {
        border-color: #1e3a5f;
        box-shadow: 0 0 0 0.25rem rgba(30, 58, 95, 0.1);
    }
    .text-gold-accent { color: #c9a66b; }
</style>

</body>
</html>
