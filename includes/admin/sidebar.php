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
                <!-- Sửa active tùy theo trang hiện tại (có thể xử lý bằng PHP sau) -->
                <a class="nav-link text-white active" href="admin_layout.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard (Tổng quan)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="quan_ly_cao_oc.php">
                    <i class="bi bi-building me-2"></i> Quản lý Cao ốc
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="tang.php">
                    <i class="bi bi-layers me-2"></i> Tầng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="phong.php">
                    <i class="bi bi-door-open me-2"></i> Phòng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="khach_hang.php">
                    <i class="bi bi-people me-2"></i> Khách hàng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="tai_khoan_khach_thue.php">
                    <i class="bi bi-person-badge me-2"></i> Tài khoản khách thuê
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="hop_dong.php">
                    <i class="bi bi-file-earmark-text me-2"></i> Hợp đồng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="hoa_don.php">
                    <i class="bi bi-receipt me-2"></i> Hóa đơn
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="tien_coc.php">
                    <i class="bi bi-cash-stack me-2"></i> Tiền cọc (Tien Coc)
                </a>
            </li>
        </ul>
    </div>
</div>
