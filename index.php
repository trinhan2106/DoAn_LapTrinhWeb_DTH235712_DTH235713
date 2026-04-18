<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Hệ Thống Quản Lý Cao Ốc</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* =========================================
           CUSTOM BRAND COLORS
           ========================================= */
        :root {
            --primary: #1e3a5f;      /* Xanh navy sâu */
            --accent: #c9a66b;       /* Vàng gold nhạt */
            --bg-color: #f4f7f9;     /* Màu nền */
            --text-color: #1f2a44;   /* Chữ */
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* -------------------------------------
           KHU VỰC: NAVBAR
           ------------------------------------- */
        .navbar-custom {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .navbar-brand {
            color: var(--primary) !important;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .nav-link {
            color: var(--text-color) !important;
            font-weight: 600;
        }
        .nav-link:hover {
            color: var(--accent) !important;
        }
        .btn-login-nav {
            background-color: var(--primary);
            color: #fff;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s;
        }
        .btn-login-nav:hover {
            background-color: var(--accent);
            color: var(--text-color);
        }

        /* -------------------------------------
           KHU VỰC: HERO BANNER & SEARCH
           ------------------------------------- */
        .hero-banner {
            /* Hình nền dummy - Có lớp phủ màu tối navy */
            background: linear-gradient(rgba(30, 58, 95, 0.7), rgba(30, 58, 95, 0.8)), 
                        url('https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            padding: 120px 0;
            color: #fff;
            text-align: center;
        }
        .hero-title {
            font-weight: 800;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 900px;
            margin: 0 auto;
        }
        .btn-search {
            background-color: var(--accent);
            color: var(--text-color);
            font-weight: 700;
            border: none;
            width: 100%;
        }
        .btn-search:hover {
            background-color: #d1b17e;
        }

        /* -------------------------------------
           KHU VỰC: DANH SÁCH PHÒNG (CARDS)
           ------------------------------------- */
        .room-section {
            padding: 60px 0;
        }
        .section-title {
            color: var(--primary);
            font-weight: 800;
            text-align: center;
            margin-bottom: 40px;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(30,58,95,0.06);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-custom:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(30,58,95,0.15);
        }
        .card-img-top {
            height: 220px;
            object-fit: cover;
        }
        .room-price {
            color: var(--accent);
            font-weight: 800;
            font-size: 1.3rem;
        }
        .btn-detail {
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
        }
        .btn-detail:hover {
            background-color: var(--primary);
            color: #fff;
        }

        /* -------------------------------------
           KHU VỰC: FOOTER
           ------------------------------------- */
        .custom-footer {
            background-color: var(--primary);
            color: #dce1e6;
            padding: 40px 0 20px 0;
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- KHU VỰC 1: HEADER / NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-building me-2"></i>BLUE SKY TOWER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMainMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMainMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Trang Chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Danh Sách Phòng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Tiện Ích</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Liên Hệ</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="dangnhap.php" class="btn btn-login-nav px-4">Đăng Nhập</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- KHU VỰC 2: HERO BANNER VÀ FORM LỌC TÌM KIẾM -->
    <section class="hero-banner">
        <div class="container">
            <h1 class="hero-title">Không Gian Làm Việc Chuyên Nghiệp</h1>
            <p class="fs-5 mb-5">Hệ thống văn phòng cho thuê hiện đại, linh hoạt diện tích và tích hợp tiện ích cao cấp.</p>
            
            <div class="search-container">
                <!-- Chèn Code PHP xử lý Gửi GET data filter vào file danh_sach_phong.php tại đây -->
                <form action="danh_sach_phong.php" method="GET" class="row g-3 align-items-end text-start">
                    <div class="col-md-3">
                        <label class="form-label fw-bold" style="color: var(--text-color)">Loại phòng</label>
                        <select class="form-select" name="loaiPhong">
                            <option value="">-- Tất cả --</option>
                            <option value="Văn phòng chia sẻ">Văn phòng chia sẻ</option>
                            <option value="Văn phòng riêng">Văn phòng riêng</option>
                            <option value="Mặt bằng bán lẻ">Mặt bằng kinh doanh</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" style="color: var(--text-color)">Vị trí Tầng</label>
                        <select class="form-select" name="maTang">
                            <option value="">-- Mọi tầng --</option>
                            <!-- Chèn mã PHP fetch danh sách Tầng ở đây -->
                            <option value="T1">Tầng 1 (Ground)</option>
                            <option value="T2">Tầng 2</option>
                            <option value="T3">Tầng 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" style="color: var(--text-color)">Mức giá (Triệu VNĐ)</label>
                        <select class="form-select" name="khoangGia">
                            <option value="">-- Không giới hạn --</option>
                            <option value="0-10">Dưới 10 Triệu</option>
                            <option value="10-20">10 Triệu - 20 Triệu</option>
                            <option value="20-50">20 Triệu - 50 Triệu</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-search py-2">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Tìm Phòng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- KHU VỰC 3: SHOWCASE PHÒNG NỔI BẬT (DÙNG BOOTSTRAP GRID) -->
    <section class="room-section container border-bottom">
        <h2 class="section-title">PHÒNG CHO THUÊ TIÊU BIỂU</h2>
        <div class="row g-4 mt-2">
            
            <!-- Vòng lặp PHP Render HTML Card tại đây (Dưới đây là 3 Card Dummy) -->
            
            <!-- Card 1 -->
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=600&q=80" class="card-img-top" alt="Room">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">P301 - Tầng 3</h5>
                        <p class="text-muted small"><i class="fa-solid fa-tag me-1"></i> Văn phòng riêng</p>
                        <div class="d-flex justify-content-between my-3 pb-3 border-bottom">
                            <span><i class="fa-solid fa-chart-area me-1 text-secondary"></i> 75 m²</span>
                            <span><i class="fa-solid fa-layer-group me-1 text-secondary"></i> Tầng 3</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="room-price">18,500,000 đ</span>
                            <a href="#" class="btn btn-detail btn-sm px-3">Xem Chi Tiết</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <img src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=600&q=80" class="card-img-top" alt="Room">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">P002 - Ground Floor</h5>
                        <p class="text-muted small"><i class="fa-solid fa-tag me-1"></i> Mặt bằng kinh doanh</p>
                        <div class="d-flex justify-content-between my-3 pb-3 border-bottom">
                            <span><i class="fa-solid fa-chart-area me-1 text-secondary"></i> 120 m²</span>
                            <span><i class="fa-solid fa-layer-group me-1 text-secondary"></i> Tầng Trệt</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="room-price">45,000,000 đ</span>
                            <a href="#" class="btn btn-detail btn-sm px-3">Xem Chi Tiết</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <img src="https://images.unsplash.com/photo-1556761175-5973eeb7487d?auto=format&fit=crop&w=600&q=80" class="card-img-top" alt="Room">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">P204 - Trọn sàn</h5>
                        <p class="text-muted small"><i class="fa-solid fa-tag me-1"></i> Văn phòng làm việc lớn</p>
                        <div class="d-flex justify-content-between my-3 pb-3 border-bottom">
                            <span><i class="fa-solid fa-chart-area me-1 text-secondary"></i> 250 m²</span>
                            <span><i class="fa-solid fa-layer-group me-1 text-secondary"></i> Tầng 2</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="room-price">32,200,000 đ</span>
                            <a href="#" class="btn btn-detail btn-sm px-3">Xem Chi Tiết</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- KHU VỰC 4: FOOTER -->
    <footer class="custom-footer text-center text-md-start">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-building me-2 text-accent-custom"></i>BLUE SKY TOWER</h5>
                    <p class="small">Khu tổ hợp văn phòng cho thuê cao cấp bậc nhất trung tâm thành phố. Kiến trúc thông minh, dịch vụ tận tâm.</p>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3">Liên Hệ</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="fa-solid fa-location-dot me-2"></i> 123 Đại lộ Trung Tâm, Quận 1, TP.HCM</li>
                        <li class="mb-2"><i class="fa-solid fa-phone me-2"></i> (028) 3883 9999</li>
                        <li class="mb-2"><i class="fa-solid fa-envelope me-2"></i> contact@blueskytower.com</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white fw-bold mb-3">Về Chúng Tôi</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Giới thiệu tòa nhà</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Chính sách thuê</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Bảo mật thông tin</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary mt-4">
            <div class="text-center small">
                © 2026 Blue Sky Tower. All Rights Reserved. Đồ án lập trình Web DTH235712_DTH235713.
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
