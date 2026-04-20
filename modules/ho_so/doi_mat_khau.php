<?php
/**
 * modules/ho_so/doi_mat_khau.php
 * Chức năng: Đổi mật khẩu chủ động cho mọi vai trò (Admin, Nhân viên, Khách thuê)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Bảo mật: Kiểm tra đăng nhập
kiemTraSession();

$pdo = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$roleId = (int)($_SESSION['user_role'] ?? 0);
$accountId = $_SESSION['accountId'] ?? null; // Chỉ có ở Tenant

$userType = ($roleId === ROLE_KHACH_HANG) ? 'tenant' : 'staff';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Lỗi bảo mật: CSRF token không hợp lệ!";
    } else {
        $oldPass = $_POST['password_old'] ?? '';
        $newPass = $_POST['password_new'] ?? '';
        $confirmPass = $_POST['password_confirm'] ?? '';

        if (empty($oldPass) || empty($newPass) || empty($confirmPass)) {
            $errorMsg = "Vui lòng nhập đầy đủ các trường.";
        } elseif (strlen($newPass) < 6) {
            $errorMsg = "Mật khẩu mới phải có ít nhất 6 ký tự.";
        } elseif ($newPass !== $confirmPass) {
            $errorMsg = "Xác nhận mật khẩu mới không khớp.";
        } elseif ($oldPass === $newPass) {
            $errorMsg = "Mật khẩu mới không được trùng với mật khẩu cũ.";
        } else {
            try {
                // 2. Lấy hash hiện tại từ DB
                if ($userType === 'staff') {
                    $stmt = $pdo->prepare("SELECT password_hash FROM NHAN_VIEN WHERE maNV = ? AND deleted_at IS NULL");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $pdo->prepare("SELECT password_hash FROM KHACH_HANG_ACCOUNT WHERE accountId = ? AND deleted_at IS NULL");
                    $stmt->execute([$accountId]);
                }
                
                $currentHash = $stmt->fetchColumn();

                if (!$currentHash || !password_verify($oldPass, $currentHash)) {
                    $errorMsg = "Mật khẩu hiện tại không chính xác.";
                } else {
                    // 3. Cập nhật mật khẩu mới
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    
                    if ($userType === 'staff') {
                        $stmtUpdate = $pdo->prepare("UPDATE NHAN_VIEN SET password_hash = ?, phai_doi_matkhau = 0 WHERE maNV = ?");
                        $stmtUpdate->execute([$newHash, $userId]);
                    } else {
                        $stmtUpdate = $pdo->prepare("UPDATE KHACH_HANG_ACCOUNT SET password_hash = ?, phai_doi_matkhau = 0 WHERE accountId = ?");
                        $stmtUpdate->execute([$newHash, $accountId]);
                    }

                    $_SESSION['success_msg'] = "Đổi mật khẩu thành công!";
                    header("Location: index.php");
                    exit();
                }
            } catch (PDOException $e) {
                error_log("Change Password Error: " . $e->getMessage());
                $errorMsg = "Có lỗi xảy ra trong quá trình xử lý hệ thống.";
            }
        }
    }
}

// --- GIAO DIỆN (HEADER) ---
if ($userType === 'staff') {
    require_once __DIR__ . '/../../includes/admin/admin-header.php';
    echo '<div class="admin-layout"><div class="admin-sidebar">';
    require_once __DIR__ . '/../../includes/admin/sidebar.php';
    echo '</div><div class="admin-main-wrapper">';
    require_once __DIR__ . '/../../includes/admin/topbar.php';
    echo '<main class="admin-main-content py-4">';
} else {
    require_once __DIR__ . '/../../includes/tenant/header.php';
    echo '<div class="container py-5">';
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Hồ sơ cá nhân</a></li>
                <li class="breadcrumb-item active" aria-current="page">Đổi mật khẩu</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-danger text-white py-3 px-4">
                <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock-fill me-2"></i>Đổi Mật Khẩu Bảo Mật</h5>
            </div>
            <div class="card-body p-4 p-lg-5">
                
                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= e($errorMsg) ?></div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password_old" class="form-control" required placeholder="Nhập mật khẩu đang dùng">
                        </div>
                    </div>

                    <hr class="my-4 opacity-50">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mật khẩu mới <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                            <input type="password" name="password_new" class="form-control" required placeholder="Tối thiểu 6 ký tự">
                        </div>
                        <div class="form-text mt-2 small">Gợi ý: Sử dụng kết hợp chữ hoa, chữ thường và số để tăng tính bảo mật.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-check2-circle"></i></span>
                            <input type="password" name="password_confirm" class="form-control" required placeholder="Nhập lại mật khẩu mới">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-arrow-repeat me-2"></i> Xác nhận Thay Đổi
                        </button>
                        <a href="index.php" class="btn btn-light btn-lg rounded-pill fw-bold border">
                            Hủy bỏ & Quay lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.1);
    }
</style>

<?php
if ($userType === 'staff') {
    echo '</main></div></div>';
    require_once __DIR__ . '/../../includes/admin/admin-footer.php';
} else {
    echo '</div>';
    require_once __DIR__ . '/../../includes/tenant/footer.php';
}
?>
