<?php
// modules/hop_dong/hd_chitiet.php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();

$soHD = trim($_GET['id'] ?? '');
if (empty($soHD)) {
    die("Thiếu mã hợp đồng.");
}

$pdo = Database::getInstance()->getConnection();

try {
    $stmtHD = $pdo->prepare("
        SELECT h.*, k.tenKH, k.sdt, k.cccd, k.diaChi, k.email, n.tenNV
        FROM HOP_DONG h
        INNER JOIN KHACH_HANG k ON h.maKH = k.maKH
        INNER JOIN NHAN_VIEN n ON h.maNV = n.maNV
        WHERE h.soHopDong = :id AND h.deleted_at IS NULL
    ");
    $stmtHD->execute([':id' => $soHD]);
    $hd = $stmtHD->fetch(PDO::FETCH_ASSOC);

    if (!$hd) {
        die("Hợp đồng không tồn tại hoặc đã bị xóa.");
    }

    $stmtPh = $pdo->prepare("
        SELECT c.maPhong, c.giaThue, c.trangThai as tt_cthd, p.tenPhong, p.dienTich
        FROM CHI_TIET_HOP_DONG c
        JOIN PHONG p ON c.maPhong = p.maPhong
        WHERE c.soHopDong = :id
    ");
    $stmtPh->execute([':id' => $soHD]);
    $listPhong = $stmtPh->fetchAll(PDO::FETCH_ASSOC);

    $stmtCoc = $pdo->prepare("
        SELECT maTienCoc, soTien, ngayNop, phuongThuc, trangThai
        FROM TIEN_COC
        WHERE soHopDong = :id
        ORDER BY ngayNop DESC
        LIMIT 1
    ");
    $stmtCoc->execute([':id' => $soHD]);
    $coc = $stmtCoc->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi truy vấn: " . htmlspecialchars($e->getMessage()));
}

function badgeTrangThai($tt) {
    switch ((int)$tt) {
        case 3: return '<span class="badge bg-warning text-dark fs-6"><i class="fa-solid fa-hourglass-half me-1"></i>Chờ Ký</span>';
        case 1: return '<span class="badge bg-success fs-6"><i class="fa-solid fa-circle-check me-1"></i>Đang Hiệu Lực</span>';
        case 4: return '<span class="badge bg-info text-dark fs-6"><i class="fa-solid fa-arrow-up-right-dots me-1"></i>Đã Gia Hạn</span>';
        case 0: return '<span class="badge bg-secondary fs-6"><i class="fa-solid fa-flag-checkered me-1"></i>Đã Kết Thúc</span>';
        case 2: return '<span class="badge bg-danger fs-6"><i class="fa-solid fa-ban me-1"></i>Đã Hủy</span>';
        default: return '<span class="badge bg-light text-dark fs-6">Không xác định</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Hợp Đồng - <?= htmlspecialchars($soHD) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --navy: #1e3a5f; --gold: #c9a66b; }
        body { background-color: #f4f7f9; }
        .page-header { background: var(--navy); border-bottom: 4px solid var(--gold); }
        .section-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 0.5rem; }
        .info-value { font-weight: 600; color: #1e293b; }
        .card-section { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
        .card-section .card-header { background: var(--navy); color: #fff; padding: 12px 20px; font-weight: 700; }
        .card-section .card-header i { color: var(--gold); }
        .table th { background: #f1f5f9; color: var(--navy); font-size: 0.85rem; }
    </style>
</head>
<body class="p-4">
<div class="container" style="max-width: 960px;">

    <!-- Header -->
    <div class="page-header rounded-3 p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="text-white fw-bold mb-1">
                <i class="fa-solid fa-file-contract me-2" style="color: var(--gold);"></i>
                CHI TIẾT HỢP ĐỒNG
            </h4>
            <div class="text-white-50 small">Số đăng ký: <strong class="text-white"><?= htmlspecialchars($soHD) ?></strong></div>
        </div>
        <div><?= badgeTrangThai($hd['trangThai']) ?></div>
    </div>

    <div class="row g-4">
        <!-- Thông tin hợp đồng -->
        <div class="col-md-6">
            <div class="card-section h-100">
                <div class="card-header"><i class="fa-solid fa-calendar-days me-2"></i>Thông Tin Hợp Đồng</div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="section-title">Ngày lập</div>
                            <div class="info-value"><?= date('d/m/Y', strtotime($hd['ngayLap'])) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">Ngày bắt đầu</div>
                            <div class="info-value"><?= date('d/m/Y', strtotime($hd['ngayBatDau'])) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">Ngày kết thúc</div>
                            <div class="info-value text-danger"><?= date('d/m/Y', strtotime($hd['ngayKetThuc'])) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">Tiền cọc</div>
                            <div class="info-value text-success"><?= number_format($hd['tienTienCoc'], 0, ',', '.') ?> ₫</div>
                        </div>
                        <div class="col-12">
                            <div class="section-title">Nhân viên lập</div>
                            <div class="info-value"><?= htmlspecialchars($hd['tenNV']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin khách hàng -->
        <div class="col-md-6">
            <div class="card-section h-100">
                <div class="card-header"><i class="fa-solid fa-user-tie me-2"></i>Bên B - Khách Hàng</div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="section-title">Tên khách hàng</div>
                            <div class="info-value fs-5"><?= htmlspecialchars($hd['tenKH']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">CCCD / Mã Cty</div>
                            <div class="info-value"><?= htmlspecialchars($hd['cccd'] ?? 'N/A') ?></div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">Số điện thoại</div>
                            <div class="info-value"><?= htmlspecialchars($hd['sdt']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="section-title">Địa chỉ</div>
                            <div class="info-value"><?= htmlspecialchars($hd['diaChi'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách phòng -->
        <div class="col-12">
            <div class="card-section">
                <div class="card-header"><i class="fa-solid fa-building me-2"></i>Danh Sách Phòng Thuê</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Mã phòng</th>
                                <th>Tên phòng</th>
                                <th class="text-end">Diện tích</th>
                                <th class="text-end pe-4">Giá thuê / tháng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listPhong)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Không có phòng nào trong hợp đồng này.</td></tr>
                            <?php else: ?>
                                <?php $tongGia = 0; foreach ($listPhong as $p): $tongGia += $p['giaThue']; ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($p['maPhong']) ?></td>
                                        <td><?= htmlspecialchars($p['tenPhong'] ?? 'N/A') ?></td>
                                        <td class="text-end"><?= $p['dienTich'] ?> m²</td>
                                        <td class="text-end pe-4 fw-bold text-success"><?= number_format($p['giaThue'], 0, ',', '.') ?> ₫</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="3" class="ps-4 text-end">Tổng giá thuê / tháng:</td>
                                    <td class="text-end pe-4 text-danger fs-5"><?= number_format($tongGia, 0, ',', '.') ?> ₫</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Thông tin tiền cọc -->
        <?php if ($coc): ?>
        <div class="col-md-6">
            <div class="card-section">
                <div class="card-header"><i class="fa-solid fa-piggy-bank me-2"></i>Thông Tin Tiền Cọc</div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="section-title">Mã phiếu cọc</div>
                            <div class="info-value"><code><?= htmlspecialchars($coc['maTienCoc']) ?></code></div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">Số tiền</div>
                            <div class="info-value text-success"><?= number_format($coc['soTien'], 0, ',', '.') ?> ₫</div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">Ngày nộp</div>
                            <div class="info-value"><?= date('d/m/Y', strtotime($coc['ngayNop'])) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="section-title">Phương thức</div>
                            <div class="info-value"><?= htmlspecialchars($coc['phuongThuc'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top flex-wrap gap-2">
        <a href="hd_hienthi.php" class="btn btn-outline-secondary px-4">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay Lại Danh Sách
        </a>
        <div class="d-flex gap-2">
            <?php if ((int)$hd['trangThai'] === 3): ?>
                <a href="hd_ky.php?id=<?= urlencode($soHD) ?>" class="btn btn-success px-4 fw-bold">
                    <i class="fa-solid fa-signature me-2"></i>Ký Duyệt Hợp Đồng
                </a>
            <?php endif; ?>
            <?php if (in_array((int)$hd['trangThai'], [1, 4])): ?>
                <a href="hd_gia_han.php?soHopDong=<?= urlencode($soHD) ?>" class="btn btn-outline-info px-4 fw-bold">
                    <i class="fa-solid fa-timeline me-2"></i>Gia Hạn
                </a>
                <a href="hd_huy.php?id=<?= urlencode($soHD) ?>" class="btn btn-outline-danger px-4">
                    <i class="fa-solid fa-ban me-2"></i>Hủy Hợp Đồng
                </a>
            <?php endif; ?>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
