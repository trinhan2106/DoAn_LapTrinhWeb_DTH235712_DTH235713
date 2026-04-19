<?php
/**
 * PROJECT: Quản lý Cao ốc (Office Rental Management)
 * PAGE: phong_trong.php (Danh sách phòng trống)
 * DESCRIPTION: Hiển thị danh sách phòng theo Grid, có bộ lọc và phân trang.
 */

// 1. Khởi tạo session & CSRF bảo mật
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Mock Data (Giả lập dữ liệu từ CSDL)
$mockRooms = [
    [
        'maPhong'   => 'P301',
        'ten'       => 'Emerald Suite 301',
        'tang'      => '3',
        'gia'       => 18500000,
        'dienTich'  => 75,
        'choNgoi'   => 12,
        'hinhAnh'   => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=600&q=80',
        'trangThai' => 'Trống'
    ],
    [
        'maPhong'   => 'P205',
        'ten'       => 'Business Center 205',
        'tang'      => '2',
        'gia'       => 12000000,
        'dienTich'  => 45,
        'choNgoi'   => 8,
        'hinhAnh'   => 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=600&q=80',
        'trangThai' => 'Trống'
    ],
    [
        'maPhong'   => 'P102',
        'ten'       => 'Ruby Executive 102',
        'tang'      => '1',
        'gia'       => 25000000,
        'dienTich'  => 110,
        'choNgoi'   => 20,
        'hinhAnh'   => 'https://images.unsplash.com/photo-1416339134316-0e91dc9ded92?auto=format&fit=crop&w=600&q=80',
        'trangThai' => 'Trống'
    ],
    [
        'maPhong'   => 'P408',
        'ten'       => 'Penthouse Office 408',
        'tang'      => '4',
        'gia'       => 45000000,
        'dienTich'  => 200,
        'choNgoi'   => 35,
        'hinhAnh'   => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=600&q=80',
        'trangThai' => 'Trống'
    ],
    [
        'maPhong'   => 'P001',
        'ten'       => 'Grand Lobby 001',
        'tang'      => 'Trệt',
        'gia'       => 35000000,
        'dienTich'  => 150,
        'choNgoi'   => 25,
        'hinhAnh'   => 'https://images.unsplash.com/photo-1568992687947-868a62a9f521?auto=format&fit=crop&w=600&q=80',
        'trangThai' => 'Trống'
    ],
    [
        'maPhong'   => 'P305',
        'ten'       => 'Creative Zone 305',
        'tang'      => '3',
        'gia'       => 9500000,
        'dienTich'  => 35,
        'choNgoi'   => 5,
        'hinhAnh'   => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80',
        'trangThai' => 'Trống'
    ]
];

// 3. Xử lý logic lọc (Filter state)
$f_tang = $_GET['tang'] ?? '';
$f_gia  = $_GET['khoangGia'] ?? '';
$f_cho  = $_GET['sucChua'] ?? '';

// 4. Nhúng Header & Navbar
include_once 'includes/public/header.php';
include_once 'includes/public/navbar.php';
?>

<style>
    /* Page specific styles to enhance premium feel */
    .phong-trong-hero {
        background: linear-gradient(rgba(30, 58, 95, 0.9), rgba(30, 58, 95, 0.8)), 
                    url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=1600&q=80');
        background-size: cover;
        background-position: center;
        padding: 4rem 0;
        color: white;
        margin-bottom: 3rem;
    }

    .filter-sidebar {
        position: sticky;
        top: 20px;
        z-index: 100;
    }

    .card-brand {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .card-brand:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(30, 58, 95, 0.15) !important;
    }

    .card-brand__img-wrapper {
        height: 220px;
        overflow: hidden;
        position: relative;
    }

    .card-brand__img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .card-brand:hover .card-brand__img-wrapper img {
        transform: scale(1.08);
    }

    .pagination .page-link {
        color: var(--color-primary);
        border: none;
        margin: 0 3px;
        border-radius: 6px !important;
        font-weight: 600;
    }

    .pagination .page-item.active .page-link {
        background-color: var(--color-accent);
        color: var(--color-text);
        box-shadow: 0 4px 10px rgba(201, 166, 107, 0.3);
    }
