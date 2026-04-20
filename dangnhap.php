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
                    <div class="text-center mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px; background-color: var(--color-background, #f4f7f9); color: var(--color-accent, #c9a66b);">
                            <i class="fa-solid fa-city fs-3"></i>
                        </div>
                        <h3 class="login-title">Đăng Nhập Hệ Thống</h3>
                    </div>
                    <!-- TAB CHỌN LOẠI TÀI KHOẢN -->
                    <ul class="nav nav-pills nav-fill mb-4 p-1 rounded-3" id="loginTab" style="background:#f0f4f8;" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="tab-staff" data-bs-toggle="pill" data-bs-target="#pane-staff" type="button" role="tab" style="border-radius:8px;">
                                <i class="fa-solid fa-user-shield me-1"></i> Nhân viên
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="tab-tenant" data-bs-toggle="pill" data-bs-target="#pane-tenant" type="button" role="tab" style="border-radius:8px;">
                                <i class="fa-solid fa-building-user me-1"></i> Khách hàng
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Vùng hiển thị lỗi -->
                    <?php if (isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-sm" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="tab-content" id="loginTabContent">

                        <!-- ═══ TAB NHÂN VIÊN ═══ -->
                        <div class="tab-pane fade show active" id="pane-staff" role="tabpanel">
                            <form method="POST" action="dangnhap_submit.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <div class="mb-3">
                                    <label for="username" class="custom-label">Tên đăng nhập</label>
                                    <div class="position-relative">
                                        <i class="fa-regular fa-user input-icon-left"></i>
                                        <input type="text" class="form-control custom-input" id="username" name="username" placeholder="Tên tài khoản" required autocomplete="username" autofocus>
                                    </div>
                                </div>
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
                                <div class="d-grid">
                                    <button type="submit" class="btn-brand btn-brand--primary btn-login">
                                        <i class="fa-solid fa-right-to-bracket me-2"></i>Đăng nhập Nhân viên
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ═══ TAB KHÁCH HÀNG ═══ -->
                        <div class="tab-pane fade" id="pane-tenant" role="tabpanel">
                            <form method="POST" action="modules/khach_hang_account/kh_dangnhap_submit.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <div class="mb-3">
                                    <label for="kh_username" class="custom-label">Tài khoản khách hàng</label>
                                    <div class="position-relative">
                                        <i class="fa-regular fa-building input-icon-left"></i>
                                        <input type="text" class="form-control custom-input" id="kh_username" name="username" placeholder="Tên tài khoản" required autocomplete="username">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="kh_password" class="custom-label">Mật khẩu</label>
                                    <div class="position-relative">
                                        <i class="fa-solid fa-lock input-icon-left"></i>
                                        <input type="password" class="form-control custom-input" id="kh_password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password">
                                        <span class="password-toggle-icon" id="togglePasswordKH" title="Hiện/ẩn mật khẩu">
                                            <i class="fa-regular fa-eye fs-6" id="toggleIconKH"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="alert border-0 py-2 px-3 mb-3" style="background:#e8f4fd;font-size:.82rem;border-radius:8px;color:#0c63a4;">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Tài khoản khách hàng được Ban Quản Lý tòa nhà cung cấp.
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn-brand btn-brand--primary btn-login">
                                        <i class="fa-solid fa-right-to-bracket me-2"></i>Đăng nhập Khách hàng
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div><!-- /.tab-content -->

                    <div class="mt-4 text-center">
                        <a href="index.php" class="text-muted text-decoration-none" style="font-size:.85rem;">
                            <i class="fa-solid fa-arrow-left me-1"></i>Quay về trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle show/hide password — Tab Nhân viên
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput  = document.getElementById('password');
            const toggleIcon     = document.getElementById('toggleIcon');
            if (togglePassword) {
                togglePassword.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPass = passwordInput.type === 'password';
                    passwordInput.type = isPass ? 'text' : 'password';
                    toggleIcon.classList.toggle('fa-eye', !isPass);
                    toggleIcon.classList.toggle('fa-eye-slash', isPass);
                });
            }

            // Toggle show/hide password — Tab Khách hàng
            const togglePasswordKH = document.getElementById('togglePasswordKH');
            const passwordInputKH  = document.getElementById('kh_password');
            const toggleIconKH     = document.getElementById('toggleIconKH');
            if (togglePasswordKH) {
                togglePasswordKH.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPass = passwordInputKH.type === 'password';
                    passwordInputKH.type = isPass ? 'text' : 'password';
                    toggleIconKH.classList.toggle('fa-eye', !isPass);
                    toggleIconKH.classList.toggle('fa-eye-slash', isPass);
                });
            }

            // Nếu session error_msg xuất hiện và user đang ở tab KH,
            // tự động mở tab KH lại (dựa trên URL param)
            const params = new URLSearchParams(window.location.search);
            if (params.get('tab') === 'tenant') {
                const tenantTab = document.getElementById('tab-tenant');
                if (tenantTab) tenantTab.click();
            }
        });
    </script>
</body>
</html>