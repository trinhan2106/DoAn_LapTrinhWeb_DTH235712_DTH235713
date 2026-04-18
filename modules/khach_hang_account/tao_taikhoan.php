<?php
// modules/khach_hang_account/tao_taikhoan.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();
if ((int)($_SESSION['user_role'] ?? $_SESSION['role_id'] ?? 4) !== ROLE_ADMIN) {
    die("Access Denied.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn Khách Hàng hợp lệ VÀ Chưa Được Cấp Account
    $stmt = $pdo->query("
        SELECT maKH, tenKH, sdt, email 
        FROM KHACH_HANG 
        WHERE deleted_at IS NULL 
          AND maKH NOT IN (SELECT maKH FROM KHACH_HANG_ACCOUNT)
    ");
    $listKH = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi DB: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấp Tài Khoản Khách Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 shadow bg-white rounded p-4">
            <h4 class="fw-bold mb-4 border-bottom pb-2 text-primary">Biên Chế Tài Khoản Khách Hàng Mới</h4>

            <?php if(isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?></div>
            <?php endif; ?>

            <form action="tao_taikhoan_submit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Chọn Khách Hàng Đại Diện</label>
                    <select name="maKH" class="form-select" required>
                        <option value="" disabled selected>-- Chọn KH chưa có tài khoản --</option>
                        <?php foreach($listKH as $kh): ?>
                            <option value="<?= htmlspecialchars($kh['maKH']) ?>">
                                <?= htmlspecialchars($kh['maKH']) ?> - <?= htmlspecialchars($kh['tenKH']) ?> (SĐT: <?= htmlspecialchars($kh['sdt']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Username Đăng Nhập</label>
                    <input type="text" name="username" class="form-control" placeholder="Tên ngắn gọn, không dấu" required>
                    <small class="text-muted d-block mt-1">Mật khẩu mặc định sẽ là <strong>123456</strong> và yêu cầu đổi lần đầu đăng nhập.</small>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="index.php" class="btn btn-secondary">Quay về</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Tạo Tài Khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
