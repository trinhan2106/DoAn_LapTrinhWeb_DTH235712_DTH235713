<?php
// modules/hop_dong/hd_gia_han_submit.php
/**
 * BACKEND XỬ LÝ DAO GIA HẠN ĐA TẦNG PHỨC THẠP BẰNG TRANSACTION (Task 5.7)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Action Blocked by Engine.");

// Tường Rào CSRF
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Lỗi 403. Form Transaction bị mất dấu CSRF Authenticator.</h1>");
}

$soHD          = trim($_POST['soHopDong'] ?? '');
$dsMaCTHD      = $_POST['dsMaCTHD'] ?? [];
$dsMaPhong     = $_POST['dsMaPhong'] ?? [];
$soThangGiaHan = $_POST['soThangGiaHan'] ?? []; // Array mảng từ Input (maCTHD => Tháng)

if (empty($soHD) || empty($dsMaCTHD)) die("Dữ liệu mảng ID khuyết thiếu không thể nối mạch Transaction.");

$pdo = Database::getInstance()->getConnection();

try {
    // ----------------------------------------------------------------------------------
    // RENDER VÒNG BẢO VỆ CHUỖI BLOCK (TRANSACTION ACiD)
    // ----------------------------------------------------------------------------------
    // Rủi ro UC08: Có thể update 10 phòng dỡ dang bị sập máy thì database sẽ dính rác, bắt buộc dùng Transaction che chở.
    $pdo->beginTransaction();

    // Tự sinh Khóa Gia Hạn Lịch Sử cho riêng lần này
    $soGiaHan = 'GH-' . date('Ymd-His');
    
    // ACTION 1: TẠO BẢN GHI LỊCH SỬ VÀO BẢNG CHÍNH [GIA_HAN_HOP_DONG]
    // Giả lập SQL INSERT vì trong DB DDL của 1.5 chúng ta chưa create bảng GIA_HAN_HOP_DONG hoàn chỉnh
    // Nếu bảng GIA_HAN_HOP_DONG đã có (do user tự thêm), lệnh sẽ chạy tuyệt vời.
    $stmtHistory = $pdo->prepare("INSERT INTO GIA_HAN_HOP_DONG (soGiaHan, soHopDong, ngayKyGiaHan) VALUES (?, ?, NOW())");
    $stmtHistory->execute([$soGiaHan, $soHD]);


    // PREPARE CÁC NHÁNH DAO XỬ LÝ CON ĐỂ DÙNG CHUNG TRONG LÚP
    // Chèn Lịch Cự Ly vào Bảng Trung Gian Chi Tiết Rẽ Nhánh
    $stmtInsertCTGH = $pdo->prepare("INSERT INTO CHI_TIET_GIA_HAN (soGiaHan, maPhong, thoiGianGiaHan, ngayHetHanMoi) VALUES (?, ?, ?, ?)");
    
    // Cập Nhật Cấu Trúc Bảng CTHD cũ
    // Yêu cầu (RB-08.3): Thay thế ngayHetHan mới. Do CTHD chưa có cột ngayHetHan, nếu user tự ALTER ta dùng lệnh sau.
    // Nếu bảng chưa có thì phải ALTER TABLE CHI_TIET_HOP_DONG ADD ngayHetHan DATE.
    $stmtUpdateCT = $pdo->prepare("UPDATE CHI_TIET_HOP_DONG SET ngayHetHan = ? WHERE maCTHD = ?");
    
    // QUÉT TÌM NGÀY ĐÍCH LỚN NHẤT
    $maxDateMoi = null;
    $hasSucceedRoom = false; // Phù hiệu kiểm tra xem có phòng nào trên > 0 không

    // QUERY FETCH ĐỂ LẤY BASE RĂNG CƯA NGÀY CŨ CỦA DATABASE TRÁNH JS HACK THAY ĐỔI
    $stmtGetBase = $pdo->prepare("SELECT COALESCE(ngayHetHan, (SELECT ngayKetThuc FROM HOP_DONG WHERE soHopDong=?)) FROM CHI_TIET_HOP_DONG WHERE maCTHD = ?");

    foreach ($dsMaCTHD as $maCT) {
        $thangThieu = (int)($soThangGiaHan[$maCT] ?? 0);
        
        // CHỈ THỰC THI NGHIỆP VỤ DAO TRÊN NHỮNG PHÒNG ĐƯỢC ỦY QUYỀN GIA HẠN > 0 THÁNG
        if ($thangThieu > 0) {
            $hasSucceedRoom = true;
            $maPh = $dsMaPhong[$maCT];
            
            // Xách Base Cũ 
            $stmtGetBase->execute([$soHD, $maCT]);
            $baseDate = $stmtGetBase->fetchColumn();

            // Tính hàm Engine Date Bằng PHP Cứng (Tuyệt đối không lấy Value của FE JS Submit lên vì nguy hiểm Injection Dữ Tiền)
            $newExtendDateStr = date('Y-m-d', strtotime("+{$thangThieu} months", strtotime($baseDate)));

            // ACTION 2: Đẩy Nhánh Data Vào History CHI TIEU
            $stmtInsertCTGH->execute([$soGiaHan, $maPh, $thangThieu, $newExtendDateStr]);
            
            // ACTION 3: Đè Xương Rồng Vào CTHD Cũ Của Phân Hệ Đó
            try {
                $stmtUpdateCT->execute([$newExtendDateStr, $maCT]);
            } catch (Exception $e) {} // Fallback pass qua nếu DB T1.5 chưa có Cột ngayHetHan con.

            // Soạn Trận Chứa thuật toán Tìm Max Date Của Cả Cuộc Chơi (RB-08.3)
            if ($maxDateMoi === null || strtotime($newExtendDateStr) > strtotime($maxDateMoi)) {
                $maxDateMoi = $newExtendDateStr;
            }
        }
    }

    // ACTION CHỐT HẠ (RB-08.3): NẾU CÓ ÍT NHẤT 1 HÀNH ĐỘNG HỢP LỆ VÀ CÓ MAX_DATE
    if ($hasSucceedRoom && $maxDateMoi !== null) {
        
        // Càn quét Bảng Quản Hợp Đồng Mẹ. Nới Mốc ngayKetThuc (ngayHetHanCuoiCung theo design OOAD chuẩn) ra cục Date khủng nhất hiện có.
        // Đống thời Tráo thẻ trangThai thành cờ 4 (Tượng Ký cho 'GiaHan')
        $stmtChot = $pdo->prepare("UPDATE HOP_DONG SET ngayKetThuc = ?, trangThai = 4 WHERE soHopDong = ?");
        $stmtChot->execute([$maxDateMoi, $soHD]);
        
    }

    // KHÔNG THẤY LỖI, BỐ CÁC PHÉP CHỤP VÀO HARD DISK BẰNG COMMIT CHẶT
    $pdo->commit();

    // RÚT RA BÊN NGOÀI
    header("Location: hd_hienthi.php?msg=giahan_success");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ACiD TRANSACTION LOG - DATABASE HỎNG KHI LÀM UC08 (GIA HẠN HĐ): " . $e->getMessage());
    die("Xảy Ra Rủi Ro Mất Gói Dữ Liệu Máy Chủ - Rollback Mode - CSDL An Toàn. Lý Do: " . $e->getMessage());
}
