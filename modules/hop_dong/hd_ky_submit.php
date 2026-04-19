<?php
// modules/hop_dong/hd_ky_submit.php
/**
 * Xu ly ky hop dong (UC04): chuyen trangThai tu ChoDuyet (3) sang DangHieuLuc (1).
 * - FOR UPDATE lock hop dong + phong truoc khi cap nhat.
 * - Chi ky duoc khi trangThai = 3 (ChoDuyet).
 * - Kiem tra phong con trong (trangThai = 1) truoc khi chuyen sang DangThue.
 * - Convention C.2 (transaction/rollback), C.4 (output escaping).
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Kiem tra phuong thuc HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: hd_hienthi.php");
    exit();
}

// Validate CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
    header("Location: hd_hienthi.php");
    exit();
}

$soHopDong = trim($_POST['soHopDong'] ?? '');
if (empty($soHopDong)) {
    $_SESSION['error_msg'] = "Thieu ma so hop dong.";
    header("Location: hd_hienthi.php");
    exit();
}

$maNV_KyHD = $_SESSION['user_id'] ?? null;
$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Lock hop dong va kiem tra trang thai hien tai
    $stmtCheckHD = $pdo->prepare("SELECT trangThai FROM HOP_DONG WHERE soHopDong = ? FOR UPDATE");
    $stmtCheckHD->execute([$soHopDong]);
    $currentStatus = $stmtCheckHD->fetchColumn();

    if ($currentStatus === false) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong [{$soHopDong}] khong ton tai.";
        header("Location: hd_hienthi.php");
        exit();
    }

    if ((int)$currentStatus !== 3) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong khong o trang thai Cho Duyet (trang thai hien tai: {$currentStatus}). Khong the ky.";
        header("Location: hd_hienthi.php");
        exit();
    }

    // 2. UPDATE hop dong: ChoDuyet (3) -> DangHieuLuc (1)
    // Defense-in-depth: them AND trangThai = 3 trong WHERE
    $stmtHD = $pdo->prepare("UPDATE HOP_DONG SET trangThai = 1 WHERE soHopDong = ? AND trangThai = 3");
    $stmtHD->execute([$soHopDong]);

    if ($stmtHD->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Khong the cap nhat trang thai hop dong. Co the da bi thay doi boi nguoi khac.";
        header("Location: hd_hienthi.php");
        exit();
    }

    // 3. Lay danh sach phong thuoc hop dong nay
    $stmtGetPh = $pdo->prepare("SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE soHopDong = ?");
    $stmtGetPh->execute([$soHopDong]);
    $dsPhong = $stmtGetPh->fetchAll(PDO::FETCH_COLUMN);

    if (count($dsPhong) > 0) {
        // Cap nhat trang thai CTHD sang DangThue (1)
        $stmtCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET trangThai = 1 WHERE soHopDong = ?");
        $stmtCT->execute([$soHopDong]);

        // Prepare cac statement dung trong vong lap
        $stmtLockPhong   = $pdo->prepare("SELECT trangThai FROM PHONG WHERE maPhong = ? FOR UPDATE");
        $stmtPhongStatus = $pdo->prepare("UPDATE PHONG SET trangThai = 2 WHERE maPhong = ?");
        $stmtDeleteLock  = $pdo->prepare("DELETE FROM PHONG_LOCK WHERE maPhong = ?");

        foreach ($dsPhong as $maPhongItem) {
            // Lock phong truoc khi cap nhat
            $stmtLockPhong->execute([$maPhongItem]);
            $phongTrangThai = (int)$stmtLockPhong->fetchColumn();

            if ($phongTrangThai !== 1) {
                // Phong khong con trong -> rollback
                $pdo->rollBack();
                $_SESSION['error_msg'] = "Phong {$maPhongItem} khong con trong (trang thai: {$phongTrangThai}). Khong the ky hop dong.";
                header("Location: hd_hienthi.php");
                exit();
            }

            // Chuyen phong sang DangThue (2)
            $stmtPhongStatus->execute([$maPhongItem]);

            // Xoa PHONG_LOCK (don dep)
            $stmtDeleteLock->execute([$maPhongItem]);
        }
    }

    $pdo->commit();

    // --- Sau commit ---

    // Ghi audit log
    $soPhongDuyet = count($dsPhong);
    ghiAuditLog(
        $pdo,
        $maNV_KyHD,
        'SIGN_HD',
        'HOP_DONG',
        $soHopDong,
        "Ky hop dong: ChoDuyet -> DangHieuLuc. So phong kich hoat: {$soPhongDuyet}"
    );

    // Rotate CSRF token
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    $_SESSION['success_msg'] = "Ky hop dong [{$soHopDong}] thanh cong. {$soPhongDuyet} phong da duoc kich hoat.";
    header("Location: hd_ky.php?id=" . urlencode($soHopDong) . "&msg=signed");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[hd_ky_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi ky hop dong. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: hd_hienthi.php");
    exit();
}
