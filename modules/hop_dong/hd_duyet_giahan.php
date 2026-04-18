<?php
// modules/hop_dong/hd_duyet_giahan.php
/**
 * Module xử lý Yêu Cầu Gia Hạn Hợp Đồng từ Khách Thuê.
 * Bao gồm cả giao diện hiển thị List (Chờ duyệt) và Action Submit (Đồng ý/Từ chối).
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
if (!in_array((int)($_SESSION['user_role'] ?? 0), [ROLE_ADMIN, ROLE_QUAN_LY_NHA])) {
    die("Access Denied: Chỉ cấp quản lý mới được quyền phê duyệt Hợp đồng.");
}

$pdo = Database::getInstance()->getConnection();

// 1. Xử Lý Submit POST Actions (Duyệt/Từ chối)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT);
    if (!$csrf || !validateCSRFToken($csrf)) {
        $_SESSION['error_msg'] = "Mã xác thực Submit Form không hợp lệ.";
        header("Location: hd_duyet_giahan.php"); exit();
    }

    $maYeuCauGH = trim($_POST['maYeuCauGH'] ?? '');
    $action = $_POST['action'] ?? ''; // 'accept' or 'reject'
    $adminId = $_SESSION['user_id'] ?? 'SYS';

    if (empty($maYeuCauGH)) {
        header("Location: hd_duyet_giahan.php"); exit();
    }

    try {
        $pdo->beginTransaction();

        // Check tồn tại request
        $stmtChk = $pdo->prepare("SELECT soHopDong, soThangDeXuat FROM YEU_CAU_GIA_HAN WHERE maYeuCauGH = ? AND trangThai = 0 FOR UPDATE");
        $stmtChk->execute([$maYeuCauGH]);
        $reqGH = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if (!$reqGH) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Yêu cầu gia hạn không tồn tại hoặc đã được xử lý bởi Cán bộ khác.";
            header("Location: hd_duyet_giahan.php"); exit();
        }

        $soHopDong = $reqGH['soHopDong'];
        $soThang = (int)$reqGH['soThangDeXuat'];

        if ($action === 'accept') {
            // Lấy ngayKetThuc cũ
            $stmtHD = $pdo->prepare("SELECT ngayKetThuc FROM HOP_DONG WHERE soHopDong = ?");
            $stmtHD->execute([$soHopDong]);
            $hd = $stmtHD->fetch(PDO::FETCH_ASSOC);
            $ngayKetThucCu = $hd['ngayKetThuc'] ?: date('Y-m-d');
            
            // Tính ngày mới
            $ngayKetThucMoi = date('Y-m-d', strtotime("{$ngayKetThucCu} + {$soThang} months"));

            // 1. Update YEU_CAU_GIA_HAN -> 1 (Đồng ý)
            $pdo->prepare("UPDATE YEU_CAU_GIA_HAN SET trangThai = 1 WHERE maYeuCauGH = ?")->execute([$maYeuCauGH]);

            // 2. Insert GIA_HAN_HOP_DONG
            $maGiaHan = sinhMaNgauNhien('GH-', 6);
            $pdo->prepare("
                INSERT INTO GIA_HAN_HOP_DONG (maGiaHan, soHopDong, ngayGiaHan, soThangGiaHan, ngayKetThucMoi, ghiChu) 
                VALUES (?, ?, CURDATE(), ?, ?, ?)
            ")->execute([$maGiaHan, $soHopDong, $soThang, $ngayKetThucMoi, "Duyệt yêu cầu tự động từ Tenant"]);

            // 3. Mapping CTHD để chèn mã tham chiếu vào CHI_TIET_GIA_HAN
            $stmtCTHD = $pdo->prepare("SELECT maPhong, donGiaThue FROM CHI_TIET_HOP_DONG WHERE soHopDong = ?");
            $stmtCTHD->execute([$soHopDong]);
            $listPhong = $stmtCTHD->fetchAll(PDO::FETCH_ASSOC);

            $stmtInsCTGH = $pdo->prepare("INSERT INTO CHI_TIET_GIA_HAN (maCTGH, maGiaHan, maPhong, giaThueMoi) VALUES (?, ?, ?, ?)");
            foreach($listPhong as $p) {
                $stmtInsCTGH->execute([sinhMaNgauNhien('CTGH-'), $maGiaHan, $p['maPhong'], $p['donGiaThue']]);
            }

            // 4. Gọi function UPDATE lại ngayHetHanCuoiCung (ngayKetThuc) ở bảng HOP_DONG Chính
            $pdo->prepare("UPDATE HOP_DONG SET ngayKetThuc = ? WHERE soHopDong = ?")->execute([$ngayKetThucMoi, $soHopDong]);

            ghiAuditLog($pdo, $adminId, 'DUYET_GIA_HAN_HD', 'HOP_DONG', $soHopDong, "Chấp thuận gia hạn thêm {$soThang} tháng lên {$ngayKetThucMoi}.");

            $_SESSION['success_msg'] = "Đã phê duyệt gia hạn thành công (Hợp đồng: $soHopDong).";

        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE YEU_CAU_GIA_HAN SET trangThai = 2 WHERE maYeuCauGH = ?")->execute([$maYeuCauGH]);
            ghiAuditLog($pdo, $adminId, 'TU_CHOI_GIA_HAN', 'YEU_CAU_GIA_HAN', $maYeuCauGH, "Từ chối yêu cầu gia hạn.");
            $_SESSION['success_msg'] = "Đã TỪ CHỐI yêu cầu gia hạn thành công.";
        }

        $pdo->commit();
        header("Location: hd_duyet_giahan.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Duyet Gia Han Transaction Fault: " . $e->getMessage());
        $_SESSION['error_msg'] = "Sự cố Database. Transaction Rollback.";
        header("Location: hd_duyet_giahan.php");
        exit();
    }
}

// 2. Giao diện Query Dữ liệu pending (trangThai = 0)
try {
    $stmtWait = $pdo->query("
        SELECT y.maYeuCauGH, y.soHopDong, y.soThangDeXuat, y.lyDo, y.thoiGianYeuCau, h.maKH, k.tenKH 
        FROM YEU_CAU_GIA_HAN y
        JOIN HOP_DONG h ON y.soHopDong = h.soHopDong
        JOIN KHACH_HANG k ON h.maKH = k.maKH
        WHERE y.trangThai = 0
        ORDER BY y.thoiGianYeuCau ASC
    ");
    $listWait = $stmtWait->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết xuất truy vấn Admin Duyệt gia hạn.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phê Duyệt Gia Hạn Hợp Đồng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-light p-4">

<div class="container shadow bg-white p-4 rounded">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h3 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-file-signature me-2"></i>Duyệt Đề Xuất Gia Hạn Thuê (Tenant Requests)</h3>
        <a href="../../modules/dashboard/admin.php" class="btn btn-secondary btn-sm">Quay về Dashboard</a>
    </div>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success px-3 py-2"><i class="fa-solid fa-check me-2"></i><?= htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger px-3 py-2"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã Đề Xuất</th>
                    <th>Ref (Hợp Đồng)</th>
                    <th>Khách Hàng (Tenant)</th>
                    <th>Thời hạn Đề xuất</th>
                    <th>Ghi chú lý do</th>
                    <th class="text-center">Thao Tác Duyệt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listWait as $row): ?>
                    <tr>
                        <td class="font-monospace text-muted"><?= htmlspecialchars($row['maYeuCauGH']) ?></td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($row['soHopDong']) ?></td>
                        <td><?= htmlspecialchars($row['tenKH']) ?> <br><small>Mã: <?= htmlspecialchars($row['maKH']) ?></small></td>
                        <td class="text-danger fw-bold">+ <?= htmlspecialchars($row['soThangDeXuat']) ?> Tháng</td>
                        <td><?= htmlspecialchars($row['lyDo']) ?></td>
                        <td class="text-center">
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="maYeuCauGH" value="<?= htmlspecialchars($row['maYeuCauGH']) ?>">
                                
                                <button type="submit" name="action" value="accept" class="btn btn-success btn-sm me-2" onclick="return confirm('Chấp thuận hệ thống tự động thiết lập thời hạn Hợp Đồng mới?');">
                                    <i class="fa-solid fa-check me-1"></i>Đồng ý
                                </button>
                                
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Khước từ Gia Hạn - Khách sẽ phải dời đi theo Hết hạn cũ?');">
                                    <i class="fa-solid fa-xmark me-1"></i>Từ chối
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($listWait)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Zero pending requests. Không có yêu cầu gia hạn đang chờ duyệt.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
