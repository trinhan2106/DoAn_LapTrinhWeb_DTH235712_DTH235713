<?php
/**
 * modules/ho_so/index.php
 * Chức năng: Xem và cập nhật hồ sơ cá nhân cho mọi vai trò (Admin, Nhân viên, Khách thuê)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Bảo mật: Kiểm tra đăng nhập
kiemTraSession();

$pdo = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$roleId = (int)($_SESSION['user_role'] ?? 0);

// XÁC ĐỊNH LOẠI NGƯỜI DÙNG VÀ LẤY DỮ LIỆU
$userData = null;
if (in_array($roleId, [1, 2, 3])) {
    // NHÂN SỰ (Admin, QLN, Kế toán)
    $stmt = $pdo->prepare("SELECT maNV as id, tenNV as name, chucVu as position, sdt as phone, email, username FROM NHAN_VIEN WHERE maNV = ? AND deleted_at IS NULL");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $userType = 'staff';
} else if ($roleId === 4) {
    // KHÁCH THUÊ
    $stmt = $pdo->prepare("
        SELECT kh.maKH as id, kh.tenKH as name, kh.sdt as phone, kh.email, kh.diaChi as address, kha.username 
        FROM KHACH_HANG kh 
        JOIN KHACH_HANG_ACCOUNT kha ON kh.maKH = kha.maKH 
        WHERE kh.maKH = ? AND kh.deleted_at IS NULL
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $userData['position'] = 'Khách thuê'; // Nhãn tĩnh cho khách hàng
    $userType = 'tenant';
}

if (!$userData) {
    $_SESSION['error_msg'] = "Không tìm thấy dữ liệu người dùng!";
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// XỬ LÝ CẬP NHẬT THÔNG TIN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Lỗi bảo mật: CSRF token không hợp lệ!";
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name) || empty($phone) || empty($email)) {
            $_SESSION['error_msg'] = "Vui lòng điền đầy đủ các trường bắt buộc!";
        } else {
            try {
                if ($userType === 'staff') {
                    $stmtUpdate = $pdo->prepare("UPDATE NHAN_VIEN SET tenNV = ?, sdt = ?, email = ? WHERE maNV = ?");
                    $stmtUpdate->execute([$name, $phone, $email, $userId]);
                } else {
                    $stmtUpdate = $pdo->prepare("UPDATE KHACH_HANG SET tenKH = ?, sdt = ?, email = ?, diaChi = ? WHERE maKH = ?");
                    $stmtUpdate->execute([$name, $phone, $email, $address, $userId]);
                }
                
                // Cập nhật lại session nếu tên thay đổi
                $_SESSION['ten_user'] = $name;
                
                $_SESSION['success_msg'] = "Cập nhật hồ sơ thành công!";
                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                error_log("Update Profile Error: " . $e->getMessage());
                $_SESSION['error_msg'] = "Có lỗi xảy ra trong quá trình cập nhật!";
            }
        }
    }
}

// --- GIAO DIỆN (HEADER) ---
if ($userType === 'staff') {
    // Giao diện Admin/Nhân viên
    require_once __DIR__ . '/../../includes/admin/admin-header.php';
    echo '<div class="admin-layout"><div class="admin-sidebar">';
    require_once __DIR__ . '/../../includes/admin/sidebar.php';
    echo '</div><div class="admin-main-wrapper">';
    require_once __DIR__ . '/../../includes/admin/topbar.php';
    echo '<main class="admin-main-content py-4">';
} else {
    // Giao diện Khách hàng
    require_once __DIR__ . '/../../includes/tenant/header.php';
    echo '<div class="container py-5">';
}
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $userType == 'staff' ? BASE_URL . 'modules/dashboard/admin.php' : BASE_URL . 'modules/tenant/dashboard.php' ?>" class="text-decoration-none">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active" aria-current="page">Hồ sơ cá nhân</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-navy text-white py-3 px-4" style="background-color: #1e3a5f;">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-bounding-box me-2"></i>Thông Tin Cá Nhân</h5>
            </div>
            <div class="card-body p-4 p-lg-5">
                <form action="" method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="row g-4">
                        <!-- CỘT TRÁI: AVATAR & SUMMARY -->
                        <div class="col-lg-4 text-center border-end">
                            <div class="profile-avatar-wrapper mb-3 position-relative d-inline-block">
                                <img src="<?= BASE_URL ?>assets/images/defaults/avatar-user.png" 
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($userData['name']) ?>&background=1e3a5f&color=fff&size=256'"
                                     class="rounded-circle shadow-sm border p-1" width="160" height="160" alt="Avatar">
                            </div>
                            <h4 class="fw-bold text-navy mb-1"><?= e($userData['name']) ?></h4>
                            <div class="badge bg-light text-navy border px-3 py-2 rounded-pill mb-4" style="color: #1e3a5f;">
                                <i class="bi bi-shield-check me-1"></i> <?= e($userData['position'] ?? 'Thành viên') ?>
                            </div>
                            
                            <div class="list-group list-group-flush text-start small border rounded-3 overflow-hidden">
                                <div class="list-group-item bg-light-subtle">
                                    <span class="text-muted d-block small mb-1">Mã định danh</span>
                                    <span class="fw-bold text-navy"><?= e($userData['id']) ?></span>
                                </div>
                                <div class="list-group-item bg-light-subtle">
                                    <span class="text-muted d-block small mb-1">Tài khoản đăng nhập</span>
                                    <span class="fw-bold text-navy"><?= e($userData['username']) ?></span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="doi_mat_khau.php" class="btn btn-outline-danger w-100 rounded-pill fw-bold py-2">
                                    <i class="bi bi-key-fill me-2"></i> Đổi mật khẩu
                                </a>
                            </div>
                        </div>

                        <!-- CỘT PHẢI: FORM CHỈNH SỬA -->
                        <div class="col-lg-8 ps-lg-5">
                            <h5 class="fw-bold text-navy mb-4 pb-2 border-bottom">Chi tiết hồ sơ</h5>
                            
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                        <input type="text" name="name" class="form-control" value="<?= e($userData['name']) ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                        <input type="text" name="phone" class="form-control" value="<?= e($userData['phone']) ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email liên hệ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" value="<?= e($userData['email']) ?>" required>
                                    </div>
                                </div>

                                <?php if ($userType === 'tenant'): ?>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Địa chỉ thường trú</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-geo-alt"></i></span>
                                        <textarea name="address" class="form-control" rows="3"><?= e($userData['address'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Tên đăng nhập (Khóa)</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($userData['username']) ?>" disabled>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted">Chức vụ / Vai trò (Khóa)</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($userData['position']) ?>" disabled>
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-5">
                                <button type="submit" class="btn btn-navy py-2 px-5 rounded-pill fw-bold shadow-sm" style="background-color: #1e3a5f; color: #fff;">
                                    <i class="bi bi-save2-fill me-2"></i> Lưu thay đổi
                                </button>
                                <button type="reset" class="btn btn-light py-2 px-4 rounded-pill fw-bold border">
                                    Hoàn tác
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #1e3a5f; }
    .bg-navy { background-color: #1e3a5f; }
    .btn-navy:hover { background-color: #152943 !important; }
    .form-control:focus {
        border-color: #1e3a5f;
        box-shadow: 0 0 0 0.25rem rgba(30, 58, 95, 0.1);
    }
    .profile-avatar-wrapper:hover::after {
        content: 'Chưa hỗ trợ đổi ảnh';
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.6);
        color: #fff;
        font-size: 10px;
        padding: 4px 8px;
        border-radius: 4px;
        white-space: nowrap;
    }
</style>

<?php
// --- GIAO DIỆN (FOOTER) ---
if ($userType === 'staff') {
    echo '</main></div></div>';
    require_once __DIR__ . '/../../includes/admin/admin-footer.php';
} else {
    echo '</div>';
    require_once __DIR__ . '/../../includes/tenant/footer.php';
}
?>
