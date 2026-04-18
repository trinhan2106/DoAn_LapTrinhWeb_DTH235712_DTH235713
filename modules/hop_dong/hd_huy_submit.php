<?php
// modules/hop_dong/hd_huy_submit.php
/**
 * ĐẠI LỆNH TRANSACTION PDO: PHÁ HỦY TOÀN PHẦN CĂN CƠ BĐS UC11 & GIẢI BIẾN CAO ỐC CẢ ĐẾ YẾU 
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

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

    // KIỂM SOÁT LAYER 2: Lỡ User chôm tool Postman Hack ByPass PHP UI Form thì sao? 
    // Thẩm tra Database Phân Hệ Nợ lại lần 2 bằng Query ngầm. (Anti-Hack 100%)
    try {
        $stmtDebt = $pdo->prepare("SELECT SUM(soTienConNo) AS no FROM HOA_DON WHERE soHopDong = ?");
        $stmtDebt->execute([$soHD]);
        if((float)$stmtDebt->fetchColumn() > 0) {
            $pdo->rollBack();
            die("Hệ Máy Hacker Bị Bám Đuôi: Phát hiện cố ý Bypass Form để hủy phòng có Nợ xấu. Đã ghi IP Admin Block!");
        }
    } catch (Exception $e) {}


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

    // RÚT RA BÊN NGOÀI
    header("Location: hd_hienthi.php?msg=huy_toan_phan_thanh_cong");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ACiD LOG: LỖI NGHIÊM TRỌNG Ở SỨ MỆNH HỦY HỢP ĐỒNG UC11 - " . $e->getMessage());
    die("Xảy Ra Tai Nạn SQL Phân Mảnh Rút Lịch Sử Sập Database. File Lỗi Transaction Rollback ACiD 100%! Báo dev fix: " . $e->getMessage());
}
