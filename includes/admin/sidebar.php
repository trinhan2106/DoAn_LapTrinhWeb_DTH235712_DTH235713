<?php
/**
 * includes/admin/sidebar.php
 * Sidebar điều hướng cho giao diện Admin
 */

// Đảm bảo BASE_URL có sẵn
if (!defined('BASE_URL')) {
    // Trèo lên 2 cấp từ includes/admin/ để tới root, rồi vào config/
    require_once __DIR__ . '/../../config/constants.php';
}

// Lấy đường dẫn hiện tại để xử lý active menu
$current_script = $_SERVER['SCRIPT_NAME'];

// Helper function để kiểm tra xem menu có đang active không (tránh lỗi redeclared)
if (!function_exists('is_nav_active')) {
    function is_nav_active($keyword, $script_name) {
        return (strpos($script_name, $keyword) !== false) ? 'active' : '';
    }
}
?>
<div class="offcanvas-lg offcanvas-start bg-brand-primary text-white admin-sidebar" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header border-bottom border-light border-opacity-10 py-3 px-4">
        <h5 class="offcanvas-title text-brand-accent fw-bold fs-5 d-flex align-items-center mb-0" id="sidebarMenuLabel">
            <i class="bi bi-buildings me-2 fs-4"></i> OfficeAdmin
        </h5>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0 pt-3">
        <ul class="nav flex-column w-100 admin-sidebar__nav">
            <li class="nav-item">
                <!-- Sửa lại link Dashboard trỏ đúng vào module dashboard -->
                <a class="nav-link text-white <?= (is_nav_active('modules/dashboard/admin.php', $current_script) || is_nav_active('admin_layout.php', $current_script)) ? 'active' : '' ?>" href="<?= BASE_URL ?>modules/dashboard/admin.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard (Tổng quan)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/cao_oc/', $current_script) ?>" href="<?= BASE_URL ?>modules/cao_oc/index.php">
                    <i class="bi bi-building me-2"></i> Quản lý Cao ốc
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/tang/', $current_script) ?>" href="<?= BASE_URL ?>modules/tang/index.php">
                    <i class="bi bi-layers me-2"></i> Tầng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/phong/', $current_script) ?>" href="<?= BASE_URL ?>modules/phong/phong_hienthi.php">
                    <i class="bi bi-door-open me-2"></i> Phòng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/khach_hang/', $current_script) ?>" href="<?= BASE_URL ?>modules/khach_hang/kh_hienthi.php">
                    <i class="bi bi-people me-2"></i> Khách hàng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/tai_khoan/', $current_script) ?>" href="<?= BASE_URL ?>modules/tai_khoan/index.php">
                    <i class="bi bi-person-badge me-2"></i> Tài khoản khách thuê
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/hop_dong/', $current_script) ?>" href="<?= BASE_URL ?>modules/hop_dong/index.php">
                    <i class="bi bi-file-earmark-text me-2"></i> Hợp đồng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/hoa_don/', $current_script) ?>" href="<?= BASE_URL ?>modules/hoa_don/index.php">
                    <i class="bi bi-receipt me-2"></i> Hóa đơn
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/thanh_toan/tranh_chap', $current_script) ?>" href="<?= BASE_URL ?>modules/thanh_toan/tranh_chap_hienthi.php">
                    <i class="bi bi-shield-exclamation me-2"></i> Tranh chấp hóa đơn
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= is_nav_active('modules/tien_coc/', $current_script) ?>" href="<?= BASE_URL ?>modules/tien_coc/index.php">
                    <i class="bi bi-cash-stack me-2"></i> Tiền cọc (Tien Coc)
                </a>
            </li>
        </ul>
    </div>
</div>
