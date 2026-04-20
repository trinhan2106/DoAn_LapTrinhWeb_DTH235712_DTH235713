<?php
/**
 * modules/phong/phong_sua.php
 * Giao diá»‡n chá»‰nh sá»­a thÃ´ng tin PhÃ²ng - IDOR Protection & Real-time Calc
 */

// 1. KHá»žI Táº O & Báº¢O Máº¬T
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// XÃ¡c thá»±c Session & PhÃ¢n quyá»n
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// 2. CHá»NG IDOR & Láº¤Y Dá»® LIá»†U CÅ¨ PHá»¤C Vá»¤ FORM
$id = $_GET['id'] ?? '';
if (empty($id)) {
    $_SESSION['error_msg'] = "MÃ£ phÃ²ng khÃ´ng há»£p lá»‡.";
    header("Location: phong_hienthi.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Query xÃ¡c minh báº£n ghi tá»“n táº¡i vÃ  chÆ°a bá»‹ xÃ³a má»m
$sqlCheck = "
    SELECT p.*, t.maCaoOc, t.heSoGia 
    FROM PHONG p 
    JOIN TANG t ON p.maTang = t.maTang 
    WHERE p.maPhong = ? AND p.deleted_at IS NULL
";
$stmtCheck = $db->prepare($sqlCheck);
$stmtCheck->execute([$id]);
$phong = $stmtCheck->fetch();

if (!$phong) {
    $_SESSION['error_msg'] = "KhÃ´ng tÃ¬m tháº¥y phÃ²ng hoáº·c phÃ²ng Ä‘Ã£ bá»‹ xÃ³a khá»i há»‡ thá»‘ng.";
    header("Location: phong_hienthi.php");
    exit();
}

// Láº¥y danh sÃ¡ch hÃ¬nh áº£nh hiá»‡n táº¡i cá»§a phÃ²ng
$stmtImages = $db->prepare("SELECT id, urlHinhAnh, is_thumbnail FROM PHONG_HINH_ANH WHERE maPhong = ?");
$stmtImages->execute([$id]);
$currentImages = $stmtImages->fetchAll();

// 3. Láº¤Y DANH SÃCH CAO á»C VÃ€ Táº¦NG PHá»¤C Vá»¤ DROPDOWN
$dsCaoOc = $db->query("SELECT maCaoOc, tenCaoOc FROM CAO_OC WHERE deleted_at IS NULL ORDER BY tenCaoOc")->fetchAll();
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
            background-color: #f8f9fa; border-left: 4px solid #c9a66b; padding: 1.5rem; border-radius: 8px;
        }
        .readonly-val { font-size: 1.25rem; font-weight: 800; color: #1e3a5f; }
        .form-label { font-weight: 600; color: #1e3a5f; }
        .text-navy { color: #1e3a5f !important; }
        
        /* Gallery Styles */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .gallery-item { position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; aspect-ratio: 1/1; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
        .gallery-item:hover img { filter: brightness(0.7); }
        .delete-overlay {
            position: absolute; top: 0; right: 0; background: rgba(231, 76, 60, 0.9); color: white;
            padding: 2px 8px; border-bottom-left-radius: 8px; font-size: 0.75rem; 
            cursor: pointer; z-index: 5;
        }
        .delete-check { position: absolute; top: 8px; left: 8px; width: 18px; height: 18px; cursor: pointer; z-index: 10; }
        .thumbnail-badge {
            position: absolute; bottom: 0; left: 0; padding: 2px 8px; background: #c9a66b; color: white; 
            font-size: 0.65rem; font-weight: bold; width: 100%; text-align: center;
        }
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
                    <li class="breadcrumb-item active">Chá»‰nh sá»­a thÃ´ng tin</li>
                </ol>
            </nav>

            <div class="card form-card shadow-sm border-0">
                <div class="form-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>Cáº¬P NHáº¬T PHÃ’NG</h2>
                        <p class="mb-0 text-white-50 small mt-1">Sá»­a Ä‘á»•i thÃ´ng sá»‘ ká»¹ thuáº­t vÃ  tráº¡ng thÃ¡i cá»§a phÃ²ng.</p>
                    </div>
                    <span class="badge bg-white text-navy px-3 py-2 fw-bold"><?= e($phong['maPhong']) ?></span>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form action="phong_sua_submit.php" method="POST" id="formSuaPhong" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="maPhong" value="<?= e($phong['maPhong']) ?>">

                        <!-- Vá»‹ trÃ­ & CÆ¡ báº£n -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-geo-alt me-2"></i>Vá»‹ TrÃ­ & CÆ¡ Báº£n</h5>
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="maCaoOc" class="form-label">TÃ²a nhÃ  <span class="text-danger">*</span></label>
                                <select id="maCaoOc" class="form-select py-2" required>
                                    <option value="">-- Chá»n tÃ²a nhÃ  --</option>
                                    <?php foreach ($dsCaoOc as $co): ?>
                                        <option value="<?= e($co['maCaoOc']) ?>" <?= ($co['maCaoOc'] == $phong['maCaoOc']) ? 'selected' : '' ?>>
                                            <?= e($co['tenCaoOc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maTang" class="form-label">Táº§ng <span class="text-danger">*</span></label>
                                <select name="maTang" id="maTang" class="form-select py-2" required>
                                    <option value="">-- Chá»n táº§ng --</option>
                                    <!-- Sáº½ Ä‘Æ°á»£c Ä‘á»• bá»Ÿi JS -->
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="tenPhong" class="form-label">TÃªn PhÃ²ng <span class="text-danger">*</span></label>
                                <input type="text" name="tenPhong" id="tenPhong" class="form-control py-2" value="<?= e($phong['tenPhong']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="trangThai" class="form-label">Tráº¡ng thÃ¡i hiá»‡n táº¡i</label>
                                <select name="trangThai" id="trangThai" class="form-select py-2">
                                    <option value="1" <?= $phong['trangThai'] == 1 ? 'selected' : '' ?>>Trá»‘ng</option>
                                    <option value="2" <?= $phong['trangThai'] == 2 ? 'selected' : '' ?>>ÄÃ£ thuÃª</option>
                                    <option value="3" <?= $phong['trangThai'] == 3 ? 'selected' : '' ?>>Báº£o trÃ¬</option>
                                    <option value="4" <?= $phong['trangThai'] == 4 ? 'selected' : '' ?>>ÄÃ£ khÃ³a</option>
                                </select>
                            </div>
                        </div>

                        <!-- ThÃ´ng sá»‘ & ÄÆ¡n giÃ¡ -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-calculator me-2"></i>ThÃ´ng Sá»‘ & ÄÆ¡n GiÃ¡</h5>
                        <div class="row g-4 mb-5 p-4 bg-light rounded-3">
                            <div class="col-md-4">
                                <label for="dienTich" class="form-label">Diá»‡n tÃ­ch (mÂ²) <span class="text-danger">*</span></label>
                                <input type="number" name="dienTich" id="dienTich" class="form-control py-2 calc-trigger" step="0.1" min="0.1" value="<?= e($phong['dienTich']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="soChoLamViec" class="form-label">Sá»‘ chá»— lÃ m viá»‡c</label>
                                <input type="number" name="soChoLamViec" id="soChoLamViec" class="form-control py-2" min="1" value="<?= e($phong['soChoLamViec']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="donGiaM2" class="form-label">ÄÆ¡n giÃ¡ / mÂ² <span class="text-danger">*</span></label>
                                <input type="number" name="donGiaM2" id="donGiaM2" class="form-control py-2 calc-trigger" min="0" value="<?= e($phong['donGiaM2']) ?>" required>
                            </div>
                            
                            <div class="col-12">
                                <div class="calc-box mt-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-7 border-md-end">
                                            <div class="small text-muted text-uppercase fw-bold">GiÃ¡ thuÃª hÃ ng thÃ¡ng (vnÄ‘)</div>
                                            <div id="giaThueDisplay" class="readonly-val"><?= number_format($phong['giaThue'], 0, ',', '.') ?></div>
                                            <input type="hidden" name="giaThue" id="giaThue" value="<?= e($phong['giaThue']) ?>">
                                        </div>
                                        <div class="col-md-5 ps-md-4">
                                            <div class="small text-muted">Há»‡ sá»‘ táº§ng hiá»‡n táº¡i: <span id="heSoDisplay" class="fw-bold text-navy"><?= number_format($phong['heSoGia'], 2) ?></span></div>
                                            <div class="small text-muted mt-1">GiÃ¡ thuÃª = Diá»‡n tÃ­ch x ÄÆ¡n giÃ¡ x Há»‡ sá»‘</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quáº£n lÃ½ HÃ¬nh áº£nh -->
                        <h5 class="text-navy fw-bold mb-4 border-bottom pb-2"><i class="bi bi-images me-2"></i>Quáº£n LÃ½ HÃ¬nh áº¢nh</h5>
                        <div class="mb-5">
                            <label class="form-label d-block mb-3">HÃ¬nh áº£nh hiá»‡n táº¡i (TÃ­ch chá»n Ä‘á»ƒ xÃ³a)</label>
                            <?php if (empty($currentImages)): ?>
                                <div class="alert alert-light border text-center py-4 rounded-3">
                                    <i class="bi bi-image text-muted d-block h1"></i>
                                    <span class="text-muted">ChÆ°a cÃ³ hÃ¬nh áº£nh nÃ o cho phÃ²ng nÃ y.</span>
                                </div>
                            <?php else: ?>
                                <div class="gallery-grid mb-4">
                                    <?php foreach ($currentImages as $img): ?>
                                        <div class="gallery-item">
                                            <input type="checkbox" name="delete_images[]" value="<?= e($img['id']) ?>" class="delete-check" title="Chá»n Ä‘á»ƒ xÃ³a">
                                            <img src="<?= BASE_URL . e($img['urlHinhAnh']) ?>" alt="Room Image">
                                            <?php if ($img['is_thumbnail']): ?>
                                                <div class="thumbnail-badge">áº¢NH Äáº I DIá»†N</div>
                                            <?php endif; ?>
                                            <div class="delete-overlay"><i class="bi bi-trash"></i></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4 p-4 border rounded-3 bg-white">
                                <label for="hinhAnh" class="form-label fw-bold"><i class="bi bi-cloud-upload me-2"></i>ThÃªm hÃ¬nh áº£nh má»›i</label>
                                <input type="file" name="hinhAnh[]" id="hinhAnh" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp">
                                <div class="form-text mt-2">Äá»‹nh dáº¡ng há»— trá»£: JPG, PNG, WEBP (Tá»‘i Ä‘a 2MB/file).</div>
                            </div>
                        </div>

                        <div class="col-12 pt-4 d-flex justify-content-end gap-2 border-top">
                            <a href="phong_hienthi.php" class="btn btn-outline-secondary px-4 py-2">Há»§y bá»</a>
                            <button type="submit" class="btn btn-gold px-5 py-2"><i class="bi bi-save me-2"></i>Cáº­p nháº­t dá»¯ liá»‡u</button>
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
        selectTang.innerHTML = '<option value="">-- Chá»n táº§ng --</option>';
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

    // Initialize state
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
