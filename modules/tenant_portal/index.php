<?php
/**
 * modules/tenant_portal/index.php
 * ==================================================================
 * Portal công cộng phục vụ quét mã QR (Task 9.2)
 * Cho phép khách hàng xem nhanh hóa đơn điện tử không cần đăng nhập.
 * Bảo mật: Xác thực bằng JWT (Token sống 15 phút).
 * ==================================================================
 */

require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/jwt_helper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

$pdo = Database::getInstance()->getConnection();
$token = $_GET['token'] ?? '';
$error = null;
$invoice = null;

// Khóa bí mật (Phải khớp với config/app.php)
require_once __DIR__ . '/../../config/app.php';
$secretKey = JWT_SECRET_KEY;

if (!$token) {
    $error = "Yêu cầu truy cập không hợp lệ: Thiếu mã xác thực.";
} else {
    try {
        // 1. Giải mã và xác thực Token
        $decoded = JWT::decode($token, $secretKey);
        $data = $decoded->data ?? null;

        if (!$data || empty($data->soHoaDon)) {
            throw new \Exception("Dữ liệu trong mã QR không hợp lệ.");
        }

        $soHoaDon = $data->soHoaDon;

        // 2. Truy vấn dữ liệu hóa đơn (JOIN với Hợp đồng & Khách hàng)
        $sql = "SELECT hd.*, hp.maKH, kh.tenKH, kh.sdt, kh.email, kh.diaChi as kh_diaChi
                FROM HOA_DON hd
                JOIN HOP_DONG hp ON hd.soHopDong = hp.soHopDong
                JOIN KHACH_HANG kh ON hp.maKH = kh.maKH
                WHERE hd.soHoaDon = ? AND hd.deleted_at IS NULL";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$soHoaDon]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            throw new \Exception("Không tìm thấy dữ liệu hóa đơn trong hệ thống.");
        }

    } catch (ExpiredException $e) {
        $error = "Mã QR đã hết hạn bảo mật. Vui lòng liên hệ Ban Quản Lý để cấp lại.";
    } catch (\Exception $e) {
        $error = "Xác thực không thành công: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Hóa Đơn - The Sapphire</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .portal-wrapper { max-width: 500px; margin: 2rem auto; padding: 0 1rem; }
        .invoice-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; border: none; }
        .navy-header { background-color: #1e3a5f; color: white; padding: 2rem 1.5rem; text-align: center; }
        .gold-line { height: 4px; background: #c9a66b; width: 60px; margin: 1rem auto; }
        .info-group { padding: 1.5rem; border-bottom: 1px dashed #eee; }
        .info-label { color: #6c757d; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        .info-value { color: #1e3a5f; font-weight: 700; font-size: 1.1rem; margin-top: 2px; }
        .status-badge { border-radius: 50px; padding: 0.5rem 1.2rem; font-weight: 700; font-size: 0.9rem; }
        .alert-error { border-radius: 15px; border: none; background-color: #fff1f0; color: #cf1322; padding: 2rem; }
        .price-total { background: #f8f9fa; padding: 1.5rem; border-radius: 0 0 20px 20px; }
        .brand-logo { color: #c9a66b; font-size: 1.5rem; font-weight: 800; letter-spacing: 2px; }
    </style>
</head>
<body>

<div class="portal-wrapper">
    <div class="text-center mb-4">
        <div class="brand-logo mb-1">THE SAPPHIRE</div>
        <div class="text-muted small">Cổng Xác Thực Điện Tử</div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error text-center shadow-sm">
            <i class="bi bi-shield-lock-fill fs-1 d-block mb-3"></i>
            <h5 class="fw-bold">LỖI XÁC THỰC</h5>
            <p class="mb-0"><?= e($error) ?></p>
            <hr>
            <p class="small text-muted mb-0">Vì lý do bảo mật, vui lòng kiểm tra lại mã QR trên hóa đơn giấy mới nhất của bạn.</p>
        </div>
    <?php elseif ($invoice): ?>
        <div class="invoice-card shadow">
            <div class="navy-header">
                <i class="bi bi-check-circle-fill fs-2 text-success mb-2 d-block"></i>
                <h4 class="fw-bold mb-0">HÓA ĐƠN HỢP LỆ</h4>
                <div class="gold-line"></div>
                <div class="small opacity-75">Hệ thống đã xác nhận thông tin hóa đơn này là chính xác.</div>
            </div>

            <div class="info-group">
                <div class="row">
                    <div class="col-6">
                        <div class="info-label">Số Hóa Đơn</div>
                        <div class="info-value">#<?= e($invoice['soHoaDon']) ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="info-label">Kỳ Thanh Toán</div>
                        <div class="info-value"><?= e($invoice['kyThanhToan']) ?></div>
                    </div>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">Khách Hàng</div>
                <div class="info-value"><?= e($invoice['tenKH']) ?></div>
                <div class="small text-muted mt-1">
                    <i class="bi bi-telephone"></i> <?= e($invoice['sdt']) ?> | <i class="bi bi-envelope"></i> <?= e($invoice['email']) ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label">Địa Chỉ</div>
                <div class="info-value" style="font-size: 0.95rem; line-height: 1.4;"><?= e($invoice['kh_diaChi']) ?></div>
            </div>

            <div class="info-group">
                <div class="row align-items-center">
                    <div class="col-6">
                        <div class="info-label">Trạng Thái</div>
                    </div>
                    <div class="col-6 text-end">
                        <?php 
                            $status = $invoice['trangThai'];
                            $batchClass = 'bg-danger';
                            $statusText = 'Chưa Thanh Toán';
                            
                            switch($status) {
                                case 'DaThu': 
                                    $batchClass = 'bg-success'; 
                                    $statusText = 'Đã Thanh Toán'; 
                                    break;
                                case 'DaThuMotPhan': 
                                    $batchClass = 'bg-warning text-dark'; 
                                    $statusText = 'Thu Một Phần'; 
                                    break;
                            }
                        ?>
                        <span class="badge <?= $batchClass ?> status-badge"><?= $statusText ?></span>
                    </div>
                </div>
            </div>

            <div class="price-total d-flex justify-content-between align-items-center">
                <div class="text-navy fw-bold text-uppercase small">Tổng tiền thanh toán</div>
                <div class="text-navy h3 fw-bold mb-0"><?= formatTien($invoice['tongTien']) ?> ₫</div>
            </div>
        </div>

        <div class="text-center mt-4 px-3">
            <p class="text-muted small">Mọi thắc mắc vui lòng liên hệ Bộ phận Kế toán: <strong>(028) 3883.9999</strong></p>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
                <i class="bi bi-printer me-1"></i> Lưu thông tin
            </button>
        </div>
    <?php endif; ?>
</div>

<footer class="text-center text-muted small mt-5 pb-4">
    &copy; <?= date('Y') ?> THE SAPPHIRE - Smart Building Management
</footer>

</body>
</html>
