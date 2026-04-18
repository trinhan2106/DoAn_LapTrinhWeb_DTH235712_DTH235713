<?php
// Gọi các core hệ thống (Lùi 2 cấp: thư mục ho_so -> thư mục modules -> GỐC)
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

// Kiểm tra phiên đăng nhập an toàn
kiemTraSession();

// [BẮT BUỘC] Không cho phép tài khoản thường (không bị cấm) vô tình lọt vào đây
if (!isset($_SESSION['phai_doi_matkhau']) || $_SESSION['phai_doi_matkhau'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$errorMsg = '';
$successMsg = '';

// Kịch bản Cập nhật DB khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vali CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$csrf_token || !validateCSRFToken($csrf_token)) {
        die("<h1>403 Forbidden</h1><p>CSRF Token lỗi.</p>");
    }

    $passNew = $_POST['password_new'] ?? '';
    $passConfirm = $_POST['password_confirm'] ?? '';

    // Logic kiểm chứng an toàn đơn giản
    if (strlen($passNew) < 6) {
        $errorMsg = "Mật khẩu mới yêu cầu tối thiểu 6 ký tự bảo mật.";
    } elseif ($passNew !== $passConfirm) {
        $errorMsg = "Hai mật khẩu không trùng khớp. Hãy nhập lại cho cẩn thận.";
    } else {
        try {
            $pdo = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            $roleId = $_SESSION['QuyenHan'];
            
            // Băm MK bằng chuẩn bcrypt
            $hashMoi = password_hash($passNew, PASSWORD_BCRYPT);

            if ($roleId == 4) {
                // Khách Hàng (Tenant)
                $stmt = $pdo->prepare("UPDATE KHACH_HANG_ACCOUNT SET password_hash = :hash, phai_doi_matkhau = 0 WHERE accountId = :id");
            } else {
                // Nhân sự điều hành: Admin (1), QLN (2), Kế Toán (3)
                $stmt = $pdo->prepare("UPDATE NHAN_VIEN SET password_hash = :hash, phai_doi_matkhau = 0 WHERE maNV = :id");
            }

            $success = $stmt->execute([
                ':hash' => $hashMoi,
                ':id' => $userId
            ]);

            if ($success) {
                // Giải phóng thẻ cấm (cờ phai_doi_matkhau) 
                $_SESSION['phai_doi_matkhau'] = 0;
                
                // Bạn có thể dùng Flash Session để ném thông báo sang trang kế tiếp
                $_SESSION['flash_msg'] = "Mật khẩu đã được thiết lập thành công. An tâm sử dụng!";

                // Hoàn tất thủ tục, chuyển vào vùng lõi (chọn index.php như một bộ chia routing)
                header("Location: " . BASE_URL . "index.php");
                exit();
            } else {
                $errorMsg = "Không thể ghi nhận cơ sở dữ liệu. Vui lòng thử lại.";
            }

        } catch (PDOException $e) {
             error_log("Lỗi UPDATE MK: " . $e->getMessage());
             $errorMsg = "Lỗi hệ thống máy chủ CSDL. Vui lòng liên hệ Admin.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập Nhật Mật Khẩu Bắt Buộc</title>
    <!-- CSS Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --brand-primary: #1e3a5f;
            --brand-accent: #c9a66b;
            --brand-bg: #f4f7f9;
            --brand-text: #1f2a44;
        }

        body {
            background-color: var(--brand-bg);
            color: var(--brand-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .auth-card {
            width: 100%;
            max-width: 500px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(30, 58, 95, 0.15);
        }

        .auth-title {
            color: var(--brand-primary);
            font-weight: 800;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .alert-error {
            background-color: #f8eaeb;
            color: #b02a37;
            border-left: 5px solid #b02a37;
            font-weight: 500;
            border-top: none; border-bottom:none; border-right:none;
        }

        .btn-custom {
            background-color: var(--brand-primary);
            color: #ffffff;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-custom:hover {
            background-color: var(--brand-accent);
            color: var(--brand-text);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(201, 166, 107, 0.4);
        }

        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.25rem rgba(30, 58, 95, 0.2);
        }
    </style>
</head>
<body>
    <div class="card auth-card m-3 m-md-0">
        <div class="card-body p-4 p-md-5">
            <h4 class="auth-title">
                <i class="fa-solid fa-shield-halved me-2"></i>ĐỔI MẬT KHẨU
            </h4>
            
            <p class="text-center text-muted mb-4" style="font-size: 0.95rem;">
                Vì lý do an ninh, bạn <strong>bắt buộc</strong> phải thiết lập một mật khẩu bí mật cá nhân mới để có thể truy cập hệ thống.
            </p>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- CSRF Token (Sử dụng hàm ẩn an toàn) -->
                <input type="hidden" name="csrf_token" value="<?= function_exists('generateCSRFToken') ? generateCSRFToken() : '' ?>">

                <!-- Ô nhập Pass Mới -->
                <div class="mb-3">
                    <label for="password_new" class="form-label fw-bold">Mật khẩu mới</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-lock" style="color: var(--brand-primary)"></i></span>
                        <input type="password" name="password_new" id="password_new" class="form-control" placeholder="Yêu cầu dài tối thiểu 6 ký tự" required autofocus>
                    </div>
                </div>

                <!-- Ô xác nhận lại -->
                <div class="mb-4">
                    <label for="password_confirm" class="form-label fw-bold">Xác nhận lại mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-check-double text-muted"></i></span>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" placeholder="Nhập lại để đối chiếu sự chính xác" required>
                    </div>
                </div>

                <div class="d-grid mt-2">
                    <button type="submit" class="btn btn-custom btn-lg">
                        Xác nhận & Cập nhật <i class="fa-solid fa-paper-plane ms-1"></i>
                    </button>
                </div>
            </form>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
