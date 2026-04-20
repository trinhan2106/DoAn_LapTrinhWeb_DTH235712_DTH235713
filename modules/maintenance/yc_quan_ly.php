<?php
// modules/maintenance/yc_quan_ly.php
/**
 * Dashboard Quản lý Yêu Cầu Bảo Trì & Tracking SLA (Dành cho Admin / QLN)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();
$role = (int)($_SESSION['user_role'] ?? 0);
if (!in_array($role, [ROLE_ADMIN, ROLE_QUAN_LY_NHA])) {
    die("Access Denied.");
}

$pdo = Database::getInstance()->getConnection();

try {
    // JOIN M_R với phòng
    $stmt = $pdo->query("
        SELECT m.*, p.tenPhong 
        FROM MAINTENANCE_REQUEST m 
        JOIN PHONG p ON m.maPhong = p.maPhong
        WHERE m.deleted_at IS NULL
        ORDER BY m.trangThai ASC, m.mucDoUT DESC, m.created_at ASC
    ");
    $listReq = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi DB Maintenance Manager: " . $e->getMessage());
}

// Logic tính cấu hình hạn mức SLA. Trả về format timestamp.
function calculateSLADeadline($createdAt, $mucDoUT) {
    $baseTime = strtotime($createdAt);
    switch ($mucDoUT) {
        case 4: return $baseTime + (4 * 3600);   // Nhanh mức cực đại (4h)
        case 3: return $baseTime + (24 * 3600);  // Cao (24h)
        case 2: return $baseTime + (48 * 3600);  // TB (48h)
        case 1: return $baseTime + (72 * 3600);  // Thấp (72h)
        default: return $baseTime + (72 * 3600);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .overdue- violated { color: var(--color-danger); font-weight: bold; }
        .table-custom thead th { background: #1e3a5f !important; color: white !important; }
    </style>
</head>
<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content">
            <div class="container-fluid bg-white p-4 shadow rounded">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h3 class="mb-0 fw-bold text-primary">SLA Maintenance Tracker</h3>
        <a href="../../modules/dashboard/admin.php" class="btn btn-secondary btn-sm">Quay về Backoffice</a>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success px-3 py-2"><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger px-3 py-2"><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle table-datatable">
            <thead class="table-light">
                <tr>
                    <th>Mã Rq</th>
                    <th>Phòng & User</th>
                    <th>Mô Tả Lỗi</th>
                    <th class="text-center">Thời Gian Tạo</th>
                    <th class="text-center">SLA Hạn Chót</th>
                    <th class="text-center">Ưu Tiên</th>
                    <th class="text-center">Trạng Thái & Xử Lý Tiến Độ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listReq as $req): ?>
                    <?php 
                        $deadlineTS = calculateSLADeadline($req['created_at'], (int)$req['mucDoUT']);
                        $isOverdue = time() > $deadlineTS; 
                        
                        // Loại bỏ cảnh báo quá hạn nếu Status là 2 (Đã hoàn thành) hoặc 3 (Đã hủy)
                        $isClosed = in_array((int)$req['trangThai'], [2, 3]);
                    ?>
                    <tr class="<?= $isClosed ? 'opacity-50' : '' ?>">
                        <td class="font-monospace fw-bold"><?= htmlspecialchars($req['id']) ?></td>
                        <td>
                            Phòng: <strong><?= htmlspecialchars($req['tenPhong']) ?></strong><br>
                            Tenant: <code><?= htmlspecialchars($req['nguoiYeuCau']) ?></code>
                        </td>
                        <td style="max-width: 250px; white-space: normal;">
                            <small><?= htmlspecialchars($req['moTa']) ?></small>
                        </td>
                        <td class="text-center"><small><?= date('d/m H:i', strtotime($req['created_at'])) ?></small></td>
                        
                        <td class="text-center fw-bold <?= (!$isClosed && $isOverdue) ? 'text-danger' : 'text-success' ?>">
                            <?= date('d/m H:i', $deadlineTS) ?>
                            <?php if(!$isClosed && $isOverdue): ?>
                                <br><small class="badge bg-danger">SLA Violated</small>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <?php if($req['mucDoUT']==4) echo '<span class="badge bg-danger">Khẩn</span>';
                                  elseif($req['mucDoUT']==3) echo '<span class="badge bg-warning text-dark">Cao</span>';
                                  elseif($req['mucDoUT']==2) echo '<span class="badge bg-primary">TB</span>';
                                  else echo '<span class="badge bg-secondary">Thấp</span>';
                            ?>
                        </td>
                        
                        <td>
                            <!-- Action cập nhật trạng thái -->
                            <form action="yc_capnhat.php" method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($req['id']) ?>">
                                <input type="hidden" name="trangThaiCu" value="<?= htmlspecialchars($req['trangThai']) ?>">

                                <select name="trangThaiMoi" class="form-select form-select-sm" <?= $isClosed ? 'disabled' : '' ?>>
                                    <option value="0" <?= $req['trangThai']==0 ? 'selected' : '' ?>>Chờ tiếp nhận</option>
                                    <option value="1" <?= $req['trangThai']==1 ? 'selected' : '' ?>>Đang xử lý</option>
                                    <option value="2" <?= $req['trangThai']==2 ? 'selected' : '' ?>>Hoàn thành</option>
                                    <option value="3" <?= $req['trangThai']==3 ? 'selected' : '' ?>>Hủy bỏ</option>
                                </select>
                                
                                <?php if(!$isClosed): ?>
                                    <button type="submit" class="btn btn-sm btn-info text-white">Save</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($listReq)): ?>
                    <tr><td colspan="7" class="text-center">Queue làm việc trống.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>
</body>
</html>
