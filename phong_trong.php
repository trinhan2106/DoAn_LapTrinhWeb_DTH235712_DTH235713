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

// 2. Kết nối Database
require_once 'includes/common/db.php';
$pdo = Database::getInstance()->getConnection();

// 3. Xử lý logic lọc (Filter state)
$f_tang = $_GET['tang'] ?? '';
$f_gia  = $_GET['khoangGia'] ?? '';
$f_loai = $_GET['loaiPhong'] ?? '';

// Tính toán phân trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Xây dựng điều kiện WHERE
$whereConditions = ["p.trangThai = 1", "p.deleted_at IS NULL"];
$params = [];

if (!empty($f_tang)) {
    // Dùng LIKE để hỗ trợ cả trường hợp CSDL lưu 'Tầng 1' hay '1'
    $whereConditions[] = "t.tenTang LIKE :tang";
    $params[':tang'] = "%$f_tang%";
}

if (!empty($f_gia) && is_numeric($f_gia)) {
    $whereConditions[] = "p.giaThue <= :gia";
    $params[':gia'] = $f_gia;
}

if (!empty($f_loai)) {
    $whereConditions[] = "p.loaiPhong = :loai";
    $params[':loai'] = $f_loai;
}

$whereSQL = implode(' AND ', $whereConditions);

// Đếm tổng số bản ghi và tính tổng số trang
$total_records = 0;
$total_pages = 1;
try {
    if (isset($pdo)) {
        $countSql = "SELECT COUNT(*) FROM PHONG p JOIN TANG t ON p.maTang = t.maTang WHERE " . $whereSQL;
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total_records = $countStmt->fetchColumn();
        $total_pages = ceil($total_records / $limit);
        if ($total_pages < 1) $total_pages = 1;
    }
} catch (PDOException $e) {
    error_log("Database count error: " . $e->getMessage());
}

