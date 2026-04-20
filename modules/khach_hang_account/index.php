<?php
/**
 * modules/khach_hang_account/index.php
 * Giao diện Quản lý Tài khoản Khách hàng
 */
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn lấy danh sách Khách hàng và Tài khoản login tương ứng (nếu có)
    $stmt = $pdo->query("
        SELECT kh.maKH, kh.tenKH, kh.sdt, kh.email, kh.cccd,
               acc.accountId, acc.username, acc.created_at as acc_created
        FROM KHACH_HANG kh
        LEFT JOIN KHACH_HANG_ACCOUNT acc ON kh.maKH = acc.maKH
        WHERE kh.deleted_at IS NULL
        ORDER BY acc.accountId DESC, kh.maKH DESC
    ");
    $listAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Lỗi truy vấn Account: " . $e->getMessage());
    $listAccounts = [];
}

$pageTitle = "Quản lý Tài khoản Khách hàng";
include __DIR__ . '/../../includes/admin/admin-header.php';
$csrf_token = generateCSRFToken();
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <!-- Header Page -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h3 fw-bold text-navy mb-1"><i class="bi bi-person-badge-fill me-2"></i>QUẢN LÝ TÀI KHOẢN KHÁCH</h2>
                        <p class="text-muted small mb-0">Cấp quyền truy cập cho khách thuê để xem hóa đơn và gửi yêu cầu.</p>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success fw-bold"><i class="bi bi-check-circle me-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger fw-bold"><i class="bi bi-exclamation-triangle me-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                <?php endif; ?>

                <div class="card shadow border-0 rounded-3">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">Mã KH</th>
                                        <th>Họ Tên</th>
                                        <th>Username</th>
                                        <th>Liên hệ</th>
                                        <th class="text-center">Trạng thái</th>
                                        <th width="15%" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listAccounts as $acc): ?>
                                        <tr>
                                            <td class="fw-bold">#<?= htmlspecialchars($acc['maKH']) ?></td>
                                            <td>
                                                <div class="fw-semibold text-navy"><?= htmlspecialchars($acc['tenKH']) ?></div>
                                                <small class="text-muted">CCCD: <?= htmlspecialchars($acc['cccd'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <?php if($acc['username']): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">
                                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($acc['username']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted italic small">Chưa cấp account</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="small"><i class="bi bi-phone me-1"></i><?= htmlspecialchars($acc['sdt']) ?></div>
                                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($acc['email']) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <?php if($acc['accountId']): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3">No Login</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <?php if(!$acc['accountId']): ?>
                                                        <a href="tao_taikhoan.php?maKH=<?= urlencode($acc['maKH']) ?>" 
                                                           class="btn btn-sm btn-primary rounded shadow-sm px-3" 
                                                           title="Cấp tài khoản mới">
                                                            <i class="bi bi-plus-circle me-1"></i> Cấp Account
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- Reset Password -->
                                                        <form id="form-reset-<?= $acc['accountId'] ?>" action="reset_matkhau.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                            <input type="hidden" name="accountId" value="<?= $acc['accountId'] ?>">
                                                            <button type="button" class="btn btn-sm btn-outline-info rounded shadow-sm" title="Đặt lại mật khẩu" 
                                                                    onclick="confirmAction('reset', '<?= $acc['accountId'] ?>')">
                                                                <i class="bi bi-key"></i>
                                                            </button>
                                                        </form>
                                                        <!-- Delete Account -->
                                                        <form id="form-delete-<?= $acc['accountId'] ?>" action="xoa_account.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                            <input type="hidden" name="accountId" value="<?= $acc['accountId'] ?>">
                                                            <button type="button" class="btn btn-sm btn-outline-danger rounded shadow-sm" title="Xóa tài khoản"
                                                                    onclick="confirmAction('delete', '<?= $acc['accountId'] ?>')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAction(type, accountId) {
    let config = {};
    if (type === 'reset') {
        config = {
            title: 'Đặt lại mật khẩu?',
            text: "Mật khẩu sẽ được trả về mặc định: 123456",
            icon: 'warning',
            confirmButtonText: 'Đồng ý, Reset!',
            formId: 'form-reset-' + accountId
        };
    } else {
        config = {
            title: 'Xóa tài khoản?',
            text: "Khách hàng sẽ không thể đăng nhập được nữa!",
            icon: 'error',
            confirmButtonText: 'Đồng ý, Xóa!',
            formId: 'form-delete-' + accountId
        };
    }

    Swal.fire({
        title: config.title,
        text: config.text,
        icon: config.icon,
        showCancelButton: true,
        confirmButtonColor: type === 'delete' ? '#d33' : '#1e3a5f',
        cancelButtonColor: '#6e7881',
        confirmButtonText: config.confirmButtonText,
        cancelButtonText: 'Hủy bỏ',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Hiển thị loader trước khi submit
            if (typeof showLoading === 'function') showLoading();
            document.getElementById(config.formId).submit();
        }
    });
}
</script>
</body>
</html>
