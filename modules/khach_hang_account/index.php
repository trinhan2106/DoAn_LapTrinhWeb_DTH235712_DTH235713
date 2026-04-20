<?php
/**
 * modules/khach_hang_account/index.php
 * Giao diện Quản lý Tài khoản Khách hàng (Phiên bản cơ bản)
 */
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([1, 2]);

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn lấy danh sách thông tin tài khoản KH
    $stmt = $pdo->query("SELECT maKH, tenKH, sdt, email, cccd FROM KHACH_HANG WHERE deleted_at IS NULL ORDER BY maKH DESC");
    $listAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <div class="card shadow border-0 rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold text-navy"><i class="bi bi-person-badge me-2"></i>QUẢN LÝ TÀI KHOẢN KHÁCH HÀNG</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle table-datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">Mã KH</th>
                                        <th>Họ Tên</th>
                                        <th>Số Điện Thoại</th>
                                        <th>Email</th>
                                        <th>Số CCCD</th>
                                        <th width="15%" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listAccounts as $acc): ?>
                                        <tr>
                                            <td class="fw-bold">#<?= htmlspecialchars($acc['maKH']) ?></td>
                                            <td><?= htmlspecialchars($acc['tenKH']) ?></td>
                                            <td><?= htmlspecialchars($acc['sdt']) ?></td>
                                            <td><?= htmlspecialchars($acc['email']) ?></td>
                                            <td><?= htmlspecialchars($acc['cccd']) ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-info text-white me-1"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-warning text-white"><i class="bi bi-pencil-square"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

</body>
</html>
