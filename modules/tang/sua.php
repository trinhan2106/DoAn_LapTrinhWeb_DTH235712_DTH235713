<?php
/**
 * modules/tang/sua.php
 * Giao diện chỉnh sửa thông tin Tầng
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
    $_SESSION['error_msg'] = "Mã tầng không hợp lệ.";
    header("Location: index.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Lấy thông tin Tầng hiện tại
$stmt = $db->prepare("SELECT * FROM TANG WHERE maTang = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$tang = $stmt->fetch();

if (!$tang) {
    $_SESSION['error_msg'] = "Không tìm thấy dữ liệu tầng hoặc tầng đã bị xóa.";
    header("Location: index.php");
    exit();
}

// Lấy danh sách Cao ốc phục vụ dropdown
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
                    <li class="breadcrumb-item active">Chỉnh sửa tầng</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>CHỈNH SỬA TẦNG
                        </h2>
                        <p class="mb-0 text-white-50 small mt-1">Sửa đổi thông tin tầng [<?= e($tang['maTang']) ?>]</p>
                    </div>
                    <span class="badge bg-white text-navy px-3 py-2 fw-bold"><?= e($tang['maTang']) ?></span>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="sua_submit.php" method="POST" id="formSuaTang">
                        <!-- Hidden ID & CSRF -->
                        <input type="hidden" name="maTang" value="<?= e($tang['maTang']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="row g-4">
                            <!-- Chọn Cao ốc -->
                            <div class="col-md-12">
                                <label for="maCaoOc" class="form-label">Tòa nhà (Cao ốc) <span class="text-danger">*</span></label>
                                <select name="maCaoOc" id="maCaoOc" class="form-select py-2" required>
                                    <option value="">-- Chọn tòa nhà --</option>
                                    <?php foreach ($dsCaoOc as $co): ?>
                                        <option value="<?= e($co['maCaoOc']) ?>" <?= ($co['maCaoOc'] == $tang['maCaoOc']) ? 'selected' : '' ?>>
                                            <?= e($co['tenCaoOc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Tên Tầng -->
                            <div class="col-md-8">
                                <label for="tenTang" class="form-label">Tên Tầng <span class="text-danger">*</span></label>
                                <input type="text" name="tenTang" id="tenTang" class="form-control py-2" value="<?= e($tang['tenTang']) ?>" required>
                            </div>

                            <!-- Hệ số giá -->
                            <div class="col-md-4">
                                <label for="heSoGia" class="form-label">Hệ số giá <span class="text-danger">*</span></label>
                                <input type="number" name="heSoGia" id="heSoGia" class="form-control py-2" step="0.01" min="0.01" value="<?= e($tang['heSoGia']) ?>" required>
                                <div class="form-text">Hệ số nhân đơn giá cho toàn tầng.</div>
                            </div>

                            <div class="col-12 mt-5 border-top pt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                        <i class="bi bi-x-circle me-2"></i>Hủy bỏ
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 py-2">
                                        <i class="bi bi-check-circle me-2"></i>Cập nhật dữ liệu
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
document.getElementById('formSuaTang').addEventListener('submit', function(e) {
    const heSoGia = document.getElementById('heSoGia').value;
    if (parseFloat(heSoGia) <= 0) {
        alert('Hệ số giá phải lớn hơn 0.');
        e.preventDefault();
    }
});
</script>

</body>
</html>
