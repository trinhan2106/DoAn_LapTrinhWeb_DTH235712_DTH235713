<?php
// modules/phong/phong_lock.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';

// File này là Backend Endpoint chỉ tiếp nhận Ajax JSON.
header('Content-Type: application/json; charset=utf-8');

// Chặn phương thức sai
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức Request không hợp lệ. Chỉ nhận POST.']);
    exit();
}

$action = $_POST['action'] ?? '';
$maPhong = trim($_POST['maPhong'] ?? '');
// Dùng Cờ Session_Id mặc định của Server PHP để định danh thằng Admin nào đang giữ phòng
$sessionId = session_id();

// Bắt rỗng dsn
if (empty($maPhong)) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi thiếu tham số mã phòng.']);
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    /**
     * TRƯỜNG HỢP: KHÓA PHÒNG BẢO ĐẢM
     */
    if ($action === 'lock') {
        // Lấy Date Now UTC hiện tại của hệ thống Database
        // MySQL NOW() sẽ tự chốt đúng TimeZone gốc
        
        // 1. SELECT ĐỂ ĐO ĐỤNG ĐỘ RACE CONDITION
        $stmtCheck = $pdo->prepare("SELECT session_id, expire_at FROM PHONG_LOCK WHERE maPhong = ?");
        $stmtCheck->execute([$maPhong]);
        $lockData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // Chuyển Time để so sánh dễ hơn ở tầng PHP
        $nowStamp = time();
        
        // Nếu đã có record bị giữ bởi ĐỨA KHÁC và THỜI GIAN CHƯA HẾT HẠN
        if ($lockData && $lockData['session_id'] !== $sessionId) {
            $expireStamp = strtotime($lockData['expire_at']);
            if ($expireStamp > $nowStamp) {
                // Reject thẳng cánh vì thằng kia chưa nhả lệnh. (Lỗi 409 Conflict)
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Phòng này đang được nhân viên/quản lý khác chọn nháp để tạo hợp đồng. Mã khóa chặn tạm thời (Lock Box) vẫn còn hiệu lực. Vui lòng chọn phòng khác!'
                ]);
                exit();
            }
        }

        // 2. KHÔNG AI CHỮ HOẶC HẾT HẠN -> CHIẾM QUYỀN
        // Dùng Cú Pháp UPSERT (Insert On Duplicate Key Update) để đè lệnh nhanh nhất
        $stmtLock = $pdo->prepare("
            INSERT INTO PHONG_LOCK (maPhong, session_id, expire_at) 
            VALUES (:phong, :sess, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ON DUPLICATE KEY UPDATE 
            session_id = :sess, 
            expire_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
        ");
        
        $stmtLock->execute([
            ':phong' => $maPhong,
            ':sess'  => $sessionId
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Hệ thống đã giữ chỗ đóng băng mã phòng: ' . $maPhong . ' trong vòng 10 phút.']);
        exit();

    } 
    /**
     * TRƯỜNG HỢP: NHẢ KHÓA GIẢI PHÓNG PHÒNG
     */
    elseif ($action === 'unlock') {
        // Gỡ khóa trả lại tự do khi thoát (Chỉ Gỡ Record PHỤ THUỘC đúng của mảng session user đó)
        // Kỹ thuật này giúp tránh rủi ro thằng JS gửi bug gỡ khóa của User khác.
        $stmtUnlock = $pdo->prepare("DELETE FROM PHONG_LOCK WHERE maPhong = ? AND session_id = ?");
        $stmtUnlock->execute([$maPhong, $sessionId]);
        
        echo json_encode(['status' => 'success', 'message' => 'Hệ thống đã giải phóng hàng rào chốt phòng.']);
        exit();
    } 
    // Invalid Flag
    else {
        echo json_encode(['status' => 'error', 'message' => 'Action Keyword sai định dạng hệ thống.']);
        exit();
    }

} catch (PDOException $e) {
    // Để ý: Đôi khi User chưa chạy file .sql để chèn bảng PHONG_LOCK, văng PDO Error table not exist
    $errStr = 'Đã có lỗi truy vấn Table Lock từ CSDL: ' . $e->getMessage();
    error_log($errStr);
    echo json_encode(['status' => 'error', 'message' => $errStr]);
    exit();
}
