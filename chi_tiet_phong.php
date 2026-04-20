<?php
/**
 * PROJECT: Quản lý Cao ốc (Office Rental Management)
 * PAGE: chi_tiet_phong.php (Chi tiết phòng)
 */

// 1. Khởi tạo Session và CSRF Token bảo mật
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Kiểm tra input hợp lệ
if (empty($_GET['maPhong'])) {
    $_SESSION['error_msg'] = "Mã phòng không hợp lệ hoặc không được cung cấp.";
    header("Location: phong_trong.php");
    exit;
}
$maPhong = $_GET['maPhong'];

// 3. Kết nối CSDL & Functions
require_once 'includes/common/db.php';
require_once 'includes/common/functions.php';
$pdo = Database::getInstance()->getConnection();

// 4. Truy vấn CSDL
try {
    // 4.1. Lấy thông tin phòng (JOIN TANG, CAO_OC)
    $sql = "
        SELECT p.maPhong, p.tenPhong, p.loaiPhong, p.dienTich, p.soChoLamViec, p.giaThue, p.trangThai, p.moTaViTri, 
               t.tenTang, 
               c.tenCaoOc, c.diaChi
        FROM PHONG p
        JOIN TANG t ON p.maTang = t.maTang
        JOIN CAO_OC c ON t.maCaoOc = c.maCaoOc
        WHERE p.maPhong = :maPhong AND p.deleted_at IS NULL
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':maPhong' => $maPhong]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    // Nếu không tồn tại phòng theo tham số truyền vào
    if (!$room) {
        $_SESSION['error_msg'] = "Không tìm thấy thông tin phòng! Phòng có thể đã bị xóa hoặc ẩn.";
        header("Location: phong_trong.php");
        exit;
    }

    // 4.2. Lấy danh sách hình ảnh (Gallery)
    $sqlImages = "SELECT urlHinhAnh FROM PHONG_HINH_ANH WHERE maPhong = :maPhong ORDER BY is_thumbnail DESC";
    $stmtImages = $pdo->prepare($sqlImages);
    $stmtImages->execute([':maPhong' => $maPhong]);
    $images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    error_log("DB_ERROR tại chi_tiet_phong.php: " . $ex->getMessage());
    $_SESSION['error_msg'] = "Hệ thống đang gặp sự cố, vui lòng thử lại sau.";
    header("Location: phong_trong.php");
    exit;
}

// 5. Layout & View (Không sử dụng kiemTraSession để Public Access)
include_once 'includes/public/header.php';
include_once 'includes/public/navbar.php';
?>

<!-- Import Lightbox2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet" />

<style>
    /* BEM & Typography System */
    .room-header {
        background: linear-gradient(135deg, rgba(30, 58, 95, 0.95), rgba(21, 41, 67, 0.95));
        color: #ffffff;
        padding: 3.5rem 0;
        margin-bottom: 2.5rem;
    }

    .room-gallery__main {
        width: 100%;
        height: 500px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        margin-bottom: 1rem;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .room-gallery__main:hover {
        transform: scale(1.015);
    }

    .room-gallery__thumb {
        width: 100%;
        height: 110px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        opacity: 0.65;
        transition: opacity 0.3s, transform 0.3s;
    }

    .room-gallery__thumb:hover {
        opacity: 1;
        transform: translateY(-4px);
    }

    .btn-brand--accent {
        background-color: #c9a66b; /* Gold brand color */
        color: #1e3a5f;
        border: none;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 10px rgba(201, 166, 107, 0.3);
        transition: all 0.3s ease;
    }

    .btn-brand--accent:hover {
        background-color: #b39158;
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(201, 166, 107, 0.4);
    }

    .info-card {
        background: #f8f9fa;
        border-left: 4px solid #1e3a5f; /* Navy brand color */
        padding: 1.25rem 1rem;
        border-radius: 0 8px 8px 0;
        transition: background 0.3s ease;
    }

    .info-card:hover {
        background: #f1f3f5;
    }

    .info-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 0.4rem;
    }

    .info-value {
        color: #1e3a5f;
        font-size: 1.15rem;
        font-weight: 600;
    }

    .card-brand {
        border-radius: 8px;
        overflow: hidden;
    }
</style>

