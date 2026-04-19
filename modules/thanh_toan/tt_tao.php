<?php
// modules/thanh_toan/tt_tao.php
/**
 * UI UC06: Form thu tien va hien thi danh sach hoa don con no theo hop dong.
 * - Kiem tra role: chi ADMIN va KE_TOAN duoc truy cap.
 * - Moi output dong deu escape bang htmlspecialchars (Convention C.4).
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN]);

$pdo = Database::getInstance()->getConnection();

// Nhan param tim kiem hop dong
$soHD = trim($_GET['soHopDong'] ?? '');
$listHoaDon_No = [];
$tongNoCongDon = 0;
$khachHangInfo = null;

if (!empty($soHD)) {
    try {
        // Query lay thong tin khach hang theo hop dong
        $stmtKH = $pdo->prepare("
            SELECT k.tenKH, k.sdt, k.email 
            FROM HOP_DONG h 
            INNER JOIN KHACH_HANG k ON h.maKH = k.maKH 
            WHERE h.soHopDong = ? AND h.deleted_at IS NULL
        ");
        $stmtKH->execute([$soHD]);
        $khachHangInfo = $stmtKH->fetch(PDO::FETCH_ASSOC);

        if ($khachHangInfo) {
            // Lay danh sach hoa don con no (chi loai Chinh, khong lay CreditNote)
            $stmtHD = $pdo->prepare("
                SELECT * FROM HOA_DON 
                WHERE soHopDong = ? AND trangThai = 'ConNo' AND loaiHoaDon = 'Chinh'
                ORDER BY kyThanhToan ASC, created_at ASC
            ");
            $stmtHD->execute([$soHD]);
            $listHoaDon_No = $stmtHD->fetchAll(PDO::FETCH_ASSOC);

            // Tinh tong no cong don
            foreach ($listHoaDon_No as $hd) {
                $tongNoCongDon += (float)$hd['soTienConNo'];
            }
        }
    } catch (PDOException $e) {
        error_log("[tt_tao.php] PDO error: " . $e->getMessage());
        $_SESSION['error_msg'] = "Loi he thong khi truy van du lieu. Vui long lien he quan tri vien.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thu Tien - Ke Toan Cao Oc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .pos-box { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); max-width: 950px; margin: 30px auto; border-top: 5px solid #28a745; }
        .bill-card { background: #fafafa; border: 1px solid #e0e0e0; border-radius: 8px;}
        .bill-header { background: #1e3a5f; color: #fff; padding: 12px 20px; border-radius: 8px 8px 0 0;}
        .rb-green { background-color: #e8f5e9; border: 2px dashed #4caf50; color: #1b5e20; }
        .rb-orange { background-color: #fff3e0; border: 2px dashed #f57c00; color: #e65100; }
    </style>
</head>
<body class="p-4">

<div class="container pos-box">
    
    <h3 class="mb-4 text-uppercase fw-bold text-center" style="color: #28a745;">
        <i class="fa-solid fa-cash-register me-2"></i> TRUNG TAM THU TIEN
    </h3>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger fw-bold">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error_msg'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success fw-bold text-center shadow-sm">
            <i class="fa-solid fa-check-double me-2"></i><?= htmlspecialchars($_SESSION['success_msg'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
        <div class="alert alert-success fw-bold text-center shadow-sm">
            <i class="fa-solid fa-check-double me-2"></i> Giao dich thanh toan da duoc ghi nhan thanh cong.<br/>
            He thong da tu dong phan bo (Waterfall) va gui bien lai dien tu qua email khach hang.
        </div>
    <?php endif; ?>

    <!-- THANH TIM KIEM HOP DONG -->
    <div class="card bg-light border-0 mb-4">
        <div class="card-body">
            <form action="tt_tao.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-9">
                    <label class="form-label fw-bold text-muted"><i class="fa-solid fa-barcode me-1"></i> Nhap ma so hop dong:</label>
                    <input type="text" class="form-control form-control-lg border-2" name="soHopDong" placeholder="VD: HD-2026-62A89" value="<?= htmlspecialchars($soHD, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="col-md-3 mt-auto">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow"><i class="fa-solid fa-magnifying-glass me-2"></i> Tim kiem</button>
                </div>
            </form>
        </div>
    </div>


    <?php if(!empty($soHD) && !$khachHangInfo): ?>
        <div class="alert alert-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> Khong tim thay hop dong [<?= htmlspecialchars($soHD, ENT_QUOTES, 'UTF-8') ?>]. Hop dong khong ton tai hoac da bi huy.</div>
    
    <?php elseif(!empty($soHD) && $khachHangInfo): ?>

        <div class="row g-4 mt-2">
            
            <!-- COT BEN TRAI: THONG TIN KHACH HANG VA DANH SACH HOA DON NO -->
            <div class="col-md-7">
                <div class="bill-card h-100 shadow-sm border-0">
                    <div class="bill-header fw-bold">
                        <i class="fa-solid fa-user-check me-2"></i> Ho so hop dong: <?= htmlspecialchars($soHD, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="p-3 bg-white">
                        <p class="mb-1"><strong>Khach hang:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($khachHangInfo['tenKH'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p class="mb-1"><strong><i class="fa-solid fa-phone me-1"></i> Dien thoai:</strong> <?= htmlspecialchars($khachHangInfo['sdt'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mb-1"><strong><i class="fa-solid fa-envelope me-1"></i> Email:</strong> <?= htmlspecialchars($khachHangInfo['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="bg-light p-3 border-top">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-file-invoice me-1"></i> Danh sach hoa don con no</h6>
                        <ul class="list-group list-group-flush">
                            <?php if(count($listHoaDon_No) === 0): ?>
                                <div class="alert alert-success mt-2 mb-0 fw-bold"><i class="fa-solid fa-check-circle me-2"></i> Khong con hoa don no nao.</div>
                            <?php else: ?>
                                <?php foreach($listHoaDon_No as $hd): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-bottom">
                                        <div>
                                            <span class="badge bg-danger me-2">Ma HD: <?= htmlspecialchars($hd['soHoaDon'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                            <br/><small class="text-muted"><i class="fa-regular fa-clock me-1"></i> Ky thanh toan: <?= htmlspecialchars($hd['kyThanhToan'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                            <br/><small class="fw-bold text-dark">Ly do: <?= htmlspecialchars($hd['lyDo'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                        <span class="fw-bold fs-6 text-danger"><?= number_format((float)($hd['soTienConNo'] ?? 0), 0) ?> d</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- COT BEN PHAI: FORM NHAP TIEN THU -->
            <div class="col-md-5">
                <form action="tt_tao_submit.php" method="POST" id="frmPOS">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="soHopDong" value="<?= htmlspecialchars($soHD, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="tongNoThucTe" value="<?= $tongNoCongDon ?>">

                    <!-- HIEN THI TONG NO -->
                    <?php if($tongNoCongDon > 0): ?>
                        <div class="p-3 mb-4 rounded rb-orange text-center shadow-sm">
                            <i class="fa-solid fa-triangle-exclamation mb-1 text-danger d-block fs-3"></i>
                            <h6 class="fw-bold">TONG NO HIEN TAI</h6>
                            <h2 class="fw-bold mb-0"><?= number_format($tongNoCongDon, 0) ?> <span class="fs-5">VND</span></h2>
                        </div>
                    <?php elseif($tongNoCongDon < 0): ?>
                        <div class="p-3 mb-4 rounded rb-green text-center shadow-sm">
                            <i class="fa-solid fa-piggy-bank mb-1 text-success d-block fs-3"></i>
                            <h6 class="fw-bold">KHACH HANG DANG CO SO DU TIN DUNG</h6>
                            <h2 class="fw-bold mb-0">+<?= number_format(abs($tongNoCongDon), 0) ?> <span class="fs-5">VND</span></h2>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fa-solid fa-wallet text-secondary me-1"></i> So tien thu <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg text-end fw-bold text-success border-3 border-success" 
                               name="soTienDaNop_POST" id="inpTienNop" value="0" min="1" required oninput="tinhDuNoLive()">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Phuong thuc thanh toan <span class="text-danger">*</span></label>
                        <select name="phuongThucM" class="form-select border-2 bg-light fw-bold" required>
                            <option value="TienMat">Tien mat</option>
                            <option value="ChuyenKhoan" selected>Chuyen khoan ngan hang</option>
                            <option value="Vi">Vi dien tu (Momo/VnPay)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">Ma giao dich (tuy chon)</label>
                        <input type="text" name="maGiaoDich" class="form-control border-2 bg-light" placeholder="VD: FT26041900123" maxlength="100">
                    </div>

                    <!-- HIEN THI DU NO LIVE -->
                    <div class="mb-4 p-3 bg-light border rounded text-center">
                        <span class="d-block text-secondary fw-bold small mb-1">Du no con lai (uoc tinh):</span>
                        <h4 class="m-0 fw-bold" id="lblLiveDuNo">
                            <?= number_format($tongNoCongDon, 0) ?> d 
                        </h4>
                    </div>

                    <?php if(count($listHoaDon_No) > 0): ?>
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow mt-2 pt-3 pb-3" onclick="return confirm('Xac nhan da nhan du tien va tien hanh ghi nhan thanh toan?')">
                            <i class="fa-solid fa-stamp me-2"></i> THU TIEN VA TRU NO
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg w-100 fw-bold mt-2" disabled>
                            <i class="fa-solid fa-lock me-2"></i> KHONG CO HOA DON CAN THU
                        </button>
                    <?php endif; ?>

                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    /**
     * Tinh du no con lai theo thoi gian thuc khi nhap so tien.
     */
    function tinhDuNoLive() {
        const inputNop = document.getElementById('inpTienNop');
        const tienNop = parseFloat(inputNop.value) || 0;
        const tongNo = parseFloat(document.getElementById('tongNoThucTe').value) || 0;
        
        const lblDuNo = document.getElementById('lblLiveDuNo');
        let duNoMoi = tongNo - tienNop;

        let formatted = new Intl.NumberFormat('vi-VN', { style: 'decimal', maximumFractionDigits: 0 }).format(Math.abs(duNoMoi));

        if (duNoMoi > 0) {
            lblDuNo.innerHTML = `<span class="text-danger"><i class="fa-solid fa-caret-down me-1"></i> Con no: ${formatted} d</span>`;
        } else if (duNoMoi < 0) {
            lblDuNo.innerHTML = `<span class="text-success"><i class="fa-solid fa-gift me-1"></i> Du tin dung: +${formatted} d</span>`;
        } else {
            lblDuNo.innerHTML = `<span class="text-primary"><i class="fa-solid fa-check-double me-1"></i> Sach no (0 d)</span>`;
        }
    }
    
    window.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('inpTienNop')) { tinhDuNoLive(); }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
