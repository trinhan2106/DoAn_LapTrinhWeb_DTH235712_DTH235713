<?php
// modules/nhan_vien/index.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

// RÀNG BUỘC: CHỈ QUẢN TRỊ VIÊN (ADMIN) MỚI ĐƯỢC XEM/SỬA NHÂN SỰ
$role = (int)($_SESSION['user_role'] ?? $_SESSION['role_id'] ?? 4);
if ($role !== ROLE_ADMIN) {
    die("Từ chối truy cập: Chỉ Quản Trị Viên mới được phép quản lý Bộ máy Nhân sự.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn Danh sách NV, hiển thị người Đang làm trước, Đã nghỉ sau
    $stmt = $pdo->query("
        SELECT maNV, tenNV, chucVu, sdt, email, username, role_id, deleted_at 
        FROM NHAN_VIEN 
        ORDER BY (deleted_at IS NOT NULL) ASC, maNV ASC
    ");
    $listNV = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi Database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Mục Nhân Viên (HRM)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .hero { background: #1e3a5f; color: #fff; padding: 20px; border-bottom: 4px solid #c9a66b; }
        .table-custom th { background: #e9ecef; }
    </style>
</head>
<body class="p-4">

<div class="container shadow p-0 bg-white rounded overflow-hidden">
    <!-- Header -->
    <div class="hero d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0 fw-bold"><i class="fa-solid fa-users-tie me-2 text-warning"></i>TRẠM QUẢN TRỊ NHÂN SỰ</h3>
            <p class="mb-0 text-light opacity-75">Quản lý Tài khoản, Phân quyền Lõi và Điều phối Nhân Viên Cao Ốc</p>
        </div>
        <div>
            <a href="them.php" class="btn btn-warning fw-bold shadow-sm me-2"><i class="fa-solid fa-user-plus me-1"></i>Thêm Mới NV</a>
            <a href="../../index.php" class="btn btn-outline-light"><i class="fa-solid fa-house"></i> Dashboard</a>
        </div>
    </div>

    <!-- Alert Thông báo -->
    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success m-3 rounded-0"><i class="fa-solid fa-check-circle me-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger m-3 rounded-0"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <div class="p-4 table-responsive">
        <table class="table table-bordered table-hover align-middle table-custom">
            <thead>
                <tr>
                    <th>Mã NV</th>
                    <th>Họ Tên / Username</th>
                    <th>Chức Vụ</th>
                    <th>Trạng Thái</th>
                    <th>Bộ Phận (Role)</th>
                    <th>Liên Hệ</th>
                    <th class="text-center">Điều Khiển</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($listNV)): ?>
                    <tr><td colspan="7" class="text-center text-muted fw-bold py-4">Chưa có bản ghi nhân sự nào (Lỗi Cơ Sở Dữ Liệu Gốc).</td></tr>
                <?php else: ?>
                    <?php foreach($listNV as $nv): ?>
                        <?php 
                            $isResigned = !is_null($nv['deleted_at']);
                            $txtRole = ($nv['role_id'] == 1) ? 'Admin' : (($nv['role_id'] == 2) ? 'Quản lý Nhà' : 'Kế toán');
                            $colorRole = ($nv['role_id'] == 1) ? 'danger' : (($nv['role_id'] == 2) ? 'primary' : 'success');
                        ?>
                        <tr class="<?= $isResigned ? 'opacity-50' : '' ?>">
                            <td class="fw-bold"><code><?= htmlspecialchars($nv['maNV']) ?></code></td>
                            <td>
                                <strong class="text-primary"><?= htmlspecialchars($nv['tenNV']) ?></strong><br>
                                <small class="text-muted"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($nv['username']) ?></small>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($nv['chucVu']) ?></td>
                            <td>
                                <?php if($isResigned): ?>
                                    <span class="badge bg-secondary"><i class="fa-solid fa-user-slash me-1"></i>Đã nghỉ việc</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-briefcase me-1"></i>Đang công tác</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= $colorRole ?>"><?= $txtRole ?></span></td>
                            <td>
                                <small><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($nv['sdt']) ?></small><br>
                                <small><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($nv['email']) ?></small>
                            </td>
                            <td class="text-center">
                                <?php if(!$isResigned): ?>
                                    <a href="sua.php?maNV=<?= urlencode($nv['maNV']) ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen"></i></a>
                                    <!-- Nút Xóa sử dụng POST form an toàn tuyệt đối -->
                                    <form action="xoa.php" method="POST" class="d-inline" onsubmit="return confirm('SA THẢI: Bạn có chắc chắn muốn ngưng hoạt động của Nhân viên [<?= $nv['maNV'] ?>] chứ?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="maNV" value="<?= htmlspecialchars($nv['maNV']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled title="Tài khoản trống"><i class="fa-solid fa-lock"></i></button>
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
