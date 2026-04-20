<?php
// modules/hop_dong/hd_them.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Auth native an ninh
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA, ROLE_KE_TOAN]); // Ngăn tài khoản KHACH_HANG (role=4) truy cập

// Phát sinh CSRF token và lưu vào biến để dùng xuyên suốt trang
$csrf_token = generateCSRFToken();

$pdo = Database::getInstance()->getConnection();

try {
    // QUERY MỒI DAO: Lấy mảng Khách Hàng vào Form Khách
    $stmtKH = $pdo->query("SELECT maKH, tenKH, sdt FROM KHACH_HANG WHERE deleted_at IS NULL ORDER BY tenKH ASC");
    $listKH = $stmtKH->fetchAll(PDO::FETCH_ASSOC);

    // QUERY MỒI DAO 2: Lấy mảng Phòng có Trạng Thái 1 (Trống / Chưa người bao)
    $stmtPhong = $pdo->query("SELECT maPhong, tenPhong FROM PHONG WHERE deleted_at IS NULL AND trangThai = 1 ORDER BY maPhong ASC");
    $listPhong = $stmtPhong->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Lỗi Database Exception từ Form Lập HĐ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Lập Hợp Đồng Wizard 4 Bước</title>
    <!-- Phân Hệ Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Bootstrap Icons (dùng cho bi bi-arrow-left trên nút Quay lại) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- FIX CSRF: expose token vào meta để room-lock.js đọc được -->
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    
    <style>
        /* CSS Gốc Thương Hiệu Tôn Nghiêm */
        :root {
            --primary: #1e3a5f;
            --accent: #c9a66b;
            --step-bg: #e9ecef;
            --bg-color: #f4f7f9;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            padding: 30px 15px;
        }

        .wizard-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            max-width: 1000px;
            margin: auto;
            border-top: 5px solid var(--accent);
        }

        /* KHUNG CSS WIZARD STEPPER */
        .wizard-nav {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        /* Line xám vẽ xuyên suốt các viên bi */
        .wizard-nav::before {
            content: '';
            position: absolute;
            top: 25px; /* Giữa viên bi 50px */
            left: 12%;
            right: 12%;
            height: 4px;
            background: var(--step-bg);
            z-index: 1;
        }

        .step-indicator {
            text-align: center;
            position: relative;
            z-index: 2;
            width: 25%;
        }

        .step-icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #fff;
            color: #adb5bd;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 1.4rem;
            border: 4px solid var(--step-bg);
            transition: all 0.4s ease;
        }

        /* TRẠNG THÁI ACTIVE HIỆN TẠI VÀ PAST */
        .step-indicator.active .step-icon {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(30, 58, 95, 0.3);
        }
        
        .step-indicator.completed .step-icon {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .step-label {
            margin-top: 12px;
            font-weight: 700;
            color: #6c757d;
            font-size: 0.95rem;
            text-transform: uppercase;
        }

        .step-indicator.active .step-label {
            color: var(--primary);
        }
        .step-indicator.completed .step-label {
            color: var(--accent);
        }

        /* ẨN HIỆN PANELS SECTION */
        .wizard-pane {
            display: none;
            animation: slideIn 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .wizard-pane.active {
            display: block;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-brand {
            background-color: var(--primary);
            color: #fff;
            font-weight: 700;
            transition: 0.2s;
        }
        .btn-brand:hover {
            background-color: var(--accent);
            color: #fff;
            transform: scale(1.02);
            box-shadow: 0 4px 10px rgba(201, 166, 107, 0.3);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="wizard-card">
        <!-- Nút quay lại danh sách hợp đồng -->
        <a href="<?php echo BASE_URL; ?>modules/hop_dong/hd_hienthi.php" class="btn btn-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left"></i> Quay về Danh sách Hợp đồng
        </a>
        <h3 class="text-center mb-5 fw-bold" style="color: var(--primary);">
            <i class="fa-solid fa-file-contract me-2 text-warning"></i> TRUNG TÂM KHỞI TẠO HỢP ĐỒNG MƯỚN BUILDING
        </h3>

        <!-- WIZARD STEPPERS UI BAR -->
        <div class="wizard-nav">
            <div class="step-indicator active" id="nav-indicator-1">
                <div class="step-icon"><i class="fa-solid fa-users"></i></div>
                <div class="step-label">1. Đại Diện Thuê</div>
            </div>
            <div class="step-indicator" id="nav-indicator-2">
                <div class="step-icon"><i class="fa-solid fa-door-open"></i></div>
                <div class="step-label">2. Pick Mã Phòng</div>
            </div>
            <div class="step-indicator" id="nav-indicator-3">
                <div class="step-icon"><i class="fa-regular fa-calendar-check"></i></div>
                <div class="step-label">3. Biên Độ Ngày</div>
            </div>
            <div class="step-indicator" id="nav-indicator-4">
                <div class="step-icon"><i class="fa-solid fa-file-shield"></i></div>
                <div class="step-label">4. Ghi Nhận Cọc</div>
            </div>
        </div>

        <!-- ENDPOINT GỬI ĐẾN BACKEND hd_them_submit.php MÀ SAU NÀY BẠN LÀM -->
        <form id="frmWizard" action="hd_them_submit.php" method="POST">
            <!-- CSRF Block: gửi kèm khi submit form đến hd_them_submit.php -->
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- STEP 1: KHÁCH HÀNG (PANEL) -->
            <div class="wizard-pane active" id="pane-step-1">
                <h5 class="border-bottom pb-3 mb-4 text-secondary">
                    <i class="fa-solid fa-magnifying-glass me-2"></i> TRUY VẤN HỒ SƠ DOANH NGHIỆP / CÁ NHÂN TỐ QUÁN
                </h5>
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Danh Bộ Khách Khách Hàng (Tồn Tại Trong DB)</label>
                        <select class="form-select form-select-lg border-2" name="maKH" id="maKH">
                            <option value="">-- Mở bảng chọn khách hàng (Dropdown Select) --</option>
                            <?php foreach($listKH as $kh): ?>
                                <option value="<?= htmlspecialchars($kh['maKH']) ?>">
                                    [<?= htmlspecialchars($kh['maKH']) ?>] - <?= htmlspecialchars($kh['tenKH']) ?> (Cell: <?= htmlspecialchars($kh['sdt']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-4">
                            <span class="text-muted fst-italic">Nếu User chưa từng đến coi phòng, tạo mới bằng Form Khách:</span>
                            <a href="../khach_hang/kh_them.php" class="btn btn-sm btn-outline-secondary ms-2 fw-bold">
                                <i class="fa-solid fa-user-plus me-1"></i> TẠO NHANH HỒ SƠ 
                            </a>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-5 pt-3 border-top">
                    <button type="button" class="btn btn-brand px-5 py-2 fs-5" onclick="nextStep(2)">
                        BƯỚC 2 <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>


            <!-- STEP 2: CHỐT PHÒNG KHÔNG GIAN BĐS (PANEL) -->
            <div class="wizard-pane" id="pane-step-2">
                <h5 class="border-bottom pb-3 mb-4 text-secondary">
                    <i class="fa-solid fa-house-lock me-2"></i> ẤN ĐỊNH VĂN PHÒNG & NÉM DÂY CƯƠNG BẢO MẬT
                </h5>
                
                <div class="alert alert-warning border text-dark fw-bold mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> 
                    LƯU Ý NGHIỆP VỤ: Ngay khi click chọn mốc Tọa độ Phòng, Hệ thống DB sẽ thả Cờ Lock đóng băng mã phòng này. Đồng nghiệp tòa nhà khác sẽ bị cấm đè hợp đồng lên Tọa độ này trong 10 phút đàm phán!
                </div>

                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Tọa độ Phòng Trống Hiện Thời</label>
                        
                        <!-- CHUYỂN MẠCH SỰ KIỆN: ONCHANGE BẮN JS JAVASCRIPT GỌI ROOM-LOCK.JS -->
                        <!-- CHUYỂN MẠCH SỰ KIỆN: ONCHANGE BẮN JS JAVASCRIPT GỌI ROOM-LOCK.JS -->
                        <select class="form-select form-select-lg border-2 border-primary" name="maPhong" id="maPhong" onchange="lockRoom(this.value)">
                            <option value="">-- Dò Cự Ly Hầm Số DSN BĐS (Phòng Trực Chiến) --</option>
                            <?php foreach($listPhong as $p): ?>
                                <option value="<?= htmlspecialchars($p['maPhong']) ?>">
                                    <?= htmlspecialchars($p['maPhong']) ?> | <?= htmlspecialchars($p['tenPhong']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-danger mt-2 fw-bold"><i class="fa-solid fa-shield-halved me-1"></i> Cờ Lock Box Tự Động Giải Cứu khi Tắt Trình Duyệt / F5.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                    <button type="button" class="btn btn-light border px-4 py-2" onclick="prevStep(1)">
                        <i class="fa-solid fa-arrow-left me-2"></i> REVERSE LÙI
                    </button>
                    <button type="button" class="btn btn-brand px-5 py-2 fs-5" onclick="nextStep(3)">
                        BƯỚC 3 <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>


            <!-- STEP 3: LIMIT THỜI GIAN NGÀY THÁNG (PANEL) -->
            <div class="wizard-pane" id="pane-step-3">
                <h5 class="border-bottom pb-3 mb-4 text-secondary"><i class="fa-regular fa-calendar-days me-2"></i> TIMETABLE ĐIỀU CHUẨN</h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ngày Khởi Tạo Bút Tích</label>
                        <input type="date" class="form-control" name="ngayLap" value="<?= date('Y-m-d') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ngày Bắt Đầu Chuyển Giao</label>
                        <input type="date" class="form-control border-2" name="ngayBatDau" id="ngayBatDau" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Kỳ Hạn Dự Kiến Ký Đáo Hết Khóa</label>
                        <input type="date" class="form-control border-2" name="ngayKetThuc" id="ngayKetThuc" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                    <button type="button" class="btn btn-light border px-4 py-2" onclick="prevStep(2)">
                        <i class="fa-solid fa-arrow-left me-2"></i> LÙI FIX LỖI
                    </button>
                    <button type="button" class="btn btn-brand px-5 py-2 fs-5" onclick="nextStep(4)">
                        FINAL BƯỚC 4 <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>


            <!-- STEP 4: HOÀNH THÀNH (CỌC VÀ CHỐT KẾT QUẢ) (PANEL) -->
            <div class="wizard-pane" id="pane-step-4">
                <h5 class="border-bottom pb-3 mb-4 text-secondary"><i class="fa-solid fa-hand-holding-dollar me-2"></i> TIỀN TỆ KÝ QUỸ (SECURITY DEPOSIT)</h5>
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="alert alert-success border-success bg-white fw-bold">
                            <i class="fa-solid fa-check-circle me-2 text-success"></i> Thao Tác Submit Lưu Form sẽ kích nổ hệ thống sinh Key Hash PDF cho Hợp Đồng Và khóa cứng Dòng Trạng Thái Phòng Thành [ĐÃ CHO THUÊ]. 
                        </div>
                    </div>
                    <div class="col-md-6 offset-md-3">
                        <label class="form-label fw-bold text-success text-center d-block">CHỐT NHẬN TIỀN ĐẶT CỌC GIỮ CHỖ (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg text-center fw-bold fs-4 border-success border-3 text-danger" name="tienTienCoc" value="0" required>
                        <div class="form-text text-center mt-2">Dữ liệu Decimal(15,2) mặc định được Audit đẩy vào sổ quỹ biên lai phiếu thu.</div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                    <button type="button" class="btn btn-light border px-4 py-2 text-danger" onclick="prevStep(3)">
                        <i class="fa-solid fa-rotate-left me-2"></i> KHÔNG TIN TƯỞNG, XEM LẠI BƯỚC CŨ
                    </button>
                    
                    <!-- NẠP ĐẠN BẮN SERVER POST VỚI FORM ACTION -->
                    <button type="submit" class="btn btn-success px-5 py-3 fs-5 fw-bold text-uppercase shadow">
                        <i class="fa-solid fa-file-signature me-2"></i> Phóng Xuất Bút Ký (Submit)
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- PHP inject CSRF token trực tiếp vào JS global — đáng tin cậy hơn querySelector -->
<script>
    window.PHP_CSRF_TOKEN = "<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>";
</script>
<!-- Cache-buster: buộc browser reload file JS mới nhất, tránh chạy bản cũ còn trong cache -->
<script src="../../assets/js/room-lock.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/room-lock.js'); ?>"></script>

<script>
    /**
     * Thuật toán JS điều hướng Động Lớp Wizard Bootstrap Bề Mặt
     */
    function nextStep(stepIdx) { 
        // Logic chặn thô sơ ép buộc Backend Validation Phụ trợ Bề Mặt
        if (stepIdx === 2) {
            if (document.getElementById('maKH').value === '') {
                alert("Vui Lòng Tìm Kiếm & Chĩa Tọa Độ Khách Thuê Khớp Để Hoàn Thuế Bước Biên! \n\n (Không có Khách thì làm Hợp Đồng làm gì anh bạn?)");
                return;
            }
        }
        if (stepIdx === 3) {
            if (document.getElementById('maPhong').value === '') {
                alert("NVKD Chưa Chọn Phòng Nào Để Gắn Mốc Đóng Cọc Block. Chặn Lệnh.");
                return;
            }
        }
        showStep(stepIdx); 
    }
    
    function prevStep(stepIdx) { 
        showStep(stepIdx); 
    }

    function showStep(idx) {
        // Tắt toàn bộ Panel HTML Tầng Data
        document.querySelectorAll('.wizard-pane').forEach((pane) => pane.classList.remove('active'));
        
        // Tẩy trắng tất cả đèn báo Navigator Bar
        document.querySelectorAll('.step-indicator').forEach((indicator) => {
            indicator.classList.remove('active');
            indicator.classList.remove('completed');
        });

        // Bật màn Data Hiện Tại
        document.getElementById('pane-step-' + idx).classList.add('active');
        
        // Tô Mực Đậm Các Đèn Theo Quá Trình Hoàn Thành Tiệu Chấm Timeline
        for(let i=1; i<=4; i++) {
            const indc = document.getElementById('nav-indicator-' + i);
            if (i < idx) {
                // Các mốc đã qua màu Vàng Brand Accent (Hoàn thành)
                indc.classList.add('completed');
            } else if (i === idx) {
                // Đèn hiện tại đang diễn ra màu Navy Bóng (Active)
                indc.classList.add('active');
            }
        }
    }
</script>
</body>
</html>
