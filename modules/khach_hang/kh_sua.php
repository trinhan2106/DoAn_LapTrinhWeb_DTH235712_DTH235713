<?php
/**
 * modules/khach_hang/kh_sua.php
 * Giao diện chỉnh sửa thông tin Khách Hàng - Hệ thống quản lý vận hành cao ốc
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Kiểm tra quyền truy cập
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA, ROLE_KE_TOAN]);

$pdo = Database::getInstance()->getConnection();
$maKH = $_GET['id'] ?? '';

// 2. XỬ LÝ CẬP NHẬT (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra bảo mật CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Lỗi xác thực bảo mật (CSRF). Vui lòng thử lại.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $maKH_post = $_POST['maKH'] ?? '';
    $tenKH     = $_POST['tenKH'] ?? '';
    $cccd      = $_POST['cccd'] ?? '';
    $sdt       = $_POST['sdt'] ?? '';
    $email     = $_POST['email'] ?? '';
    $diaChi    = $_POST['diaChi'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE KHACH_HANG SET tenKH = ?, cccd = ?, sdt = ?, email = ?, diaChi = ? WHERE maKH = ?");
        $result = $stmt->execute([$tenKH, $cccd, $sdt, $email, $diaChi, $maKH_post]);
        
        if ($result) {
            // 3. Ghi Audit Log hành động UPDATE
            ghiAuditLog($pdo, $_SESSION['user_id'] ?? null, 'UPDATE', 'KHACH_HANG', $maKH_post);

            // 4. Rotate Token sau khi thao tác thành công
            rotateCSRFToken();

            $_SESSION['success_msg'] = "Cập nhật thông tin Khách hàng [{$maKH_post}] thành công!";
            header("Location: kh_hienthi.php");
            exit();
        } else {
            $_SESSION['error_msg'] = "Cập nhật thất bại, vui lòng kiểm tra lại!";
        }
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Lỗi CSDL: Cập nhật không thành công.";
        error_log("Lỗi cập nhật Khách hàng: " . $e->getMessage());
    }
    
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Lấy dữ liệu hiển thị (GET)
if (empty($maKH)) {
    $_SESSION['error_msg'] = "Truy cập không hợp lệ: Thiếu mã khách hàng!";
    header("Location: kh_hienthi.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM KHACH_HANG WHERE maKH = ? AND deleted_at IS NULL");
    $stmt->execute([$maKH]);
    $khToEdit = $stmt->fetch(PDO::FETCH_ASSOC);

    // Chống IDOR view
    if (!$khToEdit) {
        $_SESSION['error_msg'] = "Khách hàng không tồn tại hoặc đã bị xóa khỏi hệ thống!";
        header("Location: kh_hienthi.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_msg'] = "Lỗi truy xuất dữ liệu Khách hàng!";
    header("Location: kh_hienthi.php");
    exit();
}

// Tạo CSRF token cho form
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require_once __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .form-card { max-width: 900px; margin: 0 auto; border-radius: 12px; overflow: hidden; }
        .form-header { background-color: #1e3a5f; color: white; padding: 1.5rem; }
        .btn-gold { background-color: #c9a66b; color: white; font-weight: 600; padding: 0.6rem 2.5rem; border: none; transition: 0.3s; }
        .btn-gold:hover { background-color: #b5925a; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(201, 166, 107, 0.3); }
        .form-label { font-weight: 600; color: #1e3a5f; }
        .text-navy { color: #1e3a5f !important; }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php require_once __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>CẬP NHẬT KHÁCH HÀNG</h2>
                        <p class="mb-0 text-white-50 small mt-1">Sửa đổi thông tin liên hệ và giấy tờ của khách thuê.</p>
                    </div>
                    <span class="badge bg-white text-navy px-3 py-2 fw-bold text-navy"><?= e($khToEdit['maKH']) ?></span>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form action="kh_sua.php?id=<?= urlencode($maKH) ?>" method="POST" class="needs-validation" novalidate>
                        <!-- Thêm Token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-person-lines-fill me-2"></i>Thông Tin Cơ Bản</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="maKH" class="form-label">Mã Khách Hàng</label>
                                <!-- Yêu cầu Read-only cho maKH -->
                                <input type="text" class="form-control py-2 text-muted bg-light" id="maKH" name="maKH" value="<?= e($khToEdit['maKH']) ?>" readonly required>
                            </div>
                            <div class="col-md-6">
                                <label for="tenKH" class="form-label">Tên Khách Hàng <span class="text-danger">*</span></label>
                                <input type="text" class="form-control py-2" id="tenKH" name="tenKH" value="<?= e($khToEdit['tenKH']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cccd" class="form-label">CMND / CCCD</label>
                                <input type="text" class="form-control py-2" id="cccd" name="cccd" value="<?= e($khToEdit['cccd']) ?>">
                            </div>
                        </div>

                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-telephone-outbound me-2"></i>Thông Tin Liên Hệ</h5>
                        <div class="row g-4 mb-5 bg-light p-4 rounded-3 h-100">
                            <div class="col-md-6">
                                <label for="sdt" class="form-label">Số điện thoại</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="text" class="form-control py-2" id="sdt" name="sdt" value="<?= e($khToEdit['sdt']) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" class="form-control py-2" id="email" name="email" value="<?= e($khToEdit['email']) ?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="diaChi" class="form-label">Địa chỉ</label>
                                <textarea class="form-control py-2" id="diaChi" name="diaChi" rows="3"><?= e($khToEdit['diaChi']) ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-4">
                            <a href="kh_hienthi.php" class="btn btn-outline-secondary px-4 py-2">Hủy Bỏ</a>
                            <button type="submit" class="btn btn-gold px-5 py-2">
                                <i class="bi bi-save me-2"></i>Lưu Thay Đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
        </main>
        
        <?php require_once __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

</body>
</html>
