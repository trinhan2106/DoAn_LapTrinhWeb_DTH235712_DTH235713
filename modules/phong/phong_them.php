<?php
/**
 * modules/phong/phong_them.php
 * Giao diá»‡n thÃªm má»›i PhÃ²ng - TÃ­ch há»£p tÃ­nh giÃ¡ Real-time
 */

// 1. KHá»žI Táº O & Báº¢O Máº¬T
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// XÃ¡c thá»±c Session & PhÃ¢n quyá»n
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Káº¿t ná»‘i CSDL
$db = Database::getInstance()->getConnection();

// Láº¥y danh sÃ¡ch Cao á»‘c
$dsCaoOc = $db->query("SELECT maCaoOc, tenCaoOc FROM CAO_OC WHERE deleted_at IS NULL ORDER BY tenCaoOc")->fetchAll();

// Láº¥y danh sÃ¡ch Táº§ng kÃ¨m há»‡ sá»‘ giÃ¡ Ä‘á»ƒ xá»­ lÃ½ phÃ­a client (JS)
$dsTangRaw = $db->query("SELECT maTang, tenTang, maCaoOc, heSoGia FROM TANG WHERE deleted_at IS NULL ORDER BY tenTang")->fetchAll();
$dsTangJS = [];
foreach ($dsTangRaw as $t) {
    $dsTangJS[] = [
        'maTang'   => $t['maTang'],
        'tenTang'  => $t['tenTang'],
        'maCaoOc'  => $t['maCaoOc'],
        'heSoGia'  => (float)$t['heSoGia']
    ];
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <style>
        .form-card { max-width: 900px; margin: 0 auto; border-radius: 12px; overflow: hidden; }
        .form-header { background-color: #1e3a5f; color: white; padding: 1.5rem; }
        .btn-gold { 
            background-color: #c9a66b; color: white; font-weight: 600; padding: 0.6rem 2.5rem; border: none; transition: 0.3s;
        }
        .btn-gold:hover { background-color: #b5925a; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(201, 166, 107, 0.3); }
        
        .calc-box {
            background-color: #f8f9fa;
            border-left: 4px solid #c9a66b;
            padding: 1.5rem;
            border-radius: 8px;
        }
        .readonly-val { font-size: 1.25rem; font-weight: 800; color: #1e3a5f; }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <nav aria-label="breadcrumb" class="mb-4 d-flex justify-content-center">
                <ol class="breadcrumb mb-0" style="width: 900px;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin_layout.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="phong_hienthi.php" class="text-decoration-none">Quáº£n lÃ½ PhÃ²ng</a></li>
                    <li class="breadcrumb-item active">ThÃªm má»›i</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header">
                    <h2 class="h4 mb-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>THÃŠM PHÃ’NG Má»šI</h2>
                    <p class="mb-0 text-white-50 small mt-1">Vui lÃ²ng thiáº¿t láº­p thÃ´ng sá»‘ ká»¹ thuáº­t vÃ  hÃ¬nh áº£nh mÃ´ táº£ cho phÃ²ng.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="phong_them_submit.php" method="POST" enctype="multipart/form-data" id="formThemPhong">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <!-- Section 1: Location -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-geo-alt me-2"></i>Vá»‹ TrÃ­ & CÆ¡ Báº£n</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="maCaoOc" class="form-label fw-bold">TÃ²a nhÃ  <span class="text-danger">*</span></label>
                                <select id="maCaoOc" class="form-select py-2" required>
                                    <option value="">-- Chá»n tÃ²a nhÃ  --</option>
                                    <?php foreach ($dsCaoOc as $co): ?>
                                        <option value="<?= e($co['maCaoOc']) ?>"><?= e($co['tenCaoOc']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maTang" class="form-label fw-bold">Táº§ng <span class="text-danger">*</span></label>
                                <select name="maTang" id="maTang" class="form-select py-2" required disabled>
                                    <option value="">-- Chá»n tÃ²a nhÃ  trÆ°á»›c --</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="tenPhong" class="form-label fw-bold">TÃªn PhÃ²ng <span class="text-danger">*</span></label>
                                <input type="text" name="tenPhong" id="tenPhong" class="form-control py-2" placeholder="VÃ­ dá»¥: P-301, VÄƒn phÃ²ng 4B" required>
                            </div>
                            <div class="col-md-4">
                                <label for="loaiPhong" class="form-label fw-bold">Loáº¡i PhÃ²ng</label>
                                <select name="loaiPhong" id="loaiPhong" class="form-select py-2">
                                    <option value="VÄƒn phÃ²ng">VÄƒn phÃ²ng</option>
                                    <option value="Chá»— ngá»“i linh hoáº¡t">Chá»— ngá»“i linh hoáº¡t</option>
                                    <option value="PhÃ²ng há»p">PhÃ²ng há»p</option>
                                    <option value="Trá»n gÃ³i">Trá»n gÃ³i</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section 2: Technical & Pricing -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-calculator me-2"></i>ThÃ´ng Sá»‘ & ÄÆ¡n GiÃ¡</h5>
                        <div class="row g-4 mb-5 p-4 bg-light rounded-3">
                            <div class="col-md-4">
                                <label for="dienTich" class="form-label fw-bold">Diá»‡n tÃ­ch (mÂ²) <span class="text-danger">*</span></label>
                                <input type="number" name="dienTich" id="dienTich" class="form-control py-2 calc-trigger" step="0.1" min="0.1" value="0.0" required>
                            </div>
                            <div class="col-md-4">
                                <label for="soChoLamViec" class="form-label fw-bold">Sá»‘ chá»— lÃ m viá»‡c</label>
                                <input type="number" name="soChoLamViec" id="soChoLamViec" class="form-control py-2" min="1" value="1">
                            </div>
                            <div class="col-md-4">
                                <label for="donGiaM2" class="form-label fw-bold">ÄÆ¡n giÃ¡ / mÂ² <span class="text-danger">*</span></label>
                                <input type="number" name="donGiaM2" id="donGiaM2" class="form-control py-2 calc-trigger" min="0" value="0" required>
                            </div>
                            
                            <!-- Calculated Area -->
                            <div class="col-12">
                                <div class="calc-box mt-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-7">
                                            <div class="small text-muted text-uppercase fw-bold">GiÃ¡ thuÃª dá»± kiáº¿n (vnÄ‘/thÃ¡ng)</div>
                                            <div id="giaThueDisplay" class="readonly-val">0</div>
                                            <!-- Hidden giaThue for submit -->
                                            <input type="hidden" name="giaThue" id="giaThue" value="0">
                                        </div>
                                        <div class="col-md-5 text-md-end border-md-start">
                                            <div class="small text-muted">CÃ´ng thá»©c: Diá»‡n tÃ­ch Ã— ÄÆ¡n giÃ¡ Ã— Há»‡ sá»‘ táº§ng</div>
                                            <div class="small fw-bold text-navy mt-1">Há»‡ sá»‘ táº§ng hiá»‡n táº¡i: <span id="heSoDisplay">1.00</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Gallery -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-images me-2"></i>HÃ¬nh áº¢nh (Gallery)</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-12">
                                <label class="form-label fw-bold">Táº£i lÃªn hÃ¬nh áº£nh</label>
                                <input type="file" name="hinhAnh[]" id="hinhAnh" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text mt-2">ÄÆ°á»£c phÃ©p chá»n nhiá»u file (Tá»‘i Ä‘a 2MB má»—i file). Äá»‹nh dáº¡ng: JPG, PNG, WEBP.</div>
                            </div>
                        </div>

                        <div class="col-12 pt-4 d-flex justify-content-end gap-2 border-top">
                            <a href="phong_hienthi.php" class="btn btn-outline-secondary px-4 py-2">Há»§y bá»</a>
                            <button type="submit" class="btn btn-gold px-5 py-2">XÃ¡c nháº­n ThÃªm</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<script>
// Dá»¯ liá»‡u táº§ng truyá»n tá»« PHP
const TANG_DATA = <?= json_encode($dsTangJS) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const selectCaoOc = document.getElementById('maCaoOc');
    const selectTang = document.getElementById('maTang');
    const inputDienTich = document.getElementById('dienTich');
    const inputDonGia = document.getElementById('donGiaM2');
    const inputGiaThue = document.getElementById('giaThue');
    const displayGiaThue = document.getElementById('giaThueDisplay');
    const displayHeSo = document.getElementById('heSoDisplay');

    // 1. Logic lá»c táº§ng theo cao á»‘c
    selectCaoOc.addEventListener('change', function() {
        const maCO = this.value;
        selectTang.innerHTML = '<option value="">-- Chá»n táº§ng --</option>';
        
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

    // 2. Logic tÃ­nh giÃ¡
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
