<?php
// modules/hop_dong/hd_ky_submit.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Từ chối truy cập qua phương thức GET!");
}

// Bắt tường bảo mật
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Lỗi Security chống Spam Form CSRF (Lỗi 403)</h1>");
}

$soHopDong = trim($_POST['soHopDong'] ?? '');
if(empty($soHopDong)) {
    die("Khuyết lỗi Tín hiệu DSN Hợp Đồng Truyền Tải. Backend không phân tích được Key.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // -------------------------------------------------------------------------
    // BẮT ĐẦU TRANSACTIONS
    // -------------------------------------------------------------------------
    // Rủi ro UC04: Rất cao! Chỉ cần Update cái này lỗi, cái kia lủng, BĐS bị thất thoát status logic là sụp nguồn Data.
    $pdo->beginTransaction();

    // LỆNH 1: Đẩy STATUS HỢP ĐỒNG mẹ lên 1 (Hieu Luc: DangHieuLuc)
    $stmtHD = $pdo->prepare("UPDATE HOP_DONG SET trangThai = 1 WHERE soHopDong = :so");
    $stmtHD->execute([':so' => $soHopDong]);

    // Truy tìm Khối Tài Sản Liên đới (Các phòng thuộc về mâm hợp đồng này)
    $stmtGetPh = $pdo->prepare("SELECT maPhong FROM CHI_TIET_HOP_DONG WHERE soHopDong = :so");
    $stmtGetPh->execute([':so' => $soHopDong]);
    $dsPhong = $stmtGetPh->fetchAll(PDO::FETCH_COLUMN); // Mảng array phẳng cột mã phòng

    if (count($dsPhong) > 0) {
        
        // LỆNH 2: Đẩy STATUS mảng CTHD (Trạng Thái Của Từng Phòng trên Giấy) lên 1 (Đang Thuê / DangThue)
        $stmtCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET trangThai = 1 WHERE soHopDong = :so");
        $stmtCT->execute([':so' => $soHopDong]);

        // LUỒNG DAO KHẨN: Rải vòng lặp quét trúng các BĐS thực thể và càn quét Bảng Lock
        $stmtPhongStatus = $pdo->prepare("UPDATE PHONG SET trangThai = 2 WHERE maPhong = :maphong");
        $stmtDeleteLock  = $pdo->prepare("DELETE FROM PHONG_LOCK WHERE maPhong = :maphong");

        foreach ($dsPhong as $maPhongItem) {
            
            // LỆNH 3: Bắn Cờ Hóa Vàng cho Phòng đó thuộc về Tình Trạng '2' (Đã Có Người Thầu / Da Thue)
            $stmtPhongStatus->execute([':maphong' => $maPhongItem]);
            
            // LỆNH 4: Xóa triệt để Cờ Dọn Dẹp File `PHONG_LOCK`
            // Không cho rác Lock bám lại dù đã chốt xong phòng thành công. Trải cỏ đẹp CSDL.
            $stmtDeleteLock->execute([':maphong' => $maPhongItem]);
        }
    }

    // NẾU TẤT CẢ 4 LUỒNG UPDATE ĐỀU TRÔN TRU => GÕ BÚA COMMIT VÀO Ổ CỨNG MYSQL
    $pdo->commit();

    // Điều chuyển Request User về Màn hình PDF kèm Flag Toast
    header("Location: hd_ky.php?id=" . urlencode($soHopDong) . "&msg=signed");
    exit();

} catch (PDOException $e) {
    // Ăn Exceptions => Giải phóng Bác Sĩ Dọn Dẹp Rollback (Bảo vệ dữ liệu toàn phần ACiD)
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("CRASH PDOException LOGIC KHỐI DUYỆT HỢP ĐỒNG LÕI 5.4: " . $e->getMessage());
    die("Xảy ra lỗi Transaction trầm trọng ở cấp độ PDO (Rollback Activated): " . $e->getMessage());
}
