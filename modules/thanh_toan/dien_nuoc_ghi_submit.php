<?php
// modules/thanh_toan/dien_nuoc_ghi_submit.php
/**
 * Xu ly ghi nhan chi so dien nuoc va tao hoa don tuong ung.
 * - Access control: ADMIN, KE_TOAN, QUAN_LY_NHA.
 * - Map POST fields sang dung cot DB: chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi.
 * - Validate server-side: chiSoMoi >= chiSoCu.
 * - INSERT day du cot vao CHI_SO_DIEN_NUOC (bao gom thanhTienDien, thanhTienNuoc).
 * - INSERT HOA_DON voi day du cot FIX-01 (loaiHoaDon, maNV, kyThanhToan, lyDo, created_at).
 * - Ghi audit log. Khong die() hay echo loi SQL ra HTML.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN, ROLE_QUAN_LY_NHA]);

// Kiem tra phuong thuc HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dien_nuoc_ghi.php");
    exit();
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
    header("Location: dien_nuoc_ghi.php");
    exit();
}

// Doc input tu form
$maPhong    = trim($_POST['maPhong'] ?? '');
$soHopDong  = trim($_POST['soHopDong'] ?? '');
$thangGhi   = (int)($_POST['thangGhi'] ?? 0);
$namGhi     = (int)($_POST['namGhi'] ?? 0);

// Map POST field names (tu form) sang gia tri dung cho DB columns
// Form gui: chiSoDien_Dau, chiSoDien_Cuoi -> DB: chiSoDienCu, chiSoDienMoi
$csD_Cu     = (float)($_POST['chiSoDien_Dau'] ?? 0);
$csD_Moi    = (float)($_POST['chiSoDien_Cuoi'] ?? 0);
$dgDien     = (float)($_POST['donGiaDien'] ?? 0);

$csN_Cu     = (float)($_POST['chiSoNuoc_Dau'] ?? 0);
$csN_Moi    = (float)($_POST['chiSoNuoc_Cuoi'] ?? 0);
$dgNuoc     = (float)($_POST['donGiaNuoc'] ?? 0);

$maNV_HienHanh = $_SESSION['user_id'] ?? null;

// Validate input co ban
if (empty($maPhong) || empty($soHopDong) || $thangGhi < 1 || $thangGhi > 12 || $namGhi < 2020 || $namGhi > 2099) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. Vui long kiem tra ma phong, thang va nam.";
    header("Location: dien_nuoc_ghi.php");
    exit();
}

if ($dgDien < 0 || $dgNuoc < 0) {
    $_SESSION['error_msg'] = "Don gia dien/nuoc khong duoc am.";
    header("Location: dien_nuoc_ghi.php");
    exit();
}

// Validate server-side: chi so moi phai >= chi so cu
if ($csD_Moi < $csD_Cu) {
    $_SESSION['error_msg'] = "Chi so dien moi ({$csD_Moi}) khong duoc nho hon chi so dien cu ({$csD_Cu}).";
    header("Location: dien_nuoc_ghi.php");
    exit();
}

if ($csN_Moi < $csN_Cu) {
    $_SESSION['error_msg'] = "Chi so nuoc moi ({$csN_Moi}) khong duoc nho hon chi so nuoc cu ({$csN_Cu}).";
    header("Location: dien_nuoc_ghi.php");
    exit();
}

// Tinh toan thanh tien
$deltaDien      = $csD_Moi - $csD_Cu;
$deltaNuoc      = $csN_Moi - $csN_Cu;
$thanhTienDien  = $deltaDien * $dgDien;
$thanhTienNuoc  = $deltaNuoc * $dgNuoc;
$tongTien       = $thanhTienDien + $thanhTienNuoc;

$pdo = Database::getInstance()->getConnection();

// CONCERN-F07: Validate maPhong thuoc soHopDong
$stmtVerifyPhong = $pdo->prepare("
    SELECT COUNT(*) FROM CHI_TIET_HOP_DONG 
    WHERE soHopDong = ? AND maPhong = ? AND trangThai = 'DangThue'
");
$stmtVerifyPhong->execute([$soHopDong, $maPhong]);
if ((int)$stmtVerifyPhong->fetchColumn() === 0) {
    $_SESSION['error_msg'] = "Phong {$maPhong} khong thuoc hop dong {$soHopDong} hoac khong dang hieu luc.";
    header("Location: dien_nuoc_ghi.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Sinh maChiSo va INSERT vao CHI_SO_DIEN_NUOC (day du cot theo schema)
    $maChiSo = sinhMaNgauNhien('CS-' . date('Ym') . '-', 5);

    $stmtGhiSo = $pdo->prepare("
        INSERT INTO CHI_SO_DIEN_NUOC (
            maChiSo, maPhong, thangGhi, namGhi,
            chiSoDienCu, chiSoDienMoi, chiSoNuocCu, chiSoNuocMoi,
            donGiaDien, donGiaNuoc, thanhTienDien, thanhTienNuoc
        ) VALUES (
            :maChiSo, :phong, :thang, :nam,
            :dCu, :dMoi, :nCu, :nMoi,
            :dGia, :nGia, :ttDien, :ttNuoc
        )
    ");
    $stmtGhiSo->execute([
        ':maChiSo' => $maChiSo,
        ':phong'   => $maPhong,
        ':thang'   => $thangGhi,
        ':nam'     => $namGhi,
        ':dCu'     => $csD_Cu,
        ':dMoi'    => $csD_Moi,
        ':nCu'     => $csN_Cu,
        ':nMoi'    => $csN_Moi,
        ':dGia'    => $dgDien,
        ':nGia'    => $dgNuoc,
        ':ttDien'  => $thanhTienDien,
        ':ttNuoc'  => $thanhTienNuoc
    ]);

    // 2. Sinh soHoaDon va INSERT vao HOA_DON (day du cot FIX-01)
    $soHD_INV = sinhMaNgauNhien('INV-DN-' . date('Ym') . '-', 5);
    $kyThanhToanStr = sprintf("%02d/%04d", $thangGhi, $namGhi);

    $stmtBill = $pdo->prepare("
        INSERT INTO HOA_DON (
            soHoaDon, soHopDong, thang, nam,
            tongTien, soTienDaNop, soTienConNo,
            trangThai, kyThanhToan, lyDo, maNV, loaiHoaDon
        ) VALUES (
            :soHD, :soHDP, :thang, :nam,
            :tongt, 0, :tongt_no,
            'ConNo', :kyTT, 'TienDienNuoc', :maNV, 'Chinh'
        )
    ");
    $stmtBill->execute([
        ':soHD'     => $soHD_INV,
        ':soHDP'    => $soHopDong,
        ':thang'    => $thangGhi,
        ':nam'      => $namGhi,
        ':tongt'    => $tongTien,
        ':tongt_no' => $tongTien,
        ':kyTT'     => $kyThanhToanStr,
        ':maNV'     => $maNV_HienHanh
    ]);

    $pdo->commit();

    // Ghi audit log sau commit
    ghiAuditLog(
        $pdo,
        $maNV_HienHanh,
        'CREATE_INVOICE_UTILITY',
        'CHI_SO_DIEN_NUOC',
        $maChiSo,
        "Ghi chi so dien nuoc phong={$maPhong}, ky={$kyThanhToanStr}, "
        . "dien: {$csD_Cu}->{$csD_Moi} (don gia={$dgDien}, thanh tien=" . number_format($thanhTienDien, 0) . "), "
        . "nuoc: {$csN_Cu}->{$csN_Moi} (don gia={$dgNuoc}, thanh tien=" . number_format($thanhTienNuoc, 0) . "), "
        . "tong tien=" . number_format($tongTien, 0) . " VND, hoa don={$soHD_INV}"
    );

    // Rotate CSRF token
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    // Redirect thanh cong
    $_SESSION['success_msg'] = "Ghi nhan chi so dien nuoc thanh cong. Ma hoa don: {$soHD_INV}, tong tien: " . number_format($tongTien, 0) . " VND.";
    header("Location: dien_nuoc_ghi.php");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Xu ly loi trung lap UNIQUE constraint (maPhong + thangGhi + namGhi)
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $_SESSION['error_msg'] = "Phong {$maPhong} da duoc ghi chi so dien nuoc cho thang {$thangGhi}/{$namGhi}. Khong the ghi trung.";
        header("Location: dien_nuoc_ghi.php");
        exit();
    }

    // Loi chung: ghi log, khong lo thong tin DB ra ngoai
    error_log("[dien_nuoc_ghi_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi ghi nhan chi so dien nuoc. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: dien_nuoc_ghi.php");
    exit();
}