// Truy vấn lấy dữ liệu theo offset và limit
$rooms = [];
try {
    if (isset($pdo)) {
        $sql = "SELECT p.*, t.tenTang AS tang,
                       (SELECT urlHinhAnh FROM PHONG_HINH_ANH pha WHERE pha.maPhong = p.maPhong ORDER BY pha.is_thumbnail DESC LIMIT 1) AS hinhAnh
                FROM PHONG p JOIN TANG t ON p.maTang = t.maTang WHERE " . $whereSQL . " LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database query error: " . $e->getMessage());
}

// Giữ lại tham số url hiện tại cho thẻ chuyển trang
$query_params = $_GET;
unset($query_params['page']);
$query_string = http_build_query($query_params);
$query_string = $query_string ? '&' . $query_string : '';

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
        overflow: hidden;
        position: relative;
    }

    /* Đảm bảo ảnh không bị méo và tỷ lệ quy chuẩn */
    .card-brand__img-wrapper img {
        width: 100%;
        object-fit: cover;
        aspect-ratio: 16/9;
        transition: transform 0.5s ease;
    }

    .card-brand:hover .card-brand__img-wrapper img {
        transform: scale(1.08);
    }
    
    /* Giao diện nút nhấn brand */
    .btn-brand-primary {
        background-color: #1e3a5f;
        color: #ffffff;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn-brand-primary:hover {
        background-color: #152943;
        color: #ffffff;
    }

    /* Giao diện thanh phân trang */
    .pagination .page-link {
        color: #1e3a5f;
        border: none;
        margin: 0 3px;
        border-radius: 6px !important;
        font-weight: 600;
    }

    .pagination .page-item.active .page-link {
        background-color: #c9a66b;
        color: #1f2a44;
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
                    <h5 class="fw-bold mb-4 border-bottom pb-3" style="color: #1e3a5f;">
                        <i class="fa-solid fa-sliders me-2" style="color: #c9a66b;"></i>Bộ Lọc Tìm Kiếm
                    </h5>
                    
                    <form action="phong_trong.php" method="GET">
                        <!-- Floor Filter -->
                        <div class="mb-3">
                            <label for="tang" class="form-label small fw-bold text-muted text-uppercase">Tầng</label>
                            <select name="tang" id="tang" class="form-select border-0 bg-light shadow-none py-2 px-3">
                                <option value="">Tất cả tầng</option>
                                <option value="Trệt" <?php echo ($f_tang === 'Trệt') ? 'selected' : ''; ?>>Trệt</option>
                                <?php for($i=1; $i<=10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($f_tang == $i && $f_tang !== 'Trệt') ? 'selected' : ''; ?>>Tầng <?php echo $i; ?></option>
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

                        <!-- Type Filter -->
                        <div class="mb-4">
                            <label for="loaiPhong" class="form-label small fw-bold text-muted text-uppercase">Loại phòng</label>
                            <select name="loaiPhong" id="loaiPhong" class="form-select border-0 bg-light shadow-none py-2 px-3">
                                <option value="">Chọn loại phòng</option>
                                <option value="Văn phòng riêng" <?php echo ($f_loai == 'Văn phòng riêng') ? 'selected' : ''; ?>>Văn phòng riêng</option>
                                <option value="Mặt bằng kinh doanh" <?php echo ($f_loai == 'Mặt bằng kinh doanh') ? 'selected' : ''; ?>>Mặt bằng kinh doanh</option>
                                <option value="Văn phòng ảo" <?php echo ($f_loai == 'Văn phòng ảo') ? 'selected' : ''; ?>>Văn phòng ảo</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn w-100 py-2 fw-bold text-uppercase shadow-sm" style="background-color: #c9a66b; color: #1f2a44; border: none;">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Lọc ngay
                        </button>
                        
                        <a href="phong_trong.php" class="btn btn-link w-100 mt-3 text-muted small text-decoration-none border-0">
                            <i class="fa-solid fa-rotate-right me-1"></i> Làm mới bộ lọc
                        </a>
                    </form>
                </div>

                <!-- Assistance Card -->
                <div class="card card-brand mt-4 p-4 text-white border-0" style="background-color: #1e3a5f;">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-headset me-2" style="color: #c9a66b;"></i>Cần hỗ trợ tư vấn?</h6>
                    <p class="small opacity-75 mb-3">Đội ngũ chuyên gia của chúng tôi luôn sẵn sàng hỗ trợ bạn tìm được văn phòng ưng ý nhất.</p>
                    <a href="tel:0123456789" class="btn w-100 py-2 fw-bold" style="background-color: #c9a66b; color: #1f2a44;">Gọi: 0123 456 789</a>
                </div>
            </div>
        </aside>

        <!-- == MAIN CONTENT (9/12) == -->
        <main class="col-12 col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="mb-0 text-muted">Hiển thị <span class="fw-bold" style="color: #1e3a5f;"><?php echo $total_records; ?></span> kết quả phù hợp</p>
                <div class="d-none d-md-block">
                    <!-- Khu vực chèn dropdown sắp xếp sau này -->
                </div>
            </div>

            <!-- Room Grid -->
            <div class="row g-4">
                <?php if (!empty($rooms)): ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php 
                            // Xử lý placeholder ảnh
                            $hinhAnh = !empty($room['hinhAnh']) ? $room['hinhAnh'] : 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80'; 
                        ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card card-brand h-100 rounded-3">
                                <div class="card-brand__img-wrapper">
                                    <img src="<?php echo htmlspecialchars($hinhAnh); ?>" 
                                         alt="<?php echo htmlspecialchars($room['tenPhong']); ?>">
                                    <span class="position-absolute top-0 start-0 m-3 badge shadow-sm" style="background-color: #28a745; color: white;">
                                        <i class="fa-solid fa-check-circle me-1"></i> <?php echo $room['trangThai'] == 1 ? 'Phòng Trống' : 'Khác'; ?>
                                    </span>
                                </div>
                                
                                <div class="card-body p-4 bg-white">
                                    <h5 class="card-title fw-bold mb-2 text-truncate" style="color: #1e3a5f;" title="<?php echo htmlspecialchars($room['tenPhong']); ?>">
                                        <?php echo htmlspecialchars($room['tenPhong']); ?>
                                    </h5>
                                    <p class="small text-muted mb-3">
                                        <i class="fa-solid fa-location-dot me-1" style="color: #c9a66b;"></i> 
                                        <?php echo htmlspecialchars($room['tang']); ?> • Mã: <?php echo htmlspecialchars($room['maPhong']); ?>
                                    </p>
                                    
                                    <div class="row g-2 mb-4">
                                        <div class="col-6">
                                            <div class="bg-light p-2 rounded-2 text-center h-100 d-flex flex-column justify-content-center">
                                                <span class="d-block small text-muted mb-1"><i class="fa-solid fa-clone"></i> Diện tích</span>
                                                <span class="fw-bold small" style="color: #1e3a5f;"><?php echo htmlspecialchars($room['dienTich']); ?> m²</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="bg-light p-2 rounded-2 text-center h-100 d-flex flex-column justify-content-center">
                                                <span class="d-block small text-muted mb-1"><i class="fa-solid fa-tag"></i> Loại phòng</span>
                                                <span class="fw-bold small text-truncate d-block w-100" style="color: #1e3a5f;" title="<?php echo htmlspecialchars($room['loaiPhong']); ?>">
                                                    <?php echo htmlspecialchars($room['loaiPhong']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="d-block small text-muted lh-1 mb-1">Giá thuê từ</span>
                                            <span class="fw-bold fs-5" style="color: #c9a66b;"><?php echo number_format($room['giaThue'], 0, ',', '.'); ?> đ</span>
                                            <span class="small text-muted">/tháng</span>
                                        </div>
                                    </div>
                                    
                                    <div class="border-top pt-3 mt-auto">
                                        <a href="chi_tiet_phong.php?maPhong=<?php echo urlencode($room['maPhong']); ?>" 
                                           class="btn btn-brand-primary w-100 py-2 rounded-2 fw-semibold">
                                            Xem Chi Tiết <i class="fa-solid fa-arrow-right-long ms-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa-regular fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">Không tìm thấy phòng nào phù hợp.</h5>
                        <p class="text-muted"><a href="phong_trong.php" class="text-decoration-none">Nhấn vào đây để xóa bộ lọc</a></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-5 pb-4" aria-label="Phân trang danh sách phòng">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link shadow-none px-3" href="?page=<?php echo $page - 1 . $query_string; ?>">Trước</a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link shadow-none px-3" href="#" tabindex="-1" aria-disabled="true">Trước</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link shadow-none px-3" href="?page=<?php echo $i . $query_string; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link shadow-none px-3" href="?page=<?php echo $page + 1 . $query_string; ?>">Sau</a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link shadow-none px-3" href="#" tabindex="-1" aria-disabled="true">Sau</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include_once 'includes/public/footer.php'; ?>
