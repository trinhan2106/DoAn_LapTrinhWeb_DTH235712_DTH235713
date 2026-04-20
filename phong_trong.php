<?php
/**
 * PROJECT: Quản lý Cao ốc (Office Rental Management)
 * PAGE: phong_trong.php (Danh sách phòng trống dành cho Public)
 * ARCHITECT: Antigravity - Secure Dynamic Query Builder
 */

// 1. Khởi tạo session & CSRF bảo mật hệ thống
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Khởi tạo kết nối Database An Toàn
require_once 'includes/common/db.php';
$pdo = Database::getInstance()->getConnection();

// Lấy danh sách các loại phòng đang TRỐNG để làm dữ liệu cho bộ lọc (Dynamic Filter)
$stmtTypes = $pdo->query("SELECT DISTINCT loaiPhong FROM PHONG WHERE trangThai = 1 AND deleted_at IS NULL ORDER BY loaiPhong ASC");
$availableTypes = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);

// Tính toán phân trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// 3. Khởi tạo Base Query & Nền tảng Builder
$baseQuery = "
    SELECT 
        p.*, t.tenTang AS tang,
        co.tenCaoOc,
        (SELECT urlHinhAnh FROM PHONG_HINH_ANH pha WHERE pha.maPhong = p.maPhong ORDER BY pha.is_thumbnail DESC LIMIT 1) AS hinhAnh
    FROM PHONG p
    JOIN TANG t ON p.maTang = t.maTang
    JOIN CAO_OC co ON t.maCaoOc = co.maCaoOc
";

$countBaseQuery = "
    SELECT COUNT(*) 
    FROM PHONG p
    JOIN TANG t ON p.maTang = t.maTang
    JOIN CAO_OC co ON t.maCaoOc = co.maCaoOc
";

// Ràng buộc TỐI THƯỢNG: Chỉ lấy phòng hệ thống đang báo Trống & Chưa xóa
$whereConditions = ["p.trangThai = 1", "p.deleted_at IS NULL"];
$params = [];

// 4. Thuật toán Dynamic Where Builder (Chống SQL Injection)

// Bắt lọc Tầng
$f_tang = $_GET['tang'] ?? '';
if (!empty($f_tang)) {
    // Nếu db lưu 'Tầng 1' hay '1', dùng LIKE an toàn
    $whereConditions[] = "t.tenTang LIKE :tang";
    $params[':tang'] = "%$f_tang%";
}

// Bắt lọc Khoảng Giá (Tối đa) theo thiết kế select option HTML
$f_gia = $_GET['khoangGia'] ?? '';
if (!empty($f_gia) && is_numeric($f_gia)) {
    $whereConditions[] = "p.giaThue <= :gia";
    $params[':gia'] = $f_gia;
}

// Bắt lọc Loại Phòng
$f_loai = $_GET['loaiPhong'] ?? '';
if (!empty($f_loai)) {
    $searchTerm = trim($f_loai);
    $shortTerm = str_replace('Văn phòng ', '', $searchTerm);
    $val = '%' . $searchTerm . '%';
    $sVal = '%' . $shortTerm . '%';
    
    // Lưu ý bảo mật: Khi ATTR_EMULATE_PREPARES = false, không được dùng trùng tên Placeholder trong 1 query.
    $whereConditions[] = "(p.loaiPhong LIKE :lp1 OR p.tenPhong LIKE :lp2 OR p.loaiPhong LIKE :st1 OR p.tenPhong LIKE :st2)";
    $params[':lp1'] = $val;
    $params[':lp2'] = $val;
    $params[':st1'] = $sVal;
    $params[':st2'] = $sVal;
}

// Nội suy (Implode) mảng thành chuỗi WHERE cuối cùng
$whereClause = " WHERE " . implode(" AND ", $whereConditions);

