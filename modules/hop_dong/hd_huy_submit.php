<?php
// modules/hop_dong/hd_huy_submit.php
/**
 * Xu ly huy hop dong toan phan (UC11).
 * - FOR UPDATE lock hop dong, chi huy khi trangThai = 1 (DangHieuLuc).
 * - Kiem tra cong no truoc khi huy.
 * - Xu ly tien coc: chuyen trangThai -> 2 (ChoXuLy/DaHoan).
 * - Khong nested try/catch. Dung ngayHuy, lyDoHuy tu FIX-01.
 * - Convention C.2 (transaction/rollback).
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

$soHD    = trim($_POST['soHopDong'] ?? '');
$ngayHuy = trim($_POST['ngayHuy'] ?? date('Y-m-d'));
$lyDoHuy = trim($_POST['lyDoHuy'] ?? '');

if (empty($soHD) || empty($lyDoHuy) || empty($ngayHuy)) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. Ma hop dong va ly do huy la bat buoc.";
    header("Location: hd_hienthi.php");
    exit();
}

$maNV_Huy = $_SESSION['user_id'] ?? null;
$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Lock hop dong va kiem tra trang thai hien tai
    $stmtCheckHD = $pdo->prepare("SELECT trangThai FROM HOP_DONG WHERE soHopDong = ? FOR UPDATE");
    $stmtCheckHD->execute([$soHD]);
    $currentStatus = $stmtCheckHD->fetchColumn();

    if ($currentStatus === false) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong [{$soHD}] khong ton tai.";
        header("Location: hd_hienthi.php");
        exit();
    }

    // Chi cho phep huy khi trangThai = 1 (DangHieuLuc) hoac 3 (ChoDuyet)
    if (!in_array((int)$currentStatus, [1, 3], true)) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong khong o trang thai co the huy (trang thai hien tai: {$currentStatus}). Chi huy duoc hop dong dang hieu luc hoac cho duyet.";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // 2. Kiem tra cong no: khong cho huy neu con no chua thanh toan
    $stmtDebt = $pdo->prepare("
        SELECT COALESCE(SUM(soTienConNo), 0) 
        FROM HOA_DON 
        WHERE soHopDong = ? AND trangThai IN ('ConNo', 'DaThuMotPhan') AND loaiHoaDon = 'Chinh'
    ");
    $stmtDebt->execute([$soHD]);
    $tongNo = (float)$stmtDebt->fetchColumn();

    if ($tongNo > 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Khong the huy hop dong: con cong no " . number_format($tongNo, 0) . " VND chua thanh toan. Vui long xu ly cong no truoc.";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // 3. UPDATE HOP_DONG: chuyen trangThai = 2 (DaHuy), ghi ngayHuy va lyDoHuy
    // Khong nested try/catch — ngayHuy/lyDoHuy da co san tu FIX-01
    $stmtHD = $pdo->prepare("
        UPDATE HOP_DONG 
        SET trangThai = 2, ngayHuy = ?, lyDoHuy = ? 
        WHERE soHopDong = ? AND trangThai IN (1, 3)
    ");
    $stmtHD->execute([$ngayHuy, $lyDoHuy, $soHD]);

    if ($stmtHD->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Khong the cap nhat trang thai hop dong. Co the da bi thay doi boi nguoi khac.";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // 4. UPDATE CHI_TIET_HOP_DONG: tat ca phong -> trangThai = 0 (DaKetThuc)
    $stmtCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET trangThai = 'DaKetThuc' WHERE soHopDong = ?");
    $stmtCT->execute([$soHD]);

    // 5. Tra phong ve trang thai Trong (1) va don dep PHONG_LOCK
    $stmtPH = $pdo->prepare("
        UPDATE PHONG SET trangThai = 1 
        WHERE maPhong IN (SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE soHopDong = ?)
    ");
    $stmtPH->execute([$soHD]);

    $stmtUnlock = $pdo->prepare("
        DELETE FROM PHONG_LOCK 
        WHERE maPhong IN (SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE soHopDong = ?)
    ");
    $stmtUnlock->execute([$soHD]);

    // 6. Xu ly tien coc: chuyen trangThai sang 4 (ChoXuLy) de ke toan xu ly sau
    $stmtCoc = $pdo->prepare("UPDATE TIEN_COC SET trangThai = 4 WHERE soHopDong = ? AND trangThai = 1");
    $stmtCoc->execute([$soHD]);

    $pdo->commit();

    // --- Sau commit ---

    // Ghi audit log
    ghiAuditLog(
        $pdo,
        $maNV_Huy,
        'CANCEL_HD',
        'HOP_DONG',
        $soHD,
        "Huy hop dong ngay {$ngayHuy}. Ly do: {$lyDoHuy}"
    );

    // Rotate CSRF token
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    $_SESSION['success_msg'] = "Huy hop dong [{$soHD}] thanh cong.";
    header("Location: hd_chitiet.php?id=" . urlencode($soHD) . "&msg=huy_toan_phan_thanh_cong");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[hd_huy_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi huy hop dong. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: hd_chitiet.php?id=" . urlencode($soHD));
    exit();
}
