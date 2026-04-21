<?php
// Khởi tạo session (nếu chưa có)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bổ sung CSRF token nếu chưa có (Dùng cho form tìm kiếm)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include file kết nối CSDL (biến \$pdo)
require_once 'includes/common/db.php';
$pdo = Database::getInstance()->getConnection();

// Thực thi truy vấn lấy 3 phòng nổi bật
$featuredRooms = [];
try {
    if (isset($pdo)) {
        $sql = "SELECT p.maPhong, p.tenPhong, p.loaiPhong, p.dienTich, p.giaThue, p.trangThai, t.tenTang AS tang,
                       (SELECT urlHinhAnh FROM PHONG_HINH_ANH pha WHERE pha.maPhong = p.maPhong ORDER BY pha.is_thumbnail DESC LIMIT 1) AS hinhAnh
                FROM PHONG p 
                JOIN TANG t ON p.maTang = t.maTang 
                WHERE p.deleted_at IS NULL 
                LIMIT 3";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $featuredRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Lỗi truy vấn CSDL: " . $e->getMessage());
}

// Include layout chung
include_once 'includes/public/header.php';
include_once 'includes/public/navbar.php';
?>

<style>
    body {
        background-color: #f4f7f9;
        color: #1f2a44;
    }

    .btn-outline-brand {
        border: 1px solid #1e3a5f;
        color: #1e3a5f;
        background-color: transparent;
        transition: all 0.3s ease;
        border-radius: 8px;
    }

    .btn-outline-brand:hover {
        background-color: #1e3a5f;
        color: #ffffff;
    }

    /* Bổ sung CSS riêng cho file index nếu cần thiết (để đảm bảo yêu cầu layout) */
    .hero-carousel .carousel-item {
        height: 80vh;
        min-height: 600px;
    }
    
    .hero-carousel .carousel-item img {
        object-fit: cover;
        height: 100%;
        width: 100%;
    }

    .hero-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(135deg, rgba(30, 58, 95, 0.8) 0%, rgba(0, 0, 0, 0.4) 100%);
        z-index: 1;
    }

    .hero-carousel .carousel-caption {
        z-index: 2;
        top: 50%;
        transform: translateY(-50%);
        bottom: initial;
    }

    .quick-search-wrapper {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 15px 40px rgba(30,58,95,0.1);
        padding: 25px;
        margin-top: 20px;
        margin-bottom: 15px;
        position: relative;
        z-index: 10;
        max-width: 1000px; /* Điều khiển mật độ khoảng 70-80% màn hình trên desktop */
        margin-left: auto;
        margin-right: auto;
    }

    /* Đảm bảo thẻ card cân đối, 70-80% diện tích màn hình */
    .featured-container {
        max-width: 1140px;
        margin: 0 auto;
    }
    
    /* Ghi đè thêm để đảm bảo card bo góc và đổ bóng nhẹ nếu base css không có */
    .card-brand {
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }
    
    .card-brand:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(30,58,95,0.15);
    }
    
    .card-brand img {
        height: 220px;
        object-fit: cover;
    }

    .badge-brand--success {
        background-color: #28a745;
        color: white;
        padding: 0.4em 0.8em;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .btn-brand--accent {
        background-color: #c9a66b; /* Màu Gold */
        color: #1f2a44; /* Text color */
        font-weight: 700;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-brand--accent:hover {
        background-color: #b5925a;
        color: #fff;
    }

    .text-brand-primary {
        color: #1e3a5f;
    }
</style>

<!-- Hero Carousel moved up to be flush with header -->


<!-- == 1. HERO CAROUSEL == -->
<section id="heroCarousel" class="carousel slide carousel-fade hero-carousel" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">
        <!-- Slide 1 -->
        <div class="carousel-item active" data-bs-interval="4000">
            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80" alt="Building Slide 1">
            <div class="hero-overlay"></div>
            <div class="carousel-caption d-none d-md-block">
                <h1 class="display-3 fw-bold text-uppercase" style="color: #c9a66b; letter-spacing: 2px; text-shadow: 0 4px 10px rgba(0,0,0,0.5);">The Sapphire</h1>
                <p class="fs-4 fw-light text-white mb-0">Tầm Nhìn Vượt Trội - Nâng Tầm Doanh Nghiệp</p>
            </div>
            <!-- Caption Responsive Mobile -->
            <div class="carousel-caption d-block d-md-none">
                <h2 class="fw-bold text-uppercase" style="color: #c9a66b;">The Sapphire</h2>
                <p class="fw-light text-white">Nâng Tầm Doanh Nghiệp</p>
            </div>
        </div>
        <!-- Slide 2 -->
        <div class="carousel-item" data-bs-interval="4000">
            <img src="https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1600&q=80" alt="Building Slide 2">
            <div class="hero-overlay"></div>
            <div class="carousel-caption d-none d-md-block">
                <h1 class="display-3 fw-bold text-uppercase text-white" style="text-shadow: 0 4px 10px rgba(0,0,0,0.5);">Không Gian Hiện Đại</h1>
                <p class="fs-4 fw-light mb-0" style="color: #c9a66b;">Môi Trường Chuyên Nghiệp Bậc Nhất</p>
            </div>
            <div class="carousel-caption d-block d-md-none">
                <h2 class="fw-bold text-uppercase text-white">Không Gian Hiện Đại</h2>
                <p style="color: #c9a66b;">Chuyên Nghiệp Bậc Nhất</p>
            </div>
        </div>
        <!-- Slide 3 -->
        <div class="carousel-item" data-bs-interval="5000">
            <img src="https://images.unsplash.com/photo-1416339134316-0e91dc9ded92?auto=format&fit=crop&w=1600&q=80" alt="Building Slide 3">
            <div class="hero-overlay"></div>
            <div class="carousel-caption d-none d-md-block">
                <h1 class="display-3 fw-bold text-uppercase" style="color: #c9a66b; text-shadow: 0 4px 10px rgba(0,0,0,0.5);">Vị Trí Đắc Địa</h1>
                <p class="fs-4 fw-light text-white mb-0">Liên Kết Trái Tim Thương Mại Sầm Uất</p>
            </div>
            <div class="carousel-caption d-block d-md-none">
                <h2 class="fw-bold text-uppercase" style="color: #c9a66b;">Vị Trí Đắc Địa</h2>
                <p class="text-white">Trung Tâm Thương Mại</p>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</section>


<!-- == 2. QUICK SEARCH == -->
<section class="container px-3 px-md-4">
    <div class="quick-search-wrapper">
        <form action="phong_trong.php" method="GET" class="row g-3 align-items-end justify-content-center">
            
            <!-- Bảo mật Form: CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="col-12 col-md-3">
                <label for="keyword" class="form-label fw-bold text-brand-primary mb-1">Tìm kiếm:</label>
                <input type="text" id="keyword" name="keyword" class="form-control border-0 shadow-sm rounded-3 py-2 bg-light" placeholder="Nhập tên phòng, vị trí...">
            </div>

            <div class="col-12 col-md-3">
                <label for="khoangGia" class="form-label fw-bold text-brand-primary mb-1">Khoảng giá:</label>
                <select id="khoangGia" name="khoangGia" class="form-select border-0 shadow-sm rounded-3 py-2 bg-light">
                    <option value="">Tất cả mức giá</option>
                    <option value="0-10">Dưới 10 Triệu</option>
                    <option value="10-20">10 - 20 Triệu</option>
                    <option value="20-50">20 - 50 Triệu</option>
                    <option value="50-1000">Trên 50 Triệu</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="dienTich" class="form-label fw-bold text-brand-primary mb-1">Diện tích (m²):</label>
                <select id="dienTich" name="dienTich" class="form-select border-0 shadow-sm rounded-3 py-2 bg-light">
                    <option value="">Mọi diện tích</option>
                    <option value="0-50">Dưới 50m²</option>
                    <option value="50-100">50 - 100m²</option>
                    <option value="100-200">100 - 200m²</option>
                    <option value="200-5000">Trên 200m²</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="tang" class="form-label fw-bold text-brand-primary mb-1">Tầng:</label>
                <select id="tang" name="tang" class="form-select border-0 shadow-sm rounded-3 py-2 bg-light">
                    <option value="">Chọn tầng</option>
                    <option value="Trệt">Trệt</option>
                    <?php for($i=1; $i<=10; $i++) echo "<option value=\"Tầng $i\">Tầng $i</option>"; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-brand--accent w-100 py-2 shadow-sm rounded-3 text-uppercase">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Tìm
                </button>
            </div>
        </form>
    </div>
</section>

<!-- == 3. FEATURED ROOMS == -->
<section class="container pb-5 mt-0 featured-container">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-uppercase position-relative d-inline-block text-brand-primary">
            Phòng Tiêu Biểu
            <span class="position-absolute start-50 translate-middle-x" style="bottom: -15px; width: 60px; height: 3px; background-color: #c9a66b;"></span>
        </h2>
    </div>

    <div class="row g-4 justify-content-center">
        <?php foreach ($featuredRooms as $room): ?>
            <?php 
                // Sử dụng ảnh mặc định nếu không có ảnh
                $hinhAnh = !empty($room['hinhAnh']) ? $room['hinhAnh'] : 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80'; 
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card card-brand h-100">
                    <div class="position-relative">
                        <!-- XSS protection for Display -->
                        <img src="<?php echo htmlspecialchars($hinhAnh); ?>" class="card-img-top w-100" alt="<?php echo htmlspecialchars($room['tenPhong']); ?>">
                        
                        <?php if ($room['trangThai'] == 1): ?>
                            <span class="position-absolute top-0 start-0 m-3 badge badge-brand--success shadow-sm">
                                <i class="fa-solid fa-check-circle me-1"></i> Phòng Trống
                            </span>
                        <?php else: ?>
                            <span class="position-absolute top-0 start-0 m-3 badge bg-secondary shadow-sm">
                                <i class="fa-solid fa-lock me-1"></i> Đã Có Khách
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body p-4 bg-white">
                        <h4 class="card-title fw-bold text-brand-primary mb-1">
                            <?php echo htmlspecialchars($room['tenPhong']); ?>
                        </h4>
                        <p class="text-muted small mb-3">
                            <i class="fa-solid fa-tag me-1" style="color: #c9a66b;"></i> 
                            <?php echo htmlspecialchars($room['loaiPhong']); ?>
                        </p>
                        
                        <div class="row text-center mb-3 g-2">
                            <div class="col-6">
                                <div class="bg-light p-2 rounded-3 text-brand-primary">
                                    <i class="fa-solid fa-clone text-muted d-block mb-1"></i>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($room['dienTich']); ?> m²</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light p-2 rounded-3 text-brand-primary">
                                    <i class="fa-solid fa-layer-group text-muted d-block mb-1"></i>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($room['tang']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top">
                            <div class="fw-bold" style="color: #c9a66b; font-size: 1.25rem;">
                                <?php echo number_format($room['giaThue'], 0, ',', '.'); ?> đ<span class="fs-6 fw-normal text-muted">/tháng</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pb-4 px-4 pt-0">
                        <a href="chi_tiet_phong.php?maPhong=<?php echo htmlspecialchars(urlencode($room['maPhong'])); ?>" class="btn w-100 py-2 fw-bold btn-outline-brand">
                            Xem Chi Tiết
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Include layout footer -->
<?php include_once 'includes/public/footer.php'; ?>
