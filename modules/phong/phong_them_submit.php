<?php
/**
 * modules/phong/phong_them_submit.php
 * Xử lý lưu dữ liệu phòng và tệp tin hình ảnh
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Do NOT use session cache for phai_doi_matkhau check (as per request)
// (Assuming kiemTraSession() handles the DB check as requested)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: phong_hienthi.php");
    exit();
}

// 2. KIỂM TRA CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Lỗi bảo mật: CSRF Token không hợp lệ.";
    header("Location: phong_them.php");
    exit();
}

// 3. NHẬN VÀ VALIDATE DỮ LIỆU
$maTang         = trim($_POST['maTang'] ?? '');
$tenPhong       = trim($_POST['tenPhong'] ?? '');
$loaiPhong      = trim($_POST['loaiPhong'] ?? 'Văn phòng');
$dienTich       = (float)($_POST['dienTich'] ?? 0);
$soChoLamViec   = (int)($_POST['soChoLamViec'] ?? 0);
$donGiaM2       = (float)$_POST['donGiaM2'] ?? 0;

if (empty($maTang) || empty($tenPhong) || $dienTich <= 0 || $donGiaM2 < 0) {
    $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ và chính xác thông tin phòng.";
    header("Location: phong_them.php");
    exit();
}

// 4. THIẾT LẬP KẾT NỐI & TRANSACTION
$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // 4.1. Lấy heSoGia từ bảng TANG để tính lại giaThue (Server-side validation)
    $stmtTang = $db->prepare("SELECT heSoGia FROM TANG WHERE maTang = ? AND deleted_at IS NULL FOR UPDATE");
    $stmtTang->execute([$maTang]);
    $tang = $stmtTang->fetch();
    if (!$tang) {
        throw new Exception("Tầng không tồn tại hoặc đã bị xóa.");
    }
    $heSoGia = (float)$tang['heSoGia'];
    $giaThueThucTe = round($dienTich * $donGiaM2 * $heSoGia);

    // 4.2. Sinh mã phòng ngẫu nhiên
    $maPhong = sinhMaNgauNhien('P-', 7);

    // 4.3. Insert vào bảng PHONG
    $sqlPhong = "
        INSERT INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai)
        VALUES (:maP, :maT, :ten, :loai, :dt, :soCho, :dg, :gt, 1)
    ";
    $stmtP = $db->prepare($sqlPhong);
    $stmtP->execute([
        ':maP'      => $maPhong,
        ':maT'      => $maTang,
        ':ten'      => $tenPhong,
        ':loai'     => $loaiPhong,
        ':dt'       => $dienTich,
        ':soCho'    => $soChoLamViec,
        ':dg'       => $donGiaM2,
        ':gt'       => $giaThueThucTe
    ]);

    // 4.4. Xử lý Upload Hình ảnh
    if (!empty($_FILES['hinhAnh']['name'][0])) {
        $uploadDir = __DIR__ . '/../../assets/uploads/rooms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $files = $_FILES['hinhAnh'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $fileName = $files['name'][$i];
            $fileSize = $files['size'][$i];
            $tmpPath  = $files['tmp_name'][$i];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validate extension & size (2MB)
            if (!in_array($ext, $allowedExtensions) || $fileSize > 2 * 1024 * 1024) {
                continue; // Bỏ qua file không hợp lệ
            }

            // Sinh tên file duy nhất
            $newFileName = uniqid('room_') . '.' . $ext;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpPath, $targetPath)) {
                // Insert vào bảng PHONG_HINH_ANH
                // maHinhAnh: sinh ngau nhien cho id bảng PHONG_HINH_ANH (trường id là PK)
                $maHinhAnh = sinhMaNgauNhien('IMG-', 10);
                $isThumbnail = ($i === 0) ? 1 : 0;

                $stmtImg = $db->prepare("INSERT INTO PHONG_HINH_ANH (id, maPhong, urlHinhAnh, is_thumbnail) VALUES (?, ?, ?, ?)");
                $stmtImg->execute([$maHinhAnh, $maPhong, 'assets/uploads/rooms/' . $newFileName, $isThumbnail]);
            }
        }
    }

    // 5. HOÀN TẤT
    $db->commit();

    // Ghi Audit Log
    ghiAuditLog(
        $db,
        $_SESSION['user_id'],
        'CREATE',
        'PHONG',
        $maPhong,
        "Thêm phòng [{$tenPhong}] tại tầng [{$maTang}]. Giá thuê [".formatTien($giaThueThucTe)."]",
        layIP()
    );

    // Xoay vòng CSRF Token
    rotateCSRFToken();

    $_SESSION['success_msg'] = "Thêm phòng mới thành công!";
    header("Location: phong_hienthi.php");
    exit();

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Lỗi thêm phòng: " . $e->getMessage());
    $_SESSION['error_msg'] = "Đã xảy ra lỗi: " . $e->getMessage();
    header("Location: phong_them.php");
    exit();
}
