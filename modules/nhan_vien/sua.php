<?php
// modules/nhan_vien/sua.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();
if ((int)($_SESSION['user_role'] ?? $_SESSION['role_id'] ?? 4) !== ROLE_ADMIN) {
    die("BLOCK: Access Denied.");
}

$maNV = $_GET['maNV'] ?? '';
if (empty($maNV)) die("Thiếu tọa độ Mã Tùy Chọn.");

$pdo = Database::getInstance()->getConnection();
try {
    $stmt = $pdo->prepare("SELECT * FROM NHAN_VIEN WHERE maNV = ?");
    $stmt->execute([$maNV]);
    $nv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$nv) die("Nhân sự này đã bốc hơi khỏi hệ thống.");
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập Nhật Hồ Sơ Nhân Sự</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style> body { background: #f4f7f9; } </style>
</head>
<body class="p-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white p-3 text-center">
                    <h3 class="mb-0 fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Chỉnh Sửa Lý Lịch Nhân Viên</h3>
                </div>

                <?php if(isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger m-3 rounded-0"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                <?php endif; ?>

                <div class="card-body p-4">
                    <form action="sua_submit.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <!-- Khóa cố định mã NV vì nó là PK Khóa Chính -->
                        <input type="hidden" name="maNV_old" value="<?= htmlspecialchars($nv['maNV']) ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted">Mã Nhân Viên (Chỉ Đọc)</label>
                                <input type="text" name="maNV" class="form-control font-monospace" value="<?= htmlspecialchars($nv['maNV']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tài Khoản Đăng Nhập * (Không đổi thì giữ nguyên)</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($nv['username']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 border-bottom pb-4">
                            <label class="form-label fw-bold">Họ và Tên Nhân Sự *</label>
                            <input type="text" name="tenNV" class="form-control" value="<?= htmlspecialchars($nv['tenNV']) ?>" required>
                        </div>

                        <div class="row mb-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Chức Vụ</label>
                                <input type="text" name="chucVu" class="form-control" value="<?= htmlspecialchars($nv['chucVu']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-danger"><i class="fa-solid fa-shield-halved me-1"></i>Can Thiệp Tầng Quyền (Role) *</label>
                                <select name="role_id" class="form-select fw-bold" required>
                                    <option value="1" <?= ($nv['role_id']==1)?'selected':'' ?>>Admin Hiện Hành</option>
                                    <option value="2" <?= ($nv['role_id']==2)?'selected':'' ?>>Quản lý Nhà (Operation)</option>
                                    <option value="3" <?= ($nv['role_id']==3)?'selected':'' ?>>Kế Toán Số Liệu</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Số Điện Thoại</label>
                                <input type="text" name="sdt" class="form-control" value="<?= htmlspecialchars($nv['sdt']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Địa Chỉ Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($nv['email']) ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-4">
                            <a href="index.php" class="btn btn-secondary px-4 fw-bold">Hủy Bỏ Quyết Định</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fa-solid fa-stamp me-1"></i>Kiểm Duyệt Lưu Thông Tin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
