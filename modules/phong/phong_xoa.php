<?php
// modules/phong/phong_xoa.php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';

// Bảo vệ tính riêng tư quyền bằng Auth Native
kiemTraSession();

// Rút param maPhong từ GET link
$maPhong = trim($_GET['maPhong'] ?? '');

// Kích hoạt cờ chặn URL vô kỷ luật (Nhập /phong_xoa.php mà rỗng ?maPhong=)
if(empty($maPhong)){
    header("Location: phong_hienthi.php");
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // KIẾN TRÚC SOFT DELETE UPDATE
    // Thay vì Delete vĩnh viễn (gây mất dấu Audit Logs + Phá vỡ Khoá Ngoại các Hoá Đơn cũ)
    // Hệ thống sẽ lùi sang UPDATE field `deleted_at` xuống Datetime hiện tại.
    // Lệnh PDO NOW() mysql được sử dụng cho sự an toàn đồng nhất DB Timezone.
    $stmt = $pdo->prepare("UPDATE PHONG SET deleted_at = NOW() WHERE maPhong = :id");
    
    $isOk = $stmt->execute([':id' => $maPhong]);
    
    if ($isOk) {
        header("Location: phong_hienthi.php?msg=delete_success");
        exit();
    } else {
        header("Location: phong_hienthi.php");
        exit();
    }

} catch (PDOException $e) {
    // Có thể quăng exception khi mạng yếu hoặc disconnect
    error_log("Gặp sự cố ngắt kết nối PDO lúc Soft Delete: " . $e->getMessage());
    header("Location: phong_hienthi.php?msg=delete_err");
    exit();
}
