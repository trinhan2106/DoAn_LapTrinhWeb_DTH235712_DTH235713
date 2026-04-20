<?php
/**
 * modules/thanh_toan/dien_nuoc_ghi.php
 * UI & LOGIC: GHI NHẬN CHỈ SỐ ĐIỆN NƯỚC HÀNG THÁNG
 * Tích hợp Admin Layout chuẩn hệ thống
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Bảo mật: Kiểm tra Session và Quyền hạn
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_KE_TOAN, ROLE_QUAN_LY_NHA]);

$pdo = Database::getInstance()->getConnection();

try {
    // Truy vấn danh sách Phòng ĐANG ĐƯỢC THUÊ thuộc Hợp đồng có Hiệu lực
    $sql = "
        SELECT c.maPhong, c.soHopDong, p.tenPhong,
               COALESCE((SELECT chiSoDienMoi FROM CHI_SO_DIEN_NUOC WHERE maPhong = c.maPhong ORDER BY namGhi DESC, thangGhi DESC LIMIT 1), 0) AS dienDau,
               COALESCE((SELECT chiSoNuocMoi FROM CHI_SO_DIEN_NUOC WHERE maPhong = c.maPhong ORDER BY namGhi DESC, thangGhi DESC LIMIT 1), 0) AS nuocDau
        FROM CHI_TIET_HOP_DONG c
        INNER JOIN PHONG p ON c.maPhong = p.maPhong
        INNER JOIN HOP_DONG h ON c.soHopDong = h.soHopDong
        WHERE c.trangThai = 'DangThue' AND h.trangThai = 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $listPh = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mapData = [];
    foreach($listPh as $r) {
        $mapData[$r['maPhong']] = [
            'soHopDong' => $r['soHopDong'],
            'tenPhong'  => $r['tenPhong'],
            'dienDau'   => (float)$r['dienDau'],
            'nuocDau'   => (float)$r['nuocDau']
        ];
    }
    
    // Đơn giá mặc định
    $dgDienDefault = 3500; 
    $dgNuocDefault = 18000;

} catch (PDOException $e) {
    error_log("DB Error in dien_nuoc_ghi: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi hệ thống. Vui lòng liên hệ quản trị viên.";
    header("Location: dien_nuoc_hienthi.php");
    exit();
}

$pageTitle = "Ghi Chỉ Số Điện Nước - Admin";
include __DIR__ . '/../../includes/admin/admin-header.php';
?>

<style>
    .invoice-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 1000px; margin: auto; border-top: 5px solid #1e3a5f;}
    .stat-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px;}
    .money-text { color: #d32f2f; font-size: 1.5rem; font-weight: bold;}
    .delta-warn { color: #d32f2f; font-weight: bold; background: #ffebee; padding: 2px 6px; border-radius: 4px; display: inline-block;}
    .delta-suspect { color: #e65100; font-weight: bold; background: #fff3e0; padding: 2px 6px; border-radius: 4px; display: inline-block;}
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../../includes/admin/sidebar.php'; ?>
    
    <div class="admin-main-wrapper flex-grow-1">
        <?php include __DIR__ . '/../../includes/admin/topbar.php'; ?>
        
        <main class="admin-main-content p-4">
            <div class="container-fluid">
                <!-- Header Page -->
                <div class="mb-4">
                    <h2 class="h3 fw-bold text-navy"><i class="bi bi-speedometer2 me-2"></i>QUẢN LÝ ĐIỆN NƯỚC</h2>
                    <p class="text-muted small">Cập nhật chỉ số tiêu thụ và tự động khởi tạo hóa đơn tháng.</p>
                </div>

                <div class="invoice-card">
                    <h3 class="mb-4 text-uppercase fw-bold text-center" style="color: #1e3a5f;">
                        <i class="i bi-lightning-charge me-2 text-warning"></i> PHIẾU ĐỐI SOÁT CHỈ SỐ TIÊU THỤ
                    </h3>
                    
                    <?php if(isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger fw-bold"><i class="bi bi-exclamation-triangle me-1"></i> <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['success_msg'])): ?>
                        <div class="alert alert-success fw-bold"><i class="bi bi-check-circle me-1"></i> <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                    <?php endif; ?>

                    <form action="dien_nuoc_ghi_submit.php" method="POST" id="frmDN">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="soHopDong" id="soHopDong" value="">

                        <div class="row g-4 mb-4 border-bottom pb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Chọn Phòng Xuất Phiếu <span class="text-danger">*</span></label>
                                <select class="form-select border-2 border-primary" name="maPhong" id="maPhong" onchange="chonPhongDOM()" required>
                                    <option value="">-- Quét mã phòng (Đang mướn) --</option>
                                    <?php foreach($listPh as $ph): ?>
                                        <option value="<?= htmlspecialchars($ph['maPhong']) ?>">
                                            Phòng: <?= htmlspecialchars($ph['maPhong']) ?> (HĐ: <?= htmlspecialchars($ph['soHopDong']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="lblHD" class="form-text mt-2 fw-bold text-success"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tháng Ghi <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="thangGhi" value="<?= date('m') ?>" min="1" max="12" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Năm <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="namGhi" value="<?= date('Y') ?>" min="2020" max="2099" required>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="stat-box h-100">
                                    <h5 class="fw-bold text-warning border-bottom pb-2 mb-3"><i class="bi bi-bolt-fill me-2"></i> ĐIỆN NĂNG</h5>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Chỉ Số Đầu (KWh)</label>
                                        <input type="number" class="form-control bg-light fw-bold text-secondary" id="csD_Dau" name="chiSoDien_Dau" value="0" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Chỉ Số Mới <span class="text-danger">*</span></label>
                                        <input type="number" step="0.1" class="form-control border-2" id="csD_Cuoi" name="chiSoDien_Cuoi" required oninput="tinhToanLive()">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Đơn Giá</label>
                                        <input type="number" class="form-control" id="dgDien" name="donGiaDien" value="<?= $dgDienDefault ?>" required oninput="tinhToanLive()">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                        <span>Tiêu thụ:</span>
                                        <h5 class="m-0 fw-bold" id="lbl_deltaDien">0</h5>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="stat-box h-100">
                                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-droplet-fill me-2"></i> LƯU LƯỢNG NƯỚC</h5>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Chỉ Số Đầu (Khối)</label>
                                        <input type="number" class="form-control bg-light fw-bold text-secondary" id="csN_Dau" name="chiSoNuoc_Dau" value="0" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Chỉ Số Mới <span class="text-danger">*</span></label>
                                        <input type="number" step="0.1" class="form-control border-2" id="csN_Cuoi" name="chiSoNuoc_Cuoi" required oninput="tinhToanLive()">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small">Đơn Giá</label>
                                        <input type="number" class="form-control" id="dgNuoc" name="donGiaNuoc" value="<?= $dgNuocDefault ?>" required oninput="tinhToanLive()">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                        <span>Tiêu thụ:</span>
                                        <h5 class="m-0 fw-bold" id="lbl_deltaNuoc">0</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-4 text-center rounded-3" style="background-color: #fff9e6; border: 2px dashed #f1c40f;">
                            <p class="text-secondary fw-bold mb-1">ƯỚC TÍNH NỢ PHẢI THU</p>
                            <div class="money-text" id="lbl_TongTien">0 VNĐ</div>
                        </div>

                        <div id="alertZone" class="mt-3"></div>

                        <div class="d-flex justify-content-center mt-5">
                            <button type="submit" id="btnSubmit" class="btn btn-primary btn-lg px-5 shadow" disabled>
                                <i class="bi bi-file-earmark-check me-2"></i> LƯU VÀ XUẤT HÓA ĐƠN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <?php include __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>

<script>
    const BANG_DATA_PHONG = <?= json_encode($mapData) ?>;

    function chonPhongDOM() {
        const selectId = document.getElementById('maPhong').value;
        const btnSub = document.getElementById('btnSubmit');

        if (BANG_DATA_PHONG[selectId]) {
            let data = BANG_DATA_PHONG[selectId];
            document.getElementById('csD_Dau').value = data.dienDau;
            document.getElementById('csN_Dau').value = data.nuocDau;
            document.getElementById('soHopDong').value = data.soHopDong;
            document.getElementById('lblHD').innerText = "Hợp đồng: " + data.soHopDong;
            document.getElementById('csD_Cuoi').value = "";
            document.getElementById('csN_Cuoi').value = "";
            btnSub.disabled = false;
        } else {
            document.getElementById('csD_Dau').value = 0;
            document.getElementById('csN_Dau').value = 0;
            document.getElementById('soHopDong').value = "";
            document.getElementById('lblHD').innerText = "";
            btnSub.disabled = true;
        }
        tinhToanLive();
    }

    function tinhToanLive() {
        if(!document.getElementById('maPhong').value) return;

        let dDau  = parseFloat(document.getElementById('csD_Dau').value) || 0;
        let dCuoi = parseFloat(document.getElementById('csD_Cuoi').value) || 0;
        let dGia  = parseFloat(document.getElementById('dgDien').value) || 0;

        let nDau  = parseFloat(document.getElementById('csN_Dau').value) || 0;
        let nCuoi = parseFloat(document.getElementById('csN_Cuoi').value) || 0;
        let nGia  = parseFloat(document.getElementById('dgNuoc').value) || 0;

        let deltaD = dCuoi - dDau;
        let deltaN = nCuoi - nDau;

        let lD = document.getElementById('lbl_deltaDien');
        let lN = document.getElementById('lbl_deltaNuoc');
        let lErr = document.getElementById('alertZone');
        let btn  = document.getElementById('btnSubmit');
        
        let errCount = 0;
        let warnText = "";

        if (deltaD < 0) {
            lD.innerHTML = `<span class="delta-warn">${deltaD} (LỖI)</span>`;
            warnText += "Chỉ số điện không được nhỏ hơn tháng trước.<br/>";
            errCount++;
        } else {
            lD.innerHTML = `<span class="text-success">${deltaD} KWh</span>`;
        }

        if (deltaN < 0) {
            lN.innerHTML = `<span class="delta-warn">${deltaN} (LỖI)</span>`;
            warnText += "Chỉ số nước không được nhỏ hơn tháng trước.<br/>";
            errCount++;
        } else {
            lN.innerHTML = `<span class="text-primary">${Math.round(deltaN * 100) / 100} m³</span>`;
        }

        if (errCount > 0) {
            btn.disabled = true;
            lErr.innerHTML = `<div class="alert alert-danger fw-bold"><i class="bi bi-x-circle me-1"></i> ${warnText}</div>`;
            document.getElementById('lbl_TongTien').innerText = "### ERROR ###";
        } else {
            btn.disabled = false;
            lErr.innerHTML = "";
            let totalMoney = (deltaD * dGia) + (deltaN * nGia);
            let f = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalMoney);
            document.getElementById('lbl_TongTien').innerText = f;
        }
    }
</script>

</body>
</html>
