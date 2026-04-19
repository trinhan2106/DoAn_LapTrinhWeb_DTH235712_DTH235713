<?php
/**
 * modules/tang/them.php
 * Giao diện thêm mới Tầng
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Kết nối CSDL để lấy danh sách Cao ốc phục vụ dropdown
$db = Database::getInstance()->getConnection();
$sqlCaoOc = "SELECT maCaoOc, tenCaoOc FROM CAO_OC WHERE deleted_at IS NULL ORDER BY tenCaoOc ASC";
$dsCaoOc = $db->query($sqlCaoOc)->fetchAll();

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
        .form-control:focus, .form-select:focus {
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
        <?php include __DIR__ . '/../../includes/admin/notifications.php'; ?>
        
        <main class="admin-main-content p-4">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Quản lý Tầng</a></li>
                    <li class="breadcrumb-item active">Thêm mới</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header">
                    <h2 class="h4 mb-0 fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>THÊM TẦNG MỚI
                    </h2>
                    <p class="mb-0 text-white-50 small mt-1">Vui lòng điền đầy đủ thông tin để khởi tạo tầng mới cho tòa nhà.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="them_submit.php" method="POST" id="formThemTang">
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="row g-4">
                            <!-- Chọn Cao ốc -->
                            <div class="col-md-12">
                                <label for="maCaoOc" class="form-label">Tòa nhà (Cao ốc) <span class="text-danger">*</span></label>
                                <select name="maCaoOc" id="maCaoOc" class="form-select py-2" required>
                                    <option value="">-- Chọn tòa nhà --</option>
                                    <?php foreach ($dsCaoOc as $co): ?>
                                        <option value="<?= e($co['maCaoOc']) ?>"><?= e($co['tenCaoOc']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Chọn tòa nhà mà tầng này thuộc về.</div>
                            </div>

                            <!-- Tên Tầng -->
                            <div class="col-md-8">
                                <label for="tenTang" class="form-label">Tên Tầng <span class="text-danger">*</span></label>
                                <input type="text" name="tenTang" id="tenTang" class="form-control py-2" placeholder="Ví dụ: Tầng 5, Tầng Trệt, Rooftop..." required>
                            </div>

                            <!-- Hệ số giá -->
                            <div class="col-md-4">
                                <label for="heSoGia" class="form-label">Hệ số giá <span class="text-danger">*</span></label>
                                <input type="number" name="heSoGia" id="heSoGia" class="form-control py-2" step="0.01" min="0.01" value="1.00" required>
                                <div class="form-text">Hệ số nhân đơn giá (mặc định 1.00).</div>
                            </div>

                            <div class="col-12 mt-5 border-top pt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                        <i class="bi bi-x-circle me-2"></i>Hủy bỏ
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 py-2">
                                        <i class="bi bi-check-circle me-2"></i>Lưu thông tin
                                    </a>
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

<script>
// Validation đơn giản phía client
document.getElementById('formThemTang').addEventListener('submit', function(e) {
    const heSoGia = document.getElementById('heSoGia').value;
    if (parseFloat(heSoGia) <= 0) {
        alert('Hệ số giá phải lớn hơn 0.');
        e.preventDefault();
    }
});
</script>

</body>
</html>
