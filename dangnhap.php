<?php
// Khởi tạo session (nếu chưa có)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giả định hoặc include file để tạo CSRF token nếu project yêu cầu
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản lý Cao ốc</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- CSS Branding System -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* CSS Tùy chỉnh riêng cho giao diện Premium Split-Screen */
        .login-container {
            min-height: 100vh;
        }

        /* Phần ảnh background bên trái (Desktop) */
        .login-cover {
            background-image: url('assets/img/office_cover.png');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        /* Hiệu ứng mờ overlay nhẹ để tạo chiều sâu */
        .login-cover::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(30, 58, 95, 0.85) 0%, rgba(0, 0, 0, 0.4) 100%);
        }

        .login-cover-content {
            position: relative;
            z-index: 1;
            color: var(--color-white, #fff);
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .login-cover-content h1 {
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--color-accent, #c9a66b);
        }

        /* Phần form bên phải */
        .login-form-area {
            background-color: var(--color-white, #ffffff);
            padding: 3rem 1.5rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            /* Đổ bóng cực mềm để card nổi rất nhẹ */
            box-shadow: 0 20px 40px rgba(30, 58, 95, 0.05);
            border: 1px solid rgba(0,0,0,0.03);
            border-radius: 12px;
            padding: 2.5rem;
            background: #fff;
        }

        .login-title {
            color: var(--color-primary, #1e3a5f);
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        /* Standard Clean Input Styling - Khắc phục hoàn toàn lỗi Autofill */
        .custom-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8c98a4;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: block;
        }

        .custom-input {
            background-color: #f8f9fa;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            padding: 0.85rem 1rem;
            padding-left: 2.75rem; /* Nhường chỗ cho icon trái */
            font-weight: 500;
            color: var(--color-primary, #1e3a5f);
            transition: all 0.3s ease;
            box-shadow: none;
        }

        .custom-input:focus {
            background-color: #ffffff;
            border-color: var(--color-primary, #1e3a5f);
            box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.1);
        }

        /* Xử lý dứt điểm nền xanh của Chrome Autofill */
        .custom-input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #f8f9fa inset !important;
            -webkit-text-fill-color: var(--color-primary, #1e3a5f) !important;
        }
        .custom-input:focus:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #ffffff inset !important;
        }

        .input-icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8c98a4;
            z-index: 4;
            font-size: 1.1rem;
        }

        .password-toggle-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #8c98a4;
            z-index: 5;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }
        
        .password-toggle-icon:hover {
            color: var(--color-primary, #1e3a5f);
        }

        /* Nút Submit cực kỳ nổi bật */
        .btn-login {
            border-radius: 8px;
            padding: 0.85rem;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 20px rgba(30, 58, 95, 0.2);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(30, 58, 95, 0.3);
        }

        .form-check-input:checked {
            background-color: var(--color-primary, #1e3a5f);
            border-color: var(--color-primary, #1e3a5f);
        }
    </style>
</head>
<body class="bg-light">

    <div class="container-fluid p-0 login-container">
        <div class="row g-0 h-100 min-vh-100">
            
            <!-- LEFT COLUMN: THUMBNAIL (Ẩn trên mobile, rực rỡ trên Desktop) -->
            <div class="col-lg-6 d-none d-lg-flex login-cover align-items-center justify-content-center">
                <div class="login-cover-content text-center px-5">
                    <h1 class="display-4 text-uppercase mb-3"><i class="fa-solid fa-city me-3"></i>THE SAPPHIRE</h1>
                    <p class="lead fs-4">Tinh hoa không gian - Nâng tầm sự nghiệp</p>
                    <hr class="w-25 border-warning border-2 mx-auto mt-4 mb-4" style="opacity: 1;">
                    <p class="opacity-75">Hệ thống quản lý cao ốc thông minh và bảo mật hàng đầu.</p>
                </div>
            </div>

            <!-- RIGHT COLUMN: FORM ĐĂNG NHẬP -->
            <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center login-form-area position-relative">
                
                <!-- Nút quay lại trang chủ -->
                <a href="index.php" class="btn btn-light position-absolute top-0 start-0 m-4 rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; z-index: 10; transition: all 0.2s;" title="Trở về trang chủ" id="btnBack">
                    <i class="fa-solid fa-arrow-left text-secondary"></i>
                </a>
                
                <style>
                    #btnBack:hover {
                        background-color: var(--color-primary, #1e3a5f);
                        color: white !important;
                    }
                    #btnBack:hover i {
                        color: white !important;
                    }
                </style>

                <div class="login-card w-100">
                    <div class="text-center mb-4 pb-2">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px; background-color: var(--color-background, #f4f7f9); color: var(--color-accent, #c9a66b);">
                            <i class="fa-solid fa-user-shield fs-3"></i>
                        </div>
                        <h3 class="login-title">Đăng Nhập Tài Khoản</h3>
                        <p class="text-muted">Vui lòng nhập thông tin để truy cập hệ thống</p>
                    </div>
                    
                    <!-- Vùng hiển thị lỗi (ví dụ: Session Lockout) -->
                    <?php if (isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-sm" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <?php 
                                echo htmlspecialchars($_SESSION['error_msg']); 
                                unset($_SESSION['error_msg']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="dangnhap_submit.php">
                        
                        <!-- Security: CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                        <!-- Tên đăng nhập -->
                        <div class="mb-3">
                            <label for="username" class="custom-label">Tên đăng nhập</label>
                            <div class="position-relative">
                                <i class="fa-regular fa-user input-icon-left"></i>
                                <input type="text" class="form-control custom-input" id="username" name="username" placeholder="Nhập tên đăng nhập" required autocomplete="username" autofocus>
                            </div>
                        </div>

                        <!-- Mật khẩu -->
                        <div class="mb-4">
                            <label for="password" class="custom-label">Mật khẩu</label>
                            <div class="position-relative">
                                <i class="fa-solid fa-lock input-icon-left"></i>
                                <input type="password" class="form-control custom-input" id="password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password">
                                <span class="password-toggle-icon" id="togglePassword" title="Hiện/ẩn mật khẩu">
                                    <i class="fa-regular fa-eye fs-6" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Ghi nhớ đăng nhập & Quên Mật Khẩu Layout Flex -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                <label class="form-check-label text-muted" for="remember_me">Ghi nhớ</label>
                            </div>
                            <a href="#" class="text-brand-primary text-decoration-none fw-semibold" style="font-size: 0.9rem;">Quên mật khẩu?</a>
                        </div>

                        <!-- Nút Submit -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn-brand btn-brand--primary btn-login">
                                Khởi Đầu <i class="fa-solid fa-arrow-right ms-2"></i>
                            </button>
                        </div>

                        <!-- Link điều hướng Đăng ký -->
                        <div class="mt-4 text-center">
                            <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                                Bạn là khách mới? 
                                <a href="dangky.php" class="text-brand-primary fw-bold text-decoration-none border-bottom border-warning border-2 pb-1" style="transition: all 0.2s;">Gia nhập ngay</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Vanilla JS: Hiển thị / Ẩn mật khẩu (Đã tối ưu logic cho the new layout) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePasswordBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            togglePasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                
                if (isPassword) {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>
</html>