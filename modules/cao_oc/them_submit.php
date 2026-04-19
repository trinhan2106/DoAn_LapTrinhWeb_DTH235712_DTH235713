<?php
/**
 * modules/cao_oc/them_submit.php
 * Xử lý thêm mới cao ốc
 * ==================================================================
 */

require_once '../../includes/common/auth.php';
require_once '../../includes/common/db.php';
require_once '../../includes/common/csrf.php';
require_once '../../includes/common/functions.php';

// 1. Bảo mật: Kiểm tra session và quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Kiểm tra phương thức (Chỉ nhận POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// 3. Kiểm tra CSRF Token
$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    die("Lỗi bảo mật: CSRF Token không hợp lệ. Vui lòng thử lại.");
}

// 4. Lấy và làm sạch dữ liệu
$tenCaoOc = trim($_POST['tenCaoOc'] ?? '');
$diaChi = trim($_POST['diaChi'] ?? '');
$soTang = (int)($_POST['soTang'] ?? 0);

// Validate dữ liệu đầu vào cơ bản
if (empty($tenCaoOc) || empty($diaChi) || $soTang <= 0) {
    echo "<script>alert('Vui lòng điền đầy đủ các thông tin bắt buộc.'); window.history.back();</script>";
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // 5. Sinh mã Cao ốc: CO-YYYYMM-RANDOM
    // Ví dụ: CO-202604-ABCDEF
    $ma = sinhMaNgauNhien('CO-' . date('Ym') . '-', 6);

    // 6. Thực hiện INSERT (Sử dụng Prepared Statement)
    $sql = "INSERT INTO CAO_OC (maCaoOc, tenCaoOc, diaChi, soTang) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ma, $tenCaoOc, $diaChi, $soTang]);

    // 7. Ghi nhật ký hệ thống (Audit Log)
    $chiTiet = "Tên: $tenCaoOc, Địa chỉ: $diaChi, Số tầng: $soTang";
    ghiAuditLog(
        $pdo, 
        $_SESSION['user_id'], 
        'CREATE', 
        'CAO_OC', 
        $ma, 
        $chiTiet, 
        layIP()
    );

    // Xoay vòng token sau khi submit thành công (Bảo vệ Layer 2)
    rotateCSRFToken();

    // 8. Chuyển hướng về trang danh sách với thông báo thành công
    // Trong thực tế có thể dùng $_SESSION['flash_message'] nếu hệ thống có hỗ trợ
    header("Location: index.php?status=success&msg=" . urlencode("Thêm mới cao ốc thành công!"));
    exit();

} catch (PDOException $e) {
    // Xử lý lỗi (ví dụ mã bị trùng - xác suất thấp nhưng vẫn có)
    if ($e->getCode() == 23000) {
        // Lỗi Integrity constraint violation (Duplicate entry)
        echo "<script>alert('Mã cao ốc sinh ra bị trùng hoặc dữ liệu không hợp lệ. Vui lòng thử lại.'); window.history.back();</script>";
    } else {
        error_log("Lỗi INSERT CAO_OC: " . $e->getMessage());
        die("Có lỗi xảy ra trong quá trình lưu dữ liệu. Vui lòng liên hệ quản trị viên.");
    }
    exit();
}
