<?php
// modules/hop_dong/hd_gia_han_submit.php
/**
 * Xu ly gia han hop dong (UC08).
 * - Sinh ma bang sinhMaNgauNhien (Convention C.5).
 * - Khong nested try/catch (A.2.2).
 * - Precondition: FOR UPDATE + trangThai = 1,4 + kiem tra ngay het han (A.2.3, A.2.4).
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

$soHD          = trim($_POST['soHopDong'] ?? '');
$dsMaCTHD      = $_POST['dsMaCTHD'] ?? [];
$dsMaPhong     = $_POST['dsMaPhong'] ?? [];
$soThangGiaHan = $_POST['soThangGiaHan'] ?? []; // Array: maCTHD => soThang

if (empty($soHD) || empty($dsMaCTHD) || !is_array($dsMaCTHD)) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. Ma hop dong va danh sach phong la bat buoc.";
    header("Location: hd_hienthi.php"); // Chưa biết soHD nên redirect về danh sách
    exit();
}

$maNV_GiaHan = $_SESSION['user_id'] ?? null;
$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Precondition: Lock hop dong va kiem tra trang thai + ngay het han
    $stmtCheckHD = $pdo->prepare("
        SELECT trangThai, ngayKetThuc, DATEDIFF(ngayKetThuc, CURRENT_DATE) AS ngayConLai
        FROM HOP_DONG 
        WHERE soHopDong = ? 
        FOR UPDATE
    ");
    $stmtCheckHD->execute([$soHD]);
    $hdData = $stmtCheckHD->fetch(PDO::FETCH_ASSOC);

    if (!$hdData) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong [{$soHD}] khong ton tai.";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // Chi cho phep gia han khi trangThai = 1 (DangHieuLuc) hoac 4 (DaGiaHan)
    if (!in_array((int)$hdData['trangThai'], [1, 4], true)) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong khong o trang thai co the gia han (trang thai hien tai: {$hdData['trangThai']}). Chi gia han duoc hop dong dang hieu luc.";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // Kiem tra ngay het han: khong cho gia han neu con > 30 ngay (A.2.4)
    $ngayConLai = (int)($hdData['ngayConLai'] ?? 0);
    if ($ngayConLai > 30) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hop dong con {$ngayConLai} ngay moi het han (het han: {$hdData['ngayKetThuc']}). Chi gia han khi con <= 30 ngay.";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // CHONG IDOR: Verify tat ca maCTHD thuoc soHopDong
    $placeholders = implode(',', array_fill(0, count($dsMaCTHD), '?'));
    $stmtVerify = $pdo->prepare("
        SELECT COUNT(*) FROM CHI_TIET_HOP_DONG 
        WHERE soHopDong = ? AND maCTHD IN ($placeholders)
    ");
    $stmtVerify->execute(array_merge([$soHD], $dsMaCTHD));
    
    if ((int)$stmtVerify->fetchColumn() !== count($dsMaCTHD)) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Du lieu khong hop le. Co ma chi tiet khong thuoc hop dong nay.";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // 2. Sinh ma gia han bang sinhMaNgauNhien (Convention C.5)
    $soGiaHan = sinhMaNgauNhien('GH-' . date('Ym') . '-', 6);

    // INSERT vao GIA_HAN_HOP_DONG
    $stmtHistory = $pdo->prepare("
        INSERT INTO GIA_HAN_HOP_DONG (soGiaHan, soHopDong, ngayKyGiaHan) 
        VALUES (?, ?, NOW())
    ");
    $stmtHistory->execute([$soGiaHan, $soHD]);

    // 3. Prepare cac statement dung trong vong lap
    $stmtInsertCTGH = $pdo->prepare("
        INSERT INTO CHI_TIET_GIA_HAN (soGiaHan, maPhong, thoiGianGiaHan, ngayHetHanMoi) 
        VALUES (?, ?, ?, ?)
    ");

    $stmtUpdateCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET ngayHetHan = ? WHERE maCTHD = ?");

    $stmtGetBase = $pdo->prepare("
        SELECT COALESCE(ngayHetHan, (SELECT ngayKetThuc FROM HOP_DONG WHERE soHopDong = ?)) 
        FROM CHI_TIET_HOP_DONG 
        WHERE maCTHD = ?
    ");

    $maxDateMoi = null;
    $soPhongXuLy = 0;

    foreach ($dsMaCTHD as $maCT) {
        $thangGiaHan = (int)($soThangGiaHan[$maCT] ?? 0);

        // Chi xu ly phong co so thang gia han > 0
        if ($thangGiaHan <= 0) {
            continue;
        }

        // Validate so thang hop ly (toi da 60 thang = 5 nam)
        if ($thangGiaHan > 60) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "So thang gia han khong hop le (toi da 60 thang).";
            header("Location: hd_chitiet.php?id=" . urlencode($soHD));
            exit();
        }

        $maPh = $dsMaPhong[$maCT] ?? '';
        if (empty($maPh)) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Thieu thong tin ma phong cho chi tiet {$maCT}.";
            header("Location: hd_chitiet.php?id=" . urlencode($soHD));
            exit();
        }

        // Lay ngay het han hien tai tu DB (khong tin JS)
        $stmtGetBase->execute([$soHD, $maCT]);
        $baseDate = $stmtGetBase->fetchColumn();

        if (!$baseDate) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Khong tim thay thong tin ngay het han cho chi tiet {$maCT}.";
            header("Location: hd_chitiet.php?id=" . urlencode($soHD));
            exit();
        }

        // Tinh ngay het han moi
        $newDateStr = date('Y-m-d', strtotime("+{$thangGiaHan} months", strtotime($baseDate)));

        // INSERT chi tiet gia han
        $stmtInsertCTGH->execute([$soGiaHan, $maPh, $thangGiaHan, $newDateStr]);

        // UPDATE ngayHetHan trong CHI_TIET_HOP_DONG
        $stmtUpdateCT->execute([$newDateStr, $maCT]);

        // Tim max date
        if ($maxDateMoi === null || strtotime($newDateStr) > strtotime($maxDateMoi)) {
            $maxDateMoi = $newDateStr;
        }

        $soPhongXuLy++;
    }

    // Kiem tra co it nhat 1 phong duoc gia han
    if ($soPhongXuLy === 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Chua co phong nao duoc chon gia han (so thang phai > 0).";
        header("Location: hd_chitiet.php?id=" . urlencode($soHD));
        exit();
    }

    // 4. Cap nhat HOP_DONG: noi ngayKetThuc, doi trangThai = 4 (DaGiaHan)
    if ($maxDateMoi !== null) {
        $stmtChot = $pdo->prepare("
            UPDATE HOP_DONG 
            SET ngayKetThuc = ?, trangThai = 4 
            WHERE soHopDong = ? AND trangThai IN (1, 4)
        ");
        $stmtChot->execute([$maxDateMoi, $soHD]);
    }

    $pdo->commit();

    // --- Sau commit ---

    // Ghi audit log
    ghiAuditLog(
        $pdo,
        $maNV_GiaHan,
        'EXTEND_HD',
        'HOP_DONG',
        $soHD,
        "Gia han HD: ma gia han={$soGiaHan}, so phong={$soPhongXuLy}, ngay het han moi={$maxDateMoi}"
    );

    // Rotate CSRF token
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    $_SESSION['success_msg'] = "Gia han hop dong [{$soHD}] thanh cong. {$soPhongXuLy} phong da duoc cap nhat, het han moi: {$maxDateMoi}.";
    header("Location: hd_chitiet.php?id=" . urlencode($soHD) . "&msg=giahan_success");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[hd_gia_han_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi gia han hop dong. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: hd_chitiet.php?id=" . urlencode($soHD));
    exit();
}
