<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
kiemTraSession();
kiemTraRole([1, 2]); // Chỉ Admin (1) và Quản lý Nhà (2) được thao tác
$pdo = Database::getInstance()->getConnection();

$maKH = $_GET['id'] ?? '';

// Xử lý POST (Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH_post = $_POST['maKH'] ?? '';
    $tenKH     = trim($_POST['tenKH'] ?? '');
    $cccd      = trim($_POST['cccd'] ?? '');
    $sdt       = trim($_POST['sdt'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $diaChi    = trim($_POST['diaChi'] ?? '');

    // Validate
    if (empty($tenKH) || empty($maKH_post)) {
         $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ Mã Khách Hàng và Tên Khách Hàng!";
    } else {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE KHACH_HANG 
                                         SET tenKH = ?, cccd = ?, sdt = ?, email = ?, diaChi = ? 
                                         WHERE maKH = ? AND deleted_at IS NULL");
            $result = $stmtUpdate->execute([$tenKH, $cccd, $sdt, $email, $diaChi, $maKH_post]);
            
            if ($result) {
                $_SESSION['success_msg'] = "Cập nhật thông tin Khách hàng [{$maKH_post}] thành công!";
            } else {
                $_SESSION['error_msg'] = "Cập nhật thất bại, vui lòng kiểm tra lại!";
            }
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Lỗi CSDL: Không thể cập nhật (Có thể do trùng CCCD hoặc vấn đề kết nối).";
            error_log("Lỗi cập nhật Khách hàng: " . $e->getMessage());
        }
    }
    header("Location: kh_hienthi.php");
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require_once __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .form-card { max-width: 900px; margin: 0 auto; border-radius: 12px; overflow: hidden; }
        .form-header { background-color: #1e3a5f; color: white; padding: 1.5rem; }
        .btn-gold { 
            background-color: #c9a66b; color: white; font-weight: 600; padding: 0.6rem 2.5rem; border: none; transition: 0.3s;
        }
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
            
            <nav aria-label="breadcrumb" class="mb-4 d-flex justify-content-center">
                <ol class="breadcrumb mb-0" style="width: 900px;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/dashboard/admin.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="kh_hienthi.php" class="text-decoration-none">Quản lý Khách hàng</a></li>
                    <li class="breadcrumb-item active">Chỉnh sửa thông tin</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>CẬP NHẬT KHÁCH HÀNG</h2>
                        <p class="mb-0 text-white-50 small mt-1">Sửa đổi thông tin liên hệ và giấy tờ của khách hàng.</p>
                    </div>
                    <span class="badge bg-white text-navy px-3 py-2 fw-bold"><?= htmlspecialchars($khToEdit['maKH'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form action="kh_sua.php?id=<?= urlencode($maKH) ?>" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="maKH" value="<?= htmlspecialchars($khToEdit['maKH'], ENT_QUOTES, 'UTF-8') ?>">
                        
                        <!-- Thông tin cá nhân -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-person-lines-fill me-2"></i>Thông Tin Căn Bản</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="tenKH" class="form-label">Tên Khách Hàng <span class="text-danger">*</span></label>
                                <input type="text" class="form-control py-2" id="tenKH" name="tenKH" value="<?= htmlspecialchars($khToEdit['tenKH'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cccd" class="form-label">GPLX / CCCD</label>
                                <input type="text" class="form-control py-2" id="cccd" name="cccd" value="<?= htmlspecialchars($khToEdit['cccd'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <!-- Thông tin liên hệ -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-telephone-outbound me-2"></i>Thông Tin Liên Hệ</h5>
                        <div class="row g-4 mb-5 bg-light p-4 rounded-3 h-100">
                            <div class="col-md-6">
                                <label for="sdt" class="form-label">Số điện thoại</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="text" class="form-control py-2" id="sdt" name="sdt" value="<?= htmlspecialchars($khToEdit['sdt'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" class="form-control py-2" id="email" name="email" value="<?= htmlspecialchars($khToEdit['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="diaChi" class="form-label">Địa chỉ</label>
                                <textarea class="form-control py-2" id="diaChi" name="diaChi" rows="3"><?= htmlspecialchars($khToEdit['diaChi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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
