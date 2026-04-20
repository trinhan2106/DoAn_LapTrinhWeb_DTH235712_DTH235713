<?php
// modules/hop_dong/hd_ky.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

kiemTraSession();

$soHD = trim($_GET['id'] ?? '');

if (empty($soHD)) {
    $_SESSION['error_msg'] = "Lỗi dữ liệu: Thiếu mã hợp đồng.";
    header("Location: hd_hienthi.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

try {
    // RAW QUERY Lấy Dữ liệu Chép tay của Hợp Đồng ra Render Template Hợp Đồng Mẫu
    $sql = "
        SELECT h.*, 
               k.tenKH, k.sdt, k.cccd, k.diaChi,
               n.tenNV
        FROM HOP_DONG h
        INNER JOIN KHACH_HANG k ON h.maKH = k.maKH
        INNER JOIN NHAN_VIEN n ON h.maNV = n.maNV
        WHERE h.soHopDong = :id AND h.deleted_at IS NULL
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $soHD]);
    $thongTinHD = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thongTinHD) {
        $_SESSION['error_msg'] = "Lỗi dữ liệu: Không tìm thấy hợp đồng hoặc hợp đồng đã bị hủy.";
        header("Location: hd_hienthi.php");
        exit();
    }

    // QUERY FETCH LIST CHI TIẾT CÁC PHÒNG THUỘC TỜA HĐ NÀY
    $stmtPh = $pdo->prepare("
        SELECT c.*, p.tenPhong, p.dienTich 
        FROM CHI_TIET_HOP_DONG c
        JOIN PHONG p ON c.maPhong = p.maPhong
        WHERE c.soHopDong = :id
    ");
    $stmtPh->execute([':id' => $soHD]);
    $listCP = $stmtPh->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("DB Error in hd_ky: " . $e->getMessage());
    $_SESSION['error_msg'] = "Lỗi hệ thống. Vui lòng liên hệ quản trị viên.";
    header("Location: hd_hienthi.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Ký Hợp Đồng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #1e3a5f;
            --accent: #c9a66b;
            --bg-color: #f4f7f9;
        }

        body {
            background-color: var(--bg-color);
            padding: 30px;
        }

        .contract-paper {
            background-color: #fff;
            max-width: 850px;
            margin: 0 auto;
            border-radius: 4px;
            padding: 50px 70px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
            /* Giả lập nét giấy sần */
            background-image: repeating-linear-gradient(180deg, transparent, transparent 39px, #f0f0f0 39px, #f0f0f0 40px);
            line-height: 40px; 
            font-family: "Times New Roman", Times, serif;
            font-size: 1.15rem;
        }

        .header-hd {
            text-align: center;
            line-height: 1.5;
            margin-bottom: 30px;
        }

        .brand-stamp {
            position: absolute;
            top: 20px; right: 30px;
            width: 120px; height: 120px;
            border: 4px solid rgba(201, 166, 107, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center; justify-content: center;
            color: rgba(201, 166, 107, 0.5);
            font-weight: 800;
            font-family: Arial, sans-serif;
            transform: rotate(-15deg);
            z-index: 10; pointer-events: none;
        }
        
        .brand-stamp.signed {
            border-color: rgba(40, 167, 69, 0.5);
            color: rgba(40, 167, 69, 0.5);
        }

        .btn-sign {
            background-color: var(--primary);
            color: #fff;
            font-weight: 700;
            font-family: 'Segoe UI', sans-serif;
        }
        .btn-sign:hover {
            background-color: #172d4c; color: #fff;
        }

        /* --- CSS Tối Ưu Hóa Render Bản In (PDF) --- */
        @media print {
            body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .container { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .contract-paper { 
                box-shadow: none !important; 
                padding: 15px !important; 
                max-width: 100% !important; 
                background-image: none !important; /* Bỏ lớp giả sần để chữ rõ nét */
            }
            /* Ẩn hoàn toàn các UI điều khiển không thuộc hợp đồng */
            .alert, .btn, .border-top { display: none !important; }
            @page { size: A4; margin: 20mm; }
        }
    </style>
</head>
<body>

<div class="container pb-5">
    
    <!-- MESSAGES -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success fw-bold text-center mx-auto" style="max-width: 850px;">
            <i class="fa-solid fa-check me-1"></i> BẢN THẢO HỢP ĐỒNG ĐÃ ĐƯỢC IN ẤN RANDOM SỐ LƯU TRỮ. CHỜ KHÁCH HÀNG KÝ CHỐT GIAO KÈM MỘT CỌC BỀN VỮNG.
        </div>
    <?php elseif(isset($_GET['msg']) && $_GET['msg'] === 'signed'): ?>
        <div class="alert alert-success fw-bold text-center mx-auto" style="max-width: 850px;">
            <i class="fa-solid fa-file-signature me-1 text-danger fs-4 align-middle"></i> 
            CHẤP BÚT THÀNH CÔNG! HỢP ĐỒNG ĐÃ BẬT TRẠNG THÁI ACTIVE. PHÒNG ĐÃ CÓ CHỦ!
        </div>
    <?php endif; ?>

    <div class="contract-paper">
        
        <?php if($thongTinHD['trangThai'] == 1): ?>
            <!-- ĐÓNG DẤU HIỆU LỰC MỘT KHI ĐÃ KÝ -->
            <div class="brand-stamp signed" style="font-size: 1.3rem;">BẢN CHÍNH<br/>ĐÃ KÝ</div>
        <?php else: ?>
            <div class="brand-stamp">BẢN THẢO<br/>CHỜ DUYỆT</div>
        <?php endif; ?>

        <div class="header-hd">
            <h4 class="fw-bold mb-0">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</h4>
            <h5 class="fw-bold text-decoration-underline mb-4">Độc lập - Tự do - Hạnh phúc</h5>
            <h3 class="fw-bold mt-4">HỢP ĐỒNG THUÊ KHÔNG GIAN CAO ỐC</h3>
            <div class="fst-italic">Số Ref: <?= htmlspecialchars($thongTinHD['soHopDong']) ?>/BST-2026</div>
        </div>

        <p>Hôm nay, ngày <?= date('d', strtotime($thongTinHD['ngayLap'])) ?> tháng <?= date('m', strtotime($thongTinHD['ngayLap'])) ?> năm <?= date('Y', strtotime($thongTinHD['ngayLap'])) ?>, tại BQL Blue Sky Tower, chúng tôi gồm:</p>
        
        <p class="mb-0"><strong>BÊN CHO THUÊ (BÊN A): BAN QUẢN LÝ BLUE SKY TOWER</strong></p>
        <p class="mb-0">- Đại diện kinh doanh: <strong>Ông/Bà <?= htmlspecialchars($thongTinHD['tenNV']) ?></strong></p>
        
        <p class="mb-0 mt-3"><strong>BÊN THUÊ (BÊN B): ĐỐI TÁC THƯƠNG MẠI</strong></p>
        <p class="mb-0">- Tên cá nhân / Doanh nghiệp: <strong><?= htmlspecialchars($thongTinHD['tenKH']) ?></strong></p>
        <p class="mb-0">- Số CCCD / Mã Cty: <?= htmlspecialchars($thongTinHD['cccd']) ?></p>
        <p class="mb-0">- Số Điện Thoại: <?= htmlspecialchars($thongTinHD['sdt']) ?></p>
        <p class="mb-0">- Địa chỉ thường trú: <?= htmlspecialchars($thongTinHD['diaChi']) ?></p>

        <p class="mt-4 fw-bold text-decoration-underline">ĐIỀU 1: ĐỐI TƯỢNG VÀ THỜI GIAN THUÊ</p>
        <p>1. Bên A đồng ý cho Bên B thuê phần diện tích tại:</p>
        <ul style="line-height: 25px; margin-bottom: 20px;">
            <?php foreach($listCP as $p): ?>
                <li>Mã Phòng <strong><?= htmlspecialchars($p['maPhong']) ?></strong> (<?= htmlspecialchars($p['tenPhong']) ?>) - Diện tích <?= $p['dienTich'] ?>m². Đơn giá: <?= number_format($p['giaThue'], 0) ?> VNĐ/tháng.</li>
            <?php endforeach; ?>
        </ul>
        <p>2. Thời gian thuê từ ngày <strong><?= date('d/m/Y', strtotime($thongTinHD['ngayBatDau'])) ?></strong> đến hết ngày <strong><?= date('d/m/Y', strtotime($thongTinHD['ngayKetThuc'])) ?></strong>.</p>
        <p>3. Trong ngày ký hiệu lực này Biên Bản này, Bên B xác nhận tiến hành giao một khoản Cọc Bền Vững (Security Deposit) trị giá: <strong class="text-danger"><?= number_format($thongTinHD['tienTienCoc'], 0) ?> VNĐ</strong> nhằm giữ không gian độc quyền.</p>
        
        <div class="row mt-5" style="line-height: 1.5; font-family: 'Segoe UI', sans-serif;">
            <div class="col-6 text-center">
                <p class="fw-bold mb-5 pb-5">ĐẠI DIỆN BÊN A<br/> <span class="fw-normal fst-italic text-muted">(Ký & Đóng Dấu)</span></p>
                <div class="" style="color: var(--primary); font-family: cursive; font-size: 1.5rem;">Đã xác nhận</div>
            </div>
            <div class="col-6 text-center">
                <p class="fw-bold mb-5 pb-5">ĐẠI DIỆN BÊN B<br/> <span class="fw-normal fst-italic text-muted">(Ký rõ họ tên)</span></p>
                <?php if($thongTinHD['trangThai'] == 1): ?>
                    <div style="color: #c9a66b; font-family: cursive; font-size: 1.5rem;"><?= htmlspecialchars($thongTinHD['tenKH']) ?></div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- KHU VỰC NÚT ĐIỀU KHIỂN CHỐT GIAO DỊCH -->
    <?php if((int)$thongTinHD['trangThai'] === 3): ?>
        <div class="text-center mt-4 border-top pt-4" style="max-width: 850px; margin: 0 auto;">
            <form action="hd_ky_submit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= function_exists('generateCSRFToken') ? generateCSRFToken() : '' ?>">
                <input type="hidden" name="soHopDong" value="<?= htmlspecialchars($thongTinHD['soHopDong']) ?>">
                
                <h6 class="text-danger fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> XÁC MINH CỨNG QUY TRÌNH</h6>
                <p class="text-muted small">Kiểm tra thông tin cùng khách hàng cẩn trọng. Sau khi nhấn Duyệt, Phòng này sẽ chuyển Cờ thành (Đã Thuê) và mở hiệu lực Audit Báo Cáo Tài Chính.</p>
                
                <button type="submit" class="btn btn-sign btn-lg px-5 my-2 shadow">
                    <i class="fa-solid fa-signature me-2"></i> ỦY QUYỀN DUYỆT & ĐÓNG DẤU CHI MẠNG THÀNH CÔNG
                </button>
            </form>
        </div>
    <?php else: ?>
        <!-- MÀN HÌNH ĐÃ KÝ THÀNH CÔNG -> NHẬT SẼ RÁP EXPORT BẢN IN PDF NẰM Ở ĐÂY SAU NÀY -->
        <div class="text-center mt-4 border-top pt-4" style="max-width: 850px; margin: 0 auto;">
             <a href="hd_hienthi.php" class="btn btn-outline-secondary px-4 me-2"><i class="fa-solid fa-arrow-left me-1"></i> Quay Lại Danh Sách</a>
             <button onclick="window.print();" class="btn btn-outline-danger px-4" title="In / Xuất file PDF">
                 <i class="fa-solid fa-print me-1"></i> Xuất bản lưu Trữ Bản PDF
             </button>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
