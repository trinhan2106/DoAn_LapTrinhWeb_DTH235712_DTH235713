<?php
require_once __DIR__ . '/../../config/app.php';
/**
 * modules/tenant_portal/index.php
 * ==================================================================
 * Trang đích khi khách quét mã QR (Task 9.2)
 * Xác thực JWT có thời hạn 15 phút và hiển thị thông tin.
 * ==================================================================
 */

// Task 9.2: SECRET_KEY đã được định nghĩa trong config/app.php

require_once __DIR__ . '/../../includes/common/jwt_helper.php';

$token = $_GET['token'] ?? '';
$payload = false;

if ($token) {
    // Giải mã và xác thực token (Sống 15 phút đã được kiểm tra trong decode thông qua 'exp')
    $payload = JWT::decode($token, SECRET_KEY);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Thông Tin - The Sapphire</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            max-width: 500px;
            width: 90%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            border: none;
        }
        .header-success { background-color: #198754; color: white; padding: 30px; text-align: center; }
        .header-error { background-color: #dc3545; color: white; padding: 30px; text-align: center; }
        .content-body { padding: 40px; }
        .info-label { color: #6c757d; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; }
        .info-value { color: #212529; font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; }
        .icon-box { font-size: 4rem; margin-bottom: 15px; }
        .btn-retry { border-radius: 50px; padding: 12px 30px; font-weight: 600; width: 100%; transition: all 0.3s; }
    </style>
</head>
<body>

<div class="auth-card">
    <?php if ($payload): ?>
        <div class="header-success">
            <div class="icon-box">✓</div>
            <h3 class="fw-bold mb-0">XÁC THỰC THÀNH CÔNG</h3>
        </div>
        <div class="content-body">
            <div class="mb-4 text-center text-muted">
                Hệ thống xác nhận hóa đơn/hợp đồng hợp lệ.
            </div>
            
            <div class="info-label">Số Hợp Đồng</div>
            <div class="info-value"><?php echo htmlspecialchars($payload['soHopDong'] ?? 'N/A'); ?></div>
            
            <div class="info-label">Mã Khách Hàng</div>
            <div class="info-value"><?php echo htmlspecialchars($payload['maKH'] ?? 'N/A'); ?></div>
            
            <hr class="my-4">
            <button class="btn btn-success btn-retry" onclick="window.print()">
                In xác nhận hoặc Lưu File
            </button>
        </div>
    <?php else: ?>
        <div class="header-error">
            <div class="icon-box">⚠</div>
            <h3 class="fw-bold mb-0">XÁC THỰC THẤT BẠI</h3>
        </div>
        <div class="content-body text-center">
            <p class="text-danger fw-bold fs-5">
                Mã QR Code đã hết hạn sau 15 phút hoặc không hợp lệ.
            </p>
            <p class="text-muted mb-4">
                Vui lòng yêu cầu cấp lại mã mới từ Ban Quản Lý tòa nhà để tiếp tục.
            </p>
            <button class="btn btn-outline-danger btn-retry" onclick="window.location.reload()">
                Thử quét lại
            </button>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