</style>

<!-- Hero Section -->
<section class="phong-trong-hero text-center">
    <div class="container">
        <h1 class="display-5 fw-bold text-uppercase mb-2">Không Gian Làm Việc Chuyên Nghiệp</h1>
        <p class="lead opacity-75 mb-0">Khám phá các văn phòng trống tốt nhất tại The Sapphire</p>
    </div>
</section>

<div class="container pb-5">
    <!-- Flash Notifications -->
    <div class="mb-4">
        <?php include_once 'includes/admin/notifications.php'; ?>
    </div>

    <div class="row g-4">
        <!-- == SIDEBAR FILTER (3/12) == -->
        <aside class="col-12 col-lg-3">
            <div class="filter-sidebar">
                <div class="card card-brand p-4 shadow-sm">
                    <h5 class="fw-bold text-brand-primary mb-4 border-bottom pb-3">
                        <i class="fa-solid fa-sliders me-2 text-brand-accent"></i>Bộ Lọc Tìm Kiếm
                    </h5>
                    
                    <form action="phong_trong.php" method="GET">
                        <!-- Floor Filter -->
                        <div class="mb-3">
                            <label for="tang" class="form-label small fw-bold text-muted text-uppercase">Tầng</label>
                            <select name="tang" id="tang" class="form-select border-0 bg-light shadow-none py-2 px-3">
                                <option value="">Tất cả tầng</option>
                                <option value="Trệt" <?php echo ($f_tang == 'Trệt') ? 'selected' : ''; ?>>Trệt</option>
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($f_tang == $i) ? 'selected' : ''; ?>>Tầng <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Price Filter -->
                        <div class="mb-3">
                            <label for="khoangGia" class="form-label small fw-bold text-muted text-uppercase">Giá thuê tối đa (VNĐ)</label>
                            <input type="number" name="khoangGia" id="khoangGia" 
                                   class="form-control border-0 bg-light shadow-none py-2 px-3" 
                                   placeholder="VD: 20000000" 
                                   value="<?php echo htmlspecialchars($f_gia); ?>">
                        </div>

                        <!-- Capacity Filter -->
                        <div class="mb-4">
                            <label for="sucChua" class="form-label small fw-bold text-muted text-uppercase">Số chỗ làm việc</label>
                            <select name="sucChua" id="sucChua" class="form-select border-0 bg-light shadow-none py-2 px-3">
                                <option value="">Chọn số chỗ</option>
                                <option value="5" <?php echo ($f_cho == '5') ? 'selected' : ''; ?>>Dưới 5 chỗ</option>
                                <option value="10" <?php echo ($f_cho == '10') ? 'selected' : ''; ?>>5 - 10 chỗ</option>
                                <option value="20" <?php echo ($f_cho == '20') ? 'selected' : ''; ?>>10 - 20 chỗ</option>
                                <option value="50" <?php echo ($f_cho == '50') ? 'selected' : ''; ?>>Trên 20 chỗ</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-brand--accent w-100 py-2 fw-bold text-uppercase shadow-sm">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Lọc ngay
                        </button>
                        
                        <a href="phong_trong.php" class="btn btn-link w-100 mt-3 text-muted small text-decoration-none border-0">
                            <i class="fa-solid fa-rotate-right me-1"></i> Làm mới bộ lọc
                        </a>
                    </form>
                </div>

                <!-- Assistance Card -->
                <div class="card card-brand bg-brand-primary mt-4 p-4 text-white border-0" style="background-color: var(--color-primary);">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-headset me-2 text-brand-accent"></i>Cần hỗ trợ tư vấn?</h6>
                    <p class="small opacity-75 mb-3">Đội ngũ chuyên gia của chúng tôi luôn sẵn sàng hỗ trợ bạn tìm được văn phòng ưng ý nhất.</p>
                    <a href="tel:0123456789" class="btn btn-brand--accent btn-sm w-100 py-2 fw-bold">Gọi: 0123 456 789</a>
                </div>
            </div>
        </aside>

        <!-- == MAIN CONTENT (9/12) == -->
        <main class="col-12 col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="mb-0 text-muted">Hiển thị <span class="fw-bold text-brand-primary">6</span> kết quả phù hợp</p>
                <div class="d-none d-md-block">
                    <select class="form-select form-select-sm border-0 bg-light shadow-none">
                        <option>Mới nhất</option>
                        <option>Giá thấp đến cao</option>
                        <option>Giá cao đến thấp</option>
                        <option>Diện tích tăng dần</option>
                    </select>
                </div>
            </div>

            <!-- Room Grid -->
            <div class="row g-4">
                <?php foreach ($mockRooms as $room): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card card-brand h-100 rounded-3">
                            <div class="card-brand__img-wrapper">
                                <img src="<?php echo htmlspecialchars($room['hinhAnh']); ?>" 
                                     alt="<?php echo htmlspecialchars($room['ten']); ?>">
                                <span class="position-absolute top-0 start-0 m-3 badge badge-brand--success shadow-sm">
                                    <i class="fa-solid fa-check-circle me-1"></i> <?php echo htmlspecialchars($room['trangThai']); ?>
                                </span>
                            </div>
                            
                            <div class="card-body p-4 bg-white">
                                <h5 class="card-title fw-bold text-brand-primary mb-2 line-clamp-1">
                                    <?php echo htmlspecialchars($room['ten']); ?>
                                </h5>
                                <p class="small text-muted mb-3">
                                    <i class="fa-solid fa-location-dot me-1 text-brand-accent"></i> 
                                    Tầng <?php echo htmlspecialchars($room['tang']); ?> • Mã: <?php echo htmlspecialchars($room['maPhong']); ?>
                                </p>
                                
                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded-2 text-center">
                                            <span class="d-block small text-muted mb-1"><i class="fa-solid fa-clone"></i> Diện tích</span>
                                            <span class="fw-bold text-brand-primary small"><?php echo htmlspecialchars($room['dienTich']); ?> m²</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-light p-2 rounded-2 text-center">
                                            <span class="d-block small text-muted mb-1"><i class="fa-solid fa-person-shelter"></i> Sức chứa</span>
                                            <span class="fw-bold text-brand-primary small"><?php echo htmlspecialchars($room['choNgoi']); ?> chỗ</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="d-block small text-muted lh-1 mb-1">Giá thuê từ</span>
                                        <span class="fw-bold fs-5 text-brand-accent"><?php echo number_format($room['gia'], 0, ',', '.'); ?> đ</span>
                                        <span class="small text-muted">/tháng</span>
                                    </div>
                                </div>
                                
                                <div class="border-top pt-3">
                                    <a href="chi_tiet_phong.php?maPhong=<?php echo urlencode($room['maPhong']); ?>" 
                                       class="btn btn-brand--primary w-100 py-2 rounded-2 fw-semibold">
                                        Xem Chi Tiết <i class="fa-solid fa-arrow-right-long ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <nav class="mt-5 pb-4" aria-label="Room navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link shadow-none px-3" href="#" tabindex="-1" aria-disabled="true">Trước</a>
                    </li>
                    <li class="page-item active"><a class="page-link shadow-none px-3" href="#">1</a></li>
                    <li class="page-item"><a class="page-link shadow-none px-3" href="#">2</a></li>
                    <li class="page-item"><a class="page-link shadow-none px-3" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link shadow-none px-3" href="#">Sau</a>
                    </li>
                </ul>
            </nav>
        </main>
    </div>
</div>

<?php include_once 'includes/public/footer.php'; ?>
