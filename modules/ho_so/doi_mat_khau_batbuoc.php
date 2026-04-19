<?php
// modules/ho_so/doi_mat_khau_batbuoc.php
/**
 * - Query DB truc tiep de kiem tra co phai_doi_matkhau = 1 hay khong.
 * - Phan biet Staff (NHAN_VIEN) va Tenant (KHACH_HANG_ACCOUNT) bang user_role.
 * - Tenant update qua accountId (session key 'accountId').
 * Conventions: C.1 (session keys), C.2 (transaction/rollback), C.4 (output escaping).
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

// Kiem tra phien dang nhap
kiemTraSession();

// Doc session keys chuan (Convention C.1)
$roleId    = (int)($_SESSION['user_role'] ?? 0);
$userId    = $_SESSION['user_id'] ?? null;      // maNV (staff) hoac maKH (tenant)
$accountId = $_SESSION['accountId'] ?? null;     // Chi co o tenant

// Neu chua dang nhap hop le, redirect ve trang dang nhap
if (!$userId) {
    header("Location: " . BASE_URL . "dangnhap.php");
    exit();
}

// Query DB de kiem tra co phai_doi_matkhau = 1 hay khong (khong doc tu session)
try {
    $pdo = Database::getInstance()->getConnection();

    if ($roleId === ROLE_KHACH_HANG) {
        // Tenant: query bang KHACH_HANG_ACCOUNT theo accountId
        if (!$accountId) {
            header("Location: " . BASE_URL . "dangnhap.php");
            exit();
        }
        $stmtCheck = $pdo->prepare(
            "SELECT phai_doi_matkhau FROM KHACH_HANG_ACCOUNT WHERE accountId = ? AND deleted_at IS NULL"
        );
        $stmtCheck->execute([$accountId]);
    } else {
        // Staff (Admin / QLN / Ke Toan): query bang NHAN_VIEN theo maNV
        $stmtCheck = $pdo->prepare(
            "SELECT phai_doi_matkhau FROM NHAN_VIEN WHERE maNV = ? AND deleted_at IS NULL"
        );
        $stmtCheck->execute([$userId]);
    }

    $flag = (int)($stmtCheck->fetchColumn() ?: 0);

    // Neu khong can doi mat khau, redirect ve trang chu
    if ($flag !== 1) {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("[doi_mat_khau_batbuoc] Kiem tra flag loi: " . $e->getMessage());
    $_SESSION['error_msg'] = "Loi he thong khi kiem tra trang thai tai khoan. Vui long thu lai.";
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$errorMsg = '';

// Xu ly khi submit form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$csrf_token || !validateCSRFToken($csrf_token)) {
        $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
        header("Location: " . BASE_URL . "modules/ho_so/doi_mat_khau_batbuoc.php");
        exit();
    }

    $passNew     = $_POST['password_new'] ?? '';
    $passConfirm = $_POST['password_confirm'] ?? '';

    // Validate do dai va khop mat khau
    if (strlen($passNew) < 6) {
        $errorMsg = "Mat khau moi yeu cau toi thieu 6 ky tu.";
    } elseif ($passNew !== $passConfirm) {
        $errorMsg = "Hai mat khau khong trung khop. Vui long nhap lai.";
    } else {
        try {
            $hashMoi = password_hash($passNew, PASSWORD_BCRYPT);

            if ($roleId === ROLE_KHACH_HANG) {
                // Tenant: UPDATE bang KHACH_HANG_ACCOUNT theo accountId
                $stmtUp = $pdo->prepare(
                    "UPDATE KHACH_HANG_ACCOUNT SET password_hash = :hash, phai_doi_matkhau = 0 WHERE accountId = :id AND deleted_at IS NULL"
                );
                $stmtUp->execute([':hash' => $hashMoi, ':id' => $accountId]);
            } else {
                // Staff: UPDATE bang NHAN_VIEN theo maNV
                $stmtUp = $pdo->prepare(
                    "UPDATE NHAN_VIEN SET password_hash = :hash, phai_doi_matkhau = 0 WHERE maNV = :id AND deleted_at IS NULL"
                );
                $stmtUp->execute([':hash' => $hashMoi, ':id' => $userId]);
            }

            if ($stmtUp->rowCount() > 0) {
                unset($_SESSION['_pdm_flag'], $_SESSION['_pdm_checked_at']); // Force re-check
                $_SESSION['success_msg'] = "Mat khau da duoc thiet lap thanh cong.";
                header("Location: " . BASE_URL . "index.php");
                exit();
            } else {
                $errorMsg = "Khong the cap nhat mat khau. Tai khoan khong ton tai hoac da bi vo hieu hoa.";
            }

        } catch (PDOException $e) {
            error_log("[doi_mat_khau_batbuoc] UPDATE mat khau loi: " . $e->getMessage());
            $errorMsg = "Loi he thong khi cap nhat mat khau. Vui long lien he quan tri vien.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cap Nhat Mat Khau Bat Buoc</title>
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
                <i class="fa-solid fa-shield-halved me-2"></i>DOI MAT KHAU
            </h4>
            
            <p class="text-center text-muted mb-4" style="font-size: 0.95rem;">
                Vi ly do bao mat, ban <strong>bat buoc</strong> phai thiet lap mot mat khau moi de co the truy cap he thong.
            </p>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= function_exists('generateCSRFToken') ? generateCSRFToken() : '' ?>">

                <div class="mb-3">
                    <label for="password_new" class="form-label fw-bold">Mat khau moi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-lock" style="color: var(--brand-primary)"></i></span>
                        <input type="password" name="password_new" id="password_new" class="form-control" placeholder="Toi thieu 6 ky tu" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label fw-bold">Xac nhan lai mat khau</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-check-double text-muted"></i></span>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" placeholder="Nhap lai de xac nhan" required>
                    </div>
                </div>

                <div class="d-grid mt-2">
                    <button type="submit" class="btn btn-custom btn-lg">
                        Xac nhan &amp; Cap nhat <i class="fa-solid fa-paper-plane ms-1"></i>
                    </button>
                </div>
            </form>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
