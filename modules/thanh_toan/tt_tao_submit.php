<?php
// modules/thanh_toan/tt_tao_submit.php
/**
 * Xu ly thu tien UC06: Waterfall Payment + Phieu Thu + Email bien lai.
 * - Validate so tien > 0.
 * - SELECT ... FOR UPDATE chong Race Condition.
 * - INSERT PHIEU_THU truoc khi chia tien waterfall.
 * - Moi lan UPDATE HOA_DON, INSERT PHIEU_THU_CHI_TIET tuong ung.
 * - Query thong tin KH de gui mail SAU COMMIT (giam lock time).
 * - Escape moi bien trong email template (Convention C.4).
 * - Thay die() bang flash + redirect (Convention C.2).
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN]);

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

// Doc va validate input
$soHopDong      = trim($_POST['soHopDong'] ?? '');
$soTienDaNop_POST = (float)($_POST['soTienDaNop_POST'] ?? 0);
$phuongThucM    = trim($_POST['phuongThucM'] ?? 'ChuyenKhoan');
$maGiaoDich     = trim($_POST['maGiaoDich'] ?? '');
$maNV_HienHanh  = $_SESSION['user_id'] ?? null;

// Validate: so tien phai > 0 (khong chap nhan 0 dong)
if (empty($soHopDong) || $soTienDaNop_POST <= 0 || !$maNV_HienHanh) {
    $_SESSION['error_msg'] = "Du lieu khong hop le. So tien thu phai lon hon 0, ma hop dong khong duoc trong.";
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDong));
    exit();
}

// Validate phuong thuc thanh toan
$phuongThucHopLe = ['TienMat', 'ChuyenKhoan', 'Vi'];
if (!in_array($phuongThucM, $phuongThucHopLe, true)) {
    $_SESSION['error_msg'] = "Phuong thuc thanh toan khong hop le.";
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDong));
    exit();
}

// Validate do dai maGiaoDich
if (strlen($maGiaoDich) > 100) {
    $_SESSION['error_msg'] = "Ma giao dich vuot qua 100 ky tu.";
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDong));
    exit();
}

$pdo = Database::getInstance()->getConnection();
$soPhieuThu = ''; // Luu de dung cho audit log va email

try {
    $pdo->beginTransaction();

    // SELECT ... FOR UPDATE: Khoa cac hoa don con no cua hop dong nay
    // Chi lay loaiHoaDon = 'Chinh' de tranh can tru nham CreditNote
    $stmtLock = $pdo->prepare("
        SELECT soHoaDon, soTienConNo, soTienDaNop, tongTien 
        FROM HOA_DON 
        WHERE soHopDong = ? AND trangThai = 'ConNo' AND loaiHoaDon = 'Chinh'
        ORDER BY kyThanhToan ASC, created_at ASC 
        FOR UPDATE
    ");
    $stmtLock->execute([$soHopDong]);
    $listHD_PhaiChiu = $stmtLock->fetchAll(PDO::FETCH_ASSOC);

    if (!$listHD_PhaiChiu) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Khong con hoa don nao can thu cho hop dong nay. Co the da duoc xu ly boi nhan vien khac.";
        header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDong));
        exit();
    }

    // Sinh soPhieuThu va INSERT PHIEU_THU truoc khi chia tien waterfall
    $soPhieuThu = sinhMaNgauNhien('PT-' . date('Ym') . '-', 6);
    $stmtPT = $pdo->prepare("
        INSERT INTO PHIEU_THU (soPhieuThu, ngayThu, tongTienThu, phuongThuc, maGiaoDich, maNV, ghiChu)
        VALUES (?, NOW(), ?, ?, ?, ?, NULL)
    ");
    $stmtPT->execute([
        $soPhieuThu,
        $soTienDaNop_POST,
        $phuongThucM,
        !empty($maGiaoDich) ? $maGiaoDich : null,
        $maNV_HienHanh
    ]);

    // Prepare cac statement dung trong vong lap
    $stmtUpdateHD = $pdo->prepare("
        UPDATE HOA_DON 
        SET soTienDaNop = soTienDaNop + :phanBo, 
            soTienConNo = :noMoi, 
            trangThai = :trangThaiMoi, 
            maNV = :maNV 
        WHERE soHoaDon = :soHoaDon
    ");

    $stmtChiTiet = $pdo->prepare("
        INSERT INTO PHIEU_THU_CHI_TIET (soPhieuThu, soHoaDon, soTienPhanBo)
        VALUES (?, ?, ?)
    ");

    // Waterfall payment: phan bo tien tu hoa don cu den moi
    $tienConLai = $soTienDaNop_POST;

    foreach ($listHD_PhaiChiu as $hdRow) {
        if ($tienConLai <= 0) {
            break;
        }

        $noBillHienTai = (float)$hdRow['soTienConNo'];

        if ($tienConLai >= $noBillHienTai) {
            // Du tien de thanh toan het bill nay
            $phanBoChoBill = $noBillHienTai;
            $noConLai = 0;
            $trangThaiMoi = 'DaThu';
            $tienConLai -= $noBillHienTai;
        } else {
            // Khong du tien, thanh toan mot phan
            $phanBoChoBill = $tienConLai;
            $noConLai = $noBillHienTai - $tienConLai;
            $trangThaiMoi = 'ConNo';
            $tienConLai = 0;
        }

        // UPDATE HOA_DON
        $stmtUpdateHD->execute([
            ':phanBo'       => $phanBoChoBill,
            ':noMoi'        => $noConLai,
            ':trangThaiMoi' => $trangThaiMoi,
            ':maNV'         => $maNV_HienHanh,
            ':soHoaDon'     => $hdRow['soHoaDon']
        ]);

        // INSERT PHIEU_THU_CHI_TIET cho moi hoa don duoc phan bo
        $stmtChiTiet->execute([
            $soPhieuThu,
            $hdRow['soHoaDon'],
            $phanBoChoBill
        ]);
    }

    // Xu ly tien du nop thua: ghi nhan vao hoa don cuoi cung lam credit
    if ($tienConLai > 0) {
        $lastBill = end($listHD_PhaiChiu);
        $stmtCredit = $pdo->prepare("
            UPDATE HOA_DON 
            SET soTienDaNop = soTienDaNop + :du, soTienConNo = soTienConNo - :du 
            WHERE soHoaDon = :soHoaDon
        ");
        $stmtCredit->execute([
            ':du' => $tienConLai,
            ':soHoaDon' => $lastBill['soHoaDon']
        ]);

        // Ghi nhan phan tien du vao chi tiet phieu thu
        $stmtChiTiet->execute([
            $soPhieuThu,
            $lastBill['soHoaDon'],
            $tienConLai
        ]);
    }

    // COMMIT transaction truoc khi lay thong tin KH gui mail (giam lock time)
    $pdo->commit();

    // --- SAU COMMIT: Lay thong tin KH de gui mail va ghi audit log ---

    // Ghi audit log
    $soBillXuLy = count($listHD_PhaiChiu);
    ghiAuditLog(
        $pdo,
        $maNV_HienHanh,
        'CREATE_PAYMENT',
        'HOA_DON',
        $soHopDong,
        "Thanh toan HD {$soHopDong}: phieu thu={$soPhieuThu}, so tien=" . number_format($soTienDaNop_POST, 0) . " VND, phuong thuc={$phuongThucM}, ma giao dich=" . ($maGiaoDich ?: 'N/A') . ", so bill xu ly={$soBillXuLy}"
    );

    // Rotate CSRF token sau submit thanh cong
    if (function_exists('rotateCSRFToken')) {
        rotateCSRFToken();
    } else {
        unset($_SESSION['csrf_token']);
    }

    // Query thong tin KH SAU COMMIT (toi uu hieu nang, khong giu lock khong can thiet)
    $stmtKHInfo = $pdo->prepare("
        SELECT k.tenKH, k.email 
        FROM HOP_DONG h JOIN KHACH_HANG k ON h.maKH = k.maKH 
        WHERE h.soHopDong = ?
    ");
    $stmtKHInfo->execute([$soHopDong]);
    $mMailPack = $stmtKHInfo->fetch(PDO::FETCH_ASSOC);

    // Gui email bien lai
    if ($mMailPack && !empty($mMailPack['email'])) {
        require_once __DIR__ . '/../../includes/common/mailer.php';
        
        // Escape tat ca bien truoc khi dua vao template HTML (Convention C.4)
        $kh_name_e     = htmlspecialchars($mMailPack['tenKH'] ?? '', ENT_QUOTES, 'UTF-8');
        $soHopDong_e   = htmlspecialchars($soHopDong, ENT_QUOTES, 'UTF-8');
        $soPhieuThu_e  = htmlspecialchars($soPhieuThu, ENT_QUOTES, 'UTF-8');
        $phuongThuc_e  = htmlspecialchars($phuongThucM, ENT_QUOTES, 'UTF-8');
        $maGiaoDich_e  = htmlspecialchars($maGiaoDich ?: 'Khong co', ENT_QUOTES, 'UTF-8');
        $tienFormat    = number_format($soTienDaNop_POST, 0);
        $thoiGian      = date('d/m/Y H:i:s');

        $htmlMContent = "
            <div style='background-color: #f4f7f9; padding: 20px; font-family: Arial, sans-serif;'>
                <div style='background: #fff; border-top: 5px solid #28a745; padding: 30px; border-radius: 8px; max-width: 600px; margin: auto;'>
                    <h2 style='color: #28a745; margin-top: 0;'>XAC NHAN THANH TOAN</h2>
                    <p>Kinh chao <strong>{$kh_name_e}</strong>,</p>
                    <p>Ban Quan Ly Toa Nha xac nhan da nhan duoc khoan thanh toan cho Hop Dong <strong style='color:#1e3a5f;'>[{$soHopDong_e}]</strong>.</p>
                    
                    <div style='background: #f8f9fa; border-left: 4px solid #1e3a5f; padding: 15px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>So phieu thu:</strong> {$soPhieuThu_e}</p>
                        <p style='margin: 5px 0;'><strong>So tien:</strong> <span style='color: #d32f2f; font-size: 1.2em; font-weight: bold;'>{$tienFormat} VND</span></p>
                        <p style='margin: 5px 0;'><strong>Phuong thuc:</strong> {$phuongThuc_e}</p>
                        <p style='margin: 5px 0;'><strong>Ma giao dich:</strong> {$maGiaoDich_e}</p>
                        <p style='margin: 5px 0;'><strong>Thoi gian ghi nhan:</strong> {$thoiGian}</p>
                    </div>

                    <p>Moi khoan du thua (Credit Balance) neu co se duoc luu giu va can tru cho ky cuoc phat sinh tiep theo.</p>
                    <hr style='border: none; border-top: 1px dashed #ccc;'/>
                    <p style='font-size: 0.85em; color: #7f8c8d; text-align: center;'>Email tu dong, vui long khong reply.</p>
                </div>
            </div>
        ";

        try {
            sendEmail(
                $mMailPack['email'],
                "[Blue Sky Tower] Bien lai thanh toan - {$soHopDong}",
                $htmlMContent
            );
        } catch (Exception $mailErr) {
            // Giao dich da commit thanh cong, chi ghi log loi mail
            error_log("[tt_tao_submit] Gui email loi: " . $mailErr->getMessage());
        }
    }

    // Redirect thanh cong
    $_SESSION['success_msg'] = "Giao dich thanh toan da ghi nhan thanh cong. So phieu thu: {$soPhieuThu}.";
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDong) . "&msg=success");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[tt_tao_submit] PDO error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Xay ra loi khi xu ly thanh toan. Du lieu da duoc rollback an toan. Vui long lien he quan tri vien.";
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDong));
    exit();
}
