<?php
// modules/phong/phong_them.php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

kiemTraSession();

// Nạp sảnh DB kéo mồi Select Box "TẦNG"
$pdo = Database::getInstance()->getConnection();
try {
    // Chỉ lấy ra các Tầng còn hoạt động (Chưa bị soft delete)
    $stmtTang = $pdo->prepare("SELECT maTang, tenTang, COALESCE(heSoGia, 1.0) as heSoGia FROM TANG WHERE deleted_at IS NULL ORDER BY tenTang ASC");
    $stmtTang->execute();
    $listTang = $stmtTang->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Lỗi lấy thông tin tham chiếu Tầng: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Phòng Mới</title>
    <!-- CSS Bootstrap 5 -->
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
            border-top: 5px solid var(--primary);
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
            border: 2px dashed var(--accent);
            color: #d35400; /* Nhấn mạnh số tiền tổng */
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
            <i class="fa-solid fa-door-open me-2"></i> THÊM MỚI VĂN PHÒNG
        </h4>

        <?php if(isset($_GET['err'])): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Có lỗi: Mã phòng sinh ra bị trùng CSDL.</div>
        <?php endif; ?>

        <form action="phong_them_submit.php" method="POST">
            <!-- Khóa Token Chống Tấn Công -->
            <input type="hidden" name="csrf_token" value="<?= validateCSRFToken('') ? '' : generateCSRFToken() ?>">

            <!-- KHU VỰC 1: ĐỊNH DANH PHÒNG -->
            <h6 class="text-secondary fw-bold mb-3 mt-2"><i class="fa-solid fa-tag me-1"></i> Định danh Văn Phòng</h6>
            <div class="row g-4 mb-4 pb-4 border-bottom">
                <div class="col-md-6">
                    <label class="form-label">Mã Phòng / ID DSN <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="maPhong" placeholder="VD: P-301A" required maxlength="50">
                    <small class="text-muted">Nhập mã định danh bắt buộc để hệ thống tạo Key.</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Thuộc Vị Trí Tầng <span class="text-danger">*</span></label>
                    <!-- Trigger Event: khi thay đổi mục select -> Gọi hàm tính giá bên JS -->
                    <select class="form-select" name="maTang" id="frm_maTang" onchange="thuattoanTinhGiaTreoRealtime()" required>
                        <option value="" data-hesogia="0">-- Chọn tầng tòa nhà --</option>
                        <?php foreach($listTang as $tang): ?>
                            <!-- Nhúng ngầm data-hesogia động bằng PHP để JS bắt sự kiện tính toán tự động -->
                            <option value="<?= $tang['maTang'] ?>" data-hesogia="<?= $tang['heSoGia'] ?>">
                                Tầng: <?= htmlspecialchars($tang['tenTang']) ?> (Hệ số: <?= $tang['heSoGia'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Tên hiển thị phòng (Option)</label>
                    <input type="text" class="form-control" name="tenPhong" placeholder="Phòng Studio Gốc Cây">
                </div>
            </div>

            <!-- KHU VỰC 2: KHÔNG GIAN BÀI TRÍ & CHÍNH SÁCH GIÁ -->
            <h6 class="text-secondary fw-bold mb-3 mt-2"><i class="fa-solid fa-coins me-1"></i> Không Gian & Trị Giá Realtime</h6>
            <div class="row g-4 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Diện tích (m²) <span class="text-danger">*</span></label>
                    <!-- Khi Input gõ, tự động tái tính toán tổng giá để báo cho UI biết -->
                    <input type="number" step="0.1" min="0" class="form-control" name="dienTich" id="frm_dienTich" value="0" oninput="thuattoanTinhGiaTreoRealtime()">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Chỗ Làm Việc Tối Đa</label>
                    <input type="number" min="0" class="form-control" name="soChoLamViec" value="0">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Đơn Giá Tham Chiếu (VNĐ/m²) <span class="text-danger">*</span></label>
                    <!-- Gõ thay đổi đơn giá, Auto-update Total -->
                    <input type="number" min="0" step="1000" class="form-control" name="donGiaM2" id="frm_donGiaM2" value="0" oninput="thuattoanTinhGiaTreoRealtime()">
                </div>
            </div>

            <!-- Dòng 3 (Render Giá Auto ReadOnly) -->
            <div class="row g-4 mt-1 mb-5">
                <div class="col-md-12">
                    <label class="form-label"><i class="fa-solid fa-calculator text-muted me-1"></i> Giá Thuê Tổng Định Kỳ (Chỉ đọc) = [Đơn Giá] x [Diện Tích] x [Hệ Số Tầng]</label>
                    <div class="input-group">
                        <input type="number" class="form-control control-readonly" name="giaThue" id="frm_giaThue" value="0" readonly>
                        <span class="input-group-text fw-bold">VNĐ / THÁNG</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="phong_hienthi.php" class="btn btn-light border"><i class="fa-solid fa-arrow-left"></i> Quay lại Danh sách</a>
                <button type="submit" class="btn btn-submit"><i class="fa-solid fa-floppy-disk me-1"></i> Lưu Hàng CSDL Mới</button>
            </div>
        </form>
    </div>
</div>

<script>
    /**
     * JS THUẦN (Vanilla): Kịch bản Bẫy sự kiện Tự Kích Hoạt Tính Tiền Liên Tục
     */
    function thuattoanTinhGiaTreoRealtime() {
        // Lấy Input Value
        const dienTichVal = parseFloat(document.getElementById('frm_dienTich').value) || 0;
        const donGiaVal   = parseFloat(document.getElementById('frm_donGiaM2').value) || 0;
        
        // Trích xuất thuôc tính data-hesogia nằm trong Option Tầng CỦA TÒA NHÀ
        const selectTangNode      = document.getElementById('frm_maTang');
        const theOptionDangChon   = selectTangNode.options[selectTangNode.selectedIndex];
        
        let heSoGiaVal = 0;
        // Tránh tình trạng theOptionDangChon là Null khi chưa chọn tầng (bởi UI Select Box)
        if (theOptionDangChon) {
            heSoGiaVal = parseFloat(theOptionDangChon.getAttribute('data-hesogia')) || 0;
        }

        // Logic Yêu cầu nghiệp vụ: giaThue = donGiaM2 * dienTich * heSoGia
        const tinhTongTotal = donGiaVal * dienTichVal * heSoGiaVal;

        // Điền lại Input bị Disabled dưới dạng String định dạng bỏ qua Thập phân thừa
        document.getElementById('frm_giaThue').value = tinhTongTotal.toFixed(2);
    }
</script>
</body>
</html>
