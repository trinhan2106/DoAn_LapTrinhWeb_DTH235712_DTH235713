<?php
// modules/thanh_toan/dien_nuoc_ghi_submit.php
/**
 * DAO XỬ LÝ (TASK 6.5) GHI NHẬN BIÊN BẢN CẮT CHỈ SỐ KỸ THUẬT VÀ NẠP HÓA ĐƠN
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Lỗi Mạng Backend.");

// Phôi Pha Bảo Mật (L1) CSRF 
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Cổng tường lửa chặn Request: CSRF Validation Fail.</h1>");
}

$maPhong    = trim($_POST['maPhong'] ?? '');
$soHopDong  = trim($_POST['soHopDong'] ?? '');
$thangGhi   = (int)($_POST['thangGhi'] ?? 0);
$namGhi     = (int)($_POST['namGhi'] ?? 0);

$csD_Dau    = (float)($_POST['chiSoDien_Dau'] ?? 0);
$csD_Cuoi   = (float)($_POST['chiSoDien_Cuoi'] ?? 0);
$dgDien     = (float)($_POST['donGiaDien'] ?? 0);

$csN_Dau    = (float)($_POST['chiSoNuoc_Dau'] ?? 0);
$csN_Cuoi   = (float)($_POST['chiSoNuoc_Cuoi'] ?? 0);
$dgNuoc     = (float)($_POST['donGiaNuoc'] ?? 0);

// Phôi Pha Bảo Mật (L2) Toán Trọng Lớp Lõi (Chống Hacker Dùng F12 Xóa Lệnh JS) 
// BẮT BUỘC NHƯ Requirement: "Kiểm tra lại cuoi >= dau bằng PHP. Nếu sai, đá văng về form."
if ($csD_Cuoi < $csD_Dau || $csN_Cuoi < $csN_Dau) {
    header("Location: dien_nuoc_ghi.php?err=invalid_delta");
    exit();
}

$deltaDien = $csD_Cuoi - $csD_Dau;
$deltaNuoc = $csN_Cuoi - $csN_Dau;
$tienDien  = $deltaDien * $dgDien;
$tienNuoc  = $deltaNuoc * $dgNuoc;
$tongTien  = $tienDien + $tienNuoc;

$pdo = Database::getInstance()->getConnection();

try {
    // ----------------------------------------------------------------------------------
    // ĐÓNG KHÓA VÒNG TRÒN ACID: UPDATE 2 BẢNG PARALLEL (CHI_SO VÀ HOA_DON)
    // ----------------------------------------------------------------------------------
    $pdo->beginTransaction();

    // 1. TẠO SINH LỊCH GHI NHẬN KỸ THUẬT TRONG BẢNG CHI_SO_DIEN_NUOC
    $stmtGhiSo = $pdo->prepare("
        INSERT INTO CHI_SO_DIEN_NUOC (
            maPhong, thangGhi, namGhi,
            chiSoDien_Dau, chiSoDien_Cuoi, donGiaDien,
            chiSoNuoc_Dau, chiSoNuoc_Cuoi, donGiaNuoc
        ) VALUES (
            :phong, :thang, :nam,
            :dDau, :dCuoi, :dGia,
            :nDau, :nCuoi, :nGia
        )
    ");
    $stmtGhiSo->execute([
        ':phong' => $maPhong,
        ':thang' => $thangGhi,
        ':nam'   => $namGhi,
        ':dDau'  => $csD_Dau, ':dCuoi' => $csD_Cuoi, ':dGia' => $dgDien,
        ':nDau'  => $csN_Dau, ':nCuoi' => $csN_Cuoi, ':nGia' => $dgNuoc
    ]);

    // 2. SAO KÊ CHẬP NỢ (BILLING) LÊN HỆ THỐNG KẾ TOÁN TÀI CHÍNH (HOA_DON)
    // Tự sinh ID Invoice (VD: INV-DN-2026xxxxxx)
    $soHD_INV = 'INV-DN-' . date('YmdHis') . rand(10,99);
    
    // Convert 1 -> 01/2026
    $kyThanhToanStr = sprintf("%02d/%04d", $thangGhi, $namGhi);

    // lyDo = 'TienDienNuoc' đã được fix cứng theo Luật Requirement
    // trangThai = 'ConNo'
    // soTienDaNop = 0, soTienConNo = Tống Tiền Vừa Rút Máu
    $stmtBill = $pdo->prepare("
        INSERT INTO HOA_DON (
            soHoaDon, soHopDong, lyDo,
            tongTien, soTienDaNop, soTienConNo,
            trangThai, kyThanhToan
        ) VALUES (
            :soHD, :soHDP, 'TienDienNuoc',
            :tongt, 0, :tongt_no,
            'ConNo', :kyTT
        )
    ");
    $stmtBill->execute([
        ':soHD'    => $soHD_INV,
        ':soHDP'   => $soHopDong,
        ':tongt'   => $tongTien,
        ':tongt_no'=> $tongTien,
        ':kyTT'    => $kyThanhToanStr
    ]);

    // NIÊM PHONG LUẬT ACID
    $pdo->commit();

    // Redirect ra Danh Sách Hóa Đơn Trả Thù lao 
    // Trong file này chưa thiết kế hd_hienthi của hóa đơn, mượn tạm Redirect kèm success flag.
    echo "<div style='background:#f4f7f9; padding:50px; font-family:sans-serif; text-align:center;'>";
    echo "<h1 style='color:#2ecc71;'>✅ NGHIỆP VỤ LẬP PHIẾU ĐIỆN NƯỚC THÀNH CÔNG!</h1>";
    echo "<h3>Sổ Kế Toán đã ghi nợ Số Hóa Đơn: <span style='color:red;'>$soHD_INV</span></h3>";
    echo "<h3>Tổng tiền phải thu: " . number_format($tongTien, 0) . " VNĐ</h3>";
    echo "<p>(Lưu ý: Mảng Frontend View List Hóa Đơn đang được build, bạn có thể quay lại thủ công)</p>";
    echo "<a href='dien_nuoc_ghi.php' style='padding: 10px 20px; background: #1e3a5f; color:#fff; text-decoration:none; border-radius:5px;'>Quay Lại Lập Thêm Phiếu Trạm Khác</a>";
    echo "</div>";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // ĐÂY LÀ KHÚC TRY/CATCH CỰC KỲ QUAN TRỌNG ĐỂ BẮT ĐỤNG ĐỘ DATA THEO LỆNH TASK Ràng buộc UNIQUE(maPhong, thangGhi, namGhi)
    // Code 23000 ở Tầng MySql Engine biểu hiện cho [Integrity constraint violation: 1062 Duplicate entry]
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        die("
            <div style='background:#fce4ec; border:3px solid #c62828; padding:30px; font-family:sans-serif; text-align:center;'>
                <h1 style='color:#c62828;'>CẢNH BÁO BẢO MẬT: BỊ TRÙNG LẶP SỔ PHỤ</h1>
                <h3>Hệ thống phát hiện [Mã Phòng: $maPhong] ĐÃ ĐƯỢC NHÂN VIÊN KHÁC GHI CÔNG TƠ CHỈ SỐ cho Tháng $thangGhi/$namGhi rồi!</h3>
                <p>Constraint Bảo Vệ Toàn Vẹn ACID của DB đã chủ động BLOCK giao dịch. Transaction Hủy!</p>
                <a href='dien_nuoc_ghi.php'>Bấm quay lại form làm lại</a>
            </div>
        ");
    }

    error_log("LỖI SQL KẾ TÓAN: " . $e->getMessage());
    die("Xảy ra Sự Cô Rớt Đĩa Ổ Cứng Backend CSDL (Rollback Kích Hoạt): Lỗi: " . $e->getMessage());
}
