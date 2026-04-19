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
        $_SESSION['error_msg'] = "Không tìm thấy thông tin phòng! Phòng có thể đã bị xóa hoặc ẩm.";
        header("Location: phong_trong.php");
        exit;
    }

    // 4.2. Lấy danh sách hình ảnh (Gallery)
    $sqlImages = "SELECT urlHinhAnh FROM PHONG_HINH_ANH WHERE maPhong = :maPhong ORDER BY is_thumbnail DESC";
    $stmtImages = $pdo->prepare($sqlImages);
    $stmtImages->execute([':maPhong' => $maPhong]);
    $images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    error_log("DB_ERROR ở chi_tiet_phong.php: " . $ex->getMessage());
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
                <p class="mb-1 opacity-75 fs-5">
                    <i class="fa-solid fa-building me-2" style="color: #c9a66b;"></i>Tòa nhà: <strong><?= e($room['tenCaoOc']) ?></strong> - Vị trí: <strong><?= e($room['tenTang']) ?></strong>
                </p>
                <p class="small opacity-75 mb-0">
                    <i class="fa-solid fa-map-location-dot me-2"></i><?= e($room['diaChi']) ?>
                </p>
            </div>
            <div class="col-12 col-md-4 text-lg-end mt-4 mt-lg-0">
                <span class="badge rounded-pill fs-6 px-4 py-2 <?= $room['trangThai'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                    <i class="fa-solid <?= $room['trangThai'] == 1 ? 'fa-check-circle' : 'fa-lock' ?> me-2"></i>
                    <?= $room['trangThai'] == 1 ? 'Sẵn Sàng Cho Thuê' : 'Đã Thuê / Đang Bảo Trì' ?>
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Content Layout Cột -->
<div class="container pb-5">
    <!-- Hiển thị Box Thông báo Alert -->
    <div class="mb-4">
        <?php include_once 'includes/admin/notifications.php'; ?>
    </div>

    <!-- Chia Layout Bootstrap Grid: 8/12 Gallery - 4/12 Specs & Form -->
    <div class="row g-5">
        
        <!-- Cột Gallery Ảnh (8/12) -->
        <article class="col-12 col-lg-8">
            <h3 class="fw-bold mb-4" style="color: #1e3a5f;">
                <i class="fa-regular fa-images me-2" style="color: #c9a66b;"></i> Hình Ảnh Không Gian Thực Tế
            </h3>
            
            <?php if (!empty($images)): ?>
                <!-- Ảnh Đại Diện Lớn -->
                <a href="<?= e($images[0]['urlHinhAnh']) ?>" data-lightbox="room-gallery" data-title="<?= e($room['tenPhong']) ?>">
                    <img src="<?= e($images[0]['urlHinhAnh']) ?>" alt="<?= e($room['tenPhong']) ?>" class="room-gallery__main img-fluid">
                </a>
                
                <!-- Danh Sách Ảnh Nhỏ (Thumbnail) -->
                <div class="row g-2 mt-2">
                    <?php for ($i = 1; $i < count($images); $i++): ?>
                        <div class="col-4 col-md-3">
                            <a href="<?= e($images[$i]['urlHinhAnh']) ?>" data-lightbox="room-gallery" data-title="<?= e($room['tenPhong']) ?>">
                                <img src="<?= e($images[$i]['urlHinhAnh']) ?>" alt="<?= e($room['tenPhong']) ?>" class="room-gallery__thumb img-fluid">
                            </a>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <!-- Xử lý Placeholder trống khi không có ảnh -->
                <div class="alert bg-light border-0 text-center py-5 rounded-3">
                    <i class="fa-regular fa-image fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted fw-bold">Chưa cập nhật hình ảnh</h5>
                    <p class="mb-0 text-muted small">Hình ảnh của phòng này đang trong quá trình cập nhật, xin vui lòng quay lại sau</p>
                </div>
            <?php endif; ?>
        </article>

        <!-- Cột Thông Số Kỹ Thuật (4/12) - Rơi xuống dưới cùng lúc ở Mobile 375px -->
        <aside class="col-12 col-lg-4">
            
            <!-- Box Thông Tin Chi Tiết -->
            <div class="card card-brand shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold mb-0" style="color: #1e3a5f;">
                        <i class="fa-solid fa-list-check me-2" style="color: #c9a66b;"></i>Thông Tin Kỹ Thuật
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive mb-3">
                        <table class="table table-borderless align-middle mb-0">
                            <tbody>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3" style="width: 45%;">Mã Phòng</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= e($room['maPhong']) ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Phân loại</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= e($room['loaiPhong']) ?></td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Diện tích</th>
                                    <td class="fw-semibold text-end py-3" style="color: #1e3a5f;"><?= formatTien($room['dienTich']) ?> m²</td>
                                </tr>
                                <tr class="border-bottom">
                                    <th scope="row" class="text-muted text-uppercase fw-bold small py-3">Sức chứa</th>
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
    // Tuỳ chỉnh thuộc tính của Lightbox
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
