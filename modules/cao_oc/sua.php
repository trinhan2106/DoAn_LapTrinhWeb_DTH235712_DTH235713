<?php
/**
 * modules/cao_oc/sua.php
 * Form chỉnh sửa thông tin cao ốc
 * ==================================================================
 */

require_once '../../includes/common/auth.php';
require_once '../../includes/common/db.php';
require_once '../../includes/common/csrf.php';
require_once '../../includes/common/functions.php';

// 1. Bảo mật: Kiểm tra session và quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Lấy ID từ GET
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header("Location: index.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

// 3. Truy vấn thông tin cao ốc hiện tại
try {
    $stmt = $pdo->prepare("SELECT * FROM CAO_OC WHERE maCaoOc = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $caoOc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$caoOc) {
        die("Không tìm thấy cao ốc hoặc cao ốc đã bị xóa.");
    }
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// 4. Tạo CSRF Token cho form
$csrf_token = generateCSRFToken();

$pageTitle = "Chỉnh sửa Cao ốc: " . $caoOc['tenCaoOc'];
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
            max-width: 800px;
            margin: 2rem auto;
        }
        .form-label {
            font-weight: 600;
            color: #1e3a5f;
        }
        .bg-navy-light {
            background-color: #f8f9fa;
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
                        <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa</li>
                    </ol>
                </nav>

                <div class="card card-brand card-form shadow-sm border-0">
                    <div class="card-header bg-white border-bottom p-4">
                        <h2 class="h4 mb-0 text-navy fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>CHỈNH SỬA CAO ỐC
                        </h2>
                    </div>
                    <div class="card-body p-4 bg-navy-light">
                        <form action="sua_submit.php" method="POST">
                            <!-- CSRF TOKEN -->
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <!-- Hidden ID -->
                            <input type="hidden" name="maCaoOc" value="<?= e($caoOc['maCaoOc']) ?>">

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Mã Cao ốc</label>
                                    <input type="text" class="form-control-plaintext fw-bold text-navy" value="<?= e($caoOc['maCaoOc']) ?>" readonly>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="tenCaoOc" class="form-label">Tên Cao ốc <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tenCaoOc" name="tenCaoOc" 
                                           value="<?= e($caoOc['tenCaoOc']) ?>" required>
                                </div>

                                <div class="col-md-12">
                                    <label for="diaChi" class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="diaChi" name="diaChi" rows="3" required><?= e($caoOc['diaChi']) ?></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="soTang" class="form-label">Số tầng <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="soTang" name="soTang" 
                                           min="1" max="200" value="<?= e($caoOc['soTang']) ?>" required>
                                </div>

                                <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-light px-4">
                                        <i class="bi bi-x-circle me-1"></i> Hủy bỏ
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 shadow-sm">
                                        <i class="bi bi-save me-1"></i> Cập nhật thay đổi
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
