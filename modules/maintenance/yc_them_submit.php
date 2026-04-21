<?php
/**
 * modules/maintenance/yc_them_submit.php
 * Logic: Xử lý thêm mới yêu cầu bảo trì (Task 9.3)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// 1. Kiểm tra Session & Quyền hạn (Admin hoặc Quản lý tòa nhà)
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Chặn truy cập trực tiếp qua GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: yc_them.php');
    exit();
}

// 3. Validate CSRF Token bảo vệ Form
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_msg'] = "Lỗi xác thực bảo mật (CSRF). Vui lòng thử lại.";
    header('Location: yc_them.php');
    exit();
}

// 4. Nhận và làm sạch dữ liệu đầu vào
$maPhong = trim($_POST['maPhong'] ?? '');
$tieuDe  = trim($_POST['tieuDe'] ?? '');
$moTa    = trim($_POST['moTa'] ?? '');
$mucDoUT = (int)($_POST['mucDoUT'] ?? 1);

// Kiểm tra sơ bộ trường trống
if (empty($maPhong) || empty($tieuDe) || empty($moTa)) {
    $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ các thông tin bắt buộc.";
    header('Location: yc_them.php');
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    
    // 5. Sinh mã yêu cầu ngẫu nhiên (Ví dụ: YCBT-ABC123)
    $requestId = sinhMaNgauNhien('YCBT-', 6);

    // 6. Thực thi INSERT vào bảng MAINTENANCE_REQUEST
    // Sử dụng chuẩn Prepared Statement chống SQL Injection
    // Lưu ý: Cột ID trong schema gốc là 'id', người dùng gọi là 'request_id'
    $sql = "INSERT INTO MAINTENANCE_REQUEST (id, maPhong, tieuDe, moTa, mucDoUT, trangThai, nguoiYeuCau, created_at) 
            VALUES (:id, :maPhong, :tieuDe, :moTa, :mucDoUT, :trangThai, :nguoiYeuCau, NOW())";
    
    $stmt = $db->prepare($sql);
    
    // Bind dữ liệu
    $params = [
        ':id'           => $requestId,
        ':maPhong'      => $maPhong,
        ':tieuDe'       => $tieuDe,
        ':moTa'         => $moTa,
        ':mucDoUT'      => $mucDoUT,
        ':trangThai'    => 1, // Theo sếp yêu cầu: 1 là 'Chờ xử lý'
        ':nguoiYeuCau'  => $_SESSION['user']['username'] ?? 'System'
    ];
    
    $stmt->execute($params);

    // 7. Ghi Audit Log cho hệ thống
    ghiAuditLog(
        $_SESSION['user']['username'] ?? 'Unknown',
        'CREATE',
        'MAINTENANCE_REQUEST',
        $requestId,
        "Đã tạo yêu cầu bảo trì mới cho phòng $maPhong - Tiêu đề: $tieuDe"
    );

    // 8. PRG Pattern: Redirect với thông báo thành công
    $_SESSION['success_msg'] = "Gửi yêu cầu bảo trì thành công! Mã số của bạn là: $requestId";
    header('Location: yc_quan_ly.php');
    exit();

} catch (PDOException $e) {
    // Log lỗi Server để debug nhưng không show ra cho User
    error_log("[yc_them_submit.php] PDO Error: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi hệ thống không thể lưu dữ liệu. Vui lòng thử lại sau.";
    header('Location: yc_them.php');
    exit();
}
