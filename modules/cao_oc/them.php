<?php
/**
 * modules/cao_oc/them.php
 * Giao diện thêm mới Cao ốc - Thiết kế đồng nhất hệ thống
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Tạo CSRF Token cho form
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .form-card {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
        }
        .form-header {
            background-color: #1e3a5f;
            color: white;
            padding: 1.5rem;
        }
        .btn-gold {
            background-color: #c9a66b;
            color: white;
            font-weight: 600;
            padding: 0.6rem 2rem;
            border: none;
            transition: all 0.3s;
        }
        .btn-gold:hover {
            background-color: #b5925a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(201, 166, 107, 0.4);
        }
        .form-label {
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 0.5rem;
        }
        .form-control:focus {
            border-color: #c9a66b;
            box-shadow: 0 0 0 0.25rem rgba(201, 166, 107, 0.15);
        }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4 d-flex justify-content-center">
                <ol class="breadcrumb mb-0" style="width: 800px;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Quản lý Cao ốc</a></li>
                    <li class="breadcrumb-item active">Thêm cao ốc mới</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header">
                    <h2 class="h4 mb-0 fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>THÊM CAO ỐC MỚI
                    </h2>
                    <p class="mb-0 text-white-50 small mt-1">Khởi tạo dữ liệu tòa nhà mới vào hệ thống vận hành.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="them_submit.php" method="POST">
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="row g-4">
                            <!-- Tên Cao ốc -->
                            <div class="col-md-12">
                                <label for="tenCaoOc" class="form-label">Tên Cao ốc <span class="text-danger">*</span></label>
                                <input type="text" name="tenCaoOc" id="tenCaoOc" class="form-control py-2" 
                                       placeholder="Ví dụ: SAPPHIRE Tower - Khối A" required autofocus>
                            </div>

                            <!-- Địa chỉ -->
                            <div class="col-md-12">
                                <label for="diaChi" class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                <textarea name="diaChi" id="diaChi" class="form-control py-2" rows="3" 
                                          placeholder="Số... Đường... Phường... Quận..." required></textarea>
                            </div>

                            <!-- Số tầng -->
                            <div class="col-md-6">
                                <label for="soTang" class="form-label">Số tầng <span class="text-danger">*</span></label>
                                <input type="number" name="soTang" id="soTang" class="form-control py-2" 
                                       min="1" max="250" value="1" required>
                                <div class="form-text">Tổng số tầng khai thác của tòa nhà.</div>
                            </div>

                            <div class="col-12 mt-5 border-top pt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                        <i class="bi bi-x-circle me-2"></i>Hủy bỏ
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 py-2">
                                        <i class="bi bi-check-circle me-2"></i>Lưu thông tin
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

</body>
</html>
