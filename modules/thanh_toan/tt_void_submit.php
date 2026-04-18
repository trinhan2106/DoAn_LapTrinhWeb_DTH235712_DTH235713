<?php
// modules/thanh_toan/tt_void_submit.php
/**
 * MA TRẬN BACKEND BÙ TRỪ KẾ TOÁN CAO CẤP TẦNG 4: VOID BILL VÀ SINH CREDIT NOTE
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Route Lỗi - Blocked.");

// ----------------------------------------------------------------------------------
// LỌC QUYỀN MỘT LẦN NỮA TẠI BACKEND LÕI (CHỐNG BYPASS CURL/POSTMAN)
// ----------------------------------------------------------------------------------
$role = (int)($_SESSION['role_id'] ?? 4);
if (!in_array($role, [1, 2])) {
    die("Server PHP Reject: Thẩm quyền Role của bạn dưới mức Khống Chế Database Hủy. Hệ thống hủy chuỗi Tấn công Giao Dịch.");
}

$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Xâm Phạm Cổng CSRF Mềm Layer 3.</h1>");
}

$soHoaDonVoid = trim($_POST['soHoaDon'] ?? '');
$lyDoVoid = trim($_POST['lyDoVoid'] ?? '');
$maNVThucThi = $_SESSION['user_id'] ?? null;

if (empty($soHoaDonVoid) || empty($lyDoVoid) || !$maNVThucThi) {
    die("Chưa Khai Báo Hành Lý Tại Node Nhận (Lý do trống).");
}


$pdo = Database::getInstance()->getConnection();

try {
    // ----------------------------------------------------------------------------------
    // ĐIỆN PHÂN CHÂN KHÔNG TRANSACTION (4 CÚ NỔ ĐỒNG LƯỢNG ACiD)
    // ----------------------------------------------------------------------------------
    $pdo->beginTransaction();

    // CHIÊU THỨC LOCK ĐỤC TRÁNH XUNG ĐỘT (Race Condition Lần Nữa)
    $stmtLock = $pdo->prepare("SELECT * FROM HOA_DON WHERE soHoaDon = ? FOR UPDATE");
    $stmtLock->execute([$soHoaDonVoid]);
    $billXacUop = $stmtLock->fetch(PDO::FETCH_ASSOC);

    if (!$billXacUop) {
        $pdo->rollBack();
        die("Hệ Database Bị Ổ Nhện Càn Quét: Hóa đơn mốc không còn tồn tại.");
    }
    if ($billXacUop['trangThai'] === 'Void') {
        $pdo->rollBack();
        die("Chống Hack Dội Bom Nhóm 2 Admin: File Kế Toán Đã Bị Ông Trưởng Phòng khác VOID trước bạn 1 phút! Hủy bỏ chệch hướng.");
    }

    $soHopDongGoc = $billXacUop['soHopDong'];
    $tienKhachDaNap = (float)$billXacUop['soTienDaNop']; // Điểm cốt lõi Credit


    // (a) XÉ BỎ HỒ SƠ CHÍNH - Biến Vạn Vật Thành Void (Hư Vô)
    $stmtTrangThai = $pdo->prepare("UPDATE HOA_DON SET trangThai = 'Void' WHERE soHoaDon = ?");
    $stmtTrangThai->execute([$soHoaDonVoid]);


    // (b) NHẬT KÝ CHI TIẾT HOA DON VOID
    $stmtHistoryVoid = $pdo->prepare("
        INSERT INTO HOA_DON_VOID (soPhieu, maNV_Void, lyDoVoid, ngayVoid) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmtHistoryVoid->execute([$soHoaDonVoid, $maNVThucThi, $lyDoVoid]);


    // (c) THUẬT TOÁN ĐẢO CHIỀU TÀI KHOẢN (CREDIT NOTE AUTOMATION GENERATOR)
    // Rất Hiếm Hệ Thống Việt Nam Sinh Việc Này. Nếu Hóa Đơn Trị Giá 10Tr, Bạn thu cọc 2Tr rồi. Giờ Hủy cái Bill 10tr sai đó.
    // Thì công ty đớp 2Tr của Khách luông à? Không! Tạo Lệnh Trả/Bù Trừ
    if ($tienKhachDaNap > 0) {
        $crID = 'CR-NOTE-' . date('ymd-His') . rand(100,999);
        $lyDoBuTru = "Khách Hoàn Tín (Credit Note) Từ Việc Hạn Void Hóa Đơn Vô Hiệu Lệch Code: $soHoaDonVoid";
        $crTienKienThiet_AmBaoToan = -$tienKhachDaNap;

        /*
           Mạch Máu Dòng Tiền (Waterfall Payment) Hệ Thống Ở File tt_tao_submit Của Tôi 
           đã được lập trình ĐỂ HẤP THỤ Mọi Số TIỀN ÂM Tự Động Cộng dồn vào Quỹ Nộp của Khách!
           Cắt Đặt: soTienConNo = $crTienKienThiet_AmBaoToan là Âm. Khi duyệt Thu tiền vòng tới KH xài được luông!
        */
        $stmtCR = $pdo->prepare("
            INSERT INTO HOA_DON (
                soHoaDon, soHopDong, lyDo, tongTien, soTienDaNop, soTienConNo, trangThai, kyThanhToan, maNV
            ) VALUES (
                ?, ?, ?, ?, 0, ?, 'ConNo', ?, ?
            )
        ");
        $stmtCR->execute([
            $crID, 
            $soHopDongGoc, 
            $lyDoBuTru, 
            $crTienKienThiet_AmBaoToan, // tongTien hinh thuc de bao cao (-2Tr)
            $crTienKienThiet_AmBaoToan, // soTienConNo Mang Nước (-2Tr) 
            date('m/Y'), 
            $maNVThucThi
        ]);
    }


    // (d) LƯỚI QUÉT AUDIT TẦNG CUỐI CỦA THỊ PHẦN THANH TRA DB (AUDIT_LOG)
    $stmtAudit = $pdo->prepare("
        INSERT INTO AUDIT_LOG (hanhDong, bangBiTacDong, recordId, maNguoiDung, chiTiet, thoiGian) 
        VALUES ('VOID_INVOICE', 'HOA_DON', ?, ?, ?, NOW())
    ");
    $thongTinDay = "Thủ Khổng Hủy File. Cấp Credit Tự sinh: " . ($tienKhachDaNap > 0 ? 'CóSinhCR' : 'KhôngSinh_DoBillChưaĐóngTiền');
    $stmtAudit->execute([$soHoaDonVoid, $maNVThucThi, $thongTinDay . " | Note_KhoTàng: " . $lyDoVoid]);

    // CUỘN TRÒN QUÁ TRÌNH PHÓNG TÊN LỬA VÀO VẠCH KÍ RỒI COMMIT Ổ ĐĨA!
    $pdo->commit();

    // RÚT QUÂN HÒA BÌNH 
    echo "<div style='background:#f4f7f9; padding:50px; font-family:sans-serif; text-align:center;'>";
    echo "<h1 style='color:#2ecc71;'>✅ NGHIỆP VỤ VOID (TRIỆT TIÊU KHỐNG HÓA ĐƠN) THÀNH CÔNG VANG DỘI!</h1>";
    echo "<h3>Biên Lai [ <span style='color:red;'>$soHoaDonVoid</span> ] Đã Được Khai Tử Khỏi Hành Trình Dòng Tiền Thác Nước Kế Toán.</h3>";
    
    if ($tienKhachDaNap > 0) {
        echo "<div style='background:#e8f5e9; border:2px dashed #4caf50; padding:20px; color:#1b5e20; margin: 20px 0; font-weight:bold;'>";
        echo "<i class='fa-solid fa-bell'></i> Kế Toán Máy Vừa Tự Động Phóng Tín Phiếu Hoàn Tiền (Credit Note) ".number_format($tienKhachDaNap)." đ dưới mã: <span style='color:red; font-size:1.2em;'>$crID</span><br/> Số tiền này sẽ chảy vô Ví Cấn Nợ Lần Kế Tiếp của Hợp Đồng Khách Hàng. DB Nằm Ngang Rất An Toàn!";
        echo "</div>";
    }

    echo "<p>(Lưu ý: Mảng Frontend View List Hóa Đơn đang được build, bạn có thể quay lại trang Quản Trị Trung Tâm)</p>";
    echo "<a href='../../index.php' style='padding: 10px 20px; background: #1e3a5f; color:#fff; text-decoration:none; border-radius:5px;'>Quay Lại Tảng Băng Quản Trị</a>";
    echo "</div>";
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("BOM HỦY DIỆT LỖI GÃY TRẠM XỬ LÝ TRANSACTION VOID UC_MAX: " . $e->getMessage());
    die("Xảy Ra Mảnh Rút SQL Lỗi Nặng Ở Tầng Lưu Trữ Hợp Nhất (Rollback Auto-Saved): " . $e->getMessage());
}
