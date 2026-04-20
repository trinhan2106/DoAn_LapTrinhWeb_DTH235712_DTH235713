<?php
/**
 * modules/cao_oc/sua.php
 * Giao diá»‡n chá»‰nh sá»­a Cao á»‘c - Thiáº¿t káº¿ Ä‘á»“ng bá»™
 */

// 1. KHá»žI Táº O & Báº¢O Máº¬T
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// XÃ¡c thá»±c Session & PhÃ¢n quyá»n
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Láº¤Y Dá»® LIá»†U CÅ¨ PHá»¤C Vá»¤ FORM
$id = $_GET['id'] ?? '';
if (empty($id)) {
    $_SESSION['error_msg'] = "MÃ£ cao á»‘c khÃ´ng há»£p lá»‡.";
    header("Location: index.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Láº¥y thÃ´ng tin Cao á»‘c hiá»‡n táº¡i
$stmt = $db->prepare("SELECT * FROM CAO_OC WHERE maCaoOc = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$caoOc = $stmt->fetch();

if (!$caoOc) {
    $_SESSION['error_msg'] = "KhÃ´ng tÃ¬m tháº¥y dá»¯ liá»‡u cao á»‘c hoáº·c Ä‘Ã£ bá»‹ xÃ³a.";
    header("Location: index.php");
    exit();
}

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
                    <li class="breadcrumb-item active">Cáº­p nháº­t cao á»‘c</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>Cáº¬P NHáº¬T CAO á»C
                        </h2>
                        <p class="mb-0 text-white-50 small mt-1">Sá»­a Ä‘á»•i thÃ´ng tin chi tiáº¿t cá»§a tÃ²a nhÃ .</p>
                    </div>
                    <span class="badge bg-white text-navy px-3 py-2 fw-bold"><?= e($caoOc['maCaoOc']) ?></span>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="sua_submit.php" method="POST">
                        <!-- Hidden ID & CSRF -->
                        <input type="hidden" name="maCaoOc" value="<?= e($caoOc['maCaoOc']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="row g-4">
                            <!-- TÃªn Cao á»‘c -->
                            <div class="col-md-12">
                                <label for="tenCaoOc" class="form-label">TÃªn Cao á»‘c <span class="text-danger">*</span></label>
                                <input type="text" name="tenCaoOc" id="tenCaoOc" class="form-control py-2" 
                                       value="<?= e($caoOc['tenCaoOc']) ?>" required>
                            </div>

                            <!-- Äá»‹a chá»‰ -->
                            <div class="col-md-12">
                                <label for="diaChi" class="form-label">Äá»‹a chá»‰ <span class="text-danger">*</span></label>
                                <textarea name="diaChi" id="diaChi" class="form-control py-2" rows="3" required><?= e($caoOc['diaChi']) ?></textarea>
                            </div>

                            <!-- Sá»‘ táº§ng -->
                            <div class="col-md-6">
                                <label for="soTang" class="form-label">Sá»‘ táº§ng <span class="text-danger">*</span></label>
                                <input type="number" name="soTang" id="soTang" class="form-control py-2" 
                                       min="1" max="250" value="<?= e($caoOc['soTang']) ?>" required>
                                <div class="form-text">Sá»‘ táº§ng hiá»‡n táº¡i cá»§a tÃ²a nhÃ .</div>
                            </div>

                            <div class="col-12 mt-5 border-top pt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                                        <i class="bi bi-x-circle me-2"></i>Há»§y bá»
                                    </a>
                                    <button type="submit" class="btn btn-gold px-5 py-2">
                                        <i class="bi bi-check-circle me-2"></i>Cáº­p nháº­t dá»¯ liá»‡u
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
