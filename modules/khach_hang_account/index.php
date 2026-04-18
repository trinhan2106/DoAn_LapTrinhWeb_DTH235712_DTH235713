<?php
// modules/khach_hang_account/index.php
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
    // Lấy danh sách tài khoản kèm thông tin KH
    $stmt = $pdo->query("
        SELECT acc.accountId, acc.username, acc.role_id, acc.phai_doi_matkhau, acc.created_at, acc.deleted_at,
               kh.maKH, kh.tenKH, kh.sdt, kh.email
        FROM KHACH_HANG_ACCOUNT acc
        JOIN KHACH_HANG kh ON acc.maKH = kh.maKH
        ORDER BY acc.created_at DESC
    ");
    $listAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Lỗi DB khach_hang_account/index: " . $e->getMessage());
    die("Lỗi kết nối cơ sở dữ liệu.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài Khoản Khách Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-light p-4">

<div class="container shadow bg-white rounded p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h3 class="mb-0 fw-bold"><i class="fa-solid fa-users-gear me-2 text-primary"></i>Danh Sách Tài Khoản Tenant</h3>
        <div>
            <a href="tao_taikhoan.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Cấp Account Mới</a>
            <a href="../../index.php" class="btn btn-secondary"><i class="fa-solid fa-house"></i> Dashboard</a>
        </div>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check me-2"></i><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã Account</th>
                    <th>Khách Hàng (Mã KH)</th>
                    <th>Username</th>
                    <th>Trạng Thái Định Danh</th>
                    <th>Thời Gian Tạo</th>
                    <th class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($listAccounts)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Chưa có tài khoản khách hàng nào được cấu hình.</td></tr>
                <?php else: ?>
                    <?php foreach($listAccounts as $acc): ?>
                        <?php $isDeleted = !is_null($acc['deleted_at']); ?>
                        <tr class="<?= $isDeleted ? 'opacity-50' : '' ?>">
                            <td class="font-monospace fw-bold"><?= htmlspecialchars($acc['accountId']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($acc['tenKH']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($acc['maKH']) ?> | <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($acc['sdt']) ?></small>
                            </td>
                            <td class="text-primary fw-bold"><?= htmlspecialchars($acc['username']) ?></td>
                            <td>
                                <?php if($isDeleted): ?>
                                    <span class="badge bg-secondary">Vô hiệu hóa</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Hoạt động</span>
                                <?php endif; ?>
                                
                                <?php if($acc['phai_doi_matkhau']): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-key me-1"></i>Chưa đổi pass gốc</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($acc['created_at'])) ?></td>
                            <td class="text-center">
                                <?php if(!$isDeleted): ?>
                                    <button class="btn btn-sm btn-info text-white" disabled title="Reset Password đang bảo trì" ><i class="fa-solid fa-arrow-rotate-right"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
