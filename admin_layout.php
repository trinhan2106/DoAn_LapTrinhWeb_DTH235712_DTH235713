<?php
session_start();

// Demo: Khởi tạo biến session để test hiển thị, trong thực tế sẽ lấy lúc đăng nhập
if (!isset($_SESSION['ten_user'])) {
    $_SESSION['ten_user'] = 'Admin Demo';
    $_SESSION['user_role'] = 'Quản trị viên hệ thống';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Lắp ghép Header Partial -->
    <?php include_once 'includes/admin/admin-header.php'; ?>
</head>
<body>

<div class="admin-layout">
    
    <!-- Lắp ghép Sidebar (Offcanvas trên Mobile, Tĩnh trên Desktop) -->
    <?php include_once 'includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper">
        
        <!-- Lắp ghép Topbar và Flash Messages -->
        <?php include_once 'includes/admin/topbar.php'; ?>
        
        <!-- Vùng nội dung chính -->
        <main class="admin-main-content">
            <div class="container-fluid px-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0 text-brand-primary fw-bold">Dashboard</h2>
                </div>
                
                <div class="row g-4">
                    <!-- Khu vực Chart.js -->
                    <div class="col-12 col-xl-8">
                        <div class="card-brand h-100">
                            <div class="card-brand__header">
                                <h3 class="card-brand__title">Tổng quan Doanh thu (Chart.js Placeholder)</h3>
                            </div>
                            <div class="card-brand__body d-flex align-items-center justify-content-center" style="min-height: 300px; background-color: var(--color-background); border-radius: 8px; margin: 1rem;">
                                <p class="text-muted"><i class="bi bi-bar-chart fs-3 d-block text-center mb-2"></i>Khu vực dành cho Chart.js hiển thị biểu đồ</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Khu vực DataTables -->
                    <div class="col-12 col-xl-4">
                        <div class="card-brand h-100">
                            <div class="card-brand__header">
                                <h3 class="card-brand__title">Hoạt động gần đây (DataTables Placeholder)</h3>
                            </div>
                            <div class="card-brand__body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">ID</th>
                                                <th>Mô tả hoạt động</th>
                                                <th class="pe-3 text-end">Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="ps-3 fw-bold">#1024</td>
                                                <td>Thanh toán hóa đơn tháng 4</td>
                                                <td class="pe-3 text-end"><span class="badge badge-brand badge-brand--success">Hoàn thành</span></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 fw-bold">#1025</td>
                                                <td>Ký hợp đồng phòng 302</td>
                                                <td class="pe-3 text-end"><span class="badge badge-brand badge-brand--warning">Chờ duyệt</span></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 fw-bold">#1026</td>
                                                <td>Phản hồi lỗi máy lạnh Tầng 2</td>
                                                <td class="pe-3 text-end"><span class="badge badge-brand badge-brand--danger">Chưa xử lý</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Lắp ghép Footer và các script JS -->
        <?php include_once 'includes/admin/admin-footer.php'; ?>
        
    </div>
</div>

</body>
</html>
