<?php
// modules/khach_hang_account/kh_dangnhap.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/common/csrf.php';

// Bỏ qua session cũ nếu có
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Khách Thuê (Tenant Portal)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #fdfdfd; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { width: 100%; max-width: 420px; }
        .logo-head { background: #166e44; color: white; border-radius: 8px 8px 0 0; padding: 25px; text-align: center; }
    </style>
</head>
<body>

<div class="login-box shadow-lg rounded bg-white">
    <div class="logo-head">
        <h3 class="mb-0 fw-bold"><i class="fa-solid fa-building-user me-2"></i>Tenant Portal</h3>
        <small class="opacity-75">Cổng Thông Tin Dành Riêng Cho Khách Hàng</small>
    </div>

    <div class="p-4">
        <?php if(isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger p-2"><i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success p-2"><i class="fa-solid fa-check me-1"></i><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?></div>
        <?php endif; ?>

        <form action="kh_dangnhap_submit.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="mb-3">
                <label class="form-label text-muted fw-bold">Tài Khoản Đăng Nhập</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control" required placeholder="Nhập username...">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-muted fw-bold">Mật Khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required placeholder="Nhập password...">
                </div>
            </div>
            <button type="submit" class="btn btn-success w-100 fw-bold p-2"><i class="fa-solid fa-right-to-bracket me-2"></i>Truy Cập</button>
        </form>
    </div>
</div>

</body>
</html>
