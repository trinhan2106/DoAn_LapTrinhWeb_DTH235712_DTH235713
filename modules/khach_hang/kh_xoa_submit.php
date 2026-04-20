<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
kiemTraSession();
kiemTraRole([1, 2]); // Chỉ Admin (1) và Quản lý Nhà (2) được thao tác
$pdo = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maKH = $_POST['maKH'] ?? '';

    if (empty($maKH)) {
        $_SESSION['error_msg'] = "Thao tác không hợp lệ: Mã khách hàng trống!";
        header("Location: kh_hienthi.php");
        exit();
    }

    try {
        // Kiểm tra Khách hàng có Hợp đồng đang Hiệu Lực hay không 
        // trangThai = 1 tương đương với Hợp Đồng Hiệu Lực, theo cấu trúc SQL (1: Hieu luc, 0: Ket thuc, 2: Huy, 3: ChoDuyet)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM HOP_DONG WHERE maKH = ? AND trangThai = 1 AND deleted_at IS NULL");
        $stmtCheck->execute([$maKH]);
        $countActiveContracts = $stmtCheck->fetchColumn();

        if ($countActiveContracts > 0) {
            // Chặn xóa (Ràng buộc nghiệp vụ)
            $_SESSION['error_msg'] = "KHÔNG THỂ XÓA: Khách hàng [{$maKH}] đang có {$countActiveContracts} hợp đồng ĐANG HIỆU LỰC!";
        } else {
            // Cho phép Soft Delete
            $stmtDelete = $pdo->prepare("UPDATE KHACH_HANG SET deleted_at = CURRENT_TIMESTAMP WHERE maKH = ? AND deleted_at IS NULL");
            $result = $stmtDelete->execute([$maKH]);

            if ($result && $stmtDelete->rowCount() > 0) {
                $_SESSION['success_msg'] = "Đã ẩn (xóa mềm) Khách hàng [{$maKH}] ra khỏi danh sách!";
                // Tùy chọn: Log vào AUDIT_LOG nếu cần thiết
                $stmtAudit = $pdo->prepare("INSERT INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet) VALUES (?, ?, ?, ?, ?)");
                $stmtAudit->execute([$_SESSION['user_id'] ?? 'System', 'Soft Delete', 'KHACH_HANG', $maKH, 'Xóa mềm Khách hàng vì không có hợp đồng hiệu lực']);
            } else {
                $_SESSION['error_msg'] = "Khách hàng không tồn tại hoặc đã bị xóa trước đó!";
            }
        }
    } catch (PDOException $e) {
         $_SESSION['error_msg'] = "Lỗi hệ thống khi xóa Khách hàng!";
         error_log("Lỗi Xóa Khách Hàng: " . $e->getMessage());
    }

    // Chuyển hướng về trang danh sách
    header("Location: kh_hienthi.php");
    exit();
}

// Nếu truy cập trực tiếp bằng GET -> Đẩy về hiển thị
header("Location: kh_hienthi.php");
exit();
