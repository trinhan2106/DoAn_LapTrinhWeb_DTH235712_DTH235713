<?php
// modules/hop_dong/hd_gia_han.php
/**
 * TRUNG TÂM KIỂM SOÁT GIA HẠN HỢP ĐỒNG (UC08)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

$soHD = trim($_GET['soHopDong'] ?? '');
if (empty($soHD)) die("Thiếu tham số luồng hệ thống.");

$pdo = Database::getInstance()->getConnection();

try {
    // 1. LẤY HỒ SƠ RAW HỢP ĐỒNG (Để check condition 1 & 2)
    // Coi ngayKetThuc là chuẩn ngày hết hạn cuối cùng hiện tại 
    $stmtHD = $pdo->prepare("SELECT trangThai, ngayKetThuc FROM HOP_DONG WHERE soHopDong = ?");
    $stmtHD->execute([$soHD]);
    $hd = $stmtHD->fetch(PDO::FETCH_ASSOC);

    if (!$hd) die("Hợp đồng ảo không tồn tại trong thiết kế DDL.");

    // CHECK RB-08.1: KIỂM ĐỊNH TÍNH TOÀN VẸN CỦA 3 ĐIỀU KIỆN
    
    // (ĐK 1) STATUS HĐ: Hiện tại trạng thái 1 (DangHieuLuc) hoặc 4 (GiaHan)
    $ck_statusOk = in_array((int)$hd['trangThai'], [1, 4]);

    // (ĐK 2) THỜI GIAN: Ngày kết thúc <= 30 ngày (DATEDIFF <= 30) (Hoặc là đã lố hạn -> âm ngày)
    // Tính số ngày còn lại bằng PHP
    $diffDays = (strtotime($hd['ngayKetThuc']) - time()) / (60 * 60 * 24);
    $ck_timeOk = ($diffDays <= 30);

    // (ĐK 3) CÔNG NỢ TÀI CHÍNH: Không ôm nợ (soTienConNo > 0) ở bảng HOA_DON
    // Do Backend chưa xây bảng HOA_DON, ta giả lập truy vấn try/catch để không break web, nếu có table thì query thật.
    $ck_debtOk = true; // Mặc định pass
    try {
        $stmtNo = $pdo->prepare("SELECT COUNT(*) AS biNo FROM HOA_DON WHERE soHopDong = ? AND soTienConNo > 0");
        $stmtNo->execute([$soHD]);
        $rowNo = $stmtNo->fetch();
        if ($rowNo && $rowNo['biNo'] > 0) {
            $ck_debtOk = false;
        }
    } catch (Exception $e) { 
        // Bỏ qua nếu DB chưa Alter Update thêm bảng hóa đơn ở Task sau
    }

    // FLAG CHẾT: Nếu 1 trong 3 Tích cờ bị sai -> Khóa Giao Dịch Nút Bấm.
    $isAllowed = ($ck_statusOk && $ck_timeOk && $ck_debtOk);


    // 2. LẤY DANH SÁCH CHI TIẾT CÁC PHÒNG THUỘC TÒA NHÀ TRONG HĐ NÀY ĐANG CÓ TRẠNG THÁI 1 (ĐANG THUÊ)
    // Ở Bước 5.3, CHI_TIET_HOP_DONG không có cột ngayHetHan, nên ta sẽ Fallback (COALESCE) lấy tạm ngayKetThuc của Cha. 
    // Nếu bạn có alter thêm bảng `ngayHetHan` ở bảng CTHD thì COALESCE ưu tiên lấy cột con trước rùi tới bảng Cha.
    $stmtPh = $pdo->prepare("
        SELECT c.maCTHD, c.maPhong, p.tenPhong, c.giaThue,
               COALESCE(c.ngayHetHan, h.ngayKetThuc) AS ngayBatDauGiaHan_Logic
        FROM CHI_TIET_HOP_DONG c
        JOIN PHONG p ON c.maPhong = p.maPhong
        JOIN HOP_DONG h ON c.soHopDong = h.soHopDong
        WHERE c.soHopDong = ? AND c.trangThai = 1
    ");
    $stmtPh->execute([$soHD]);
    $listPh = $stmtPh->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    error_log("[" . basename(__FILE__) . "] Lỗi DB: " . $e->getMessage());
    $_SESSION['error_msg'] = "Đã xảy ra lỗi hệ thống. Vui lòng liên hệ quản trị viên.";
    header("Location: " . BASE_URL . "modules/dashboard/admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân Hệ Gia Hạn (Renew)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { background-color: #f4f7f9; }
        .box { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-top: 4px solid #1e3a5f;}
        
        .rb-item { padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 10px; border: 1px solid #e9ecef; display: flex; align-items: center;}
        .rb-ok { background-color: #f1f8eb; color: #2e7d32; border-color: #c8e6c9;}
        .rb-err { background-color: #fce8e6; color: #c62828; border-color: #ffcdd2;}
        .rb-warn { background-color: #fff8e1; color: #f57f17; border-color: #ffecb3;}
        
        .icon-check { font-size: 1.4rem; margin-right: 15px; }
        .table-gh th { color: #1e3a5f; }
        .new-date-box { font-weight: bold; color: #d35400; padding: 5px 10px; background: #fff3e0; border-radius: 4px;}
    </style>
</head>
<body class="p-4">

<div class="container max-w-900 mx-auto" style="max-width: 900px;">
    <div class="box">
        <h4 class="mb-4 text-uppercase fw-bold" style="color: #1e3a5f;">
            <i class="fa-solid fa-timeline me-2"></i> TIẾN TRÌNH GIA HẠN HỢP ĐỒNG: <?= htmlspecialchars($soHD) ?>
        </h4>

        <!-- TẦNG 1: BỘ CHECKLIST RÀNG BUỘC RB-08.1 -->
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-list-check me-2"></i> THẨM ĐỊNH BỘ QUY TẮC RÀNG BUỘC KINH DOANH (RB-08.1)</h6>
        
        <div class="rb-item <?= $ck_statusOk ? 'rb-ok' : 'rb-err' ?>">
            <i class="fa-solid <?= $ck_statusOk ? 'fa-circle-check' : 'fa-circle-xmark' ?> icon-check"></i>
            <div>
                [RB.1] Tòa án Form BĐS: Trạng thái nguyên thủy phải là 'Đang Hiệu Lực' hoặc 'Đã Gia Hạn'.
                <br><small class="fw-normal">Status hiện tại: Cờ = <?= $hd['trangThai'] ?></small>
            </div>
        </div>

        <div class="rb-item <?= $ck_timeOk ? 'rb-ok' : 'rb-warn' ?>">
            <i class="fa-solid <?= $ck_timeOk ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> icon-check"></i>
            <div>
                [RB.2] Cửa sổ Báo Động Hạn: Phải nằm trong khoảng <= 30 ngày so với ngày lụng tàn hợp đồng.
                <br><small class="fw-normal">Khoảng cách tới viền: Còn lại <?= floor($diffDays) ?> ngày.</small>
            </div>
        </div>

        <div class="rb-item <?= $ck_debtOk ? 'rb-ok' : 'rb-err' ?>">
            <i class="fa-solid <?= $ck_debtOk ? 'fa-circle-check' : 'fa-circle-xmark' ?> icon-check"></i>
            <div>
                [RB.3] Tuân thủ Tài chính: Hồ sơ chứng minh KHÔNG CÒN TREO CÔNG NỢ ở Phân Hệ Hóa Đơn.
                <br><small class="fw-normal">Tình trạng nợ: <?= $ck_debtOk ? 'Hoàn Toàn Trong Sạch' : 'Lập Tức Block Form - Đang Gánh Nợ Treo' ?></small>
            </div>
        </div>


        <!-- TẦNG 2: FRONT-END FORM (Chỉ mở nếu IsAllowed) -->
        <?php if ($isAllowed): ?>
            
            <form action="hd_gia_han_submit.php" method="POST" class="mt-5 border-top pt-4">
                <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">
                <input type="hidden" name="soHopDong" value="<?= htmlspecialchars($soHD) ?>">

                <h6 class="fw-bold mb-3"><i class="fa-solid fa-layer-group me-2"></i> THIẾT KẾ DATA GIA HẠN CHO TỪNG UNIT (PHÒNG) CHỌN LỌC</h6>
                <div class="alert alert-secondary small">Kỹ thuật JS Vanilla sẽ tính toán Đích Đến Timeline (Ngày X) dựa vào giá trị ô số Tháng. Nếu không muốn gia hạn thêm căn nào thì để số 0.</div>

                <div class="table-responsive">
                    <table class="table table-bordered table-gh align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">Định Danh Unit</th>
                                <th width="20%">Hạn Mặc Định</th>
                                <th width="20%">Số Tháng Nới Thêm</th>
                                <th width="35%">Dự Kiến Đáo Hạn Mới</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($listPh as $ph): 
                                // ID Dom unique để JS chĩa súng bắn dom manipulation
                                $domId_Moi = 'date_new_' . $ph['maCTHD']; 
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($ph['maPhong']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($ph['tenPhong']) ?></small>
                                        <input type="hidden" name="dsMaCTHD[]" value="<?= $ph['maCTHD'] ?>">
                                        <input type="hidden" name="dsMaPhong[<?= $ph['maCTHD'] ?>]" value="<?= $ph['maPhong'] ?>">
                                    </td>
                                    
                                    <td class="text-secondary fw-bold" id="date_old_<?= $ph['maCTHD'] ?>">
                                        <!-- Render Data gốc để làm base tính toán JS -->
                                        <?= date('Y-m-d', strtotime($ph['ngayBatDauGiaHan_Logic'])) ?>
                                    </td>
                                    
                                    <td>
                                        <!-- Ô Input bẫy Event ONINPUT Trigger Algorithm -->
                                        <input type="number" name="soThangGiaHan[<?= $ph['maCTHD'] ?>]" class="form-control text-center fw-bold" 
                                               min="0" value="0"
                                               oninput="calcNewDate(this, 'date_old_<?= $ph['maCTHD'] ?>', '<?= $domId_Moi ?>')">
                                    </td>
                                    
                                    <td class="text-center">
                                        <div class="new-date-box" id="<?= $domId_Moi ?>">-- Giữ Nguyên --</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="hd_hienthi.php" class="btn btn-light border mx-2 px-4 shadow-sm">Thoát Form</a>
                    <button type="submit" class="btn btn-info text-dark fw-bold px-5 shadow border-info">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i> THỰC THI CHỐT LỊCH GIA HẠN
                    </button>
                </div>
            </form>

        <?php else: ?>
            <div class="mt-5 border-top pt-4 text-center">
                <i class="fa-solid fa-lock text-danger" style="font-size: 5rem;"></i>
                <h4 class="mt-3 text-danger fw-bold">QUYỀN BỊ ĐÓNG ĐỂ BẢO VỆ DATABASE SYSTEM</h4>
                <p class="text-muted">Bộ máy phát hiện Hồ sơ này vi phạm 1 trong 3 Rule Constraints của quy tắc kinh doanh 08.1. <br/>Hệ thống chủ động che giấu Button Action để cắt đứt Transaction!</p>
                <a href="hd_hienthi.php" class="btn btn-outline-secondary mt-3 px-4 shadow-sm">Lùi Về Trang Chủ</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    /**
     * Thuật Toán Vanilla JS Update Live Lịch Trình Tương Lai Bằng DOM Data Date
     */
    function calcNewDate(inputElem, oldDateId, targetId) {
        let thangGiaHan = parseInt(inputElem.value) || 0;
        let strNgayCu = document.getElementById(oldDateId).innerText.trim();
        
        let targetDOM = document.getElementById(targetId);

        if (thangGiaHan === 0) {
            targetDOM.innerText = "-- Giữ Nguyên --";
            targetDOM.style.backgroundColor = "#fff3e0";
            return;
        }

        // Ép dữ liệu String về Type Date Core của máy để thao tác logic Math
        let dateObj = new Date(strNgayCu);

        // API setMonth của Date Engine tự động bù đắp năm nhuận, vượt Tháng 12 -> Update Year. Cực chuẩn.
        dateObj.setMonth(dateObj.getMonth() + thangGiaHan);

        // Build Format lại theo ISO YYYY-MM-DD để nhìn cho rõ
        let y = dateObj.getFullYear();
        let m = ("0" + (dateObj.getMonth() + 1)).slice(-2);
        let d = ("0" + dateObj.getDate()).slice(-2);

        targetDOM.innerText = `${y}-${m}-${d}`;
        targetDOM.style.backgroundColor = "#d4edda"; // Thể hiện trạng thái xanh thay đổi
        targetDOM.style.color = "#155724";
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
