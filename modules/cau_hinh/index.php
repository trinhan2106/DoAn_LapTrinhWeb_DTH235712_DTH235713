<?php
// modules/cau_hinh/index.php
/**
 * Giao diện Quản trị Cấu hình Hệ thống (Admin Edit)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();
if ((int)($_SESSION['user_role'] ?? $_SESSION['role_id'] ?? 4) !== ROLE_ADMIN) {
    die("Access Denied: Quyền truy cập bị từ chối.");
}

$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->query("SELECT key_name, key_value, description FROM CAU_HINH_HE_THONG ORDER BY key_name ASC");
    $listConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Query Error: " . $e->getMessage());
    die("Sự cố truy xuất dữ liệu Cấu hình Hệ thống.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu hình Hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .config-card { border-radius: 6px; border: 1px solid #ddd; }
    </style>
</head>
<body class="p-4">

<div class="container shadow bg-white p-4 config-card">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h3 class="mb-0 fw-bold"><i class="fa-solid fa-sliders me-2 text-primary"></i>Tham Số Cấu Hình Hệ Thống</h3>
        <a href="../../modules/dashboard/admin.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Quay lại Dashboard</a>
    </div>

    <!-- Hiển thị thông báo (Alerts) -->
    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check me-2"></i><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <form action="cau_hinh_submit.php" method="POST">
        <!-- Input ẩn chứa CSRF Token bảo mật -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <?php if(empty($listConfigs)): ?>
            <div class="alert alert-warning">Database không tồn tại Record cấu hình nào ban đầu.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach($listConfigs as $row): ?>
                    <div class="col-md-6 mb-4">
                        <div class="form-group border p-3 rounded bg-light h-100">
                            <!-- Label chứa MMT description -->
                            <label class="form-label fw-bold mb-1">
                                <?= htmlspecialchars($row['description']) ?>
                            </label>
                            <br>
                            <small class="text-muted font-monospace d-block mb-2">Key: <?= htmlspecialchars($row['key_name']) ?></small>
                            
                            <!-- Bắt buộc config dưới dạng mảng POST -->
                            <?php if (strlen($row['key_value']) > 100): ?>
                                <textarea name="configs[<?= htmlspecialchars($row['key_name']) ?>]" class="form-control" rows="3"><?= htmlspecialchars($row['key_value']) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="configs[<?= htmlspecialchars($row['key_name']) ?>]" value="<?= htmlspecialchars($row['key_value']) ?>" class="form-control">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i>Lưu Cập Nhật Cấu Hình</button>
            </div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
