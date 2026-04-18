<?php
// modules/phong/phong_hienthi.php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';

// Xác thực an ninh truy cập
kiemTraSession();

// Lấy Database Connection instance
$pdo = Database::getInstance()->getConnection();

try {
    /** 
     * Truy vấn danh sách Phòng
     * Tuân thủ kiến trúc: Chỉ lấy dữ liệu chưa bị Trash (Soft Delete = NULL)
     * JOIN với 2 bảng TANG và CAO_OC để lấy thông tin mô tả chi tiết của Tầng và Tòa Nhà.
     * Chú ý dùng COALESCE() hoặc alias cẩn thận để chống duplicate name.
     */
    $sql = "
        SELECT 
            p.maPhong, p.tenPhong, p.dienTich, p.soChoLamViec, p.donGiaM2, p.giaThue, p.trangThai,
            t.tenTang, t.heSoGia,
            c.tenCaoOc
        FROM PHONG p
        INNER JOIN TANG t ON p.maTang = t.maTang
        INNER JOIN CAO_OC c ON t.maCaoOc = c.maCaoOc
        WHERE p.deleted_at IS NULL
        ORDER BY t.tenTang ASC, p.maPhong ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $danhSachPhong = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Lỗi tải danh sách Phòng: " . $e->getMessage());
    die("Xảy ra lỗi khi tải danh sách. Vui lòng thử lại sau.");
}

// Map Trạng Thái Text
function getTextTrangThai($tt) {
    switch ($tt) {
        case 1: return '<span class="badge bg-success">Phòng Trống</span>';
        case 2: return '<span class="badge bg-danger">Đã Cho Thuê</span>';
        case 3: return '<span class="badge bg-warning text-dark">Đang Sửa Chữa</span>';
        case 4: return '<span class="badge bg-secondary">Bị Khóa (Locked)</span>';
        default: return '<span class="badge bg-light text-dark">Không Rõ</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Danh Sách Phòng</title>
    <!-- Phân Hệ CSS Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* CSS Gốc Thương Hiệu */
        :root {
            --primary: #1e3a5f;
            --accent: #c9a66b;
            --bg-color: #f4f7f9;
            --text-color: #1f2a44;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border-left: 5px solid var(--primary);
        }

        .table-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .table-custom thead {
            background-color: #f8f9fa;
        }
        
        .table-custom th {
            color: var(--primary);
            font-weight: 700;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }

        .table-custom td {
            vertical-align: middle;
            color: var(--text-color);
        }

        .btn-custom {
            background-color: var(--primary);
            color: #fff;
            font-weight: 600;
        }

        .btn-custom:hover {
            background-color: var(--accent);
            color: var(--text-color);
        }

        /* Action Buttons */
        .btn-action {
            width: 32px; height: 32px;
            padding: 0;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    
    <!-- KHU VỰC HEADER -->
    <div class="header-box">
        <h4 class="m-0 fw-bold" style="color: var(--primary)">
            <i class="fa-solid fa-list-check me-2"></i> DANH SÁCH KHÔNG GIAN PHÒNG
        </h4>
        <a href="phong_them.php" class="btn btn-custom">
            <i class="fa-solid fa-plus me-1"></i> Thêm Phòng Mới
        </a>
    </div>

    <!-- KHÔNG GIAN THÔNG BÁO FLASH (GET MESSAGE) -->
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] === 'add_success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-check-circle me-1"></i> Thêm phòng thành công!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] === 'delete_success'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-trash-can me-1"></i> Phòng đã được khoá (Soft Delete) an toàn.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- KHU VỰC RENDER TABLE DỮ LIỆU -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">STT</th>
                        <th width="12%">Mã Phòng</th>
                        <th width="15%">Thuộc Tòa / Tầng</th>
                        <th width="10%">Diện Tích</th>
                        <th width="10%">Số Chỗ</th>
                        <th width="15%">Hệ số (Tầng) / Đơn Giá</th>
                        <th width="13%">Giá Thuê (Thực Tính)</th>
                        <th width="10%">Trạng Thái</th>
                        <th width="10%" class="text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($danhSachPhong) > 0): ?>
                        <?php $stt = 1; foreach ($danhSachPhong as $row): ?>
                            <tr>
                                <td class="fw-bold text-muted"><?= $stt++ ?></td>
                                <td>
                                    <span class="fw-bold" style="color: var(--primary)"><?= htmlspecialchars($row['maPhong']) ?></span><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['tenPhong']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($row['tenTang']) ?></strong><br>
                                    <small class="text-muted"><i class="fa-regular fa-building me-1"></i><?= htmlspecialchars($row['tenCaoOc']) ?></small>
                                </td>
                                <td><?= number_format($row['dienTich'], 1) ?> m²</td>
                                <td><?= (int)$row['soChoLamViec'] ?> Chỗ</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        HS: <?= number_format($row['heSoGia'], 2) ?>
                                    </span>
                                    <br>
                                    <small class="text-danger fw-bold"><?= number_format($row['donGiaM2'], 0) ?> đ/m²</small>
                                </td>
                                <td class="fw-bold" style="color: var(--primary); font-size: 1.1rem;">
                                    <?= number_format($row['giaThue'], 0) ?> VNĐ
                                </td>
                                <td>
                                    <?= getTextTrangThai($row['trangThai']) ?>
                                </td>
                                <td class="text-center">
                                    <a href="phong_sua.php?maPhong=<?= urlencode($row['maPhong']) ?>" class="btn btn-outline-primary btn-sm btn-action me-1" title="Sửa">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <!-- Xác nhận Cảnh Báo JS trước khi Soft Delete -->
                                    <a href="phong_xoa.php?maPhong=<?= urlencode($row['maPhong']) ?>" 
                                       class="btn btn-outline-danger btn-sm btn-action" 
                                       title="Xóa mềm"
                                       onclick="return confirm('Bạn có chắc chắn muốn ngưng hoạt động phòng CSDL này không?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Hệ thống CSDL hiện tại chưa ghi nhận dữ liệu Phòng nào đang hoạt động.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
