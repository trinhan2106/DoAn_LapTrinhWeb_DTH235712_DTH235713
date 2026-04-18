<?php
// modules/nhan_vien/them.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();
if ((int)($_SESSION['user_role'] ?? $_SESSION['role_id'] ?? 4) !== ROLE_ADMIN) {
    die("BLOCK: Access Denied.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Nhân Sự Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style> body { background: #f4f7f9; } </style>
</head>
<body class="p-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white p-3 text-center">
                    <h3 class="mb-0 fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Bổ Nhiệm Nhân Viên Mới</h3>
                </div>
                <!-- Alert Thông báo -->
                <?php if(isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger m-3 rounded-0"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                <?php endif; ?>

                <div class="card-body p-4">
                    <div class="alert alert-warning mb-4">
                        <i class="fa-solid fa-circle-info me-1"></i> Mật khẩu đăng nhập mặc định cho nhân sự mới là <strong>123456</strong>. Hệ thống sẽ tự động ép buộc nhân viên phải đổi mật khẩu ở lần đăng nhập đầu tiên!
                    </div>

                    <form action="them_submit.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Mã Nhân Viên *</label>
                                <input type="text" name="maNV" class="form-control" required placeholder="VD: NV-QL-01">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tài Khoản Đăng Nhập (Username) *</label>
                                <input type="text" name="username" class="form-control" required placeholder="VD: an.nguyen">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Họ và Tên Nhân Sự *</label>
                            <input type="text" name="tenNV" class="form-control" required placeholder="Nguyễn Văn An">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Chức Vụ</label>
                                <input type="text" name="chucVu" class="form-control" placeholder="Trưởng phòng, Chuyên viên...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Bộ Phận Phân Quyền (Role) *</label>
                                <select name="role_id" class="form-select" required>
                                    <option value="1">Admin (Quản trị Hệ thống)</option>
                                    <option value="2" selected>Quản lý Nhà (Vận hành, Hợp đồng)</option>
                                    <option value="3">Kế Toán (Lõi Tài chính)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Số Điện Thoại</label>
                                <input type="text" name="sdt" class="form-control" placeholder="090...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Địa Chỉ Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@congty.com">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-4">
                            <a href="index.php" class="btn btn-secondary px-4 fw-bold">Hủy / Về Danh Sách</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i>Lưu Dữ Liệu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
