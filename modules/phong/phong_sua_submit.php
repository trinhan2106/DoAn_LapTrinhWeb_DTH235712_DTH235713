<?php
/**
 * modules/phong/phong_sua_submit.php
 * Xử lý cập nhật thông tin Phòng - Server-side Calc & IDOR Protection
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: phong_hienthi.php");
    exit();
}

// 2. KIỂM TRA CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Lỗi bảo mật: CSRF Token không hợp lệ.";
    header("Location: phong_hienthi.php");
    exit();
}

// 3. NHẬN DỮ LIỆU VÀ KIỂM TRA IDOR
$maPhong       = trim($_POST['maPhong'] ?? '');
$maTang        = trim($_POST['maTang'] ?? '');
$tenPhong      = trim($_POST['tenPhong'] ?? '');
$trangThai     = (int)($_POST['trangThai'] ?? 1);
$dienTich      = (float)($_POST['dienTich'] ?? 0);
$soChoLamViec  = (int)($_POST['soChoLamViec'] ?? 0);
$donGiaM2      = (float)($_POST['donGiaM2'] ?? 0);

if (empty($maPhong) || empty($maTang) || empty($tenPhong) || $dienTich <= 0 || $donGiaM2 < 0) {
    $_SESSION['error_msg'] = "Dữ liệu nhập vào chưa đầy đủ hoặc không hợp lệ.";
    header("Location: phong_sua.php?id=" . urlencode($maPhong));
    exit();
}

$db = Database::getInstance()->getConnection();

try {
    // 4. KIỂM TRA TỒN TẠI (ANTI-IDOR)
    $stmtCheck = $db->prepare("SELECT maPhong FROM PHONG WHERE maPhong = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$maPhong]);
    if (!$stmtCheck->fetch()) {
        throw new Exception("Bản ghi phòng không tồn tại hoặc đã bị xóa.");
    }

    // 5. TÍNH TOÁN LẠI GIA THUE (SERVER-SIDE)
    // Lấy heSoGia từ bảng TANG
    $stmtTang = $db->prepare("SELECT heSoGia FROM TANG WHERE maTang = ? AND deleted_at IS NULL");
    $stmtTang->execute([$maTang]);
    $tang = $stmtTang->fetch();
    if (!$tang) {
        throw new Exception("Tầng đã chọn không tồn tại hoặc đã bị ẩn.");
    }
    
    $heSoGia = (float)$tang['heSoGia'];
    $giaThueMoi = round($dienTich * $donGiaM2 * $heSoGia);

    // 6. THỰC THI CẬP NHẬT (TRANSACTION)
    $db->beginTransaction();

    $sql = "
        UPDATE PHONG 
        SET maTang = :maT, 
            tenPhong = :ten, 
            trangThai = :st, 
            dienTich = :dt, 
            soChoLamViec = :soCho, 
            donGiaM2 = :dg, 
            giaThue = :gt
        WHERE maPhong = :maP AND deleted_at IS NULL
    ";

    $stmtUpdate = $db->prepare($sql);
    $result = $stmtUpdate->execute([
        ':maT'      => $maTang,
        ':ten'      => $tenPhong,
        ':st'       => $trangThai,
        ':dt'       => $dienTich,
        ':soCho'    => $soChoLamViec,
        ':dg'       => $donGiaM2,
        ':gt'       => $giaThueMoi,
        ':maP'      => $maPhong
    ]);

    if ($result) {
        // Ghi Audit Log thành công
        ghiAuditLog(
            $db,
            $_SESSION['user_id'],
            'UPDATE',
            'PHONG',
            $maPhong,
            "Cập nhật phòng [{$maPhong}]: Diện tích [{$dienTich}], Giá mới [".number_format($giaThueMoi, 0, ',', '.')." VNĐ]",
            layIP()
        );

        // 6.1. Xử lý xóa hình ảnh
        if (!empty($_POST['delete_images'])) {
            $deleteIds = $_POST['delete_images'];
            foreach ($deleteIds as $imgId) {
                // Lấy đường dẫn file để xóa vật lý
                $stmtImgPath = $db->prepare("SELECT urlHinhAnh FROM PHONG_HINH_ANH WHERE id = ? AND maPhong = ?");
                $stmtImgPath->execute([$imgId, $maPhong]);
                $imgData = $stmtImgPath->fetch();

                if ($imgData) {
                    $fullPath = __DIR__ . '/../../' . $imgData['urlHinhAnh'];
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                    // Xóa record trong DB
                    $stmtDelImg = $db->prepare("DELETE FROM PHONG_HINH_ANH WHERE id = ?");
                    $stmtDelImg->execute([$imgId]);
                }
            }
        }

        // 6.2. Xử lý upload hình ảnh mới
        if (!empty($_FILES['hinhAnh']['name'][0])) {
            $uploadDir = __DIR__ . '/../../assets/uploads/rooms/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $files = $_FILES['hinhAnh'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                $fileName = $files['name'][$i];
                $fileSize = $files['size'][$i];
                $tmpPath  = $files['tmp_name'][$i];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowedExtensions) || $fileSize > 2 * 1024 * 1024) continue;

                $newFileName = uniqid('room_') . '.' . $ext;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpPath, $targetPath)) {
                    $maHinhAnh = sinhMaNgauNhien('IMG-', 10);
                    // Kiểm tra xem đã có ảnh đại diện chưa, nếu chưa thì đặt là thumbnail
                    $stmtCheckThumb = $db->prepare("SELECT COUNT(*) FROM PHONG_HINH_ANH WHERE maPhong = ? AND is_thumbnail = 1");
                    $stmtCheckThumb->execute([$maPhong]);
                    $hasThumb = $stmtCheckThumb->fetchColumn() > 0;
                    $isThumbnail = $hasThumb ? 0 : 1;

                    $stmtInsertImg = $db->prepare("INSERT INTO PHONG_HINH_ANH (id, maPhong, urlHinhAnh, is_thumbnail) VALUES (?, ?, ?, ?)");
                    $stmtInsertImg->execute([$maHinhAnh, $maPhong, 'assets/uploads/rooms/' . $newFileName, $isThumbnail]);
                }
            }
        }

        $db->commit();

        // Xoay vòng CSRF Token sau khi hoàn tất
        rotateCSRFToken();

        $_SESSION['success_msg'] = "Cập nhật thông tin phòng thành công!";
        header("Location: phong_hienthi.php");
        exit();
    } else {
        throw new Exception("Thực thi câu lệnh UPDATE thất bại.");
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Lỗi cập nhật phòng: " . $e->getMessage());
    $_SESSION['error_msg'] = "Đã xảy ra lỗi: " . $e->getMessage();
    header("Location: phong_sua.php?id=" . urlencode($maPhong));
    exit();
}
