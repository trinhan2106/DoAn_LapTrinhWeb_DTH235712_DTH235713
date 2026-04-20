<?php
/**
 * modules/ho_so/index.php
 * Trang hiển thị Hồ sơ cá nhân người dùng
 */
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Xác thực session
kiemTraSession();

// Title trang
$pageTitle = "Hồ sơ cá nhân";

// Nhúng Header Admin
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
                        <div class="card shadow border-0 rounded-3 overflow-hidden">
                            <div class="card-header bg-brand-primary text-white py-3 text-center">
                                <h4 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2"></i>HỒ SƠ CÁ NHÂN</h4>
                            </div>
                            <div class="card-body p-4 text-center">
                                <div class="mb-4">
                                    <div class="avatar-large mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; border: 4px solid #f8f9fa; font-size: 3rem; color: var(--color-primary);">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <h3 class="fw-bold text-navy mb-1"><?= htmlspecialchars($_SESSION['ten_user'] ?? 'Họ Tên') ?></h3>
                                    <span class="badge bg-gold-soft text-brand-accent px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($_SESSION['user_role'] ?? 'Vai trò') ?>
                                    </span>
                                </div>
                                
                                <div class="row text-start g-3 mb-4">
                                    <div class="col-12 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                                        <span class="text-muted"><i class="bi bi-envelope me-2"></i>Tên đăng nhập (Username)</span>
                                        <span class="fw-bold text-navy"><?= htmlspecialchars($_SESSION['username'] ?? 'username') ?></span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="<?= BASE_URL ?>doi_mat_khau_batbuoc.php" class="btn btn-primary py-2 fw-bold">
                                        <i class="bi bi-key me-2"></i>Đổi Mật Khẩu
                                    </a>
                                    <a href="<?= BASE_URL ?>modules/dashboard/admin.php" class="btn btn-outline-secondary py-2">
                                        <i class="bi bi-arrow-left me-2"></i>Về Dashboard
                                    </a>
                                </div>
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
.bg-gold-soft { background-color: rgba(201, 166, 107, 0.1); }
</style>

</body>
</html>
