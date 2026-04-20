<?php
// modules/hop_dong/hd_ket_thuc_le.php
/**
 * TRUNG TÂM UC10: TRẢ PHÒNG LẺ MỘT PHẦN CỦA HỢP ĐỒNG (Bớt Căn)
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
    // ----------------------------------------------------------------------------------
    // KIỂM SOÁT RB-10.1: BUỘC PHẢI KHẾP KÍN Ở TRẠNG THÁI > 2 PHÒNG
    // ----------------------------------------------------------------------------------
    $stmtCheckCount = $pdo->prepare("SELECT COUNT(*) FROM CHI_TIET_HOP_DONG WHERE soHopDong = ? AND trangThai = 'DangThue'");
    $stmtCheckCount->execute([$soHD]);
    $soPhongActive = (int)$stmtCheckCount->fetchColumn();

    if ($soPhongActive < 2) {
        // Redirection Bạo Lực: Bắt buộc sang UC11 (Hủy Toàn Phần) vì không còn đủ số phòng để rẽ nhánh.
        header("Location: hd_huy.php?soHopDong=" . urlencode($soHD) . "&msg=force_uc11");
        exit();
    }

    // LẤY DANH SÁCH CHI TIẾT CÁC PHÒNG ĐỂ RENDER
    $stmtPh = $pdo->prepare("
        SELECT c.maCTHD, c.maPhong, p.tenPhong, c.giaThue,
               COALESCE(c.ngayHetHan, h.ngayKetThuc) AS ngayBatDauGiaHan_Logic
        FROM CHI_TIET_HOP_DONG c
        JOIN PHONG p ON c.maPhong = p.maPhong
        JOIN HOP_DONG h ON c.soHopDong = h.soHopDong
        WHERE c.soHopDong = ? AND c.trangThai = 'DangThue'
    ");
    $stmtPh->execute([$soHD]);
    $listPh = $stmtPh->fetchAll(PDO::FETCH_ASSOC);

    // KIỂM TRA MỘT LƯỢT LOGIC NỢ THEO PHÒNG (NẾU CÓ BẢNG HÓA ĐƠN HỖ TRỢ MA_PHONG)
    $stmtNoPhong = null;
    $hasHoaDonTable = true;
    try {
        $stmtNoPhong = $pdo->prepare("SELECT COUNT(*) FROM HOA_DON WHERE soHopDong = ? AND maPhong = ? AND soTienConNo > 0");
    } catch (Exception $e) {
        $hasHoaDonTable = false; // System chưa alter bảng Hóa đơn
    }

} catch (PDOException $e) {
    die("Lỗi kết nối CSDL Lõi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quy Trình Trả Phòng Lẻ (Giảm Tải Hợp Đồng)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { background-color: #f4f7f9; }
        .box-container {
            background: #fff; padding: 35px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            max-width: 900px; margin: 0 auto;
            border-top: 5px solid #1e3a5f;
        }
        .chk-room { transform: scale(1.5); cursor: pointer; }
        .warning-debt { color: #d32f2f; font-size: 0.9rem; font-weight: bold; background: #ffebee; padding: 5px 10px; border-radius: 4px; display: inline-block; margin-top: 5px;}
    </style>
</head>
<body class="p-4">

<div class="container box-container">
    <h4 class="mb-3 text-uppercase fw-bold text-center" style="color: #1e3a5f;">
        <i class="fa-solid fa-person-walking-arrow-right me-2"></i> THỦ TỤC TRẢ CĂN HỘ LẺ (TỪNG PHẦN)
    </h4>
    <div class="text-center mb-5 text-muted">
        Ref Hợp Đồng: <strong class="text-primary"><?= htmlspecialchars($soHD) ?></strong> <br/>
        <small>Quy tắc RB-10.1: Không cho phép trả 100% căn hộ ở Form này. Vui lòng tick chọn các phòng cần trả.</small>
    </div>

    <form action="hd_ket_thuc_le_submit.php" method="POST" id="frmTraLe">
        <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">
        <input type="hidden" name="soHopDong" value="<?= htmlspecialchars($soHD) ?>">
        <input type="hidden" id="maxRooms" value="<?= $soPhongActive ?>">

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="table-light">
                    <tr>
                        <th width="10%" class="text-center">Chọn Trả</th>
                        <th width="30%">Mã BĐS</th>
                        <th width="25%">Đơn Giá/Tháng</th>
                        <th width="35%">Kiểm Định Kế Toán (RB-10.2)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($listPh as $ph): 
                        // THẨM ĐỊNH RB-10.2
                        $biNo = false;
                        if ($hasHoaDonTable && $stmtNoPhong) {
                            try {
                                $stmtNoPhong->execute([$soHD, $ph['maPhong']]);
                                $biNo = ($stmtNoPhong->fetchColumn() > 0);
                            } catch (Exception $e) {}
                        }
                    ?>
                        <tr class="<?= $biNo ? 'table-danger' : '' ?>">
                            <td class="text-center">
                                <?php if ($biNo): ?>
                                    <i class="fa-solid fa-lock text-danger fs-5" title="Bị khóa vì nợ"></i>
                                <?php else: ?>
                                    <input type="checkbox" name="chonTraPhan[]" value="<?= $ph['maCTHD'] ?>|<?= $ph['maPhong'] ?>" class="form-check-input chk-room">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold fs-5 text-primary"><?= htmlspecialchars($ph['maPhong']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($ph['tenPhong']) ?></small>
                            </td>
                            <td class="fw-bold"><?= number_format($ph['giaThue'], 0) ?> đ</td>
                            <td>
                                <?php if ($biNo): ?>
                                    <div class="warning-debt"><i class="fa-solid fa-circle-exclamation me-1"></i> Tồn đọng nợ Hóa Đơn phòng này. Chặn cắt!</div>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Đủ điều kiện tháo dỡ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning mt-4 fw-bold">
            <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i> Hành động Submit sẽ kích hoạt cơ chế MySQL Transaction, tiến hành đổi trạng thái Căn được tick về "Phòng Trống". Các căn không tick vẫn chạy tính tiền Hóa Đơn bình thường.
        </div>

        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
            <a href="hd_hienthi.php" class="btn btn-outline-secondary px-4 mx-2 border-2 fw-bold">Rút lui</a>
            <button type="button" class="btn btn-danger px-5 fs-5 fw-bold shadow-sm" onclick="checkSubmitLogic()">
                <i class="fa-solid fa-hammer me-2"></i> BỔ TRANH KẾT THÚC CĂN
            </button>
        </div>
    </form>
</div>

<script>
    function checkSubmitLogic() {
        const checkboxes = document.querySelectorAll('.chk-room:checked');
        const countChecked = checkboxes.length;
        const maxRooms = parseInt(document.getElementById('maxRooms').value);

        if (countChecked === 0) {
            alert("Lỗi Giao Diện: Bạn phải tick chọn ít nhất 1 phòng để tiến hành biên bản làm việc.");
            return;
        }

        // JS Chặn RB-10.1 ở phía Client
        if (countChecked === maxRooms) {
            alert("⚠ VI PHẠM RB-10.1: Bạn đang đánh dấu tick Trả TOÀN BỘ tất cả căn hộ của Hợp đồng này.\n\nHệ thống thiết kế quy ước, nếu trả sạch phòng thì phải sử dụng Luồng 'HỦY/THANH LÝ HỢP ĐỒNG'. Module Này sẽ khóa lệnh!");
            return;
        }

        if(confirm("Bạn có chắc chắn tiến hành Thu Hồi các căn BĐS đã tick? Hành động này sẽ thay đổi trạng thái gốc.")) {
            document.getElementById('frmTraLe').submit();
        }
    }
</script>
</body>
</html>
