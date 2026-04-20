<?php
/**
 * lien_he_submit.php
 * Xử lý gửi lời nhắn liên hệ từ trang gioi_thieu.php
 */
// Bắt đầu session để sử dụng Flash Message và CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Require các file cấu hình và thư viện
require_once __DIR__ . '/includes/common/db.php';
require_once __DIR__ . '/includes/common/csrf.php';

// 2. Chặn truy cập trực tiếp qua phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: gioi_thieu.php");
    exit();
}

// 3. Kiểm tra CSRF Token (Bảo mật Senior)
$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    $_SESSION['error_msg'] = "Lỗi bảo mật: Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ.";
    header("Location: gioi_thieu.php#contact");
    exit();
}

// 4. Tiếp nhận và Cleanup dữ liệu (Lọc XSS)
$hoTen       = htmlspecialchars(trim($_POST['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
$email       = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$soDienThoai = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$noiDung     = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

// 5. Kiểm tra dữ liệu hợp lệ cơ bản
if (empty($hoTen) || empty($email) || empty($noiDung)) {
    $_SESSION['error_msg'] = "Vui lòng điền đầy đủ các thông tin bắt buộc (Họ tên, Email, Nội dung).";
    header("Location: gioi_thieu.php#contact");
    exit();
}

// 6. Thực hiện lưu vào Database bằng PDO Prepared Statement
try {
    $pdo = Database::getInstance()->getConnection();
    
    $sql = "INSERT INTO LIEN_HE (hoTen, email, soDienThoai, noiDung) VALUES (:hoTen, :email, :soDienThoai, :noiDung)";
    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        ':hoTen'       => $hoTen,
        ':email'       => $email,
        ':soDienThoai' => $soDienThoai,
        ':noiDung'     => $noiDung
    ]);

    if ($result) {
        // Thành công: Gán Flash Message tích cực
        $_SESSION['success_msg'] = "Cảm ơn bạn đã gửi lời nhắn! Ban quản lý sẽ liên hệ lại qua email trong thời gian sớm nhất.";
    } else {
        $_SESSION['error_msg'] = "Không thể lưu dữ liệu vào hệ thống. Vui lòng thử lại sau.";
    }

} catch (PDOException $e) {
    // Ghi log lỗi vào file hệ thống (không show cho user xem lỗi SQL)
    error_log("Lỗi gửi liên hệ: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi hệ thống: Không thể xử lý yêu cầu lúc này.";
}

// 7. Chuyển hướng trở lại trang giới thiệu
header("Location: gioi_thieu.php#contact");
exit();
