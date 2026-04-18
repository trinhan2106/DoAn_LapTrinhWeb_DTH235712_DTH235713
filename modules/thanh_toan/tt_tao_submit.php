<?php
// modules/thanh_toan/tt_tao_submit.php
/**
 * LÕI THẦN KINH XỬ LÝ (TASK 6.2 - UC06 BÙ TRỪ KẾ TOÁN)
 * Chứa Cốt Lõi FOR UPDATE & WATERFALL PAYMENT & EMAIL FIRE
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Truy Cập Block Bởi Framework.");

// CSRF Thẩm định cấp 10
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Lỗi 403 Anti-CSRF - Bảo vệ Ví Điện tử.</h1>");
}

$soHopDong = trim($_POST['soHopDong'] ?? '');
$soTienDaNop_POST = (float)($_POST['soTienDaNop_POST'] ?? 0);
$phuongThucM = trim($_POST['phuongThucM'] ?? 'Chuyen_Khoan');
$maNV_HienHanh = $_SESSION['user_id'] ?? null;

if (empty($soHopDong) || $soTienDaNop_POST < 0 || !$maNV_HienHanh) {
    die("Data Giao Diện Đầu Vào Cố Tình Phá Vỡ Rules (Số âm hoặc vô cực Hợp đồng). Block Node Kế Toán Ngay!");
}

$pdo = Database::getInstance()->getConnection();

try {
    // ----------------------------------------------------------------------------------
    // RENDER LÁ CHẮN TRANSACTION CẤP KẾ TÓAN DOANH NGHIỆP ACiD
    // ----------------------------------------------------------------------------------
    $pdo->beginTransaction();

    // CHIÊU THỨC RB-06.2 CAO CẤP: (SELECT ... FOR UPDATE)
    // Tình huống Rủi Ro Bóng Tối: Chi nhánh 1 đang nhập thu tiền phòng này, chi nhánh 2 vô tình ấn thu tiền 
    // trên máy tính khác ở cùng 1 phần nghìn giây. Nếu không xài lệnh này, cả 2 sẽ báo thu vào 1 bill -> Khách mất tiền DB!
    // -> Khóa vĩnh cửu CỤM Data Hóa Đơn Thuộc Hợp Động Này Tới Ngây Khi PDO Chết Hoặc Commit.
    $stmtLock = $pdo->prepare("
        SELECT * FROM HOA_DON 
        WHERE soHopDong = ? AND trangThai = 'ConNo'
        ORDER BY kyThanhToan ASC, created_at ASC 
        FOR UPDATE
    ");
    $stmtLock->execute([$soHopDong]);
    $listHD_PhaiChiu = $stmtLock->fetchAll(PDO::FETCH_ASSOC);

    if (!$listHD_PhaiChiu) {
        $pdo->rollBack();
        die("Chặn Trễ Mạng: Hợp đồng của đối tác này Vừa Mới Được Ai Đó (Admin Khác) thanh toán sạch sẽ trước bạn vài ly giây Rán Rệp. Không Còn Số Dư!");
    }

    // THUẬT TOÁN ĐIỀU ĐẠN THÁC NƯỚC (WATERFALL PAYMENT ROUTING ACiD)
    // Concept: Khách nạp 1 cục 10 triệu. Hệ thống tự động bốc 10 triệu đó chảy từ trên xuống dưới đi trừ sạch từng Bill lẻ 1 triệu, 2 triệu (Từ bill cũ đến hóa đơn điện mới nhất). Tràn ra thì dồn vào bill cuối tạo Credit.
    $tienLuuChuyenHanhTrinh = $soTienDaNop_POST;
    
    // Yêu Cầu (RB-06.3): UPDATE bảng HOA_DON. Set soTienDaNop, soTienConNo mới. Đổi trangThai thành 'DaThu'. Ghi nhận maNV
    $stmtTrangThaiHoaDon = $pdo->prepare("
        UPDATE HOA_DON 
        SET soTienDaNop = soTienDaNop + :chiecKhauDaNap, 
            soTienConNo = :noMoiTruTaiBuc, 
            trangThai = :statusMoi, 
            maNV = :nvKeToanNhapLieu 
        WHERE soHoaDon = :maBillChotHinh
    ");

    foreach ($listHD_PhaiChiu as $hdKiemToan) {
        if ($tienLuuChuyenHanhTrinh <= 0) {
            // Đã Chia Đều Hết Sạch Sẽ Quỹ Tiền Khách Đưa Nhưng Vẫn Đang Còn Bill Ở Trạm Dưới 
            // -> Break Vòng Lặp. Các Thẻ Bill Còn Lại Để Tự Nhiên.
            break; 
        }

        $noBillHienHanhNodeTrenCung = (float)$hdKiemToan['soTienConNo'];

        if ($tienLuuChuyenHanhTrinh >= $noBillHienHanhNodeTrenCung) {
            // Đủ Quỹ tiền Để Quất Sạch Cái Bill Hiện Tại Này 
            $khoanTienTieuHaoChoBillNay = $noBillHienHanhNodeTrenCung;
            $soNoConLaiCuaBillNayBocHoiVe_0 = 0;
            $trangThaiLienThongMoi = 'DaThu'; // RB-06.3
            
            // Lượng Nước Máu (Quỹ Tiền) Bị Tụt Giảm Chuẩn Bị Cho Vòng Lặp Phía Dưới
            $tienLuuChuyenHanhTrinh -= $noBillHienHanhNodeTrenCung; 
        } else {
            // Khách Đâm Vô Tổng Tiền Không Đủ Sức Để Tiêu Hủy Bill Này (Có Nộp Thác Thiết)
            // VD Bill Nợ 5 Triệu Mả Đưa Tiền Lúc Này Chỉ Còn Dư Lại 2 Triệu => Trút Sạch 2 Triệu Vô, Còn Nợ Bill Này 3 Tr 
            $khoanTienTieuHaoChoBillNay = $tienLuuChuyenHanhTrinh;
            $soNoConLaiCuaBillNayBocHoiVe_0 = $noBillHienHanhNodeTrenCung - $tienLuuChuyenHanhTrinh; // Theo RB-06.2 Logic
            $trangThaiLienThongMoi = 'ConNo'; // Vẫn Nợ (RB-06.3)
            
            $tienLuuChuyenHanhTrinh = 0; // Trút Hết Nước Rùi
        }

        // BÓP VÀI CỘT ACID TRANSCATION
        $stmtTrangThaiHoaDon->execute([
            ':chiecKhauDaNap'      => $khoanTienTieuHaoChoBillNay,
            ':noMoiTruTaiBuc'      => $soNoConLaiCuaBillNayBocHoiVe_0,
            ':statusMoi'           => $trangThaiLienThongMoi,
            ':nvKeToanNhapLieu'    => $maNV_HienHanh,
            ':maBillChotHinh'      => $hdKiemToan['soHoaDon']
        ]);
    }

    // CONCEPT BÙ TRỪ NỢ DƯ VÀO QUỸ TƯƠNG LẠI TÍCH LŨY (Tức là KHÁCH BAO BAO TIỀN ĐEM NÉM XUỐNG DƯ GẦN 20 Tỉ Chẳng Hạn)
    // Sẽ dồn con số dư thừa $tienLuuChuyenHanhTrinh này làm con số ÂM (Credit Point Tích Cực) vào mông Của Hóa Đơn Cuối Cùng.
    if ($tienLuuChuyenHanhTrinh > 0) {
        $lastBillIdArrayPoint = end($listHD_PhaiChiu);
        $stmtCreditNapTrangThaiHoaDon = $pdo->prepare("
            UPDATE HOA_DON 
            SET soTienDaNop = soTienDaNop + :du, soTienConNo = soTienConNo - :du 
            WHERE soHoaDon = :mb
        ");
        $stmtCreditNapTrangThaiHoaDon->execute([
            ':du' => $tienLuuChuyenHanhTrinh, 
            ':mb' => $lastBillIdArrayPoint['soHoaDon']
        ]);
    }

    // GET INFO MỒI TRÁI CÔNG CỤ EMAIL KẾT NỐI (LÀM TRONG DB TRANSACTION ĐỂ TRÁNH QUÁ NẶNG)
    $stmtKHInfo = $pdo->prepare("
        SELECT k.tenKH, k.email 
        FROM HOP_DONG h JOIN KHACH_HANG k ON h.maKH = k.maKH 
        WHERE h.soHopDong = ?
    ");
    $stmtKHInfo->execute([$soHopDong]);
    $mMailPack = $stmtKHInfo->fetch(PDO::FETCH_ASSOC);

    // DỌN SỔ SẠCH BĂNG BĂNG, COMMIT DATA!
    $pdo->commit();


    // ----------------------------------------------------------------------------------
    // API PHÁT BĂNG EMAIL TRƯỚC KHI DỜI VỊ TRÍ HỆ THỐNG
    // ----------------------------------------------------------------------------------
    if ($mMailPack && !empty($mMailPack['email'])) {
        require_once __DIR__ . '/../../includes/common/mailer.php';
        
        $kh_name = htmlspecialchars($mMailPack['tenKH']);
        $tienDaPhayFormat = number_format($soTienDaNop_POST, 0);
        $phTh = str_replace('_', ' ', $phuongThucM);

        $htmlMContent = "
            <div style='background-color: #f4f7f9; padding: 20px; font-family: Arial, sans-serif;'>
                <div style='background: #fff; border-top: 5px solid #28a745; padding: 30px; border-radius: 8px; max-width: 600px; margin: auto;'>
                    <h2 style='color: #28a745; margin-top: 0;'>XÁC NHẬN THANH TOÁN (E-INVOICE RECEIPT)</h2>
                    <p>Kính chào Đại Diện Thuê <strong>$kh_name</strong>,</p>
                    <p>Ban Quản Lý Tòa Nhà Blue Sky Tower xin trân trọng xác nhận và cảm ơn Quý Khách đã thanh toán khoản cước phí duy trì Không Gian Hợp Đồng <strong style='color:#1e3a5f;'>[$soHopDong]</strong>.</p>
                    
                    <div style='background: #f8f9fa; border-left: 4px solid #1e3a5f; padding: 15px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Khoản Nhận Thực Tế:</strong> <span style='color: #d32f2f; font-size: 1.2em; font-weight: bold;'>$tienDaPhayFormat VNĐ</span></p>
                        <p style='margin: 5px 0;'><strong>Bàng Phương Thức Thẻ (Gate):</strong> $phTh</p>
                        <p style='margin: 5px 0;'><strong>Thời Gian Trạm Ghi Nhận:</strong> " . date('d/m/Y H:i:s') . "</p>
                    </div>

                    <p>Mọi khoản dư thừa (Credit Balance) nếu có sẽ được lưu giữ chập quỹ và cấn trừ cho kỳ cước phát sinh tháng Tới liên quan số Phí Điều Hòa.</p>
                    <p>Kính chúc Công Ty Khách hàng Đạt Thêm Nhiều Dự Án Chiến Lược - Tốt Của Khối.</p>
                    <hr style='border: none; border-top: 1px dashed #ccc;'/>
                    <p style='font-size: 0.85em; color: #7f8c8d; text-align: center;'>Email Mạch Nổi System tự động, xin quý vị không Reply phản hồi.</p>
                </div>
            </div>
        ";

        try {
            // Task Yêu Cầu Fire Email
            sendEmail($mMailPack['email'], "[Blue Sky Tower] Biên Lai Dịch Vụ Thanh Toán $soHopDong", $htmlMContent);
        } catch(Exception $mailErrE) {
            error_log("FIRE MAIL FAILED, BUT FINANCIAL TX SUCCESSFUL Cứu Lỗi Khẩn Mãi Mãi Vẫn Lên Tiền.: " . $mailErrE->getMessage());
        }
    }


    // ĐIỀU CHUYỂN KỸ SƯ SAU KHI LÀM TRÒN Sứ Mệnh Database
    header("Location: tt_tao.php?soHopDong=" . urlencode($soHopDong) . "&msg=success");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("CRASH PDOException ACID THÁC NƯỚC KÊ KẾ TÓA (UC06): " . $e->getMessage());
    die("Máy Tàu Lõi Lụi Kích Hoạt Lô Gíc Nước Bị Chệch Khớp Hệ Đứt Gãy Rút DB. <br>Lỗi Lại SQL Đâm Block: " . $e->getMessage());
}
