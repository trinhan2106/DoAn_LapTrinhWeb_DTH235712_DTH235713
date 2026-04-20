<?php
/**
 * modules/tang/sua.php
 * Giao diá»‡n chá»‰nh sá»­a thÃ´ng tin Táº§ng
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
    $_SESSION['error_msg'] = "MÃ£ táº§ng khÃ´ng há»£p lá»‡.";
    header("Location: index.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Láº¥y thÃ´ng tin Táº§ng hiá»‡n táº¡i
$stmt = $db->prepare("SELECT * FROM TANG WHERE maTang = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$tang = $stmt->fetch();

if (!$tang) {
    $_SESSION['error_msg'] = "KhÃ´ng tÃ¬m tháº¥y dá»¯ liá»‡u táº§ng hoáº·c táº§ng Ä‘Ã£ bá»‹ xÃ³a.";
    header("Location: index.php");
    exit();
}

// Láº¥y danh sÃ¡ch Cao á»‘c phá»¥c vá»¥ dropdown
$sqlCaoOc = "SELECT maCaoOc, tenCaoOc FROM CAO_OC WHERE deleted_at IS NULL ORDER BY tenCaoOc ASC";
$dsCaoOc = $db->query($sqlCaoOc)->fetchAll();

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
        .form-control:focus, .form-select:focus {
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
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Quáº£n lÃ½ Táº§ng</a></li>
                    <li class="breadcrumb-item active">Chá»‰nh sá»­a táº§ng</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>CHá»ˆNH Sá»¬A Táº¦NG
                        </h2>
                        <p class="mb-0 text-white-50 small mt-1">Sá»­a Ä‘á»•i thÃ´ng tin táº§ng [<?= e($tang['maTang']) ?>]</p>
                    </div>
                    <span class="badge bg-white text-navy px-3 py-2 fw-bold"><?= e($tang['maTang']) ?></span>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="sua_submit.php" method="POST" id="formSuaTang">
                        <!-- Hidden ID & CSRF -->
                        <input type="hidden" name="maTang" value="<?= e($tang['maTang']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="row g-4">
                            <!-- Chá»n Cao á»‘c -->
                            <div class="col-md-12">
                                <label for="maCaoOc" class="form-label">TÃ²a nhÃ  (Cao á»‘c) <span class="text-danger">*</span></label>
                                <select name="maCaoOc" id="maCaoOc" class="form-select py-2" required>
                                    <option value="">-- Chá»n tÃ²a nhÃ  --</option>
                                    <?php foreach ($dsCaoOc as $co): ?>
                                        <option value="<?= e($co['maCaoOc']) ?>" <?= ($co['maCaoOc'] == $tang['maCaoOc']) ? 'selected' : '' ?>>
                                            <?= e($co['tenCaoOc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- TÃªn Táº§ng -->
                            <div class="col-md-8">
                                <label for="tenTang" class="form-label">TÃªn Táº§ng <span class="text-danger">*</span></label>
                                <input type="text" name="tenTang" id="tenTang" class="form-control py-2" value="<?= e($tang['tenTang']) ?>" required>
                            </div>

                            <!-- Há»‡ sá»‘ giÃ¡ -->
                            <div class="col-md-4">
                                <label for="heSoGia" class="form-label">Há»‡ sá»‘ giÃ¡ <span class="text-danger">*</span></label>
                                <input type="number" name="heSoGia" id="heSoGia" class="form-control py-2" step="0.01" min="0.01" value="<?= e($tang['heSoGia']) ?>" required>
                                <div class="form-text">Há»‡ sá»‘ nhÃ¢n Ä‘Æ¡n giÃ¡ cho toÃ n táº§ng.</div>
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

<script>
document.getElementById('formSuaTang').addEventListener('submit', function(e) {
    const heSoGia = document.getElementById('heSoGia').value;
    if (parseFloat(heSoGia) <= 0) {
        alert('Há»‡ sá»‘ giÃ¡ pháº£i lá»›n hÆ¡n 0.');
        e.preventDefault();
    }
});
</script>

</body>
</html>
