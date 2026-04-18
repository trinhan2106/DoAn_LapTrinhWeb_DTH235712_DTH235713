<?php
// modules/audit_log/index.php
/**
 * TRUNG TÂM KIỂM TOÁN HỆ THỐNG - APPEND ONLY LOGS
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

// RÀNG BUỘC AN NINH KHẮT KHE: CHỈ ADMIN LÕI ĐƯỢC PHÉP TRUY CẬP
$role = (int)($_SESSION['user_role'] ?? 4);
if ($role !== 1) {
    die("
    <div style='background:#fce4ec; border:3px solid #c62828; padding:50px; font-family:sans-serif; text-align:center;'>
        <h1 style='color:#c62828;'>🚫 ACCESS DENIED - VÙNG TRỊ SỰ CAO CẤP</h1>
        <h3>Tài khoản của bạn [Role ID: $role] không đủ quyền hạn. Chỉ System Admin mới được tham chiếu Cục Audit Log!</h3>
        <p>Hệ thống tự động ngắt kết nối chặn rò rỉ thông tin kiểm toán.</p>
        <a href='../../index.php' style='padding: 10px 20px; text-decoration:none; color:white; background:#1e3a5f;'>Rút lui</a>
    </div>
    ");
}

$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->query("
        SELECT id, maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, thoiGian 
        FROM AUDIT_LOG 
        ORDER BY thoiGian DESC 
        LIMIT 500 -- Chặn query tràn RAM
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết xuất Audit Log: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Kiểm Toán (Audit Trail)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #1e3a5f; 
            --accent: #c9a66b; 
            --bg-color: #f4f7f9; 
            --text-color: #1f2a44;
        }
        body { background-color: var(--bg-color); color: var(--text-color); }
        .audit-header { background: #343a40; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; }
        .log-table th { background-color: var(--primary); color: #fff; border-bottom: 2px solid var(--accent); }
        .log-table tbody tr:hover { background-color: #e9ecef; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="audit-header d-flex justify-content-between align-items-center shadow-sm mb-0">
        <div>
            <h2 class="mb-0 fw-bold"><i class="fa-solid fa-user-secret me-2 text-warning"></i>KHO LƯU TRỮ VẾT KIỂM TOÁN (AUDIT TRAIL)</h2>
            <small class="text-light">Append-only Mode: Dữ liệu bị cấm xóa/sửa vĩnh viễn nhằm đảm bảo tính minh bạch</small>
        </div>
        <a href="../../index.php" class="btn btn-outline-light"><i class="fa-solid fa-house me-1"></i>Về Trang Chủ</a>
    </div>

    <div class="bg-white p-3 shadow-sm border border-top-0 rounded-bottom">
        <div class="table-responsive">
            <table class="table table-bordered table-striped log-table align-middle">
                <thead>
                    <tr>
                        <th class="text-center" width="5%">#</th>
                        <th width="15%"><i class="fa-regular fa-clock me-1"></i>Thời Gian Trực Thi</th>
                        <th width="15%"><i class="fa-solid fa-user-shield me-1"></i>User Thao Tác</th>
                        <th width="15%"><i class="fa-solid fa-bolt me-1"></i>Hành Động Nhạy Cảm</th>
                        <th width="15%"><i class="fa-solid fa-database me-1"></i>Bảng Tác Động</th>
                        <th width="10%"><i class="fa-solid fa-fingerprint me-1"></i>Record ID</th>
                        <th width="25%"><i class="fa-solid fa-circle-info me-1"></i>Chi Tiết Vi Tế</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted border-bottom-0">Chưa có bản ghi hoạt động nào lọt vào Trạm kiểm soát.</td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $idx => $lg): ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                            <td><span class="badge bg-secondary"><?= date('d/m/Y H:i:s', strtotime($lg['thoiGian'])) ?></span></td>
                            <td><strong class="text-dark"><?= htmlspecialchars($lg['maNguoiDung']) ?></strong></td>
                            <td>
                                <?php if($lg['hanhDong'] == 'VOID_INVOICE'): ?>
                                    <span class="badge bg-danger"><i class="fa-solid fa-radiation me-1"></i>VOID INVOICE</span>
                                <?php elseif($lg['hanhDong'] == 'RESTORE_DATA'): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-recycle me-1"></i>RESTORE DATA</span>
                                <?php else: ?>
                                    <span class="badge bg-dark"><?= htmlspecialchars($lg['hanhDong']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge" style="background:#1e3a5f;"><?= htmlspecialchars($lg['bangBiTacDong']) ?></span></td>
                            <td class="font-monospace text-primary fw-bold"><?= htmlspecialchars($lg['recordId']) ?></td>
                            <td><small class="text-muted d-block" style="max-height:60px; overflow-y:auto;"><?= htmlspecialchars($lg['chiTiet']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
