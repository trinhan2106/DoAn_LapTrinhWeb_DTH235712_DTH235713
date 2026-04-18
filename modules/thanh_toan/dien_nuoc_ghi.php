<?php
// modules/thanh_toan/dien_nuoc_ghi.php
/**
 * UI & LOGIC UC07: GHI NHẬN CHỈ SỐ ĐIỆN NƯỚC HÀNG THÁNG
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

$pdo = Database::getInstance()->getConnection();

try {
    // TASK 6.4: Truy vấn danh sách Phòng ĐANG ĐƯỢC THUÊ (Dữ liệu gốc từ CTHD trangThai = 1)
    // Tích hợp Lấy số lùi: Đọc `chiSo_Cuoi` của kỳ trước gần nhất làm `chiSo_Dau` của kỳ này. (Sub-query)
    $sql = "
        SELECT c.maPhong, c.soHopDong, p.tenPhong,
               COALESCE((SELECT chiSoDien_Cuoi FROM CHI_SO_DIEN_NUOC WHERE maPhong = c.maPhong ORDER BY namGhi DESC, thangGhi DESC LIMIT 1), 0) AS dienDau,
               COALESCE((SELECT chiSoNuoc_Cuoi FROM CHI_SO_DIEN_NUOC WHERE maPhong = c.maPhong ORDER BY namGhi DESC, thangGhi DESC LIMIT 1), 0) AS nuocDau
        FROM CHI_TIET_HOP_DONG c
        INNER JOIN PHONG p ON c.maPhong = p.maPhong
        WHERE c.trangThai = 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $listPh = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gói data siêu tốc sang định dạng JSON để JS Vanilla xài ngay (Đỡ phải gọi AJAX phiền phức)
    $mapData = [];
    foreach($listPh as $r) {
        $mapData[$r['maPhong']] = [
            'soHopDong' => $r['soHopDong'],
            'tenPhong'  => $r['tenPhong'],
            'dienDau'   => (float)$r['dienDau'],
            'nuocDau'   => (float)$r['nuocDau']
        ];
    }
    
    // Default Đơn giá (Có thể móc từ Bảng Cấu Hình Hệ Thống Task sau)
    $dgDienDefault = 3500; 
    $dgNuocDefault = 18000;

} catch (PDOException $e) {
    die("Lỗi Truy Xuất Backend Điện Nước: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chốt Số Điện Nước & Nạp Hóa Đơn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f7f9; }
        .invoice-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 900px; margin: auto; border-top: 5px solid #1e3a5f;}
        .stat-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px;}
        .money-text { color: #d32f2f; font-size: 1.5rem; font-weight: bold;}
        .delta-warn { color: #d32f2f; font-weight: bold; background: #ffebee; padding: 2px 6px; border-radius: 4px; display: inline-block;}
        .delta-suspect { color: #e65100; font-weight: bold; background: #fff3e0; padding: 2px 6px; border-radius: 4px; display: inline-block;}
    </style>
</head>
<body class="p-4">

<div class="container invoice-card">
    <h3 class="mb-4 text-uppercase fw-bold text-center" style="color: #1e3a5f;">
        <i class="fa-solid fa-bolt me-2 text-warning"></i> PHIẾU ĐỐI SOÁT CHỈ SỐ TIÊU THỤ NĂNG LƯỢNG
    </h3>
    
    <?php if(isset($_GET['err'])): ?>
        <div class="alert alert-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Backend chặn: Chỉ số Mới nhập vào Không được phép Nhỏ hơn Chỉ số bảo lưu Kỳ cũ!</div>
    <?php endif; ?>

    <form action="dien_nuoc_ghi_submit.php" method="POST" id="frmDN">
        <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">
        <input type="hidden" name="soHopDong" id="soHopDong" value="">

        <div class="row g-4 mb-4 border-bottom pb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Chọn Phòng Xuất Phiếu <span class="text-danger">*</span></label>
                <select class="form-select border-2 border-primary" name="maPhong" id="maPhong" onchange="chonPhongDOM()" required>
                    <option value="">-- Quét mã phòng BĐS (Đang mướn) --</option>
                    <?php foreach($listPh as $ph): ?>
                        <option value="<?= htmlspecialchars($ph['maPhong']) ?>">
                            Căn: <?= htmlspecialchars($ph['maPhong']) ?> (Sở hữu bởi HĐ: <?= htmlspecialchars($ph['soHopDong']) ?>)
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
            <!-- MODULE KẾT TOÁN ĐIỆN -->
            <div class="col-md-6">
                <div class="stat-box h-100">
                    <h5 class="fw-bold text-warning border-bottom pb-2 mb-3"><i class="fa-solid fa-plug me-2 border p-1 rounded"></i> ĐIỆN NĂNG THIÊU THỤ</h5>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Cột Mốc Tháng Trước (KWh)</label>
                        <input type="number" class="form-control bg-light fw-bold text-secondary" id="csD_Dau" name="chiSoDien_Dau" value="0" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chỉ Số Đồng Hồ Mới Nhất (KWh) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" class="form-control border-2" id="csD_Cuoi" name="chiSoDien_Cuoi" required oninput="tinhToanLive()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Đơn Giá KWh Hiện Hành (VNĐ)</label>
                        <input type="number" class="form-control" id="dgDien" name="donGiaDien" value="<?= $dgDienDefault ?>" required oninput="tinhToanLive()">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span>Lượng Tiêu Thụ Đo Đạt (Delta):</span>
                        <h5 class="m-0 fw-bold" id="lbl_deltaDien">0</h5>
                    </div>
                </div>
            </div>

            <!-- MODULE KẾT TOÁN NƯỚC -->
            <div class="col-md-6">
                <div class="stat-box h-100">
                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-faucet-drip me-2 border p-1 rounded"></i> LƯU LƯỢNG NƯỚC</h5>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Cột Mốc Tháng Trước (Khối)</label>
                        <input type="number" class="form-control bg-light fw-bold text-secondary" id="csN_Dau" name="chiSoNuoc_Dau" value="0" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chỉ Số Đồng Hồ Mới Nhất (Khối) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" class="form-control border-2" id="csN_Cuoi" name="chiSoNuoc_Cuoi" required oninput="tinhToanLive()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Đơn Tiền / Khối (VNĐ)</label>
                        <input type="number" class="form-control" id="dgNuoc" name="donGiaNuoc" value="<?= $dgNuocDefault ?>" required oninput="tinhToanLive()">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span>Dung Tích Đã Xả (Delta):</span>
                        <h5 class="m-0 fw-bold" id="lbl_deltaNuoc">0</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- TỔNG TÀI CHÍNH TẠM TÍNH UI REALTIME -->
        <div class="mt-4 p-4 text-center rounded-3" style="background-color: #fff9e6; border: 2px dashed #f1c40f;">
            <p class="text-secondary fw-bold mb-1"><i class="fa-solid fa-calculator me-1"></i> ƯỚC TÍNH NỢ PHẢI THU CHO KỲ HÓA ĐƠN</p>
            <div class="money-text" id="lbl_TongTien">0 VNĐ</div>
        </div>

        <!-- ALERT ZONE -->
        <div id="alertZone" class="mt-3"></div>

        <div class="d-flex justify-content-center mt-5">
            <button type="submit" id="btnSubmit" class="btn btn-primary btn-lg px-5 shadow" disabled>
                <i class="fa-solid fa-file-invoice-dollar me-2"></i> LƯU TRỮ VÀ XUẤT HÓA ĐƠN KẾ TOÁN
            </button>
        </div>
    </form>
</div>

<!-- DATA TỪ BACKEND -> JSON -->
<script>
    const BANG_DATA_PHONG = <?= json_encode($mapData) ?>;

    function chonPhongDOM() {
        const selectId = document.getElementById('maPhong').value;
        const btnSub = document.getElementById('btnSubmit');

        if (BANG_DATA_PHONG[selectId]) {
            let data = BANG_DATA_PHONG[selectId];
            // Render Chỉ số Đầu (Mốc lùi)
            document.getElementById('csD_Dau').value = data.dienDau;
            document.getElementById('csN_Dau').value = data.nuocDau;
            
            // Xách soHopDong gắn chìm để Push về Backend phục vụ bảng HoaDon
            document.getElementById('soHopDong').value = data.soHopDong;
            document.getElementById('lblHD').innerText = "Trực thuộc File Phiếu Hợp Đồng BĐS Lõi: " + data.soHopDong;
            
            // Clean Input
            document.getElementById('csD_Cuoi').value = "";
            document.getElementById('csN_Cuoi').value = "";
            
            btnSub.disabled = false;
        } else {
            // Rỗng
            document.getElementById('csD_Dau').value = 0;
            document.getElementById('csN_Dau').value = 0;
            document.getElementById('soHopDong').value = "";
            document.getElementById('lblHD').innerText = "";
            btnSub.disabled = true;
        }
        
        tinhToanLive();
    }

    /**
     * JS Vanilla - Live Math Tốc Độ Cao
     * [THUẬT TOÁN DELTA BẪY NGƯỢC]
     */
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
        
        // Reset Error Array
        let errCount = 0;
        let warnText = "";

        // 1. NGHIỆP VỤ DELTA ĐIỆN
        if (deltaD < 0) {
            lD.innerHTML = `<span class="delta-warn">${deltaD} (LỖI ÂM SỐ)</span>`;
            warnText += "Khóa Lệnh Dòng Điện: Chỉ số KWh Đồng hồ Cuối đang bị nhập nhỏ hơn tháng cũ.<br/>";
            errCount++;
        } else if (deltaD > 9999) {
            lD.innerHTML = `<span class="delta-suspect">${deltaD} (CÀY BITCOIN ?)</span>`;
            warnText += "<span class='text-warning'><i class='fa-solid fa-bell'></i> Cảnh báo: Lượng Điện tiêu thụ quá khủng khiếp, xin kiểm tra kỹ có bấm thêm dư số 0 ngớ ngẩn nào không!</span><br/>";
        } else {
            lD.innerHTML = `<span class="text-success">${deltaD} KWh</span>`;
        }

        // 2. NGHIỆP VỤ DELTA NƯỚC
        if (deltaN < 0) {
            lN.innerHTML = `<span class="delta-warn">${deltaN} (LỖI ÂM SỐ)</span>`;
            warnText += "Khóa Lệnh Thủy Dòng: Chỉ số Khối Cuối đang bị nhập nhỏ hơn mốc tháng cũ.<br/>";
            errCount++;
        } else if (deltaN > 9999) {
            lN.innerHTML = `<span class="delta-suspect">${deltaN} (RÒ RỈ TÒA NHÀ)</span>`;
            warnText += "<span class='text-warning'><i class='fa-solid fa-bell'></i> Cảnh báo: Lượng Nước xả đang cao bằng 1 cái đập thủy điện! Kiểm tra lại đường ống hoặc gõ lố phím.</span><br/>";
        } else {
            lN.innerHTML = `<span class="text-primary">${Math.round(deltaN * 100) / 100} m³</span>`;
        }

        // 3. RENDER LUẬT CHẶN
        if (errCount > 0) {
            btn.disabled = true;
            lErr.innerHTML = `<div class="alert alert-danger fw-bold"><i class="fa-solid fa-ban me-1"></i> ${warnText}</div>`;
            document.getElementById('lbl_TongTien').innerText = "### ERROR ###";
        } else {
            btn.disabled = false;
            // Cho phép Submit. Nếu có warning nhưng ko Error thì in warning 
            if(warnText !== "") {
                lErr.innerHTML = `<div class="alert alert-dark border-warning fw-bold text-warning">${warnText}</div>`;
            } else {
                lErr.innerHTML = "";
            }

            // In tổng tiền
            let totalMoney = (deltaD * dGia) + (deltaN * nGia);
            // Format Number JS Intl
            let f = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalMoney);
            document.getElementById('lbl_TongTien').innerText = f;
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
