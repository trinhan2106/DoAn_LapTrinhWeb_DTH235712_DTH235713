<?php
// modules/thanh_toan/tt_void_submit.php
/**
 * Xu ly void hoa don va sinh credit note (neu khach da nop tien).
 * - Precondition: chi void khi trangThai la 'ConNo', 'DaThuMotPhan', hoac 'DaThu'.
 * - Credit note: loaiHoaDon = 'CreditNote' de waterfall khong lay nham.
 * - Sinh ID bang sinhMaNgauNhien (Convention C.5).
 * - Dung ghiAuditLog() helper thay vi INSERT thu cong.
 * - Khong die() hay echo HTML truc tiep (Convention C.2, C.4).
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
    header("Location: tt_tao.php");
    exit();
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Phien lam viec het han. Vui long tai lai trang.";
    header("Location: tt_tao.php");
    exit();
}

// Doc input
$soHoaDonVoid = trim($_POST['soHoaDon'] ?? '');
$lyDoVoid     = trim($_POST['lyDoVoid'] ?? '');
$maNVThucThi  = $_SESSION['user_id'] ?? null;

if (empty($soHoaDonVoid) || empty($lyDoVoid) || !$maNVThucThi) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. Ma hoa don va ly do void la bat buoc.";
    header("Location: tt_tao.php");
    exit();
}

// Validate do dai input
if (strlen($soHoaDonVoid) > 50 || strlen($lyDoVoid) > 2000) {
    $_SESSION['error_msg'] = "Du lieu vuot qua do dai cho phep.";
    header("Location: tt_tao.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();
$crID = ''; // Luu ma credit note (neu co) de hien thi trong success message
$soHopDongGoc = '';

try {
    $pdo->beginTransaction();

    // SELECT ... FOR UPDATE: Khoa hoa don truoc khi xu ly
    $stmtLock = $pdo->prepare("SELECT * FROM HOA_DON WHERE soHoaDon = ? FOR UPDATE");
    $stmtLock->execute([$soHoaDonVoid]);
    $billData = $stmtLock->fetch(PDO::FETCH_ASSOC);

    if (!$billData) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hoa don khong ton tai hoac da bi xoa.";
        header("Location: tt_tao.php");
        exit();
    }

    // Precondition: Kiem tra trang thai hoa don truoc khi void
    // Chi cho phep void khi trang thai la: ConNo, DaThuMotPhan, DaThu
    $trangThaiHopLe = ['ConNo', 'DaThuMotPhan', 'DaThu'];
    if (!in_array($billData['trangThai'], $trangThaiHopLe, true)) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Hoa don o trang thai '" . htmlspecialchars($billData['trangThai'], ENT_QUOTES, 'UTF-8') . "', khong the void. Chi void duoc hoa don ConNo, DaThuMotPhan hoac DaThu.";
        header("Location: tt_tao.php?soHopDong=" . urlencode($billData['soHopDong']));
        exit();
    }

    $soHopDongGoc = $billData['soHopDong'];
    $tienKhachDaNop = (float)$billData['soTienDaNop'];

    // (a) Cap nhat trang thai hoa don thanh 'Void'
    $stmtVoid = $pdo->prepare("UPDATE HOA_DON SET trangThai = 'Void' WHERE soHoaDon = ?");
    $stmtVoid->execute([$soHoaDonVoid]);

    // (b) Ghi nhan vao bang HOA_DON_VOID
    $stmtHistoryVoid = $pdo->prepare("
        INSERT INTO HOA_DON_VOID (soPhieu, maNV_Void, lyDoVoid, ngayVoid) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmtHistoryVoid->execute([$soHoaDonVoid, $maNVThucThi, $lyDoVoid]);

    // (c) Sinh Credit Note neu khach da nop tien > 0
    // Credit note luu vao HOA_DON voi loaiHoaDon = 'CreditNote'
    // de waterfall (tt_tao_submit) khong lay nham khi phan bo tien
    if ($tienKhachDaNop > 0) {
        // Convention C.5: Dung sinhMaNgauNhien thay cho date+rand
        $crID = sinhMaNgauNhien('CR-' . date('Ym') . '-', 7);
        $lyDoCreditNote = "Hoan tien tu void hoa don: {$soHoaDonVoid}";
        $soTienAm = -$tienKhachDaNop;
        $kyThanhToan = date('m/Y');

        $stmtCR = $pdo->prepare("
            INSERT INTO HOA_DON (
                soHoaDon, soHopDong, thang, nam,
                lyDo, tongTien, soTienDaNop, soTienConNo,
                trangThai, kyThanhToan, maNV, loaiHoaDon
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, 0, ?,
                'ConNo', ?, ?, 'CreditNote'
            )
        ");
        $stmtCR->execute([
            $crID,
            $soHopDongGoc,
            (int)date('m'),
            (int)date('Y'),
            $lyDoCreditNote,
            $soTienAm,       // tongTien am (VD: -2,000,000)
            $soTienAm,       // soTienConNo am
            $kyThanhToan,
            $maNVThucThi
        ]);
    }

    $pdo->commit();

    // Ghi audit log sau commit — dung helper ghiAuditLog() thay vi INSERT thu cong
    $chiTietAudit = "Void hoa don {$soHoaDonVoid}, ly do: {$lyDoVoid}";
    if ($tienKhachDaNop > 0) {
        $chiTietAudit .= ", credit note: {$crID}, so tien hoan: " . number_format($tienKhachDaNop, 0) . " VND";
    } else {
        $chiTietAudit .= ", khong sinh credit note (khach chua nop tien)";
    }
    ghiAuditLog(
        $pdo,
        $maNVThucThi,
        'VOID_INVOICE',
        'HOA_DON',
        $soHoaDonVoid,
        $chiTietAudit
    );

    // Rotate CSRF token
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    // Flash message thanh cong va redirect
    $msgSuccess = "Void hoa don [{$soHoaDonVoid}] thanh cong.";
    if ($tienKhachDaNop > 0) {
        $msgSuccess .= " Credit note [{$crID}] da duoc tao voi so tien hoan: " . number_format($tienKhachDaNop, 0) . " VND.";
    }
    $_SESSION['success_msg'] = $msgSuccess;
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDongGoc));
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[tt_void_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi void hoa don. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDongGoc ?: ''));
    exit();
}
