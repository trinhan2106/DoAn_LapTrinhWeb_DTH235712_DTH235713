<?php
/**
 * scratch/test_jwt.php
 * Script kiểm thử logic JWT (HS256 + Base64Url)
 */

require_once __DIR__ . '/../includes/common/jwt_helper.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\ExpiredException;

// Mô phỏng cấu hình
$secretKey = 'Test_Secret_Key_123';

echo "--- JWT UNIT TEST (HS256) ---\n";

// 1. Test Encode/Decode chuẩn
echo "[Test 1] Encode & Decode chuẩn: ";
$payload = [
    'iat' => time(),
    'exp' => time() + 3600,
    'data' => ['id' => 'HD001', 'user' => 'admin']
];

try {
    $token = JWT::encode($payload, $secretKey);
    // Kiểm tra xem có chứa các ký tự URL-unsafe không (+, /, =)
    if (strpos($token, '+') !== false || strpos($token, '/') !== false || strpos($token, '=') !== false) {
        throw new Exception("Token chứa ký tự URL-unsafe!");
    }
    
    $decoded = JWT::decode($token, $secretKey);
    if ($decoded->data->id === 'HD001') {
        echo "PASS\n";
    } else {
        echo "FAIL (Dữ liệu không khớp)\n";
    }
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// 2. Test Token hết hạn
echo "[Test 2] Kiểm tra Token hết hạn: ";
$payload_expired = [
    'iat' => time() - 2000,
    'exp' => time() - 500, // Đã hết hạn 500 giây
    'data' => ['id' => 'HD002']
];

try {
    $token_expired = JWT::encode($payload_expired, $secretKey);
    JWT::decode($token_expired, $secretKey);
    echo "FAIL (Lẽ ra phải báo hết hạn)\n";
} catch (ExpiredException $e) {
    echo "PASS (Bắt được lỗi: " . $e->getMessage() . ")\n";
} catch (Exception $e) {
    echo "FAIL (Lỗi không mong muốn: " . $e->getMessage() . ")\n";
}

// 3. Test sai chữ ký (Tam biến/Giả mạo)
echo "[Test 3] Kiểm tra giả mạo chữ ký: ";
try {
    $token_legit = JWT::encode($payload, $secretKey);
    $token_tampered = $token_legit . 'modified';
    JWT::decode($token_tampered, $secretKey);
    echo "FAIL (Lẽ ra phải báo sai chữ ký)\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Chữ ký không hợp lệ') !== false) {
        echo "PASS (Phát hiện giả mạo)\n";
    } else {
        echo "FAIL (" . $e->getMessage() . ")\n";
    }
}

echo "--- KẾT THÚC KIỂM THỬ ---\n";
