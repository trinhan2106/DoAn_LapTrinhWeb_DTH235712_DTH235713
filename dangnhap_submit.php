<?php
// dangnhap_submit.php

// 1. Phải khởi tạo Cấu trúc phiên làm việc đầu tiên
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Tái cấu trúc (Require các File Lõi logic)
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/roles.php';
require_once __DIR__ . '/includes/common/db.php';
require_once __DIR__ . '/includes/common/csrf.php';
require_once __DIR__ . '/includes/common/auth.php'; 
require_once __DIR__ . '/includes/common/login_throttle.php';

// Chỉ nhận DATA từ form POST -> chặn Request GET/Direct Access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dangnhap.php");
    exit();
}

// -------------------------------------------------------------
// [BẢO MẬT] VALIDATE CSRF TOKEN
// -------------------------------------------------------------
$csrf_token = $_POST['csrf_token'] ?? '';
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    // Nếu bị mất/Sai Token -> Lệnh die chặt chẽ
    die("<h1>Lỗi 403 - Forbidden</h1><p>Phát hiện lỗ hổng bảo mật rủi ro (Invalid CSRF). Vui lòng tải lại Form đăng nhập!</p>");
}

// Nhận Form Data
$username = trim($_POST['username'] ?? ''); // Trim chống space dư chặn SQL fail logic
$password = $_POST['password'] ?? '';
$ip_address = $_SERVER['REMOTE_ADDR'];

// Triển khai Singleton DB PDO Object
$pdo = Database::getInstance()->getConnection();


// -------------------------------------------------------------
// [BẢO MẬT] KIỂM TRA PENALTY (BRUTE FORCE THROTTLING)
// -------------------------------------------------------------
$lockTime = kiemTraKhoaTaiKhoan($pdo, $ip_address, $username);
if ($lockTime > 0) {
    // Đang dính án phạt (Chuyển param GET wait để ném lỗi UI kèm số phút)
    header("Location: dangnhap.php?error=locked&wait=" . $lockTime);
    exit();
}


// -------------------------------------------------------------
// LUỒNG TÌM KIẾM ACCOUNT THEO HAI NHÁNH NHÂN VIÊN & KHÁCH HÀNG
// -------------------------------------------------------------
$user = null;

// Nhánh 1: Tìm bên bảng Tài Khoản Nhân Viên Quản Trị
// Chú ý hàm COALESCE hỗ trợ gán giá trị 0 nếu phai_doi_matkhau bị NULL
$stmt_nv = $pdo->prepare("
    SELECT maNV as MaND, tenNV as HoVaTen, password_hash, role_id, COALESCE(phai_doi_matkhau, 0) as phai_doi_matkhau 
    FROM NHAN_VIEN 
    WHERE username = :u AND deleted_at IS NULL
");
$stmt_nv->execute([':u' => $username]);

if ($stmt_nv->rowCount() > 0) {
    $user = $stmt_nv->fetch(PDO::FETCH_ASSOC);
} 
else {
    // Nhánh 2: Nếu chưa tìm ra ở Nhân Sự, chuyển sang check Tenant Account
    $stmt_kh = $pdo->prepare("
        SELECT a.accountId as MaND, k.tenKH as HoVaTen, a.password_hash, a.role_id, COALESCE(a.phai_doi_matkhau, 0) as phai_doi_matkhau 
        FROM KHACH_HANG_ACCOUNT a 
        JOIN KHACH_HANG k ON a.maKH = k.maKH 
        WHERE a.username = :u AND a.deleted_at IS NULL
    ");
    $stmt_kh->execute([':u' => $username]);
    if ($stmt_kh->rowCount() > 0) {
        $user = $stmt_kh->fetch(PDO::FETCH_ASSOC);
    }
}


// -------------------------------------------------------------
// XÁC MINH CỨNG BẰNG NGHIỆP VỤ BĂM MẬT KHẨU
// -------------------------------------------------------------
// Tuyệt đối dẹp bỏ MD5, PHP hiện nay dùng password_verify đối chiếu mã Băm (BCrypt, Argon)
if ($user && password_verify($password, $user['password_hash'])) {
    
    // Đăng nhập hợp lệ gốc rễ -> Gỡ Bỏ Bộ Đếm Sai Pass
    xoaLichSuDangNhapSai($pdo, $ip_address, $username);
    
    // Yêu cầu quan trọng: Chống Session Fixation tặc
    session_regenerate_id(true);

    // Gán cờ lệnh Session
    $_SESSION['user_id'] = $user['MaND'];        // Phù hợp chuẩn check kiemTraSession()
    $_SESSION['MaND'] = $user['MaND'];           
    $_SESSION['HoVaTen'] = $user['HoVaTen'];
    
    // Ép kiểu Data Roles thành INT để hàm in_array kiểm định Strict_Type
    $_SESSION['user_role'] = (int)$user['role_id']; 
    $_SESSION['QuyenHan'] = (int)$user['role_id'];
    
    // Reset thời điểm Online Timeout
    $_SESSION['last_activity'] = time();

    // ---------------------------------------------------------
    // HƯỚNG DẪN KỊCH BẢN ĐỔI MẬT KHẨU HOẶC TIẾN VÀO DASHBOARD
    // ---------------------------------------------------------
    $phaiDoiMatKhau = (int)$user['phai_doi_matkhau'];
    // Gán cờ hiệu trạng thái đổi mật khẩu vào phiên làm việc
    $_SESSION['phai_doi_matkhau'] = $phaiDoiMatKhau;

    if ($phaiDoiMatKhau === 1) {
        // Redict văng ra cổng ép buộc đổi pass
        header("Location: " . BASE_URL . "modules/ho_so/doi_mat_khau_batbuoc.php");
        exit();
    } else {
        // Redict thẳng vào Màn hình chính
        header("Location: dashboard.php");
        exit();
    }
    
} else {
    // ---------------------------------------------------------
    // ĐĂNG NHẬP PASS SAI / HOẶC KHÔNG CÓ USER
    // ---------------------------------------------------------
    // Cộng thẻ phạt đếm log
    ghiNhanDangNhapSai($pdo, $ip_address, $username);
    
    header("Location: dangnhap.php?error=invalid");
    exit();
}
