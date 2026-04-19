<?php
/**
 * modules/cao_oc/them.php
 * Form thêm mới cao ốc
 * ==================================================================
 */

require_once '../../includes/common/auth.php';
require_once '../../includes/common/csrf.php';
require_once '../../includes/common/functions.php';

// 1. Bảo mật: Kiểm tra session và quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Tạo CSRF Token cho form
$csrf_token = generateCSRFToken();

$pageTitle = "Thêm mới Cao ốc";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include_once '../../includes/admin/admin-header.php'; ?>
    <style>
        .btn-gold {
            background-color: #c9a66b;
            color: #ffffff;
            border: none;
        }
        .btn-gold:hover {
            background-color: #b38f5a;
            color: #ffffff;
        }
        .card-form {
            max-width: 800px; /* 70-80% screen width pattern */
            margin: 2rem auto;
        }
        .form-label {
            font-weight: 600;
            color: #1e3a5f;
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include_once '../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper">
        <?php include_once '../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content">
            <div class="container-fluid">
                <nav aria-label="breadcrumb" class="card-form mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Quản lý Cao ốc</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Thêm mới</li>
                    </ol>
                </nav>

                <div class="card card-brand card-form shadow-sm border-0">
                    <div class="card-header bg-white border-bottom p-4">
                        <h2 class="h4 mb-0 text-navy fw-bold">
                            <i class="bi bi-plus-circle me-2"></i>THÊM CAO ỐC MỚI
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <form action="them_submit.php" method="POST">
                            <!-- CSRF TOKEN -->
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="tenCaoOc" class="form-label">Tên Cao ốc <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tenCaoOc" name="tenCaoOc" 
                                           placeholder="Ví dụ: Blue Sky Tower - Khối B" required autofocus>
                                </div>

                                <div class="col-md-12">
                                    <label for="diaChi" class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="diaChi" name="diaChi" rows="3" 
                                              placeholder="Nhập địa chỉ chi tiết của cao ốc..." required></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="soTang" class="form-label">Số tầng <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="soTang" name="soTang" 
                                           min="1" max="200" value="1" required>
                                </div>

                                <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light px-4">
                                        <i class="bi bi-x-circle me-1"></i> Hủy bỏ
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 shadow-sm">
                                        <i class="bi bi-check2-circle me-1"></i> Lưu thông tin
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <?php include_once '../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

</body>
</html>
