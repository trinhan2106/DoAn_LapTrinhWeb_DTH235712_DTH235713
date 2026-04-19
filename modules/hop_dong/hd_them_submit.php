<?php
// modules/hop_dong/hd_them_submit.php
/**
 * Xu ly tao hop dong moi (UC03) qua form Wizard.
 * - Sinh ma HD va CTHD bang sinhMaNgauNhien (Convention C.5).
 * - Validate server-side: ngayBatDau >= ngayLap, ngayKetThuc > ngayBatDau, tienCoc >= 0.
 * - INSERT TIEN_COC neu co tien coc > 0 (A.2.6).
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
    header("Location: hd_them.php");
    exit();
}

// Validate CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
    header("Location: hd_them.php");
    exit();
}

// Doc input
$maKH        = trim($_POST['maKH'] ?? '');
$maPhong     = trim($_POST['maPhong'] ?? '');
$ngayLap     = trim($_POST['ngayLap'] ?? date('Y-m-d'));
$ngayBatDau  = trim($_POST['ngayBatDau'] ?? '');
$ngayKetThuc = trim($_POST['ngayKetThuc'] ?? '');
$tienTienCoc = (float)($_POST['tienTienCoc'] ?? 0);
$maNV        = $_SESSION['user_id'] ?? null;

// Validate input co ban
if (empty($maKH) || empty($maPhong) || empty($ngayBatDau) || empty($ngayKetThuc) || !$maNV) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. Vui long dien day du thong tin bat buoc.";
    header("Location: hd_them.php");
    exit();
}

// Validate do dai input
if (strlen($maKH) > 20 || strlen($maPhong) > 50) {
    $_SESSION['error_msg'] = "Ma khach hang hoac ma phong vuot qua do dai cho phep.";
    header("Location: hd_them.php");
    exit();
}

// Validate server-side: rang buoc ngay thang va tien coc (A.2.5)
if (strtotime($ngayBatDau) < strtotime($ngayLap)) {
    $_SESSION['error_msg'] = "Ngay bat dau ({$ngayBatDau}) khong duoc truoc ngay lap ({$ngayLap}).";
    header("Location: hd_them.php");
    exit();
}

if (strtotime($ngayKetThuc) <= strtotime($ngayBatDau)) {
    $_SESSION['error_msg'] = "Ngay ket thuc ({$ngayKetThuc}) phai sau ngay bat dau ({$ngayBatDau}).";
    header("Location: hd_them.php");
    exit();
}

if ($tienTienCoc < 0) {
    $_SESSION['error_msg'] = "Tien coc khong duoc am.";
    header("Location: hd_them.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    // Lock phong va kiem tra trang thai = 1 (Trong)
    $stmtCheck = $pdo->prepare("SELECT trangThai, giaThue FROM PHONG WHERE maPhong = ? FOR UPDATE");
    $stmtCheck->execute([$maPhong]);
    $phongData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$phongData) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Phong [{$maPhong}] khong ton tai trong he thong.";
        header("Location: hd_them.php");
        exit();
    }

    if ((int)$phongData['trangThai'] !== 1) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Phong [{$maPhong}] khong con trong (trang thai: {$phongData['trangThai']}). Co the da duoc nhan vien khac lap hop dong.";
        header("Location: hd_them.php");
        exit();
    }

    // Sinh ma hop dong bang sinhMaNgauNhien (Convention C.5)
    $soHD_Ran = sinhMaNgauNhien('HD-' . date('Y') . '-', 6);

    // INSERT HOP_DONG — trangThai = 3 (ChoDuyet)
    $stmtHD = $pdo->prepare("
        INSERT INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayKetThuc, tienTienCoc, trangThai)
        VALUES (:soHD, :kh, :nv, :nlap, :nbd, :nkt, :coc, 3)
    ");
    $stmtHD->execute([
        ':soHD' => $soHD_Ran,
        ':kh'   => $maKH,
        ':nv'   => $maNV,
        ':nlap' => $ngayLap,
        ':nbd'  => $ngayBatDau,
        ':nkt'  => $ngayKetThuc,
        ':coc'  => $tienTienCoc
    ]);

    // Sinh ma CTHD bang sinhMaNgauNhien — KHONG dung str_shuffle (A.1.6, C.5)
    $maCTHD_Ran = sinhMaNgauNhien('CTHD-', 7);

    // INSERT CHI_TIET_HOP_DONG — giaThue lay tu DB (chong F12 sua gia)
    $stmtCT = $pdo->prepare("
        INSERT INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, trangThai)
        VALUES (:ct, :hd, :phong, :gia, 0)
    ");
    $stmtCT->execute([
        ':ct'    => $maCTHD_Ran,
        ':hd'    => $soHD_Ran,
        ':phong' => $maPhong,
        ':gia'   => $phongData['giaThue']
    ]);

    $pdo->commit();

    // --- Sau commit ---

    // INSERT TIEN_COC neu co tien coc > 0 (A.2.6)
    if ($tienTienCoc > 0) {
        $maTC = sinhMaNgauNhien('TC-' . date('Ym') . '-', 5);
        $stmtCoc = $pdo->prepare("
            INSERT INTO TIEN_COC (maTienCoc, soHopDong, soTien, phuongThuc, nguoiThu, trangThai, ngayNop)
            VALUES (?, ?, ?, 'TienMat', ?, 1, NOW())
        ");
        $stmtCoc->execute([$maTC, $soHD_Ran, $tienTienCoc, $maNV]);
    }

    // Ghi audit log
    ghiAuditLog(
        $pdo,
        $maNV,
        'CREATE_HD',
        'HOP_DONG',
        $soHD_Ran,
        "Lap hop dong moi: KH={$maKH}, phong={$maPhong}, coc=" . number_format($tienTienCoc, 0) . " VND, trangThai=ChoDuyet"
    );

    // Rotate CSRF token
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    $_SESSION['success_msg'] = "Lap hop dong [{$soHD_Ran}] thanh cong.";
    header("Location: hd_ky.php?id=" . urlencode($soHD_Ran) . "&msg=created");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[hd_them_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi luu hop dong. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: hd_them.php");
    exit();
}