try {
    // ----------------------------------------------------
    // XỬ LÝ 1: TRUY VẤN ĐẾM (COUNT) CỦA PHÂN TRANG
    // ----------------------------------------------------
    $sqlCount = $countBaseQuery . $whereClause;
    $stmtCount = $pdo->prepare($sqlCount);
    // Bind parameters for count query
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    
    $total_records = $stmtCount->fetchColumn();
    $total_pages = ceil($total_records / $limit);
    if ($total_pages < 1) $total_pages = 1;

    // ----------------------------------------------------
    // XỬ LÝ 2: TRUY VẤN DỮ LIỆU CHÍNH (DATA) CÓ LIMIT THU LƯỚI
    // ----------------------------------------------------
    $sqlData = $baseQuery . $whereClause . " ORDER BY p.giaThue ASC LIMIT :limit OFFSET :offset";
    $stmtData = $pdo->prepare($sqlData);

    // Bind các biến chuẩn theo nguyên tắc Prepared Statement khắt khe
    foreach ($params as $key => $value) {
        $stmtData->bindValue($key, $value);
    }
    
    // Bind cứng kiểu INT cho hàm LIMIT & OFFSET (Bảo mật ép kiểu)
    $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);

    // Execute PDO
    $stmtData->execute();
    $rooms = $stmtData->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log lỗi Server an toàn không rò rỉ cấu trúc DB ra màn hình
    error_log("[phong_trong.php] Lỗi Truy vấn PDO: " . $e->getMessage());
    $rooms = [];
    $total_records = 0;
    $total_pages = 1;
}

// Giữ lại tham số url hiện tại cho thẻ chuyển trang
$query_params = $_GET;
unset($query_params['page']);
$query_string = http_build_query($query_params);
$query_string = $query_string ? '&' . $query_string : '';

// 5. Nhúng Header & Navbar
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

    /* Đảm bảo ảnh không bị méo và tỉ lệ quy chuẩn */
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

    .filter-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .filter-title {
        color: #1e3a5f;
        font-weight: 700;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #c9a66b;
    }
</style>

<!-- == HERO SECTION == -->
<header class="phong-trong-hero text-center">
    <div class="container">
        <h1 class="display-4 fw-bold">Danh sách phòng trống</h1>
        <p class="lead">Tìm kiếm không gian văn phòng hợp lý và hiện đại nhất cho doanh nghiệp của bạn.</p>
    </div>
</header>

<div class="container pb-5">
    <div class="row g-4">
        
        <!-- == SIDEBAR FILTER (3/12) == -->
        <aside class="col-12 col-lg-3">
            <div class="filter-sidebar">
                <div class="card filter-card p-4">
                    <h5 class="filter-title"><i class="fa-solid fa-filter me-2"></i> Lọc kết quả</h5>
                    
                    <form action="phong_trong.php" method="GET">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Vị trí (Tầng)</label>
                            <input type="text" name="tang" class="form-control" placeholder="VD: Tầng 5" value="<?php echo htmlspecialchars($f_tang); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Khoảng giá tối đa</label>
                            <select name="khoangGia" class="form-select">
                                <option value="">Tất cả mức giá</option>
                                <option value="5000000" <?php echo $f_gia == '5000000' ? 'selected' : ''; ?>>Dưới 5 Triệu</option>
                                <option value="10000000" <?php echo $f_gia == '10000000' ? 'selected' : ''; ?>>Dưới 10 Triệu</option>
                                <option value="20000000" <?php echo $f_gia == '20000000' ? 'selected' : ''; ?>>Dưới 20 Triệu</option>
                                <option value="50000000" <?php echo $f_gia == '50000000' ? 'selected' : ''; ?>>Dưới 50 Triệu</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Loại văn phòng</label>
                             <select name="loaiPhong" class="form-select">
                                <option value="">Tất cả loại hình</option>
                                <?php foreach ($availableTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $f_loai == $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-brand-primary w-100 py-2 fw-bold mb-2">
                             Áp dụng bộ lọc
                        </button>
                        <a href="phong_trong.php" class="btn btn-outline-secondary w-100 py-2 small fw-bold">Xóa tất cả</a>
                    </form>
                </div>

                <div class="card filter-card p-4 mt-4 bg-light">
                    <h6 class="fw-bold text-navy mb-3">Bạn cần hỗ trợ?</h6>
                    <p class="small text-muted mb-3">Để được tư vấn nhanh chóng nhất về mặt bằng phù hợp.</p>
                    <a href="tel:0912345678" class="btn w-100 py-2 fw-bold" style="background-color: #c9a66b; color: #1e3a5f;">Gọi: 0912 345 678</a>
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
                                        <?php echo htmlspecialchars($room['tang']); ?> | MÃ: <?php echo htmlspecialchars($room['maPhong']); ?>
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
