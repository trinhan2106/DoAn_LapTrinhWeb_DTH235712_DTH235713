<?php
/**
 * modules/phong/phong_them.php
 * Giao diện thêm mới Phòng - Hệ thống Quản lý Cao ốc
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

// 2. LẤY DỮ LIỆU PHỤC VỤ FORM
try {
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
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    error_log("[" . basename(__FILE__) . "] Lỗi DB: " . $e->getMessage());
    $_SESSION['error_msg'] = "Đã xảy ra lỗi hệ thống. Vui lòng liên hệ quản trị viên.";
    header("Location: " . BASE_URL . "modules/dashboard/admin.php");
    exit();
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
        .calc-box { background-color: #f8f9fa; border: 2px dashed #c9a66b; padding: 1.5rem; border-radius: 8px; }
        .readonly-val { font-size: 1.5rem; font-weight: 800; color: #c9a66b; }
        .text-navy { color: #1e3a5f !important; }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <nav aria-label="breadcrumb" class="mb-4 d-flex justify-content-center">
                <ol class="breadcrumb mb-0" style="width: 1000px;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="phong_hienthi.php" class="text-decoration-none">Quản lý Phòng</a></li>
                    <li class="breadcrumb-item active">Thêm phòng mới</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header">
                    <h2 class="h4 mb-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>THÊM PHÒNG MỚI</h2>
                    <p class="mb-0 text-white-50 small mt-1">Thiết kế không gian làm việc chuyên nghiệp.</p>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form action="phong_them_submit.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <!-- Section 1: Basic Info -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-info-circle me-2"></i>Thông Tin Cơ Bản</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="maCaoOc" class="form-label fw-bold">Tòa nhà <span class="text-danger">*</span></label>
                                <select id="maCaoOc" class="form-select py-2" required>
                                    <option value="">-- Chọn Cao ốc --</option>
                                    <?php foreach ($dsCaoOc as $co): ?>
                                        <option value="<?= e($co['maCaoOc']) ?>"><?= e($co['tenCaoOc']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maTang" class="form-label fw-bold">Tầng <span class="text-danger">*</span></label>
                                <select name="maTang" id="maTang" class="form-select py-2" required disabled>
                                    <option value="">-- Chọn tòa nhà trước --</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="tenPhong" class="form-label fw-bold">Tên Phòng <span class="text-danger">*</span></label>
                                <input type="text" name="tenPhong" id="tenPhong" class="form-control py-2" placeholder="Ví dụ: P-301, Văn phòng 4B" required>
                            </div>
                            <div class="col-md-4">
                                <label for="loaiPhong" class="form-label fw-bold">Loại Phòng</label>
                                <select name="loaiPhong" id="loaiPhong" class="form-select py-2">
                                    <option value="Văn phòng">Văn phòng</option>
                                    <option value="Chỗ ngồi linh hoạt">Chỗ ngồi linh hoạt</option>
                                    <option value="Phòng họp">Phòng họp</option>
                                    <option value="Trọn gói">Trọn gói</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section 2: Technical & Pricing -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-calculator me-2"></i>Thông Số & Đơn Giá</h5>
                        <div class="row g-4 mb-5 p-4 bg-light rounded-3">
                            <div class="col-md-4">
                                <label for="dienTich" class="form-label fw-bold">Diện tích (m²) <span class="text-danger">*</span></label>
                                <input type="number" name="dienTich" id="dienTich" class="form-control py-2 calc-trigger" step="0.1" min="0.1" value="0.0" required>
                            </div>
                            <div class="col-md-4">
                                <label for="soChoLamViec" class="form-label fw-bold">Số chỗ làm việc</label>
                                <input type="number" name="soChoLamViec" id="soChoLamViec" class="form-control py-2" min="1" value="1">
                            </div>
                            <div class="col-md-4">
                                <label for="donGiaM2" class="form-label fw-bold">Đơn giá / m² <span class="text-danger">*</span></label>
                                <input type="number" name="donGiaM2" id="donGiaM2" class="form-control py-2 calc-trigger" min="0" value="0" required>
                            </div>
                            
                            <!-- Calculated Area -->
                            <div class="col-12">
                                <div class="calc-box mt-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-7">
                                            <div class="small text-muted text-uppercase fw-bold">Giá thuê dự kiến (vnđ/tháng)</div>
                                            <div id="giaThueDisplay" class="readonly-val">0</div>
                                            <input type="hidden" name="giaThue" id="giaThue" value="0">
                                        </div>
                                        <div class="col-md-5 text-md-end border-md-start">
                                            <div class="small text-muted">Công thức: Diện tích x Đơn giá x Hệ số tầng</div>
                                            <div class="small fw-bold text-navy mt-1">Hệ số tầng hiện tại: <span id="heSoDisplay">1.00</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Gallery -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-images me-2"></i>Hình ảnh (Gallery)</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-12">
                                <label class="form-label fw-bold">Tải lên hình ảnh</label>
                                <input type="file" name="hinhAnh[]" id="hinhAnh" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text mt-2">Được phép chọn nhiều file (Tối đa 2MB mỗi file). Định dạng: JPG, PNG, WEBP.</div>
                            </div>
                        </div>

                        <div class="col-12 pt-4 d-flex justify-content-end gap-2 border-top">
                            <a href="phong_hienthi.php" class="btn btn-outline-secondary px-4 py-2">Hủy bỏ</a>
                            <button type="submit" class="btn btn-gold px-5 py-2">Xác nhận Thêm</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<script>
// Dữ liệu tầng truyền từ PHP
const TANG_DATA = <?= json_encode($dsTangJS) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const selectCaoOc = document.getElementById('maCaoOc');
    const selectTang = document.getElementById('maTang');
    const inputDienTich = document.getElementById('dienTich');
    const inputDonGia = document.getElementById('donGiaM2');
    const inputGiaThue = document.getElementById('giaThue');
    const displayGiaThue = document.getElementById('giaThueDisplay');
    const displayHeSo = document.getElementById('heSoDisplay');

    // 1. Logic lọc tầng theo cao ốc
    selectCaoOc.addEventListener('change', function() {
        const maCO = this.value;
        selectTang.innerHTML = '<option value="">-- Chọn tầng --</option>';
        
        if (maCO) {
            const listTang = TANG_DATA.filter(t => t.maCaoOc === maCO);
            listTang.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.maTang;
                opt.textContent = t.tenTang;
                opt.dataset.heso = t.heSoGia;
                selectTang.appendChild(opt);
            });
            selectTang.disabled = false;
        } else {
            selectTang.disabled = true;
        }
        updatePrice();
    });

    // 2. Logic tính giá
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

    selectTang.addEventListener('change', updatePrice);
    inputDienTich.addEventListener('input', updatePrice);
    inputDonGia.addEventListener('input', updatePrice);
});
</script>

</body>
</html>
