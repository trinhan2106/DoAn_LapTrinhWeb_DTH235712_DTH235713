<?php
// modules/tien_coc/tc_xuly_submit.php
/**
 * LÕI BACKEND THỰC THI CHUYỂN TRẠNG THÁI TIỀN CỌC + GHI KIỂM TOÁN LENO AUDIT_LOG
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

// KIỂM SOÁT QUYỀN
$role = (int)($_SESSION['role_id'] ?? 4);
if (!in_array($role, [1, 2])) {
    die("BLOCK: Chỉ QLN hoặc Admin mới được phong ấn Tiền Cọc.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Route Cấm Cửa.");

// CHỐNG CSRF LỖ HỔNG FORGED
$csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
if (!$csrf || !validateCSRFToken($csrf)) {
    $_SESSION['error_msg'] = "Lỗi Phiên Giao Dịch CSRF Hết Hạn. Vui lòng thử lại lệnh.";
    header("Location: tc_hienthi.php");
    exit();
}

$maTienCoc = trim($_POST['maTienCoc'] ?? '');
$actionStatus = (int)($_POST['actionStatus'] ?? 0); // 2: Hoàn, 3: Tịch thu
$ghiChu = trim($_POST['ghiChu'] ?? '');
$nguoiThucHien = $_SESSION['user_id'] ?? 'Unknown_Admin';

if (empty($maTienCoc) || !in_array($actionStatus, [2, 3]) || empty($ghiChu)) {
    $_SESSION['error_msg'] = "Dữ liệu đệ trình rỗng hoặc thi hành Quyết định không hợp lệ.";
    header("Location: tc_xuly.php?id=" . urlencode($maTienCoc));
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // -------------------------------------------------------------
    // KHÁCH ĐẾN NHÀ: MỞ KHÓA TRANSACTION BẢO ĐẢM TÍNH ACID ACID
    // -------------------------------------------------------------
    $pdo->beginTransaction();

    // 1. UPDATE BẢNG CỌC (Trạng Thái + Safe Update)
    // Ràng buộc rất kỹ: Không bị hacker sửa khi đã bị chốt (chỉ update khi trangThai = 1)
    $stmtUpdate = $pdo->prepare("
        UPDATE TIEN_COC 
        SET trangThai = ? 
        WHERE maTienCoc = ? AND trangThai = 1
    ");
    $stmtUpdate->execute([$actionStatus, $maTienCoc]);

    if ($stmtUpdate->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Sự cố Bồi thẩm đoàn: Hoặc Khoản Cọc không tồn tại hoặc Ai đó ĐÃ xử lý nó trước bạn rồi (Xung đột Concurrency)!";
        header("Location: tc_hienthi.php");
        exit();
    }

    // 2. GHI DẤU LIỆU LOG (Tòa Án Append-Only)
    $quyetDinhLetter = ($actionStatus === 2) ? "[HOÀN TRẢ TIỀN MẶT]" : "[TỊCH THU XUNG QUỸ]";
    $chiTietAudit = sprintf("Hành động: %s. Lý do phê chuẩn: %s", $quyetDinhLetter, $ghiChu);

    $stmtAudit = $pdo->prepare("
        INSERT INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, thoiGian) 
        VALUES (?, 'XU_LY_TIEN_COC', 'TIEN_COC', ?, ?, NOW())
    ");
    $stmtAudit->execute([$nguoiThucHien, $maTienCoc, $chiTietAudit]);

    // -------------------------------------------------------------
    // HẠ BÚA KẾT THÚC TRANSACTION
    // -------------------------------------------------------------
    $pdo->commit();

    $_SESSION['success_msg'] = "Quyết Định " . $quyetDinhLetter . " Khoản Cọc [".htmlspecialchars($maTienCoc)."] Đã Được Ban Hành Và Gắn Dấu Audit Log.";
    header("Location: tc_hienthi.php");
    exit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Lỗi System Kế Toán Cọc: " . $e->getMessage());
    $_SESSION['error_msg'] = "LỖI LÕI SẬP MẠCH: " . htmlspecialchars($e->getMessage());
    header("Location: tc_hienthi.php");
    exit();
}
