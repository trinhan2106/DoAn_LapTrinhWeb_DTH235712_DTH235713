<?php
// Bao gồm file tạo CSRF Token để hàm generateCSRFToken() khả dụng (Nối tiếp cấu trúc ở Task 1)
require_once __DIR__ . '/includes/common/csrf.php';

// Đảm bảo session sẵn sàng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản lý Cao ốc</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome (Dùng cho các icon UI chân thật hơn) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* =========================================
           CUSTOM BRAND COLORS & TYPOGRAPHY
           ========================================= */
        :root {
            --brand-primary: #1e3a5f;
            /* Xanh navy sâu */
            --brand-accent: #c9a66b;
            /* Vàng gold nhạt */
            --brand-bg: #f4f7f9;
            /* Màu nền tổng thể */
            --brand-text: #1f2a44;
            /* Màu Text cơ bản */
        }

        body {
            background-color: var(--brand-bg);
            color: var(--brand-text);
            /* Setup thiết kế Flexbox để ném form vào giữa */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        /* -----------------------------------------
           UI: CHỈNH SỬA CARD ĐĂNG NHẬP 
           ----------------------------------------- */
        .login-card {
            width: 100%;
            max-width: 450px;
            border: none;
            border-radius: 12px;
            /* Yêu cầu: Đổ bóng nhẹ với shadow custom màu tối */
            box-shadow: 0 12px 35px rgba(30, 58, 95, 0.15) !important;
        }

        .login-card .card-body {
            padding: 3rem 2.5rem;
        }

        /* Logo tĩnh / Tiêu đề */
        .login-title {
            color: var(--brand-primary);
            font-weight: 800;
            letter-spacing: 0.5px;
            text-align: center;
            margin-bottom: 2rem;
            text-transform: uppercase;
        }

        /* -----------------------------------------
           UI: FORM INPUTS 
           ----------------------------------------- */
        .form-label {
            color: var(--brand-text);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-group-text,
        .form-control {
            border-color: #dce1e6;
            padding: 0.75rem 1rem;
        }

        /* Ràng buộc CSS Focus: Màu Primary làm viền input khi focus */
        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.25rem rgba(30, 58, 95, 0.2);
        }

        /* Hiệu ứng focus chung cho Icon nằm trong input group */
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--brand-primary);
        }

        /* Icon con mắt Ẩn/Hiện pass */
        .toggle-password {
            cursor: pointer;
            color: #6c757d;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: var(--brand-primary);
        }

        /* -----------------------------------------
           UI: BUTTON SUBMIT 
           ----------------------------------------- */
        .btn-login {
            background-color: var(--brand-primary);
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.8rem;
            border-radius: 6px;
            border: none;
            transition: all 0.3s ease-in-out;
            letter-spacing: 0.5px;
        }

        /* Hiệu ứng Accent Vàng Gold nhạt khi Hover */
        .btn-login:hover,
        .btn-login:focus {
            background-color: var(--brand-accent);
            color: var(--brand-text);
            /* Đổi màu chữ sang tối để nổi hơn trên nền nhạt */
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(201, 166, 107, 0.4);
        }

        /* -----------------------------------------
           UI: ALERTS BÁO LỖI PHP 
           ----------------------------------------- */
        .alert-locked {
            background-color: #ffe6e2;
            /* Nền đỏ cam nhạt mềm mại */
            color: #c0392b;
            /* Text đỏ đậm để cảnh báo */
            font-weight: 500;
            border-left: 5px solid #c0392b;
            border-top: none;
            border-bottom: none;
            border-right: none;
        }

        .alert-invalid {
            background-color: #f8eaeb;
            color: #b02a37;
            font-weight: 500;
            border-left: 5px solid #b02a37;
            border-top: none;
            border-bottom: none;
            border-right: none;
        }
    </style>
</head>

<body>

    <!-- Vùng form bọc trong Card Bootstrap 5 -->
    <div class="card login-card shadow-lg">
        <div class="card-body">

            <h3 class="login-title">
                <i class="fa-solid fa-city me-2"></i>QUẢN LÝ CAO ỐC
            </h3>

            <!-- 
            =================================================
            KHU VỰC HIỂN THỊ LỖI CHECK TỪ BACKEND
            ================================================= 
            -->
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'locked'): ?>
                    <div class="alert alert-locked alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-lock me-2"></i>
                        Tài khoản bị khóa do nhập sai nhiều lần. Vui lòng thử lại sau
                        <strong><?php echo htmlspecialchars($_GET['wait'] ?? 15); ?> phút</strong>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['error'] === 'invalid'): ?>
                    <div class="alert alert-invalid alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Tên đăng nhập hoặc mật khẩu không chính xác!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- 
            =================================================
            FORM ĐĂNG NHẬP 
            ================================================= 
            -->
            <form method="POST" action="dangnhap_submit.php">

                <!-- BẮT BUỘC: Bảo mật chặn tấn công chéo CSRF Token -->
                <input type="hidden" name="csrf_token"
                    value="<?php echo (function_exists('generateCSRFToken')) ? generateCSRFToken() : ''; ?>">

                <!-- Trường Tên Đăng Nhập -->
                <div class="mb-4">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Nhập tài khoản" required autocomplete="username" autofocus>
                    </div>
                </div>

                <!-- Trường Mật Khẩu -->
                <div class="mb-4">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Nhập mật khẩu" required autocomplete="current-password">

                        <!-- Toggle Password Icon -->
                        <span class="input-group-text bg-white toggle-password" onclick="togglePasswordVisibility()"
                            title="Ẩn/Hiện mật khẩu">
                            <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Nút Đăng nhập -->
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-login btn-lg">
                        Đăng nhập hệ thống <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- Script thư viện của Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JS Thuần (Vanilla JS) Xử lý Cục bộ -->
    <script>
        /**
         * Hàm ẩn / hiện mật khẩu text thông qua đổi type = password/text
         */
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById("password");
            const icon = document.getElementById("togglePasswordIcon");

            // Nếu đang là password -> chuyển thành text xem được chữ và đổi icon Gạch chéo
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
            // Nếu đang là text -> chuyển ngược lại thành chấm bi và đổi icon Mắt mở
            else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>

</html>