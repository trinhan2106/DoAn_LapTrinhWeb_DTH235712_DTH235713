<?php
require_once __DIR__ . '/../../includes/common/db.php';
header('Content-Type: application/json; charset=utf-8');

// Nhận dữ liệu từ JS Fetch API
$data = json_decode(file_get_contents('php://input'), true);
$message = mb_strtolower(trim($data['message'] ?? ''), 'UTF-8');

// Lời chào mặc định nếu không hiểu
$response = "Xin lỗi, tôi chưa hiểu ý bạn. Bạn có thể hỏi tôi về <b>'phòng trống'</b>, <b>'giá thuê'</b>, hoặc <b>'liên hệ'</b>.";

if (empty($message)) {
    echo json_encode(['status' => 'success', 'reply' => 'Bạn muốn hỏi thông tin gì nào?']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // RULE 1: Chào hỏi
    if (strpos($message, 'chào') !== false || strpos($message, 'hi') !== false || strpos($message, 'hello') !== false) {
        $response = "Chào bạn! Tôi là trợ lý ảo của Hệ thống Cao ốc. Tôi có thể giúp bạn tra cứu 'phòng trống' hoặc 'giá thuê'.";
    } 
    // RULE 2: Tra cứu phòng trống
    elseif (strpos($message, 'phòng trống') !== false || strpos($message, 'còn phòng') !== false || strpos($message, 'thuê phòng') !== false) {
        $stmt = $pdo->prepare("SELECT maPhong, dienTich, giaThue FROM PHONG WHERE trangThai = N'Trống' LIMIT 3");
        $stmt->execute();
        $phongTrong = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($phongTrong) > 0) {
            $response = "Hiện tại hệ thống đang có các phòng trống nổi bật sau:<br>";
            foreach ($phongTrong as $p) {
                $gia = number_format($p['giaThue'], 0, ',', '.');
                $response .= "🏢 Phòng <b>{$p['maPhong']}</b>: {$p['dienTich']}m² - Giá: {$gia} VNĐ<br>";
            }
            $response .= "<i>Bạn có thể vào mục 'Phòng trống' trên menu để xem chi tiết hơn nhé!</i>";
        } else {
            $response = "Rất tiếc, hiện tại tất cả các phòng của hệ thống đều đã được thuê kín.";
        }
    } 
    // RULE 3: Tra cứu giá
    elseif (strpos($message, 'giá') !== false || strpos($message, 'tiền') !== false || strpos($message, 'chi phí') !== false) {
        $response = "Giá thuê phòng của chúng tôi được tính tự động dựa trên <b>Diện tích (m²)</b> và <b>Hệ số giá của từng tầng</b>. Vui lòng gõ 'phòng trống' để xem giá cụ thể của các phòng đang khả dụng.";
    } 
    // RULE 4: Liên hệ
    elseif (strpos($message, 'liên hệ') !== false || strpos($message, 'hotline') !== false || strpos($message, 'admin') !== false) {
        $response = "Bạn có thể liên hệ Ban Quản Lý qua hotline: <b>0123.456.789</b> hoặc gửi Email tới: <b>admin@caooc.vn</b>.";
    }

    echo json_encode(['status' => 'success', 'reply' => $response]);

} catch (PDOException $e) {
    error_log("Chatbot DB Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'reply' => 'Hệ thống bot đang bảo trì. Vui lòng thử lại sau.']);
}
?>
