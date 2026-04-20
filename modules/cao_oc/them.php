<?php
/**
 * modules/cao_oc/them.php
 * Giao diá»‡n thÃªm má»›i Cao á»‘c - Thiáº¿t káº¿ Ä‘á»“ng nháº¥t há»‡ thá»‘ng
 */

// 1. KHá»žI Táº O & Báº¢O Máº¬T
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// XÃ¡c thá»±c Session & PhÃ¢n quyá»n
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Táº¡o CSRF Token cho form
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .form-card {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
        }
        .form-header {
            background-color: #1e3a5f;
            color: white;
            padding: 1.5rem;
        }
        .btn-gold {
            background-color: #c9a66b;
            color: white;
            font-weight: 600;
            padding: 0.6rem 2rem;
            border: none;
            transition: all 0.3s;
        }
        .btn-gold:hover {
            background-color: #b5925a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(201, 166, 107, 0.4);
        }
        .form-label {
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 0.5rem;
        }
        .form-control:focus {
            border-color: #c9a66b;
            box-shadow: 0 0 0 0.25rem rgba(201, 166, 107, 0.15);
        }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4 d-flex justify-content-center">
                <ol class="breadcrumb mb-0" style="width: 800px;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Quáº£n lÃ½ Cao á»‘c</a></li>
                    <li class="breadcrumb-item active">ThÃªm cao á»‘c má»›i</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header">
                    <h2 class="h4 mb-0 fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>THÃŠM CAO á»C Má»šI
                    </h2>
                    <p class="mb-0 text-white-50 small mt-1">Khá»Ÿi táº¡o dá»¯ liá»‡u tÃ²a nhÃ  má»›i vÃ o há»‡ thá»‘ng váº­n hÃ nh.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="them_submit.php" method="POST">
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="row g-4">
                            <!-- TÃªn Cao á»‘c -->
                            <div class="col-md-12">
                                <label for="tenCaoOc" class="form-label">TÃªn Cao á»‘c <span class="text-danger">*</span></label>
                                <input type="text" name="tenCaoOc" id="tenCaoOc" class="form-control py-2" 
                                       placeholder="VÃ­ dá»¥: SAPPHIRE Tower - Khá»‘i A" required autofocus>
                            </div>

                            <!-- Äá»‹a chá»‰ -->
                            <div class="col-md-12">
                                <label for="diaChi" class="form-label">Äá»‹a chá»‰ <span class="text-danger">*</span></label>
                                <textarea name="diaChi" id="diaChi" class="form-control py-2" rows="3" 
                                          placeholder="Sá»‘... ÄÆ°á»ng... PhÆ°á»ng... Quáº­n..." required></textarea>
                            </div>

                            <!-- Sá»‘ táº§ng -->
                            <div class="col-md-6">
                                <label for="soTang" class="form-label">Sá»‘ táº§ng <span class="text-danger">*</span></label>
                                <input type="number" name="soTang" id="soTang" class="form-control py-2" 
                                       min="1" max="250" value="1" required>
                                <div class="form-text">Tá»•ng sá»‘ táº§ng khai thÃ¡c cá»§a tÃ²a nhÃ .</div>
                            </div>

                            <div class="col-12 mt-5 border-top pt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                        <i class="bi bi-x-circle me-2"></i>Há»§y bá»
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 py-2">
                                        <i class="bi bi-check-circle me-2"></i>LÆ°u thÃ´ng tin
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

</body>
</html>
