<?php
/**
 * Tác vụ Backend Task 2.8: Xử lý Khách Hàng gửi Yêu Cầu Thuê từ trang Public ngoài hệ thống.
 */

// Đảm bảo session sẵn sàng để gọi thư viện Check Token bảo mật
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/common/db.php';
require_once __DIR__ . '/includes/common/csrf.php';
require_once __DIR__ . '/includes/common/mailer.php'; 

// Kiên quyết từ chối Luồng truy cập trực tiếp bằng đường GET (Chống SPAM)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// -------------------------------------------------------------
// BẢO MẬT: XÁC MINH CỔNG VÀO BẰNG CSRF TOKEN
// -------------------------------------------------------------
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    // Tấn công giả mạo hoặc treo máy quá tải hạn Token, chặn tay luôn
    die("<h1>Lỗi 403 - Block Request</h1><p>Vui lòng làm mới trang do mã bảo vệ CSRF đã hết hạn.</p>");
}


// -------------------------------------------------------------
// NHẬN DỮ LIỆU ĐƯỢC POST TỪ TRÌNH DUYỆT CỦA KHÁCH WEB
// -------------------------------------------------------------
$hoTen       = trim($_POST['hoTen'] ?? '');
$soDienThoai = trim($_POST['soDienThoai'] ?? '');
$email       = trim($_POST['email'] ?? '');
$maPhong     = trim($_POST['maPhong'] ?? '');
// Dữ liệu mở rộng $ghiChu đang dùng kỹ thuật Bypass vì DB T1.5 chưa có
$ghiChu      = trim($_POST['ghiChu'] ?? ''); 

// Neo URL trở lại trang giao diện để dùng cho Redict kèm biến báo `msg`
// Dùng hàm urlencode để đề phòng tham số maPhong chứa khoảng trắng
$redirectUrl = "chi_tiet_phong.php?id=" . urlencode($maPhong);


// -------------------------------------------------------------
//  KIỂM ĐỊNH FORM BẮT BUỘC RỖNG KIỂU PHP NATIVE BỞI BACKEND
// -------------------------------------------------------------
if (empty($hoTen) || empty($soDienThoai) || empty($maPhong)) {
    // Nếu Frontend chưa cản kĩ Required HTML5, thì Backend đá ngược kèm Error Empty
    header("Location: {$redirectUrl}&msg=error_empty");
    exit();
}

// Tự động sản xuất mã Yêu Cầu Thuê ID để lưu trự vào CSDL (Prefix VD: YC-12345)
$maYC = 'YC-' . rand(10000, 99999);


// -------------------------------------------------------------
// LUỒNG DAO: TIẾP CẬN VÀ GHI DỮ LIỆU BẰNG PDO
// -------------------------------------------------------------
try {
    $pdo = Database::getInstance()->getConnection();

    /**
     * Dùng PDO Prepared Statements an toàn chống SQL Injection.
     * Lưu ý cho Backend: Trong yêu cầu bạn ném thêm `ghiChu` POST từ form nhưng trong câu
     * Thiết kế DB DDL ở Task 1.5, tôi và bạn hoàn toàn chưa cài đặt trường `ghiChu` trong bảng YEU_CAU_THUE.
     * Để tránh Crash hệ thống lúc chạy do Insert Unknown Column, đoạn Prepare dưới đây tôi đã tạm loại bỏ biến :ghichu.
     * Backend có thể tự ALTER ADD COLUMN nếu cần thiết mở rộng.
     */
    $stmt = $pdo->prepare("
        INSERT INTO YEU_CAU_THUE (maYeuCau, maPhong, hoTen, sdt, email) 
        VALUES (:mayc, :phong, :ten, :sdt, :mail)
    ");
    
    // Đẩy param mapping vào để chôn data
    $isSuccess = $stmt->execute([
        ':mayc'  => $maYC,
        ':phong' => $maPhong,
        ':ten'   => $hoTen,
        ':sdt'   => $soDienThoai,
        ':mail'  => $email
    ]);

    // -------------------------------------------------------------
    // POST-INSERT EVENT: RÚT THÔNG BÁO VÀ KÍCH HOẠT QUY TRÌNH MAIL
    // -------------------------------------------------------------
    if ($isSuccess) {
        
        // Trigger Đẩy tin Email thông báo nếu khách có điền Format Email hợp lệ!
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $subject = "Xác Nhận Đăng Ký Thuê Văn Phòng Thành Công";
            
            // Xây dựng khối HTML giao diện CSS Nội Tuyến để gửi qua hộp Email Tĩnh
            $htmlBody = "
                <div style='font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; max-width: 600px;'>
                    <h2 style='color: #1e3a5f;'>Kính gửi Anh/Chị {$hoTen},</h2>
                    <p>Hệ thống quản lý <strong>Blue Sky Tower</strong> xin cảm ơn anh/chị đã quan tâm và đăng ký nhận tư vấn thuê mã gian phòng/tầng: <strong>{$maPhong}</strong>.</p>
                    
                    <div style='background-color: #f4f7f9; padding: 15px; border-left: 4px solid #c9a66b; margin-top:15px; margin-bottom: 20px;'>
                        <strong>Mã yêu cầu của anh/chị là: <span style='color: #d35400; font-size: 1.25rem;'>{$maYC}</span></strong>
                    </div>
                    
                    <p>Đội ngũ chuyên viên Tòa nhà sẽ liên lạc qua Hotline số <i>{$soDienThoai}</i> trong hệ thống để thông tin bảng kế hoạch, tư vấn hợp đồng hoặc sắp xếp lịch tiếp đón anh/chị xem không gian BĐS thực tế.</p>
                    <p>Trân trọng cảm ơn,</p>
                    <br/>
                    <p><strong>BQL Tòa Nhà Mẫu Blue Sky</strong></p>
                </div>
            ";
            
            // Call API Wrapper nhả TCP kết nối mail ngầm định
            sendEmail($email, $subject, $htmlBody);
        }

        // Action Response: Thành Công xuất sắc (Chuẩn Exit)
        header("Location: {$redirectUrl}&msg=success");
        exit();

    } else {
        // Tình huống chập chờn SQL lúc Execute (Rất hiếm)
        header("Location: {$redirectUrl}&msg=error_db");
        exit();
    }

} catch (PDOException $e) {
    // Tình huống vỡ Data Integrity lớn (Khách nhập bừa MaPhong ảo mà DB gốc không hề có) thì sẽ bị chặn ở Constrain Foreign Key
    error_log("Lỗi Xử Lý Đơn Form Yêu Cầu Trang Chủ Public: " . $e->getMessage());
    header("Location: {$redirectUrl}&msg=error_exception");
    exit();
}
