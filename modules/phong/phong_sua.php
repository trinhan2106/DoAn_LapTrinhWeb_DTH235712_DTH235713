<?php
// modules/phong/phong_sua.php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

// Lấy Mã Phòng truyền trên URL tham số
$maPhong = trim($_GET['maPhong'] ?? '');

// Kích hoạt chặn truy cập URL tay không
if(empty($maPhong)) {
    header("Location: phong_hienthi.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

// FETCH 1: Lấy lại Dữ Liệu cũ của Phòng khớp vào HTML Input Form
try {
    $stmtPhong = $pdo->prepare("SELECT * FROM PHONG WHERE maPhong = :id AND deleted_at IS NULL");
    $stmtPhong->execute([':id' => $maPhong]);
    $phongData = $stmtPhong->fetch(PDO::FETCH_ASSOC);

    if (!$phongData) {
        // Cố tình sửa URL maPhong ảo không tồn tại, hoặc phòng đã bị soft-delete
        header("Location: phong_hienthi.php?err=notfound");
        exit();
    }
} catch (Exception $e) {
    die("Lỗi kết nối truy vấn dữ liệu phòng: " . $e->getMessage());
}

// FETCH 2: Lấy List Box Tầng giả lập để render lại <select>
try {
    $stmtTang = $pdo->prepare("SELECT maTang, tenTang, COALESCE(heSoGia, 1.0) as heSoGia FROM TANG WHERE deleted_at IS NULL ORDER BY tenTang ASC");
    $stmtTang->execute();
    $listTang = $stmtTang->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Lỗi lấy danh sách Tầng: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Thông Tin Phòng</title>
    <!-- Mốc thư viện Giao Diện Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
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
            padding: 30px 15px;
        }

        .form-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            max-width: 900px;
            margin: auto;
            border-top: 5px solid var(--accent); /* Đổi viền Highlight Vàng để phân biệt module Sửa */
        }

        .form-title {
            color: var(--primary);
            font-weight: 800;
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-color);
        }

        .control-readonly {
            background-color: #e9ecef;
            border: 2px dashed #b02a37; /* Cảnh báo Đỏ vì khoá sửa */
            color: #b02a37;
            font-weight: 800;
            font-size: 1.1rem;
        }

        .btn-submit {
            background-color: var(--primary);
            color: #fff;
            font-weight: 700;
            padding: 10px 25px;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background-color: var(--accent);
            color: var(--text-color);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-card">
        <h4 class="form-title">
            <i class="fa-solid fa-pen-to-square me-2"></i> CHỈNH SỬA VĂN PHÒNG: <?= htmlspecialchars($phongData['maPhong']) ?>
        </h4>

        <form action="phong_sua_submit.php" method="POST">
            <!-- Neo biến CSRF -->
            <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">
            
            <!-- Trừu tượng hóa maPhong thật để đẩy qua POST, che giấu ko cho sửa ở View DSN -->
            <input type="hidden" name="maPhong" value="<?= htmlspecialchars($phongData['maPhong']) ?>">

            <!-- KHU VỰC 1: ĐỊNH DANH PHÒNG -->
            <h6 class="text-secondary fw-bold mb-3 mt-2"><i class="fa-solid fa-tag me-1"></i> Định danh Văn Phòng</h6>
            <div class="row g-4 mb-4 pb-4 border-bottom">
                <div class="col-md-6">
                    <label class="form-label">Mã Phòng Định Danh (Read Only)</label>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($phongData['maPhong']) ?>" disabled>
                    <small class="text-danger">Nguyên tắc: Không được phép sửa mã định danh cấp 1.</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Thuộc Vị Trí Tầng <span class="text-danger">*</span></label>
                    <select class="form-select" name="maTang" id="frm_maTang" onchange="thuattoanTinhGiaTreoRealtime()" required>
                        <option value="" data-hesogia="0">-- Chọn tầng tòa nhà --</option>
                        <?php foreach($listTang as $tang): ?>
                            <!-- Nếu maTang khớp DB, chèn keyword attribute `selected` -->
                            <option value="<?= $tang['maTang'] ?>" 
                                    data-hesogia="<?= $tang['heSoGia'] ?>"
                                    <?= ($phongData['maTang'] === $tang['maTang']) ? 'selected' : '' ?> >
                                Tầng: <?= htmlspecialchars($tang['tenTang']) ?> (Hệ số: <?= $tang['heSoGia'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tên hiển thị phòng</label>
                    <input type="text" class="form-control" name="tenPhong" value="<?= htmlspecialchars($phongData['tenPhong']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Trạng Thái Thực Tế</label>
                    <select class="form-select" name="trangThai">
                        <option value="1" <?= ($phongData['trangThai'] == 1) ? 'selected' : '' ?>>1. Phòng Trống</option>
                        <option value="2" <?= ($phongData['trangThai'] == 2) ? 'selected' : '' ?>>2. Đã Cho Thuê</option>
                        <option value="3" <?= ($phongData['trangThai'] == 3) ? 'selected' : '' ?>>3. Đang Bảo Trì / Sửa Chữa</option>
                        <option value="4" <?= ($phongData['trangThai'] == 4) ? 'selected' : '' ?>>4. Bị Quản Trị Khóa</option>
                    </select>
                </div>
            </div>

            <!-- KHU VỰC 2: KHÔNG GIAN BÀI TRÍ & CHÍNH SÁCH GIÁ -->
            <h6 class="text-secondary fw-bold mb-3 mt-2"><i class="fa-solid fa-coins me-1"></i> Không Gian & Trị Giá Realtime</h6>
            <div class="row g-4 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                    <!-- Tải dữ liệu vào View -->
                    <input type="number" step="0.1" min="0" class="form-control" name="dienTich" id="frm_dienTich" 
                           value="<?= htmlspecialchars($phongData['dienTich']) ?>" oninput="thuattoanTinhGiaTreoRealtime()">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Chỗ Làm Việc Tối Đa</label>
                    <input type="number" min="0" class="form-control" name="soChoLamViec" 
                           value="<?= htmlspecialchars($phongData['soChoLamViec']) ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Đơn Giá M2 Tính Nhanh (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" min="0" step="1000" class="form-control" name="donGiaM2" id="frm_donGiaM2" 
                           value="<?= round($phongData['donGiaM2'], 0) ?>" oninput="thuattoanTinhGiaTreoRealtime()">
                </div>
            </div>

            <!-- Dòng 3 (Render Giá Bị Bắt Buộc Tính Lại ReadOnly) -->
            <div class="row g-4 mt-1 mb-5">
                <div class="col-md-12">
                    <label class="form-label"><i class="fa-solid fa-calculator text-muted me-1"></i> Giá Thuê Tổng Định Kỳ (Chỉ đọc) = [Đơn Giá] x [Diện Tích] x [Hệ Số Tầng]</label>
                    <div class="input-group">
                        <input type="number" class="form-control control-readonly" name="giaThue" id="frm_giaThue" 
                               value="<?= round($phongData['giaThue'], 2) ?>" readonly>
                        <span class="input-group-text fw-bold">VNĐ / THÁNG</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="phong_hienthi.php" class="btn btn-light border"><i class="fa-solid fa-xmark text-danger"></i> Hủy Bỏ</a>
                <button type="submit" class="btn btn-submit"><i class="fa-solid fa-clock-rotate-left me-1"></i> Thực thi Lệnh Cập Nhật (Update)</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * Tái ứng dụng thư viện thuật toán Real-time update Giá Thuê
     */
    function thuattoanTinhGiaTreoRealtime() {
        const dienTichVal = parseFloat(document.getElementById('frm_dienTich').value) || 0;
        const donGiaVal   = parseFloat(document.getElementById('frm_donGiaM2').value) || 0;
        
        const selectTangNode      = document.getElementById('frm_maTang');
        const theOptionDangChon   = selectTangNode.options[selectTangNode.selectedIndex];
        
        let heSoGiaVal = 0;
        if (theOptionDangChon) {
            heSoGiaVal = parseFloat(theOptionDangChon.getAttribute('data-hesogia')) || 0;
        }

        const tinhTongTotal = donGiaVal * dienTichVal * heSoGiaVal;
        document.getElementById('frm_giaThue').value = tinhTongTotal.toFixed(2);
    }
</script>
</body>
</html>
