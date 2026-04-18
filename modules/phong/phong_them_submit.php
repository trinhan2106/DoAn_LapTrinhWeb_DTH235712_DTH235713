<?php
// modules/phong/phong_them_submit.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

// Chống URL trực tiếp
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: phong_hienthi.php");
    exit();
}

// Xác thực thẻ bài CSRF Form
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>403 Lỗi bảo mật CSRF</h1>");
}

// Bắt Params
$maPhong       = trim($_POST['maPhong'] ?? '');
// Tên phòng nếu để trống, lấy bằng luôn Mã phòng mặc định
$tenPhong      = trim($_POST['tenPhong'] ?? '');
if($tenPhong === '') $tenPhong = $maPhong; 

$maTang        = trim($_POST['maTang'] ?? '');
$dienTich      = (float)($_POST['dienTich'] ?? 0);
$soChoLamViec  = (int)($_POST['soChoLamViec'] ?? 0);
$donGiaM2      = (float)($_POST['donGiaM2'] ?? 0);
$giaThue       = (float)($_POST['giaThue'] ?? 0); // Nhận field readonly (Bên HTML vẫn đẩy Post lên được do Readonly không phải Disabled)

// Thiết lập Fixed Data gốc lúc khởi tạo mới phòng
$trangThai     = 1; // 1 = Tượng Trưng cho Cờ: "Trong" / "Rỗng chưa khách"
$loaiPhong     = 'Văn phòng'; 

// Validate Logic Backend
if(empty($maPhong) || empty($maTang)) {
    die("Lỗi không được bỏ trống các trường định danh cấp 1.");
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // VALIDATE DB LEVEL: Kiểm tra Id Mã Phòng bị người tạo trùng (Duplicate Key Constraints)
    $stmtCheck = $pdo->prepare("SELECT maPhong FROM PHONG WHERE maPhong = ?");
    $stmtCheck->execute([$maPhong]);
    if ($stmtCheck->rowCount() > 0) {
        // Trùng -> Quăng về trang list kèm flag err
        header("Location: phong_them.php?err=duplicate");
        exit();
    }
    
    // GHI NHẬN LÊN TẦNG PHẦN MỀM THỰC THI (TRANSACTION)
    $sqlInsert = "
        INSERT INTO PHONG (maPhong, maTang, tenPhong, loaiPhong, dienTich, soChoLamViec, donGiaM2, giaThue, trangThai) 
        VALUES (:ma, :tang, :ten, :loai, :dt, :cho, :dongia, :gia, :tt)
    ";
    
    $stmt = $pdo->prepare($sqlInsert);
    
    $isVao = $stmt->execute([
        ':ma'      => $maPhong,
        ':tang'    => $maTang,
        ':ten'     => $tenPhong,
        ':loai'    => $loaiPhong,
        ':dt'      => $dienTich,
        ':cho'     => $soChoLamViec,
        ':dongia'  => $donGiaM2,
        ':gia'     => $giaThue,
        ':tt'      => $trangThai
    ]);
    
    if($isVao) {
        header("Location: phong_hienthi.php?msg=add_success");
        exit();
    } else {
        die("Hệ thống MySQL từ chối lưu lệnh!");
    }

} catch (PDOException $e) {
    // Crash bắt ngẫu nhiên Catch, báo log Server Error
    error_log("LÔI INSERT CSDL TẠI QUẢN LÝ PHÒNG: " . $e->getMessage());
    die("Truy vấn thất bại Exception: " . $e->getMessage() . " (Vui lòng kiểm tra lại cấu trúc DB Data Types đã Add Column đủ chưa)");
}
