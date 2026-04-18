<?php
// modules/hop_dong/hd_ket_thuc_le_submit.php
/**
 * DAO XỬ LÝ GIAO DỊCH DATABASE BÀI TOÁN "TÁCH LẼ PHÒNG" UC10
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
// Chỉ Admin và Quản lý nhà được kết thúc phòng lẻ
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Action Blocked by Engine.");

// CSRF Layer
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Lỗi Security Form CSRF Match Failed.</h1>");
}

$soHD = trim($_POST['soHopDong'] ?? '');
$mangPhayChon = $_POST['chonTraPhan'] ?? []; // Array ["maCTHD|maPhong", ...]

if (empty($soHD) || empty($mangPhayChon)) {
    die("Bạn chưa tick chọn bất kỳ phòng BĐS nào để giải phẫu rút phòng.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // -------------------------------------------------------------------------
    // BẮT ĐẦU TRANSACTIONS THEO CHỈ THỊ (RB-10.3)
    // -------------------------------------------------------------------------
    $pdo->beginTransaction();

    // 1. RE-CHECK DB ĐỂ CHỐNG HACKER BYPASS JAVASCRIPT (RB-10.1 Lõi)
    $stmtCheckCount = $pdo->prepare("SELECT COUNT(*) FROM CHI_TIET_HOP_DONG WHERE soHopDong = ? AND trangThai = 1");
    $stmtCheckCount->execute([$soHD]);
    $soPhongActiveThucTe = (int)$stmtCheckCount->fetchColumn();

    if (count($mangPhayChon) >= $soPhongActiveThucTe) {
        $pdo->rollBack();
        die("Backend Reject Block: Hành vị Cố Tình Hủy Toàn Bộ Phòng lọt qua lưới JS. Bạn bắt buộc phải xài file Hủy Toàn Phần UC11.");
    }

    // MAP QUERY
    // CHI_TIET_HOP_DONG = 0 (Da Ket Thuc), PHONG = 1 (Trong)
    $stmtCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET trangThai = 0 WHERE maCTHD = ?");
    $stmtPH = $pdo->prepare("UPDATE PHONG SET trangThai = 1 WHERE maPhong = ?");
    $stmtLog = $pdo->prepare("DELETE FROM PHONG_LOCK WHERE maPhong = ?"); // Dọn dẹp bổ sung cho sạch

    foreach ($mangPhayChon as $item) {
        // Tách chuỗi maCTHD|maPhong
        $parts = explode('|', $item);
        if(count($parts) === 2) {
            $mCT = $parts[0];
            $mPH = $parts[1];

            // Rút dây bảo vệ hệ thống CTHD
            $stmtCT->execute([$mCT]);
            
            // Re-active Tòa nhà cho Phòng Sáng Đèn Lên (Sẵn sàng cho thuê khách khác)
            $stmtPH->execute([$mPH]);
            $stmtLog->execute([$mPH]); // Anti rác
        }
    }


    // THẨM ĐỊNH LẠI CỘT TÍNH CHẤT THỜI GIAN NGÀY (RB-10.3 CẬP NHẬT LẠI NGÀY MAX HẾT HẠN)
    // Cực kỳ tinh tế: Có thể cái căn vừa bị RÚT ra chứa lịch Hết hạn Xa Nhất. Cần tính lại!
    $sqlThuatToanMaxDate = "
        SELECT MAX(COALESCE(ngayHetHan, (SELECT ngayKetThuc FROM HOP_DONG WHERE soHopDong=:hd1)))
        FROM CHI_TIET_HOP_DONG 
        WHERE soHopDong = :hd2 AND trangThai = 1
    ";
    $stmtMax = $pdo->prepare($sqlThuatToanMaxDate);
    $stmtMax->execute([':hd1' => $soHD, ':hd2' => $soHD]);
    $maxDateTinhToanMoi = $stmtMax->fetchColumn();

    // Ốp khối Max Date an toàn này cắm ngược vào Bảng Tổng HOP_DONG dọn cỏ sạch sẽ
    if ($maxDateTinhToanMoi) {
        $stmtUpHD = $pdo->prepare("UPDATE HOP_DONG SET ngayKetThuc = ? WHERE soHopDong = ?");
        $stmtUpHD->execute([$maxDateTinhToanMoi, $soHD]);
    }

    // NGÃ ĐÍCH THÀNH CÔNG VÀ LƯU DI CHÚC VÀO DB THẬT
    $pdo->commit();

    // [AUDIT] Ghi log sau khi kết thúc phòng lẻ thành công
    $maNV_KetThuc = $_SESSION['user_id'] ?? null;
    $soPhongKetThuc = is_array($mangPhayChon) ? count($mangPhayChon) : 0;
    ghiAuditLog(
        $pdo,
        $maNV_KetThuc,
        'END_ROOM_PARTIAL',
        'CHI_TIET_HOP_DONG',
        $soHD,
        "Kết thúc phòng lẻ HĐ {$soHD}: số phòng trả={$soPhongKetThuc}, ngày hết hạn HĐ mới={$maxDateTinhToanMoi}"
    );

    header("Location: hd_hienthi.php?msg=tra_le_success");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // [SEC] Không lộ $e->getMessage() ra HTML
    error_log("hd_ket_thuc_le_submit PDO error: " . $e->getMessage());
    die("Xảy ra lỗi khi kết thúc phòng lẻ. Dữ liệu đã được rollback an toàn. Vui lòng liên hệ quản trị viên.");
}
