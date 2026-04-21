<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/jwt_helper.php';

/**
 * modules/tenant_portal/index.php
 * ==================================================================
 * Trang đích khi khách quét mã QR (Task 9.2 - Enhanced)
 * Xác thực JWT và truy xuất dữ liệu real-time từ Database.
 * ==================================================================
 */

$token = $_GET['token'] ?? '';
$payload = false;
$data = null;
$error_reason = ''; 

if ($token) {
    // 1. Giải mã bằng SapphireAuth
    $payload = SapphireAuth::decode($token, JWT_SECRET_KEY);
    
    if (!$payload) {
        $error_reason = 'Mã xác thực không đúng định dạng hoặc chữ ký số không hợp lệ.';
    } elseif (isset($payload['exp']) && $payload['exp'] < time()) {
        $error_reason = 'Mã xác thực này đã hết hạn bảo mật (giới hạn 15-30 phút).';
        $payload = false; // Đánh dấu thất bại
    } elseif ($payload && isset($payload['data'])) {
        $innerData = $payload['data'];
        $type = $innerData['type'] ?? '';
        $id = $innerData['id'] ?? '';
        
        $pdo = Database::getInstance()->getConnection();
        
        if ($type === 'contract') {
            $stmt = $pdo->prepare("
                SELECT hd.*, kh.tenKH, kh.email, kh.sdt 
                FROM HOP_DONG hd
                JOIN KHACH_HANG kh ON hd.maKH = kh.maKH
                WHERE hd.soHopDong = ? AND hd.deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $stmtRooms = $pdo->prepare("
                    SELECT p.tenPhong FROM CHI_TIET_HOP_DONG cthd 
                    JOIN PHONG p ON cthd.maPhong = p.maPhong 
                    WHERE cthd.soHopDong = ?
                ");
                $stmtRooms->execute([$id]);
                $data['rooms'] = $stmtRooms->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $error_reason = 'Không tìm thấy thông tin Hợp đồng này trên hệ thống (có thể đã bị hủy).';
            }
        } elseif ($type === 'invoice') {
            $stmt = $pdo->prepare("
                SELECT hd.*, kh.tenKH, kh.sdt
                FROM HOA_DON hd
                JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong
                JOIN KHACH_HANG kh ON h.maKH = kh.maKH
                WHERE hd.soHoaDon = ? AND hd.deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                $error_reason = 'Không tìm thấy thông tin Hóa đơn này trên hệ thống.';
            }
        }
    }
} else {
    $error_reason = 'Thiếu mã token xác thực trong yêu cầu.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Điện Tử - The Sapphire</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-navy: #1e3a5f;
            --accent-gold: #c9a66b;
            --success-green: #28a745;
            --info-blue: #0dcaf0;
        }

        body {
            background: #0f172a; /* Dark sleek background */
            background-image: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            max-width: 550px;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            overflow: hidden;
            color: #fff;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header-status {
            padding: 40px 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .status-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }

        .status-success { background: rgba(40, 167, 69, 0.2); color: #4ade80; border: 2px solid #4ade80; }
        .status-error { background: rgba(220, 53, 69, 0.2); color: #f87171; border: 2px solid #f87171; }

        .content-body { padding: 30px; }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-label { color: #94a3b8; font-size: 0.9rem; }
        .detail-value { font-weight: 600; text-align: right; }

        .badge-premium {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success { background: #064e3b; color: #34d399; }
        .badge-warning { background: #78350f; color: #fbbf24; }
        .badge-danger { background: #7f1d1d; color: #f87171; }

        .room-tag {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .brand-footer {
            padding: 20px;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            background: rgba(0, 0, 0, 0.2);
        }

        .btn-print {
            background: var(--accent-gold);
            border: none;
            color: #fff;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-print:hover { background: #b08d55; transform: scale(1.02); color: #fff; }

        @media print {
            .btn-print { display: none; }
            body { background: #fff; color: #000; }
            .auth-card { background: #fff; color: #000; border: 1px solid #ddd; box-shadow: none; backdrop-filter: none; }
        }
    </style>
</head>
<body>

<div class="auth-card">
    <?php if ($data): ?>
        <div class="card-header-status">
            <div class="status-icon status-success">
                <i class="bi bi-shield-check"></i>
            </div>
            <h3 class="fw-bold mb-1">XÁC THỰC THÀNH CÔNG</h3>
            <p class="small text-muted mb-0">Tài liệu được xác nhận chính chủ & hợp lệ</p>
        </div>

        <div class="content-body">
            <!-- Thông tin chung -->
            <div class="detail-row">
                <span class="detail-label"><i class="bi bi-person me-2"></i>Khách hàng</span>
                <span class="detail-value text-info"><?= e($data['tenKH']) ?></span>
            </div>

            <?php if ($innerData['type'] === 'contract'): ?>
                <!-- Thông tin Hợp đồng -->
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-file-earmark-text me-2"></i>Số Hợp đồng</span>
                    <span class="detail-value"><?= e($data['soHopDong']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-calendar-range me-2"></i>Thời hạn</span>
                    <span class="detail-value text-warning">
                        <?= date('d/m/Y', strtotime($data['ngayBatDau'])) ?> 
                        <i class="bi bi-arrow-right mx-2"></i>
                        <?= $data['ngayHetHanCuoiCung'] ? date('d/m/Y', strtotime($data['ngayHetHanCuoiCung'])) : 'N/A' ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-building me-2"></i>Phòng thuê</span>
                    <span class="detail-value">
                        <?php foreach (($data['rooms'] ?? []) as $room): ?>
                            <span class="room-tag"><?= e($room) ?></span>
                        <?php endforeach; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-check-circle me-2"></i>Trạng thái</span>
                    <span class="detail-value">
                        <?php if ($data['trangThai'] == 1): ?>
                            <span class="badge-premium badge-success">Đang hiệu lực</span>
                        <?php else: ?>
                            <span class="badge-premium badge-danger">Hết hiệu lực</span>
                        <?php endif; ?>
                    </span>
                </div>

            <?php else: ?>
                <!-- Thông tin Hóa đơn -->
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-receipt me-2"></i>Mã Hóa đơn</span>
                    <span class="detail-value"><?= e($data['soHoaDon']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-calendar3 me-2"></i>Kỳ thanh toán</span>
                    <span class="detail-value fw-bold"><?= e($data['kyThanhToan']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-cash-stack me-2"></i>Tổng tiền</span>
                    <span class="detail-value fs-5"><?= number_format($data['tongTien'], 0, ',', '.') ?> VNĐ</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-credit-card-2-front me-2"></i>Trạng thái</span>
                    <span class="detail-value">
                        <?php 
                            $st = $data['trangThai'];
                            if ($st === 'DaThu') echo '<span class="badge-premium badge-success">Đã thanh toán</span>';
                            elseif ($st === 'ConNo') echo '<span class="badge-premium badge-danger">Còn nợ</span>';
                            else echo '<span class="badge-premium badge-warning">Thu một phần</span>';
                        ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-exclamation-triangle me-2"></i>Số tiền nợ</span>
                    <span class="detail-value <?= $data['soTienConNo'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($data['soTienConNo'], 0, ',', '.') ?> VNĐ
                    </span>
                </div>
            <?php endif; ?>

            <button class="btn-print" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>In xác nhận điện tử
            </button>
        </div>

    <?php else: ?>
        <div class="card-header-status">
            <div class="status-icon status-error">
                <i class="bi bi-exclamation-octagon"></i>
            </div>
            <h3 class="fw-bold mb-1">XÁC THỰC THẤT BẠI</h3>
            <p class="small text-muted mb-0">Tài liệu không hợp lệ hoặc đã hết hạn</p>
        </div>
        <div class="content-body text-center">
            <p class="text-secondary mb-4">
                <?= e($error_reason ?: 'Tài liệu không hợp lệ hoặc đã hết hạn truy cập.') ?>
            </p>
            <a href="../../index.php" class="btn btn-outline-light rounded-pill px-4">
                Quay lại trang chủ
            </a>
        </div>
    <?php endif; ?>

    <div class="brand-footer">
        <div class="mb-1"><strong>THE SAPPHIRE TOWER SYSTEM</strong></div>
        <div>Xác thực số hóa - Bảo mật tuyệt đối</div>
    </div>
</div>

</body>
</html>
