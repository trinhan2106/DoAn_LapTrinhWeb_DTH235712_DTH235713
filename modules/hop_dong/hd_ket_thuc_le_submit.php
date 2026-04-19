<?php
// modules/hop_dong/hd_ket_thuc_le_submit.php
/**
 * Xu ly ket thuc phong le (UC10): tra mot so phong trong hop dong, giu lai phan con lai.
 * - Chong IDOR: xac minh tat ca maCTHD thuoc dung soHopDong hien tai.
 * - Kiem tra trang thai hop dong = 1 (DangHieuLuc) truoc khi xu ly.
 * - SELECT FOR UPDATE de chong race condition.
 * - Khong die() — dung flash message + redirect.
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

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
    header("Location: hd_hienthi.php");
    exit();
}

// Doc input
$soHD = trim($_POST['soHopDong'] ?? '');
$mangPhayChon = $_POST['chonTraPhan'] ?? []; // Array ["maCTHD|maPhong", ...]

if (empty($soHD) || empty($mangPhayChon) || !is_array($mangPhayChon)) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. Vui long chon it nhat mot phong de ket thuc.";
    header("Location: hd_hienthi.php");
    exit();
}

// Trich xuat danh sach maCTHD va maPhong tu POST data
$dsMaCTHD = [];
$dsMapCTHD_Phong = []; // maCTHD => maPhong

foreach ($mangPhayChon as $item) {
    $parts = explode('|', $item);
    if (count($parts) === 2) {
        $maCTHD = trim($parts[0]);
        $maPhong = trim($parts[1]);
        if (!empty($maCTHD) && !empty($maPhong)) {
            $dsMaCTHD[] = $maCTHD;
            $dsMapCTHD_Phong[$maCTHD] = $maPhong;
        }
    }
}

if (empty($dsMaCTHD)) {
    $_SESSION['error_msg'] = "Du lieu phong khong hop le. Vui long chon lai.";
    header("Location: hd_hienthi.php");
    exit();
}

$maNV_KetThuc = $_SESSION['user_id'] ?? null;
$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Kiem tra trang thai hop dong: chi cho phep khi trangThai = 1 (DangHieuLuc)
    $stmtCheckHD = $pdo->prepare("SELECT trangThai FROM HOP_DONG WHERE soHopDong = ? FOR UPDATE");
    $stmtCheckHD->execute([$soHD]);
    $hdTrangThai = $stmtCheckHD->fetchColumn();

    if ($hdTrangThai === false) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong khong ton tai.";
        header("Location: hd_hienthi.php");
        exit();
    }

    if ((int)$hdTrangThai !== 1) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong khong o trang thai hieu luc (trang thai hien tai: {$hdTrangThai}). Khong the ket thuc phong le.";
        header("Location: hd_hienthi.php");
        exit();
    }

    // 2. CHONG IDOR: Xac minh TAT CA maCTHD thuoc dung soHopDong nay
    // va dang o trang thai active (trangThai = 1)
    $placeholders = implode(',', array_fill(0, count($dsMaCTHD), '?'));
    $stmtVerify = $pdo->prepare("
        SELECT COUNT(*) FROM CHI_TIET_HOP_DONG 
        WHERE soHopDong = ? AND maCTHD IN ({$placeholders}) AND trangThai = 1
        FOR UPDATE
    ");
    $paramsVerify = array_merge([$soHD], $dsMaCTHD);
    $stmtVerify->execute($paramsVerify);
    $countMatched = (int)$stmtVerify->fetchColumn();

    if ($countMatched !== count($dsMaCTHD)) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Du lieu khong hop le. Co ma chi tiet khong thuoc hop dong nay hoac khong o trang thai dang thue. So yeu cau: " . count($dsMaCTHD) . ", so xac minh: {$countMatched}.";
        header("Location: hd_hienthi.php");
        exit();
    }

    // 3. Kiem tra khong duoc ket thuc tat ca phong (phai dung UC11 huy toan phan)
    $stmtCheckCount = $pdo->prepare("SELECT COUNT(*) FROM CHI_TIET_HOP_DONG WHERE soHopDong = ? AND trangThai = 1");
    $stmtCheckCount->execute([$soHD]);
    $soPhongActive = (int)$stmtCheckCount->fetchColumn();

    if (count($dsMaCTHD) >= $soPhongActive) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Khong the ket thuc tat ca phong bang chuc nang nay. Vui long su dung chuc nang huy hop dong (UC11) de ket thuc toan bo.";
        header("Location: hd_hienthi.php");
        exit();
    }

    // 4. Thuc hien UPDATE: ket thuc tung phong da chon
    $stmtCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET trangThai = 0 WHERE maCTHD = ? AND soHopDong = ?");
    $stmtPH = $pdo->prepare("UPDATE PHONG SET trangThai = 1 WHERE maPhong = ?");
    $stmtLock = $pdo->prepare("DELETE FROM PHONG_LOCK WHERE maPhong = ?");

    // Lay maPhong tu DB (da verify thuoc $soHD) thay vi trust POST
    $stmtGetPhongDB = $pdo->prepare("SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE maCTHD = ? AND soHopDong = ?");
    
    foreach ($dsMaCTHD as $maCTHD) {
        $stmtGetPhongDB->execute([$maCTHD, $soHD]);
        $maPhong = $stmtGetPhongDB->fetchColumn();
        
        if (!$maPhong) continue; // Should not happen after verify

        $stmtCT->execute([$maCTHD, $soHD]);
        $stmtPH->execute([$maPhong]);
        $stmtLock->execute([$maPhong]);
    }

    // 5. Tinh lai ngayKetThuc cho hop dong dua tren cac phong con lai
    $sqlMaxDate = "
        SELECT MAX(COALESCE(ngayHetHan, (SELECT ngayKetThuc FROM HOP_DONG WHERE soHopDong = :hd1)))
        FROM CHI_TIET_HOP_DONG 
        WHERE soHopDong = :hd2 AND trangThai = 1
    ";
    $stmtMax = $pdo->prepare($sqlMaxDate);
    $stmtMax->execute([':hd1' => $soHD, ':hd2' => $soHD]);
    $maxDateMoi = $stmtMax->fetchColumn();

    if ($maxDateMoi) {
        $stmtUpHD = $pdo->prepare("UPDATE HOP_DONG SET ngayKetThuc = ? WHERE soHopDong = ?");
        $stmtUpHD->execute([$maxDateMoi, $soHD]);
    }

    $pdo->commit();

    // Ghi audit log sau commit
    $soPhongKetThuc = count($dsMaCTHD);
    $dsPhongStr = implode(', ', array_values($dsMapCTHD_Phong));
    ghiAuditLog(
        $pdo,
        $maNV_KetThuc,
        'END_ROOM_PARTIAL',
        'CHI_TIET_HOP_DONG',
        $soHD,
        "Ket thuc phong le HD {$soHD}: so phong tra={$soPhongKetThuc}, phong=[{$dsPhongStr}], ngay het han HD moi={$maxDateMoi}"
    );

    // Rotate CSRF token
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    $_SESSION['success_msg'] = "Ket thuc {$soPhongKetThuc} phong thanh cong cho hop dong {$soHD}.";
    header("Location: hd_hienthi.php?msg=tra_le_success");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[hd_ket_thuc_le_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi ket thuc phong le. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: hd_hienthi.php");
    exit();
}