<!-- Phần Header Tiêu Đề Phòng -->
<section class="room-header text-center text-lg-start">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 col-md-8">
                <h1 class="display-5 fw-bold mb-2"><?= e($room['tenPhong']) ?></h1>
                <p class="lead mb-0 opacity-75">
                    <i class="fa-solid fa-hotel me-2"></i> <?= e($room['tenCaoOc']) ?> - <?= e($room['tenTang']) ?>
                </p>
            </div>
            <div class="col-12 col-md-4 text-lg-end mt-4 mt-lg-0">
                <span class="badge px-4 py-3 fs-6 <?= $room['trangThai'] == 1 ? 'bg-success' : 'bg-warning' ?>" style="border-radius: 50px;">
                    <i class="fa-solid <?= $room['trangThai'] == 1 ? 'fa-check-circle' : 'fa-clock' ?> me-2"></i>
                    <?= $room['trangThai'] == 1 ? 'Phòng Đang Trống' : 'Đã Có Người Thuê' ?>
                </span>
            </div>
        </div>
    </div>
</section>

<div class="container mb-5">
    <div class="row g-5">
        <!-- == CỘT TRÁI: Gallery & Chi Tiết == -->
        <div class="col-12 col-lg-8">
            
            <!-- Room Gallery -->
            <div class="mb-5">
                <?php if (!empty($images)): ?>
                    <a href="<?= e($images[0]['urlHinhAnh']) ?>" data-lightbox="room-gallery">
                        <img src="<?= e($images[0]['urlHinhAnh']) ?>" class="room-gallery__main" alt="Main View">
                    </a>
                    
                    <div class="row g-2">
                        <?php for($i = 1; $i < count($images); $i++): ?>
                            <div class="col-3 col-md-2">
                                <a href="<?= e($images[$i]['urlHinhAnh']) ?>" data-lightbox="room-gallery">
                                    <img src="<?= e($images[$i]['urlHinhAnh']) ?>" class="room-gallery__thumb" alt="Thumb <?= $i ?>">
                                </a>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-secondary bg-opacity-10 d-flex flex-column align-items-center justify-content-center" style="height: 400px; border-radius: 12px;">
                        <i class="fa-regular fa-image fs-1 text-muted mb-2"></i>
                        <span class="text-muted small">Chưa có hình ảnh mô tả cho phòng này.</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Features Icons -->
            <div class="row g-4 mb-5">
                <div class="col-6 col-md-3">
                    <div class="info-card">
                        <div class="info-label">Diện tích</div>
                        <div class="info-value"><?= e($room['dienTich']) ?> m²</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-card">
                        <div class="info-label">Loại phòng</div>
                        <div class="info-value text-truncate" title="<?= e($room['loaiPhong']) ?>"><?= e($room['loaiPhong']) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-card">
                        <div class="info-label">Sức chứa</div>
                        <div class="info-value"><?= e($room['soChoLamViec']) ?> người</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-card">
                        <div class="info-label">Giá thuê</div>
                        <div class="info-value"><?= formatTien($room['giaThue']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="mb-5">
                <h4 class="fw-bold mb-4" style="color: #1e3a5f; border-bottom: 3px solid #c9a66b; display: inline-block; padding-bottom: 5px;">
                    Thông Số Kỹ Thuật
                </h4>
                <div class="card border-0 bg-transparent">
                    <div class="card-body p-0">
                        <table class="table table-borderless table-striped align-middle mb-0">
                            <tbody>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Mã định danh</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= e($room['maPhong']) ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Cao ốc sở hữu</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= e($room['tenCaoOc']) ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Vị trí địa chỉ</th>
                                    <td class="fw-semibold text-end py-3 text-muted small"><?= e($room['diaChi']) ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Tầng</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= e($room['tenTang']) ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Loại không gian</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= e($room['loaiPhong']) ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Khả năng chứa</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= e($room['soChoLamViec']) ?> người</td>
                                </tr>
                                <tr>
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3 align-top">Mô tả vị trí</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;">
                                        <?= !empty($room['moTaViTri']) ? nl2br(e($room['moTaViTri'])) : 'Đang cập nhật' ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-4 text-center mt-4 shadow-sm" style="background-color: #1e3a5f; border-radius: 8px;">
                        <div class="mb-1 text-uppercase fw-bold small" style="color: #c9a66b;">Mức Giá Thuê Đề Xuất</div>
                        <div class="fs-3 fw-bold" style="color: #ffffff;">
                            <?= formatTien($room['giaThue']) ?> <span class="fs-6 fw-normal opacity-75">VND/tháng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- == CỘT PHẢI: Liên Hệ & Đăng Ký == -->
        <aside class="col-12 col-lg-4">
            
            <div class="card card-brand shadow-sm border-0 mb-4 overflow-hidden">
                <div class="p-4 text-center" style="background-color: #f1f3f5;">
                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Hotline Hỗ Trợ 24/7</h6>
                    <h4 class="fw-bold mb-0" style="color: #1e3a5f;">(+84) 912 345 678</h4>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-muted small mb-4">Cần thêm thông tin chi tiết hoặc muốn xem văn phòng trực tiếp? Liên hệ ngay với chúng tôi.</p>
                    <a href="tel:0912345678" class="btn w-100 py-3 fw-bold shadow-sm" style="background-color: #c9a66b; color: #1e3a5f;">
                        <i class="fa-solid fa-phone-volume me-2"></i> GỌI TƯ VẤN NGAY
                    </a>
                </div>
            </div>

            <!-- Box Form Đăng Ký Thuê Phòng -->
            <div class="card card-brand shadow-sm border-0 mb-4" style="border-top: 5px solid #c9a66b !important;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-uppercase text-center" style="color: #1e3a5f;">
                        Đăng Ký Tư Vấn Thuê
                    </h5>
                    <p class="text-muted text-center small mb-4">Để lại thông tin, chúng tôi sẽ liên hệ lại với bạn trong 24h.</p>
                    
                    <?php if ($room['trangThai'] == 1): ?>
                        <form action="dang_ky_thue_submit.php" method="POST" class="needs-validation" novalidate>
                            <!-- Bảo mật CSRF -->
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="maPhong" value="<?= e($room['maPhong']) ?>">
                            
                            <div class="mb-3">
                                <label for="hoTen" class="form-label small fw-bold text-muted">Họ và Tên <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0 ps-0 shadow-none" id="hoTen" name="hoTen" required placeholder="Nguyễn Văn A" maxlength="100">
                                    <div class="invalid-feedback">Vui lòng nhập họ và tên hợp lệ.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="soDienThoai" class="form-label small fw-bold text-muted">Số điện thoại <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0 ps-0 shadow-none" id="soDienThoai" name="soDienThoai" required pattern="(84|0[3|5|7|8|9])+([0-9]{8})\b" title="Vui lòng nhập định dạng số điện thoại Việt Nam (VD: 0912345678)" placeholder="09xx xxx xxx">
                                    <div class="invalid-feedback">Vui lòng nhập số điện thoại Việt Nam hợp lệ (VD: 0912345678).</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label small fw-bold text-muted">Email <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control bg-light border-start-0 ps-0 shadow-none" id="email" name="email" required placeholder="email@congty.com" maxlength="100">
                                    <div class="invalid-feedback">Vui lòng nhập định dạng email hợp lệ.</div>
                                </div>
                            </div>
                            
                            <!-- Hiển thị mức giá (Readonly) cho người dùng kiểm chứng -->
                            <div class="mb-4">
                                <label for="giaThueDisplay" class="form-label small fw-bold text-muted">Mức Giá Mới Nhất</label>
                                <input type="text" class="form-control fw-bold text-center border-0 py-2" style="background-color: #f1f3f5; color: #1e3a5f;" id="giaThueDisplay" value="<?= formatTien($room['giaThue']) ?> VND/tháng" readonly>
                            </div>
                            
                            <button type="submit" class="btn btn-brand--accent btn-lg w-100 py-3 mt-2 shadow-sm d-flex justify-content-center align-items-center gap-2">
                                <i class="fa-solid fa-paper-plane"></i> Gửi Yêu Cầu Thuê Phòng
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Ẩn Form, Hiển thị Alert khi trạng thái khác 1 (Đã thuê/Khác) -->
                        <div class="alert alert-warning text-center border-warning-subtle shadow-sm py-4 my-2" style="border-radius: 8px;">
                            <i class="fa-solid fa-triangle-exclamation fs-1 mb-3 d-block" style="color: #fd7e14;"></i>
                            <h6 class="fw-bold mb-2">Phòng hiện đã có người thuê</h6>
                            <p class="mb-0 small text-muted">Bạn không thể đăng ký tư vấn cho không gian này vào thời điểm hiện tại.</p>
                            <a href="phong_trong.php" class="btn btn-sm w-100 py-2 mt-3 fw-bold" style="background-color: #1e3a5f; color: white;">
                                Nhấn xem phòng tương tự
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </aside>
    </div>
</div>

<!-- Import Lightbox2 JS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<script>
    // Tùy chỉnh thuộc tính của Lightbox
    lightbox.option({
      'resizeDuration': 200,
      'wrapAround': true,
      'albumLabel': "Hình ảnh %1 / %2"
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
      'use strict'
      var forms = document.querySelectorAll('.needs-validation')
      Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
          form.classList.add('was-validated')
        }, false)
      })
    });
</script>

<?php include_once 'includes/public/footer.php'; ?>
