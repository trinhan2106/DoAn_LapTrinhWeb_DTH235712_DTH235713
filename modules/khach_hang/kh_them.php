<?php
/**
 * modules/khach_hang/kh_them.php
 * Form thêm mới Khách hàng - Chống CSRF/XSS, Audit Log
 */

require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([1, 2]); // Chỉ Admin (1) và Quản lý Nhà (2) được thao tác
$pdo = Database::getInstance()->getConnection();

// Xử lý form gửi lên (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH  = trim($_POST['maKH'] ?? '');
    $tenKH = trim($_POST['tenKH'] ?? '');
    $cccd  = trim($_POST['cccd'] ?? '');
    $sdt   = trim($_POST['sdt'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $diaChi = trim($_POST['diaChi'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    // 1. Chống CSRF
    if (!validateCSRFToken($csrfToken)) {
        $_SESSION['error_msg'] = "Lỗi bảo mật (CSRF). Vui lòng thử lại!";
        header("Location: kh_hienthi.php");
        exit();
    }

    if (empty($maKH) || empty($tenKH)) {
        $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ Mã Khách Hàng và Tên Khách Hàng!";
    } else {
        try {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM KHACH_HANG WHERE maKH = ?");
            $stmtCheck->execute([$maKH]);
            if ($stmtCheck->fetchColumn() > 0) {
                $_SESSION['error_msg'] = "Mã khách hàng này đã tồn tại, vui lòng nhập mã khác!";
            } else {
                $sqlInsert = "INSERT INTO KHACH_HANG (maKH, tenKH, cccd, sdt, email, diaChi) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $result = $stmtInsert->execute([$maKH, $tenKH, $cccd, $sdt, $email, $diaChi]);
                
                if ($result) {
                    // 2. Ghi Audit Log CREATE
                    ghiAuditLog($pdo, $_SESSION['user_id'] ?? null, 'INSERT', 'KHACH_HANG', $maKH);

                    // 3. Rotate Token
                    rotateCSRFToken();

                    $_SESSION['success_msg'] = "Thêm Khách hàng mới [{$maKH}] thành công!";
                    header("Location: kh_hienthi.php");
                    exit();
                } else {
                    $_SESSION['error_msg'] = "Thêm thất bại, vui lòng thử lại!";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Lỗi CSDL: Thêm mới không thành công.";
            error_log("Insert Khách hàng error: " . $e->getMessage());
        }
    }
}

$csrf_token = generateCSRFToken();
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
                    <li class="breadcrumb-item active">Thêm Khách hàng mới</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>THÊM KHÁCH HÀNG MỚI</h2>
                        <p class="mb-0 text-white-50 small mt-1">Khởi tạo nhanh hồ sơ khách thuê mới.</p>
                    </div>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form action="kh_them.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-person-lines-fill me-2"></i>Thông Tin Căn Bản</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="maKH" class="form-label">Mã Khách Hàng <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-hash text-muted"></i></span>
                                    <input type="text" class="form-control py-2 text-uppercase bg-light fw-bold text-navy" id="maKH" name="maKH" value="KH-<?= rand(1000, 9999) ?>" readonly required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="tenKH" class="form-label">Tên Khách Hàng <span class="text-danger">*</span></label>
                                <input type="text" class="form-control py-2" id="tenKH" name="tenKH" placeholder="VD: Công ty TNHH ABC hoặc Nguyễn Văn A" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cccd" class="form-label">CMND / CCCD (Đối với Cá nhân)</label>
                                <input type="text" class="form-control py-2" id="cccd" name="cccd" placeholder="Nhập số CCCD/CMND (Nếu có)">
                            </div>
                        </div>

                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-telephone-outbound me-2"></i>Thông Tin Liên Hệ</h5>
                        <div class="row g-4 mb-5 bg-light p-4 rounded-3 h-100">
                            <div class="col-md-6">
                                <label for="sdt" class="form-label">Số điện thoại</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="text" class="form-control py-2" id="sdt" name="sdt" placeholder="VD: 0901234567">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" class="form-control py-2" id="email" name="email" placeholder="VD: abc@gmail.com">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="diaChi" class="form-label">Địa chỉ</label>
                                <textarea class="form-control py-2" id="diaChi" name="diaChi" rows="3" placeholder="Nhập địa chỉ cư trú hoặc xuất hóa đơn..."></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-4">
                            <a href="kh_hienthi.php" class="btn btn-outline-secondary px-4 py-2">Hủy Bỏ</a>
                            <button type="submit" class="btn btn-gold px-5 py-2">
                                <i class="bi bi-plus-circle me-2"></i>Tạo Khách Hàng
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
