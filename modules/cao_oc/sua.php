<?php
/**
 * modules/cao_oc/sua.php
 * Giao diện chỉnh sửa Cao ốc - Hệ thống Quản lý Cao ốc
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. LẤY DỮ LIỆU CŨ PHỤC VỤ FORM
$id = $_GET['id'] ?? '';
if (empty($id)) {
    $_SESSION['error_msg'] = "Mã cao ốc không hợp lệ.";
    header("Location: index.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Lấy thông tin Cao ốc hiện tại
$stmt = $db->prepare("SELECT * FROM CAO_OC WHERE maCaoOc = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$caoOc = $stmt->fetch();

if (!$caoOc) {
    $_SESSION['error_msg'] = "Không tìm thấy dữ liệu cao ốc hoặc đã bị xóa.";
    header("Location: index.php");
    exit();
}

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
        .form-header { background-color: #1e3a5f; color: white; padding: 1.5rem; }
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
        .form-label { font-weight: 600; color: #1e3a5f; margin-bottom: 0.5rem; }
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
                    <li class="breadcrumb-item active">Cập nhật cao ốc</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>CẬP NHẬT CAO ỐC
                        </h2>
                        <p class="mb-0 text-white-50 small mt-1">Sửa đổi thông tin chi tiết của tòa nhà.</p>
                    </div>
                    <span class="badge bg-white text-navy px-3 py-2 fw-bold"><?= e($caoOc['maCaoOc']) ?></span>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="sua_submit.php" method="POST">
                        <!-- Hidden ID & CSRF -->
                        <input type="hidden" name="maCaoOc" value="<?= e($caoOc['maCaoOc']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="row g-4">
                            <!-- Tên Cao ốc -->
                            <div class="col-md-12">
                                <label for="tenCaoOc" class="form-label">Tên Cao ốc <span class="text-danger">*</span></label>
                                <input type="text" name="tenCaoOc" id="tenCaoOc" class="form-control py-2" 
                                       value="<?= e($caoOc['tenCaoOc']) ?>" required>
                            </div>

                            <!-- Địa chỉ -->
                            <div class="col-md-12">
                                <label for="diaChi" class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                <textarea name="diaChi" id="diaChi" class="form-control py-2" rows="3" required><?= e($caoOc['diaChi']) ?></textarea>
                            </div>

                            <!-- Số tầng -->
                            <div class="col-md-6">
                                <label for="soTang" class="form-label">Số tầng <span class="text-danger">*</span></label>
                                <input type="number" name="soTang" id="soTang" class="form-control py-2" 
                                       min="1" max="250" value="<?= e($caoOc['soTang']) ?>" required>
                                <div class="form-text">Số tầng hiện tại của tòa nhà.</div>
                            </div>

                            <div class="col-12 mt-5 border-top pt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                        <i class="bi bi-x-circle me-2"></i>Hủy bỏ
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 py-2">
                                        <i class="bi bi-check-circle me-2"></i>Cập nhật dữ liệu
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
