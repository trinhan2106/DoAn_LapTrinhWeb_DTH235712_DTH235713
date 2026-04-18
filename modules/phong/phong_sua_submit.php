<?php
// modules/phong/phong_sua_submit.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: phong_hienthi.php");
    exit();
}

// Bắt rào CSRF
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Lỗi bảo vệ Anti-CSRF</h1>");
}

// Xử lý Lọc Dữ Liệu Raw POST
$maPhong       = trim($_POST['maPhong'] ?? ''); // MaPhong làm khoá WHERE
$tenPhong      = trim($_POST['tenPhong'] ?? '');
if($tenPhong === '') $tenPhong = $maPhong; 

$maTang        = trim($_POST['maTang'] ?? '');
$trangThai     = (int)($_POST['trangThai'] ?? 1);
$dienTich      = (float)($_POST['dienTich'] ?? 0);
$soChoLamViec  = (int)($_POST['soChoLamViec'] ?? 0);
$donGiaM2      = (float)($_POST['donGiaM2'] ?? 0);
$giaThue       = (float)($_POST['giaThue'] ?? 0);

// Double check Input Rỗng
if(empty($maPhong) || empty($maTang)) {
    die("Khuyết lỗi DSN Tham Chiếu. Vui lòng thử lại!");
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // TIẾN TRÌNH UPDATE MYSQL
    $sqlUpdate = "
        UPDATE PHONG 
        SET maTang = :tang, 
            tenPhong = :ten, 
            dienTich = :dt, 
            soChoLamViec = :cho, 
            donGiaM2 = :dongia, 
            giaThue = :gia, 
            trangThai = :tt
        WHERE maPhong = :ma AND deleted_at IS NULL
    ";
    
    $stmt = $pdo->prepare($sqlUpdate);
    $res = $stmt->execute([
        ':tang'    => $maTang,
        ':ten'     => $tenPhong,
        ':dt'      => $dienTich,
        ':cho'     => $soChoLamViec,
        ':dongia'  => $donGiaM2,
        ':gia'     => $giaThue,
        ':tt'      => $trangThai,
        ':ma'      => $maPhong
    ]);
    
    if($res) {
        // Có thể chèn Flash Session ở đây nếu cần báo Message Box sang file HT
        header("Location: phong_hienthi.php?msg=add_success"); // Tái sử dụng Msg cho Form View
        exit();
    } else {
        die("Hệ thống từ chối quyền UPDATE vào CSDL!");
    }

} catch (PDOException $e) {
    error_log("LÔI UPDATE CSDL ADMIN PHÒNG CỐT LÕI: " . $e->getMessage());
    die("Sụp đổ Database: " . $e->getMessage());
}
