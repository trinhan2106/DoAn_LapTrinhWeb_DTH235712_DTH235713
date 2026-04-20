<?php
$ten_user = isset($_SESSION['ten_user']) ? $_SESSION['ten_user'] : 'Admin User';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Quản trị viên';
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';

// Đảm bảo hằng số BASE_URL luôn khả dụng (đặc biệt khi topbar được nạp độc lập)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/constants.php';
}
?>
<header class="admin-topbar bg-white shadow-sm d-flex justify-content-between align-items-center px-4 py-3 sticky-top">
    <div class="d-flex align-items-center gap-3 flex-grow-1">
        <!-- Nút toggle sidebar cho mobile -->
        <button class="btn btn-outline-secondary d-lg-none me-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fs-5 fw-bold text-brand-primary d-none d-lg-block m-0 me-2" style="white-space:nowrap;">Hệ thống Quản lý Cao ốc</span>

        <!-- Expandable Search Bar (WOW UI) -->
        <div class="expandable-search ms-2" id="searchContainer">
            <button class="search-toggle" id="searchToggle" title="Tìm kiếm">
                <i class="bi bi-search"></i>
            </button>
            
            <form method="GET" action="<?= BASE_URL ?>modules/dashboard/tim_kiem.php" class="search-form" id="searchForm">
                <div class="input-group">
                    <input
                        type="search"
                        name="s"
                        id="topbar-search-input"
                        class="form-control"
                        placeholder="Tìm khách hàng, hợp đồng, phòng..."
                        value="<?php echo isset($_GET['s']) ? htmlspecialchars($_GET['s'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                        autocomplete="off"
                    >
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-search"></i>
                    </button>
                    <button type="button" class="btn-close-search" id="searchClose">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </form>
        </div>
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

<style>
/* ── Expandable Search Styles ────────────────────────── */
.expandable-search {
    position: relative;
    display: flex;
    align-items: center;
    flex-grow: 1;
    max-width: fit-content;
}

.search-toggle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: none;
    background: #f8f9fa;
    color: #1e3a5f;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.search-toggle:hover {
    background: #1e3a5f;
    color: #fff;
    transform: scale(1.05);
}

.search-form {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    z-index: 10;
}

.expandable-search.active .search-form {
    width: 700px;
    opacity: 1;
    visibility: visible;
}

@media (max-width: 1200px) {
    .expandable-search.active .search-form { width: 450px; }
}
@media (max-width: 991px) {
    .expandable-search.active .search-form { width: 300px; }
}

.expandable-search.active .search-toggle {
    opacity: 0;
    visibility: hidden;
}

.search-form .input-group {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(30, 58, 95, 0.15);
    overflow: hidden;
    border: 1px solid #e0e0e0;
}

.search-form .form-control {
    border: none;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    box-shadow: none;
}

.btn-submit, .btn-close-search {
    border: none;
    background: transparent;
    padding: 0 1rem;
    color: #6c757d;
    transition: color 0.2s;
}
.btn-submit:hover { color: #1e3a5f; }
.btn-close-search { border-left: 1px solid #eee; }
.btn-close-search:hover { color: #dc3545; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchContainer = document.getElementById('searchContainer');
    const searchToggle = document.getElementById('searchToggle');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('topbar-search-input');

    searchToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        searchContainer.classList.add('active');
        setTimeout(() => searchInput.focus(), 100);
    });

    searchClose.addEventListener('click', function(e) {
        e.stopPropagation();
        searchContainer.classList.remove('active');
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchContainer.contains(e.target)) {
            searchContainer.classList.remove('active');
        }
    });

    // Handle ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchContainer.classList.remove('active');
        }
    });
});
</script>

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
