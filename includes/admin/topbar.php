<?php
$ten_user = isset($_SESSION['ten_user']) ? $_SESSION['ten_user'] : 'Admin User';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Quản trị viên';
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
?>
<header class="admin-topbar bg-white shadow-sm d-flex justify-content-between align-items-center px-4 py-3 sticky-top">
    <div class="d-flex align-items-center">
        <!-- Nút toggle sidebar cho mobile -->
        <button class="btn btn-outline-secondary d-lg-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fs-5 fw-bold text-brand-primary d-none d-lg-block m-0">Hệ thống Quản lý Cao ốc</span>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <!-- Nút Toggle Dark Mode -->
        <button class="btn btn-light rounded-circle shadow-sm" id="themeToggle" title="Toggle Dark Mode" data-user-id="<?php echo htmlspecialchars($current_user_id, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-moon"></i>
        </button>
        
        <!-- User Dropdown Menu -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border-0 shadow-sm" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-4 text-brand-primary"></i>
                <div class="text-start d-none d-md-block">
                    <!-- Bảo vệ chống XSS bằng htmlspecialchars -->
                    <div class="small fw-bold lh-1 mb-1"><?php echo htmlspecialchars($ten_user, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="small text-muted lh-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenuButton">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>index.php"><i class="bi bi-house-door me-2 text-muted"></i>Trang chủ hệ thống</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2 text-muted"></i>Hồ sơ cá nhân</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2 text-muted"></i>Cài đặt</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>dangxuat.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</header>

<!-- Flash Messages Area (Nằm cố định bên dưới Topbar) -->
<div class="admin-flash-messages px-4 pt-3 pb-0">
    <!-- Màu Xanh cho thành công -->
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3 shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['success_msg'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- Màu Đỏ cho lỗi -->
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3 shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['error_msg'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>
    
    <!-- Màu Cam cho cảnh báo -->
    <?php if (isset($_SESSION['warning_msg'])): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-3 shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['warning_msg'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['warning_msg']); ?>
    <?php endif; ?>
</div>
