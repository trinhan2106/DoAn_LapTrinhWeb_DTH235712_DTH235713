<?php
// modules/tien_coc/tc_xuly_submit.php
/**
 * Xu ly chuyen trang thai tien coc (hoan tra hoac tich thu).
 * - Phan quyen bang kiemTraRole (Convention C.1), khong hardcode so.
 * - Khong lo $e->getMessage() ra UI (A.2.14).
 * - Dung ghiAuditLog() thay vi INSERT thu cong.
 * - Convention C.4 (output escaping).
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
// Convention C.1: dung hang so role thay vi hardcode [1, 2]
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Kiem tra phuong thuc HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tc_hienthi.php");
    exit();
}

// Validate CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
    header("Location: tc_hienthi.php");
    exit();
}

// Doc input
$maTienCoc    = trim($_POST['maTienCoc'] ?? '');
$actionStatus = (int)($_POST['actionStatus'] ?? 0); // 2: Hoan tra, 3: Tich thu
$ghiChu       = trim($_POST['ghiChu'] ?? '');
$nguoiThucHien = $_SESSION['user_id'] ?? null;

// Validate input
if (empty($maTienCoc) || !in_array($actionStatus, [2, 3], true) || empty($ghiChu)) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. Vui long chon hanh dong va nhap ly do.";
    header("Location: tc_xuly.php?id=" . urlencode($maTienCoc));
    exit();
}

if (strlen($ghiChu) > 2000) {
    $_SESSION['error_msg'] = "Ghi chu vuot qua do dai cho phep (toi da 2000 ky tu).";
    header("Location: tc_xuly.php?id=" . urlencode($maTienCoc));
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // UPDATE tien coc: chi cho phep khi trangThai = 1 (Dang giu) hoac 4 (Cho xu ly)
    $stmtUpdate = $pdo->prepare("
        UPDATE TIEN_COC 
        SET trangThai = ? 
        WHERE maTienCoc = ? AND trangThai IN (1, 4)
    ");
    $stmtUpdate->execute([$actionStatus, $maTienCoc]);

    if ($stmtUpdate->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Khong the cap nhat. Khoan coc khong ton tai hoac da duoc xu ly truoc do.";
        header("Location: tc_hienthi.php");
        exit();
    }

    $pdo->commit();

    // --- Sau commit ---

    // Ghi audit log bang helper (thay vi INSERT thu cong)
    $quyetDinh = ($actionStatus === 2) ? "HOAN_TRA" : "TICH_THU";
    $quyetDinhLabel = ($actionStatus === 2) ? "Hoan tra tien mat" : "Tich thu xung quy";

    ghiAuditLog(
        $pdo,
        $nguoiThucHien,
        'XU_LY_TIEN_COC',
        'TIEN_COC',
        $maTienCoc,
        "Hanh dong: {$quyetDinhLabel}. Ly do: {$ghiChu}"
    );

    // Rotate CSRF token
    rotateCSRFToken();

    $_SESSION['success_msg'] = "Quyet dinh [{$quyetDinhLabel}] khoan coc [" . htmlspecialchars($maTienCoc, ENT_QUOTES, 'UTF-8') . "] da duoc ghi nhan.";
    header("Location: tc_hienthi.php");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // A.2.14: KHONG lo $e->getMessage() ra UI — chi ghi log
    error_log("[tc_xuly_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Da xay ra loi he thong khi cap nhat trang thai coc. Vui long lien he quan tri vien.";
    header("Location: tc_hienthi.php");
    exit();
}
