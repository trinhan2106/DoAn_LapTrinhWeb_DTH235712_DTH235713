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
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .audit-header { background: #343a40; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; }
        .log-table thead th { background-color: var(--color-primary) !important; color: #fff !important; border-bottom: 2px solid var(--color-accent); }
    </style>
</head>
<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content">

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
            <table class="table table-bordered table-striped log-table align-middle table-datatable">
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
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>
</body>
</html>
