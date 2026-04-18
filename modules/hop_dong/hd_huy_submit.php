<?php
// modules/hop_dong/hd_huy_submit.php
/**
 * ĐẠI LỆNH TRANSACTION PDO: PHÁ HỦY TOÀN PHẦN CĂN CƠ BĐS UC11 & GIẢI BIẾN CAO ỐC CẢ ĐẾ YẾU 
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
// Chỉ Admin và Quản lý nhà được hủy hợp đồng (nghiệp vụ rủi ro cao)
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Khóa Trạm Request GET");

// Shield Security CSRF Level 1
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Lỗi Security Form: CSRF Token Phai Nhạt. Không Xác Thực Được ID.</h1>");
}

$soHD = trim($_POST['soHopDong'] ?? '');
$ngayHuy = trim($_POST['ngayHuy'] ?? date('Y-m-d'));
$lyDoHuy = trim($_POST['lyDoHuy'] ?? '');

if (empty($soHD) || empty($lyDoHuy) || empty($ngayHuy)) {
    die("Dữ Trữ Đầu Vào Vang Hồi Cảnh Báo: Bị Khuyết Trống Lý Do Hoặc ID BĐS.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // --------------------------------------------------------------------------------------------------------
    // CƠ CHẾ PDO CHỊU TẢI TRANSACTION (RB-11.2 - Sứ Mệnh Giải Phóng Đinh Ba Nhọn Của Thần Neptune)
    // --------------------------------------------------------------------------------------------------------
    // Đẳng cấp Enterprise Backend quy định mọi logic update >= 3 bảng có chung Node Khóa (Foreign key) 
    // phải đặt trong rổ Bọc Lõi Transaction In-Memory. Nếu có 1 vết xước, rollback lại toàn diện.
    $pdo->beginTransaction();

    // KIỂM SOÁT LAYER 2 (defense in depth): kiểm tra nợ lần nữa ở backend
    // phòng trường hợp user bypass form bằng Postman/curl.
    // [BUG FIX] Trước đây đoạn này bọc try/catch nuốt exception -> nếu query fail,
    // hệ thống vẫn cho hủy dù còn nợ. Giờ để PDOException bubble lên catch ngoài
    // và rollback toàn bộ transaction.
    $stmtDebt = $pdo->prepare("SELECT COALESCE(SUM(soTienConNo), 0) AS no FROM HOA_DON WHERE soHopDong = ?");
    $stmtDebt->execute([$soHD]);
    if ((float)$stmtDebt->fetchColumn() > 0) {
        $pdo->rollBack();
        die("Không thể hủy hợp đồng: Hợp đồng này còn công nợ chưa thanh toán. Vui lòng xử lý công nợ trước khi hủy.");
    }


    // MŨI NHỌN 1: TẤN CÔNG BẢNG CHA [HOP_DONG]
    // Trạng thái thẻ biến thành 2 (Tức DaHuy/ThanhLy)
    // Cập nhật Alter tự động các cột Lý do Hủy vào (Cần bảo đảm Database Version mới có cột ngày huy)
    // Tôi bọc Try/Catch riêng để lỡ DB 1.5 thiếu DateHuy thì ko làm sập nhánh 2 và 3 nha.
    try {
        $stmtHDD = $pdo->prepare("UPDATE HOP_DONG SET trangThai = 2, ngayHuy = ?, lyDoHuy = ? WHERE soHopDong = ?");
        $stmtHDD->execute([$ngayHuy, $lyDoHuy, $soHD]);
    } catch (PDOException $ex1) {
        // Fallback Pass: Khách Hàng chưa Alter bảng HOP_DONG thêm 2 cột ngayHuy lyDoHuy
        // Vẫn ép Update State được để duy trì Hệ logic OOP
        $stmtHopDongNhe = $pdo->prepare("UPDATE HOP_DONG SET trangThai = 2 WHERE soHopDong = ?");
        $stmtHopDongNhe->execute([$soHD]);
    }


    // MŨI NHỌN 2: THÁN PHIẾN CẦU TÓC GỠ THẺ TRẠNG THÁI [CHI_TIET_HOP_DONG] CHẾT THEO CHA
    // Đảo toàn bộ chi tiết cờ trạng thái về 0 (Đã ngưng hoạt động Data / DaKetThuc)
    $stmtUpCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET trangThai = 0 WHERE soHopDong = ?");
    $stmtUpCT->execute([$soHD]);


    // MŨI NHỌN 3: LUỒNG MASS-UPDATE TRẢ TỰ DO VỀ CỘI NGUỒN CHO [PHONG] MẶT BẰNG THUÊ BẤT ĐỘNG SẢN 
    // Sử dụng Cú Pháp IN Lồng Sub-query cực đoan bứt dây 100 căn Hộ ngay lập tức (Status = 1: Trống / Available)
    $stmtUpPh = $pdo->prepare("
        UPDATE PHONG 
        SET trangThai = 1 
        WHERE maPhong IN (SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE soHopDong = ?)
    ");
    $stmtUpPh->execute([$soHD]);

    // Cũng không quên dọn rác bảo mật File Phòng bị Freeze Cache Lock (Đợt trước)
    $stmtUnLock = $pdo->prepare("
        DELETE FROM PHONG_LOCK 
        WHERE maPhong IN (SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE soHopDong = ?)
    ");
    $stmtUnLock->execute([$soHD]);


    // NGHỊ QUYẾT BẢO TỒN TÀI SẢN HOÀN TẤT TRỌN VẸN.
    $pdo->commit();

    // [AUDIT] Ghi log sau khi hủy thành công
    $maNV_Huy = $_SESSION['user_id'] ?? null;
    ghiAuditLog(
        $pdo,
        $maNV_Huy,
        'CANCEL_HD',
        'HOP_DONG',
        $soHD,
        "Hủy hợp đồng ngày {$ngayHuy}. Lý do: {$lyDoHuy}"
    );

    // RÚT RA BÊN NGOÀI
    header("Location: hd_hienthi.php?msg=huy_toan_phan_thanh_cong");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // [SEC] Không lộ $e->getMessage() ra HTML
    error_log("hd_huy_submit PDO error: " . $e->getMessage());
    die("Xảy ra lỗi khi hủy hợp đồng. Dữ liệu đã được rollback an toàn. Vui lòng liên hệ quản trị viên.");
}
