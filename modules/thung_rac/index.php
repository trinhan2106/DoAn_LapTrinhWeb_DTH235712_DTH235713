<?php
// modules/thung_rac/index.php
/**
 * TRẠM CỨU HỘ VÀ KHÔI PHỤC DỮ LIỆU BỊ XÓA MỀM (RECYCLE BIN)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

// Quyền truy cập: Admin (1) và Trưởng bộ phận (2)
$role = (int)($_SESSION['user_role'] ?? 4);
if (!in_array($role, [1, 2])) {
    die("Access Denied: Chỉ cấp quản lý mới được phép vào kho Thùng Rác khôi phục.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // Truy xuất Danh sách Phòng bị Xóa mềm
    $stmt = $pdo->query("
        SELECT p.maPhong, p.tenPhong, p.dienTich, p.deleted_at, t.tenTang, c.tenCaoOc
        FROM PHONG p
        LEFT JOIN TANG t ON p.maTang = t.maTang
        LEFT JOIN CAO_OC c ON t.maCaoOc = c.maCaoOc
        WHERE p.deleted_at IS NOT NULL
        ORDER BY p.deleted_at DESC
    ");
    $listPhongXoa = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết xuất Thùng Rác: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thùng Rác - Phục Hồi Dữ Liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --primary: #1e3a5f; --accent: #c9a66b; --bg-color: #f4f7f9; }
        body { background-color: var(--bg-color); }
        .hero-bar { background: var(--primary); color: white; border-bottom: 4px solid var(--accent); }
    </style>
</head>
<body class="p-4">

<div class="container shadow-lg bg-white p-0 rounded overflow-hidden">
    
    <div class="hero-bar p-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-0 fw-bold"><i class="fa-solid fa-trash-arrow-up me-2 text-warning"></i>KHO CHỨA TÀI SẢN BỊ XÓA (RECYCLE BIN)</h2>
            <p class="mb-0 text-light opacity-75">Nơi lưu trữ và hồi sinh Phòng/Tài sản lỡ tay thi hành án Soft-Delete.</p>
        </div>
        <a href="../../index.php" class="btn btn-outline-light"><i class="fa-solid fa-chevron-left me-2"></i>Thoát về Home</a>
    </div>

    <!-- Thông báo Alert nếu có Redirect từ Submit về -->
    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success m-3 rounded-0 border-success"><i class="fa-solid fa-check-circle me-1"></i> <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger m-3 rounded-0 border-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <div class="p-4">
        <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2">Danh mục Căn Phòng chờ Hồi Sinh</h5>
        
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã PK</th>
                    <th>Căn Phòng Bị Xóa</th>
                    <th>Thuộc Tòa/Tầng</th>
                    <th>Thời Điểm Rơi Vào Thùng Rác</th>
                    <th class="text-center">Thao Tác Controller</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($listPhongXoa)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted fw-bold">Hiện không có Mệnh Căn nào bị đọa thùng rác. Hệ thống vẹn toàn!</td></tr>
                <?php else: ?>
                    <?php foreach($listPhongXoa as $px): ?>
                        <tr>
                            <td class="text-danger fw-bold"><i class="fa-solid fa-ghost me-1"></i><?= htmlspecialchars($px['maPhong']) ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($px['tenPhong']) ?> <br><small class="text-muted fw-normal">S: <?= $px['dienTich'] ?> m2</small></td>
                            <td><?= htmlspecialchars($px['tenCaoOc'] ?? 'N/A') ?> <br> <span class="badge bg-secondary"><?= htmlspecialchars($px['tenTang'] ?? 'N/A') ?></span></td>
                            <td><span class="text-danger"><i class="fa-regular fa-clock me-1"></i><?= date('d/m/Y H:i:s', strtotime($px['deleted_at'])) ?></span></td>
                            <td class="text-center">
                                <!-- Tạo mini Form Submit để giữ nguyên tính an toàn POST CSRF cho lệnh Restore -->
                                <form action="restore_submit.php" method="POST" onsubmit="return confirm('Bạn thực sự muốn triệu hồi linh vật [<?= $px['maPhong'] ?>] về DB Trắng?');">
                                    <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">
                                    <input type="hidden" name="maPhong" value="<?= htmlspecialchars($px['maPhong']) ?>">
                                    <button type="submit" class="btn btn-success btn-sm fw-bold shadow-sm">
                                        <i class="fa-solid fa-truck-medical me-1"></i> Khôi Phục (Restore)
                                    </button>
                                </form>
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
