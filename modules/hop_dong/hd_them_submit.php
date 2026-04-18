<?php
// modules/hop_dong/hd_them_submit.php
/**
 * Tác vụ xử lý Lưu Form Wizard bằng Giao Dịch Mức Thấp (Database Transaction)
 * Bắt buộc bảo vệ toàn vẹn Data & Loại trừ Race Condition theo thiết kế UC03
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
// Chỉ Admin và Quản lý nhà được lập hợp đồng mới
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: hd_them.php");
    exit();
}

// Bức Tường Lửa 1: CSRF
$csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    die("<h1>Lỗi Bảo Mật Form Đã Hết Hạn CSRF. Vui lòng thử lại.</h1>");
}

// Chắt lọc Data Đầu Vào POST
$maKH        = trim($_POST['maKH'] ?? '');
$maPhong     = trim($_POST['maPhong'] ?? ''); // Wizard hiện tại hỗ trợ nhặt 1 phòng (Có thể mở rộng sau lặp vòng)
$ngayLap     = trim($_POST['ngayLap'] ?? date('Y-m-d'));
$ngayBatDau  = trim($_POST['ngayBatDau'] ?? '');
$ngayKetThuc = trim($_POST['ngayKetThuc'] ?? '');
$tienTienCoc = (float)($_POST['tienTienCoc'] ?? 0);

// ID Admin Lập Lệnh (Từ Session đã kiểm định ở auth.php)
$maNV        = $_SESSION['user_id'] ?? null;

if (empty($maKH) || empty($maPhong) || empty($ngayBatDau) || empty($ngayKetThuc) || !$maNV) {
    die("Lỗi Nghiệp vụ: Dữ liệu bị cản vì không thỏa mãn các trường mấu chốt.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // -------------------------------------------------------------
    // KHƠI MÀO VÒNG TRÒN GIAO DỊCH (TRANSACTION)
    // -------------------------------------------------------------
    // Nếu có 1 bước lỗi SQL, tất  cả dữ liệu rác sẽ bị ROLLBACK ngay lập tức!
    $pdo->beginTransaction();

    // CHIẾU BINH PHÁP 1: ROW LOCKING CỰC BÉN CHỐNG CÀ THEO THỜI GIAN THỰC (FOR UPDATE)
    // Dù Room Lock bằng Redis/DB bằng JS có lủng, thằng chốt đơn ở mili-giây cuối vẫn bị cản nếu mất phòng.
    // Lệnh SELECT ... FOR UPDATE báo MySQL khóa chết dòng Data ở bảng PHONG này, đứt đọc/ghi các Query khác cho đến khi COMMIT.
    $stmtCheck = $pdo->prepare("SELECT trangThai, giaThue FROM PHONG WHERE maPhong = ? FOR UPDATE");
    $stmtCheck->execute([$maPhong]);
    $phongData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$phongData) {
        $pdo->rollBack();
        die("Hệ thống từ chối: Quá trình truy soát mã phòng lõi thất bại.");
    }
    
    // Nếu trạng thái Phòng khác 1 (Trống)
    if ((int)$phongData['trangThai'] !== 1) {
        $pdo->rollBack(); // Nhả Transaction
        die("CẢNH BÁO BẢO MẬT ACiD: Lệnh lập Hợp Đồng bị MySQL từ chối vì Phòng <{$maPhong}> đã có Admin khác lập xong Hợp Đồng và trạng thái đã hóa vàng. Không thể đè dữ liệu.");
    }

    // SINH KHÓA ID CHO HỢP ĐỒNG - dùng random_int (CSPRNG) thay str_shuffle
    // str_shuffle không cryptographically secure và dễ trùng, đặc biệt khi
    // nhiều user tạo HĐ đồng thời. sinhMaNgauNhien dùng random_int nên an toàn hơn.
    $soHD_Ran = sinhMaNgauNhien('HD-' . date('Y') . '-', 6);

    // BƯỚC INSERT 1: NÉM DATA VÀO BẢNG HỢP ĐỒNG GỐC 
    // Trạng thái (3: ChoDuyet / Chờ Khách Ký Cọc) - (1: Hieu Luc Da Ky) - (0: Thanh ly)
    $stmtHD = $pdo->prepare("
        INSERT INTO HOP_DONG (soHopDong, maKH, maNV, ngayLap, ngayBatDau, ngayKetThuc, tienTienCoc, trangThai)
        VALUES (:soHD, :kh, :nv, :nlap, :nbd, :nkt, :coc, :tt)
    ");
    $stmtHD->execute([
        ':soHD' => $soHD_Ran,
        ':kh'   => $maKH,
        ':nv'   => $maNV,
        ':nlap' => $ngayLap,
        ':nbd'  => $ngayBatDau,
        ':nkt'  => $ngayKetThuc,
        ':coc'  => $tienTienCoc,
        ':tt'   => 3 // 3 là tự hiểu 'Chờ Duyệt' trong tài liệu
    ]);


    // BƯỚC INSERT 2: RẢI DATA CHO BẢNG CHI TIẾT CON
    $maCTHD_Ran = 'CTHD-' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 7);
    $stmtCT = $pdo->prepare("
        INSERT INTO CHI_TIET_HOP_DONG (maCTHD, soHopDong, maPhong, giaThue, trangThai)
        VALUES (:ct, :hd, :phong, :gia, :tt)
    ");
    $stmtCT->execute([
        ':ct'    => $maCTHD_Ran,
        ':hd'    => $soHD_Ran,
        ':phong' => $maPhong,
        // Ép bắt giá Thuê chuẩn nhất từ Backend lúc Read FOR UPDATE (Khỏi sợ JS bị F12 sửa giá ảo)
        ':gia'   => $phongData['giaThue'], 
        ':tt'    => 0 // Tự hiểu: 0 (Đang neo tạm chờ Ký), 1 (Đang Thuê thực Thụ)
    ]);


    // MỌI BƯỚC ĐÃ XONG, COMMIT TRANSACTION
    $pdo->commit();

    // [AUDIT] Ghi log sau khi commit thành công (ngoài transaction chính)
    ghiAuditLog(
        $pdo,
        $maNV,
        'CREATE_HD',
        'HOP_DONG',
        $soHD_Ran,
        "Lập hợp đồng mới cho KH={$maKH}, phòng={$maPhong}, trạng thái=ChoDuyet"
    );

    // Chuyển sang màn hình ký hợp đồng (UC04)
    header("Location: hd_ky.php?id=" . urlencode($soHD_Ran) . "&msg=created");
    exit();

} catch (PDOException $e) {
    // Rollback nếu transaction còn đang mở
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // [SEC] Không lộ $e->getMessage() ra HTML - chỉ log internal
    error_log("hd_them_submit PDO error: " . $e->getMessage());
    die("Xảy ra sự cố khi lưu hợp đồng. Dữ liệu đã được rollback an toàn. Vui lòng liên hệ quản trị viên.");
}
