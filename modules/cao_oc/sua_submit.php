<?php
/**
 * modules/cao_oc/sua_submit.php
 * Xử lý cập nhật thông tin cao ốc
 * ==================================================================
 */

require_once '../../includes/common/auth.php';
require_once '../../includes/common/db.php';
require_once '../../includes/common/csrf.php';
require_once '../../includes/common/functions.php';

// 1. Bảo mật: Kiểm tra session và quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. Kiểm tra phương thức
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// 3. Kiểm tra CSRF Token
$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    die("Lỗi bảo mật: CSRF Token không hợp lệ.");
}

// 4. Lấy dữ liệu
$maCaoOc = trim($_POST['maCaoOc'] ?? '');
$tenCaoOc = trim($_POST['tenCaoOc'] ?? '');
$diaChi = trim($_POST['diaChi'] ?? '');
$soTang = (int)($_POST['soTang'] ?? 0);

if (empty($maCaoOc) || empty($tenCaoOc) || empty($diaChi) || $soTang <= 0) {
    echo "<script>alert('Vui lòng điền đầy đủ các thông tin bắt buộc.'); window.history.back();</script>";
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // 5. Kiểm tra Cao ốc có tồn tại và chưa bị xóa không
    $checkStmt = $pdo->prepare("SELECT maCaoOc FROM CAO_OC WHERE maCaoOc = ? AND deleted_at IS NULL");
    $checkStmt->execute([$maCaoOc]);
    if (!$checkStmt->fetch()) {
        die("Không tìm thấy bản ghi cần cập nhật.");
    }

    // 6. Thực hiện UPDATE
    $sql = "UPDATE CAO_OC SET tenCaoOc = ?, diaChi = ?, soTang = ? WHERE maCaoOc = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tenCaoOc, $diaChi, $soTang, $maCaoOc]);

    // 7. Ghi nhật ký hệ thống (Audit Log)
    ghiAuditLog(
        $pdo, 
        $_SESSION['user_id'], 
        'UPDATE', 
        'CAO_OC', 
        $maCaoOc, 
        "Cập nhật thông tin cao ốc. Tên mới: $tenCaoOc", 
        layIP()
    );

    rotateCSRFToken();

    header("Location: index.php?status=success&msg=" . urlencode("Cập nhật thông tin cao ốc thành công!"));
    exit();

} catch (PDOException $e) {
    error_log("Lỗi UPDATE CAO_OC: " . $e->getMessage());
    die("Có lỗi xảy ra trong quá trình cập nhật. Vui lòng thử lại sau.");
}
