<?php
/**
 * modules/tang/them_submit.php
 * Xử lý thêm mới Tầng vào cơ sở dữ liệu
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. NHẬN DỮ LIỆU VÀ KIỂM TRA CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    $_SESSION['error_msg'] = "Lỗi bảo mật: CSRF Token không hợp lệ.";
    header("Location: them.php");
    exit();
}

// 3. LẤY VÀ VALIDATE DỮ LIỆU FORM
$maCaoOc = trim($_POST['maCaoOc'] ?? '');
$tenTang  = trim($_POST['tenTang'] ?? '');
$heSoGia  = (float)($_POST['heSoGia'] ?? 1.00);

if (empty($maCaoOc) || empty($tenTang)) {
    $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ tên tầng và chọn tòa nhà.";
    header("Location: them.php");
    exit();
}

if ($heSoGia <= 0) {
    $_SESSION['error_msg'] = "Hệ số giá phải lớn hơn 0.";
    header("Location: them.php");
    exit();
}

// 4. THỰC THI TRUY VẤN
$db = Database::getInstance()->getConnection();

try {
    // Sinh mã tầng ngẫu nhiên: T-YYYYMM-RANDOM (Prefix 'T-', length 7 sau prefix)
    $maTang = sinhMaNgauNhien('T-', 7);
    
    $sql = "INSERT INTO TANG (maTang, maCaoOc, tenTang, heSoGia) VALUES (:maTang, :maCaoOc, :tenTang, :heSoGia)";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':maTang'   => $maTang,
        ':maCaoOc'  => $maCaoOc,
        ':tenTang'  => $tenTang,
        ':heSoGia'  => $heSoGia
    ]);

    if ($result) {
        // Ghi Audit Log thành công
        ghiAuditLog(
            $db,
            $_SESSION['user_id'],
            'CREATE',
            'TANG',
            $maTang,
            "Thêm tầng mới [{$tenTang}] tại Cao ốc [{$maCaoOc}] với hệ số giá [{$heSoGia}]",
            layIP()
        );

        // Xoay vòng CSRF Token sau khi submit thành công
        rotateCSRFToken();

        $_SESSION['success_msg'] = "Thêm tầng mới thành công!";
        header("Location: index.php");
        exit();
    } else {
        throw new Exception("Không thể thực thi lệnh INSERT.");
    }

} catch (Exception $e) {
    error_log("Lỗi thêm tầng: " . $e->getMessage());
    $_SESSION['error_msg'] = "Đã xảy có lỗi xảy ra trong quá trình lưu dữ liệu.";
    header("Location: them.php");
    exit();
}
