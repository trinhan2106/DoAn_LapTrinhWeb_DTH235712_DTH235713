<?php
/**
 * modules/phong/phong_sua.php
 * Giao diện chỉnh sửa Phòng - Hệ thống Quản lý Cao ốc
 */

// 1. KHỞI TẠO & BẢO MẬT
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Xác thực Session & Phân quyền
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

$db = Database::getInstance()->getConnection();

// 2. LẤY DỮ LIỆU PHÒNG
$maPhong = $_GET['id'] ?? '';
if (empty($maPhong)) {
    $_SESSION['error_msg'] = "Mã phòng không hợp lệ.";
    header("Location: phong_hienthi.php");
    exit();
}

try {
    // Chi tiết phòng kèm tòa nhà
    $stmt = $db->prepare("
        SELECT p.*, t.maCaoOc 
        FROM PHONG p 
        JOIN TANG t ON p.maTang = t.maTang 
        WHERE p.maPhong = ? AND p.deleted_at IS NULL
    ");
    $stmt->execute([$maPhong]);
    $phong = $stmt->fetch();

    if (!$phong) {
        $_SESSION['error_msg'] = "Không tìm thấy thông tin phòng.";
        header("Location: phong_hienthi.php");
        exit();
    }

    // Danh sách ảnh hiện tại
    $stmtImg = $db->prepare("SELECT id, urlHinhAnh, is_thumbnail FROM PHONG_HINH_ANH WHERE maPhong = ? ORDER BY is_thumbnail DESC");
    $stmtImg->execute([$maPhong]);
    $currentImages = $stmtImg->fetchAll();

    // Dữ liệu cho Dropdown
    $dsCaoOc = $db->query("SELECT maCaoOc, tenCaoOc FROM CAO_OC WHERE deleted_at IS NULL ORDER BY tenCaoOc")->fetchAll();
    $dsTang = $db->query("SELECT maTang, tenTang, maCaoOc, heSoGia FROM TANG WHERE deleted_at IS NULL ORDER BY tenTang")->fetchAll();
    
    // Map dữ liệu Tầng sang JS để xử lý Dynamic Select
    $dsTangJS = [];
    foreach ($dsTang as $t) {
        $dsTangJS[] = [
            'maTang' => $t['maTang'],
            'tenTang' => $t['tenTang'],
            'maCaoOc' => $t['maCaoOc'],
            'heSoGia' => (float)$t['heSoGia']
        ];
    }
} catch (PDOException $e) {
    die("Lỗi Database: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .form-card { max-width: 1000px; margin: 0 auto; border-radius: 12px; overflow: hidden; }
        .form-header { background-color: #1e3a5f; color: white; padding: 1.5rem; }
        .btn-gold { background-color: #c9a66b; color: white; font-weight: 600; padding: 0.6rem 2.5rem; border: none; transition: 0.3s; }
        .btn-gold:hover { background-color: #b5925a; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(201, 166, 107, 0.3); }
        .form-label { font-weight: 600; color: #1e3a5f; }
        .calc-box { background-color: #f8f9fa; border: 2px dashed #c9a66b; padding: 1.5rem; border-radius: 8px; }
        .readonly-val { font-size: 1.5rem; font-weight: 800; color: #c9a66b; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
        .gallery-item { position: relative; border-radius: 8px; overflow: hidden; height: 120px; border: 2px solid #eee; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        .delete-check { position: absolute; top: 5px; right: 5px; z-index: 10; width: 20px; height: 20px; cursor: pointer; }
        .thumbnail-badge { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(30, 58, 95, 0.8); color: white; font-size: 0.65rem; text-align: center; padding: 2px; }
        .delete-overlay { position: absolute; inset: 0; background: rgba(231, 76, 60, 0.4); display: none; align-items: center; justify-content: center; color: white; font-size: 1.5rem; pointer-events: none; }
        .delete-check:checked ~ .delete-overlay { display: flex; }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>CẬP NHẬT THÔNG TIN PHÒNG</h2>
                        <p class="mb-0 text-white-50 small mt-1">Sửa đổi và cập nhật cấu hình không gian thuê.</p>
                    </div>
                    <span class="badge bg-white text-primary px-3 py-2 fw-bold text-navy"><?= e($phong['maPhong']) ?></span>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form action="phong_sua_submit.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="maPhong" value="<?= e($phong['maPhong']) ?>">

                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-info-circle me-2"></i>Thông Tin Cơ Bản</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="maCaoOc" class="form-label">Tòa nhà chủ quản <span class="text-danger">*</span></label>
                                <select id="maCaoOc" class="form-select py-2" required>
                                    <option value="">-- Chọn Cao ốc --</option>
                                    <?php foreach ($dsCaoOc as $co): ?>
                                        <option value="<?= e($co['maCaoOc']) ?>" <?= $co['maCaoOc'] == $phong['maCaoOc'] ? 'selected' : '' ?>>
                                            <?= e($co['tenCaoOc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maTang" class="form-label">Vị trí Tầng <span class="text-danger">*</span></label>
                                <select name="maTang" id="maTang" class="form-select py-2" required>
                                    <option value="">-- Chọn tầng --</option>
                                    <!-- Dynamic Tầng -->
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="tenPhong" class="form-label">Tên Phòng <span class="text-danger">*</span></label>
                                <input type="text" name="tenPhong" id="tenPhong" class="form-control py-2" value="<?= e($phong['tenPhong']) ?>" placeholder="VD: Suite 501 - Góc Phố" required>
                            </div>
                            <div class="col-md-6">
                                <label for="loaiPhong" class="form-label">Loại Phòng</label>
                                <select name="loaiPhong" id="loaiPhong" class="form-select py-2">
                                    <option value="Văn phòng hạng A" <?= $phong['loaiPhong'] == 'Văn phòng hạng A' ? 'selected' : '' ?>>Văn phòng hạng A</option>
                                    <option value="Văn phòng hạng B" <?= $phong['loaiPhong'] == 'Văn phòng hạng B' ? 'selected' : '' ?>>Văn phòng hạng B</option>
                                    <option value="Văn phòng hạng C" <?= $phong['loaiPhong'] == 'Văn phòng hạng C' ? 'selected' : '' ?>>Văn phòng hạng C</option>
                                    <option value="Phòng họp" <?= $phong['loaiPhong'] == 'Phòng họp' ? 'selected' : '' ?>>Phòng họp</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="trangThai" class="form-label">Trạng Thái</label>
                                <select name="trangThai" id="trangThai" class="form-select py-2">
                                    <option value="1" <?= $phong['trangThai'] == 1 ? 'selected' : '' ?>>Trống</option>
                                    <option value="2" <?= $phong['trangThai'] == 2 ? 'selected' : '' ?>>Đã thuê</option>
                                    <option value="3" <?= $phong['trangThai'] == 3 ? 'selected' : '' ?>>Bảo trì</option>
                                    <option value="4" <?= $phong['trangThai'] == 4 ? 'selected' : '' ?>>Đã khóa</option>
                                </select>
                            </div>
                        </div>

                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-calculator me-2"></i>Cấu Hình Diện Tích & Giá</h5>
                        <div class="row g-4 mb-5 p-4 bg-light rounded-3">
                            <div class="col-md-4">
                                <label for="dienTich" class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                                <input type="number" name="dienTich" id="dienTich" class="form-control py-2 calc-trigger" step="0.1" min="0.1" value="<?= e($phong['dienTich']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="soChoLamViec" class="form-label">Số chỗ làm việc</label>
                                <input type="number" name="soChoLamViec" id="soChoLamViec" class="form-control py-2" min="1" value="<?= e($phong['soChoLamViec']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="donGiaM2" class="form-label">Đơn giá / m² <span class="text-danger">*</span></label>
                                <input type="number" name="donGiaM2" id="donGiaM2" class="form-control py-2 calc-trigger" min="0" value="<?= e($phong['donGiaM2']) ?>" required>
                            </div>
                            
                            <div class="col-12">
                                <div class="calc-box mt-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-7 border-md-end">
                                            <div class="small text-muted text-uppercase fw-bold">Giá thuê hàng tháng (vnđ)</div>
                                            <div id="giaThueDisplay" class="readonly-val"><?= number_format($phong['giaThue'], 0, ',', '.') ?></div>
                                            <input type="hidden" name="giaThue" id="giaThue" value="<?= e($phong['giaThue']) ?>">
                                        </div>
                                        <div class="col-md-5 ps-md-4">
                                            <div class="small text-muted">Hệ số tầng hiện tại: <span id="heSoDisplay" class="fw-bold text-navy"><?= number_format($phong['heSoGia'], 2) ?></span></div>
                                            <div class="small text-muted mt-1">Giá thuê = Diện tích x Đơn giá x Hệ số</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-images me-2"></i>Quản Lý Hình Ảnh</h5>
                        <div class="mb-5">
                            <label class="form-label d-block mb-3">Hình ảnh hiện tại (Tích chọn để xóa)</label>
                            <?php if (empty($currentImages)): ?>
                                <div class="alert alert-light border text-center py-4 rounded-3">
                                    <i class="bi bi-image text-muted d-block h1"></i>
                                    <span class="text-muted">Chưa có hình ảnh nào cho phòng này.</span>
                                </div>
                            <?php else: ?>
                                <div class="gallery-grid mb-4">
                                    <?php foreach ($currentImages as $img): ?>
                                        <div class="gallery-item">
                                            <input type="checkbox" name="delete_images[]" value="<?= e($img['id']) ?>" class="delete-check" title="Chọn để xóa">
                                            <img src="<?= BASE_URL . e($img['urlHinhAnh']) ?>" alt="Room Image">
                                            <?php if ($img['is_thumbnail']): ?>
                                                <div class="thumbnail-badge">ẢNH ĐẠI DIỆN</div>
                                            <?php endif; ?>
                                            <div class="delete-overlay"><i class="bi bi-trash"></i></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4 p-4 border rounded-3 bg-white">
                                <label for="hinhAnh" class="form-label fw-bold"><i class="bi bi-cloud-upload me-2"></i>Thêm hình ảnh mới</label>
                                <input type="file" name="hinhAnh[]" id="hinhAnh" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text mt-2">Định dạng hỗ trợ: JPG, PNG, WEBP (Tối đa 2MB/file).</div>
                            </div>
                        </div>

                        <div class="col-12 pt-4 d-flex justify-content-end gap-2 border-top">
                            <a href="phong_hienthi.php" class="btn btn-outline-secondary px-4 py-2">Hủy bỏ</a>
                            <button type="submit" class="btn btn-gold px-5 py-2"><i class="bi bi-save me-2"></i>Cập nhật dữ liệu</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<script>
const TANG_DATA = <?= json_encode($dsTangJS) ?>;
const currentTangId = "<?= e($phong['maTang']) ?>";

document.addEventListener('DOMContentLoaded', function() {
    const selectCaoOc = document.getElementById('maCaoOc');
    const selectTang = document.getElementById('maTang');
    const inputDienTich = document.getElementById('dienTich');
    const inputDonGia = document.getElementById('donGiaM2');
    const inputGiaThue = document.getElementById('giaThue');
    const displayGiaThue = document.getElementById('giaThueDisplay');
    const displayHeSo = document.getElementById('heSoDisplay');

    function updateTangList(maCO, selectedId = null) {
        selectTang.innerHTML = '<option value="">-- Chọn tầng --</option>';
        if (maCO) {
            const listTang = TANG_DATA.filter(t => t.maCaoOc === maCO);
            listTang.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.maTang;
                opt.textContent = t.tenTang;
                opt.dataset.heso = t.heSoGia;
                if (selectedId && t.maTang === selectedId) opt.selected = true;
                selectTang.appendChild(opt);
            });
            selectTang.disabled = false;
        } else {
            selectTang.disabled = true;
        }
    }

    function updatePrice() {
        const option = selectTang.options[selectTang.selectedIndex];
        const heSo = option ? (parseFloat(option.dataset.heso) || 1.0) : 1.0;
        const dienTich = parseFloat(inputDienTich.value) || 0;
        const donGia = parseFloat(inputDonGia.value) || 0;
        
        const total = Math.round(dienTich * donGia * heSo);
        
        inputGiaThue.value = total;
        displayGiaThue.textContent = total.toLocaleString('vi-VN');
        displayHeSo.textContent = heSo.toFixed(2);
    }

    updateTangList(selectCaoOc.value, currentTangId);
    
    selectCaoOc.addEventListener('change', function() {
        updateTangList(this.value);
        updatePrice();
    });

    selectTang.addEventListener('change', updatePrice);
    inputDienTich.addEventListener('input', updatePrice);
    inputDonGia.addEventListener('input', updatePrice);
});
</script>

</body>
</html>
