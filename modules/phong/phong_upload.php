<?php
/**
 * modules/phong/phong_upload.php
 * Xử lý tải lên hình ảnh cho Phòng (Gallery management)
 * Có thể sử dụng độc lập hoặc qua AJAX
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

header('Content-Type: application/json; charset=utf-8');

// Xác thực Session & Phân quyền (AJAX-safe logic)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập hoặc phiên hết hạn.']);
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn. Vui lòng đăng nhập lại.']);
    exit();
}
$_SESSION['last_activity'] = time();

$userRole = (int)($_SESSION['user_role'] ?? 0);
if (!in_array($userRole, [ROLE_ADMIN, ROLE_QUAN_LY_NHA], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện thao tác này.']);
    exit();
}

// 2. NHẬN DỮ LIỆU & KIỂM TRA CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Lỗi bảo mật: CSRF Token không hợp lệ.']);
    exit();
}

$maPhong = $_POST['maPhong'] ?? '';
if (empty($maPhong)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mã phòng không được để trống.']);
    exit();
}

// 3. XỬ LÝ UPLOAD
$uploadDir = __DIR__ . '/../../assets/uploads/rooms/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$maxSize = 2 * 1024 * 1024; // 2MB
$successCount = 0;
$errors = [];

$db = Database::getInstance()->getConnection();

if (!empty($_FILES['hinhAnh']['name'][0])) {
    $files = $_FILES['hinhAnh'];
    
    // Đảm bảo cấu trúc array dù upload 1 hay nhiều file
    if (!is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }

    try {
        $db->beginTransaction();

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileName = $files['name'][$i];
            $fileSize = $files['size'][$i];
            $tmpPath  = $files['tmp_name'][$i];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                $errors[] = "File {$fileName} không đúng định dạng cho phép.";
                continue;
            }

            if ($fileSize > $maxSize) {
                $errors[] = "File {$fileName} vượt quá dung lượng 2MB.";
                continue;
            }

            // Sinh tên file duy nhất bằng uniqid() theo yêu cầu
            $newFileName = uniqid('room_gallery_') . '.' . $ext;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpPath, $targetPath)) {
                $maHinhAnh = sinhMaNgauNhien('IMG-', 12);
                $url = 'assets/uploads/rooms/' . $newFileName;

                $stmt = $db->prepare("INSERT INTO PHONG_HINH_ANH (id, maPhong, urlHinhAnh, is_thumbnail) VALUES (?, ?, ?, 0)");
                $stmt->execute([$maHinhAnh, $maPhong, $url]);
                $successCount++;
            }
        }

        $db->commit();
        
        // Ghi Audit Log nếu có ít nhất 1 file thành công
        if ($successCount > 0) {
            ghiAuditLog(
                $db,
                $_SESSION['user_id'],
                'UPLOAD_GALLERY',
                'PHONG',
                $maPhong,
                "Tải lên {$successCount} ảnh cho phòng [{$maPhong}]",
                layIP()
            );
        }

        echo json_encode([
            'success' => true,
            'message' => "Đã tải lên {$successCount} ảnh thành công.",
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Lỗi upload gallery: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi xử lý cơ sở dữ liệu.']);
        exit();
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy tệp tin được tải lên.']);
    exit();
}
?>
