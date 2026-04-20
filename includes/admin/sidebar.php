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
    <div class="sidebar-header border-bottom border-light border-opacity-10 py-4 px-4 d-flex align-items-center justify-content-between">
        <h5 class="fw-bold d-flex align-items-center mb-0" style="color: inherit;">
            <i class="bi bi-buildings me-2 fs-4 text-brand-accent"></i>
            <span class="d-flex flex-column">
                <span class="text-white fs-6 lh-1 opacity-75" style="font-size: 0.7rem !important; letter-spacing: 1px;">HỆ THỐNG</span>
                <span class="text-brand-accent fw-bold" style="letter-spacing: 1.5px; font-size: 1rem;">QUẢN LÝ CAO ỐC</span>
            </span>
        </h5>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0 py-2">
        <ul class="nav flex-column w-100 admin-sidebar__nav">
            <!-- Tổng quan -->
            <li class="nav-item">
                <a class="nav-link <?= (is_nav_active('modules/dashboard/admin.php', $current_script) || is_nav_active('admin_layout.php', $current_script)) ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>modules/dashboard/admin.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            <hr class="admin-sidebar__divider">
            <span class="admin-sidebar__section-label">Tài sản</span>

            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/cao_oc/', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/cao_oc/index.php">
                    <i class="bi bi-building me-2"></i> Cao ốc
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/tang/', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/tang/index.php">
                    <i class="bi bi-layers me-2"></i> Tầng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/phong/', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/phong/phong_hienthi.php">
                    <i class="bi bi-door-open me-2"></i> Phòng
                </a>
            </li>

            <hr class="admin-sidebar__divider">
            <span class="admin-sidebar__section-label">Khách hàng</span>

            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/khach_hang/', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/khach_hang/kh_hienthi.php">
                    <i class="bi bi-people me-2"></i> Khách hàng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/khach_hang_account/', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/khach_hang_account/index.php">
                    <i class="bi bi-person-badge me-2"></i> Tài khoản KH
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/yeu_cau_thue/', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/yeu_cau_thue/yc_hienthi.php">
                    <i class="bi bi-envelope-paper me-2"></i> Yêu cầu thuê phòng
                </a>
            </li>

            <hr class="admin-sidebar__divider">
            <span class="admin-sidebar__section-label">Tài chính</span>

            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/hop_dong/hd_hienthi.php', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/hop_dong/hd_hienthi.php">
                    <i class="bi bi-file-earmark-text me-2"></i> Hợp đồng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/thanh_toan/tt_tao.php', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/thanh_toan/tt_tao.php">
                    <i class="bi bi-receipt me-2"></i> Lập hóa đơn
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/thanh_toan/dien_nuoc_ghi.php', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/thanh_toan/dien_nuoc_ghi.php">
                    <i class="bi bi-lightning-charge me-2"></i> Điện / Nước
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/thanh_toan/tranh_chap', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/thanh_toan/tranh_chap_hienthi.php">
                    <i class="bi bi-shield-exclamation me-2"></i> Tranh chấp
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_nav_active('modules/tien_coc/coc_hienthi.php', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/tien_coc/coc_hienthi.php">
                    <i class="bi bi-cash-stack me-2"></i> Tiền cọc
                </a>
            </li>

            <hr class="admin-sidebar__divider">
            <span class="admin-sidebar__section-label">Phân tích</span>

            <li class="nav-item mb-2">
                <a class="nav-link <?= is_nav_active('modules/bao_cao/', $current_script) ?>"
                   href="<?= BASE_URL ?>modules/bao_cao/bao_cao.php">
                    <i class="bi bi-graph-up-arrow me-2"></i> Báo cáo & Thống kê
                </a>
            </li>
        </ul>
    </div>
</div>
