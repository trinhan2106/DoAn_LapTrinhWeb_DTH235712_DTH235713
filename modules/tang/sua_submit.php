<?php
/**
 * modules/tang/sua_submit.php
 * Xử lý cập nhật thông tin Tầng vào cơ sở dữ liệu
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
    header("Location: index.php");
    exit();
}

// 3. LẤY VÀ VALIDATE DỮ LIỆU FORM
$maTang  = trim($_POST['maTang'] ?? '');
$maCaoOc = trim($_POST['maCaoOc'] ?? '');
$tenTang = trim($_POST['tenTang'] ?? '');
$heSoGia = (float)($_POST['heSoGia'] ?? 1.00);

if (empty($maTang) || empty($maCaoOc) || empty($tenTang)) {
    $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ thông tin yêu cầu.";
    header("Location: sua.php?id=" . urlencode($maTang));
    exit();
}

if ($heSoGia <= 0) {
    $_SESSION['error_msg'] = "Hệ số giá phải lớn hơn 0.";
    header("Location: sua.php?id=" . urlencode($maTang));
    exit();
}

// 4. THỰC THI TRUY VẤN
$db = Database::getInstance()->getConnection();

try {
    // Kiểm tra xem mã tầng có tồn tại không
    $stmtCheck = $db->prepare("SELECT maTang FROM TANG WHERE maTang = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$maTang]);
    if (!$stmtCheck->fetch()) {
        $_SESSION['error_msg'] = "Dữ liệu tầng không tồn tại hoặc đã bị xóa.";
        header("Location: index.php");
        exit();
    }

    $sql = "UPDATE TANG SET maCaoOc = :maCaoOc, tenTang = :tenTang, heSoGia = :heSoGia WHERE maTang = :maTang";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':maCaoOc'  => $maCaoOc,
        ':tenTang'  => $tenTang,
        ':heSoGia'  => $heSoGia,
        ':maTang'   => $maTang
    ]);

    if ($result) {
        // Ghi Audit Log thành công
        ghiAuditLog(
            $db,
            $_SESSION['user_id'],
            'UPDATE',
            'TANG',
            $maTang,
            "Cập nhật thông tin tầng [{$maTang}]: Tên [{$tenTang}], Tòa nhà [{$maCaoOc}], Hệ số [{$heSoGia}]",
            layIP()
        );

        // Xoay vòng CSRF Token sau khi submit thành công
        rotateCSRFToken();

        $_SESSION['success_msg'] = "Cập nhật thông tin tầng thành công!";
        header("Location: index.php");
        exit();
    } else {
        throw new Exception("Không thể thực thi lệnh UPDATE.");
    }

} catch (Exception $e) {
    error_log("Lỗi cập nhật tầng: " . $e->getMessage());
    $_SESSION['error_msg'] = "Đã xảy có lỗi xảy ra trong quá trình cập nhật dữ liệu.";
    header("Location: sua.php?id=" . urlencode($maTang));
    exit();
}
