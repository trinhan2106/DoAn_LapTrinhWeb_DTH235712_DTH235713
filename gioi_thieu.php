<?php
// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sinh CSRF token nếu chưa có (dành cho form liên hệ)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = "Giới thiệu & Liên hệ - Quản lý Cao ốc";
include_once 'includes/public/header.php';
include_once 'includes/public/navbar.php';
?>

<main class="main-content">
    <!-- Hero Banner -->
    <section class="page-header py-5 bg-dark text-white text-center position-relative" style="background: linear-gradient(rgba(30, 58, 95, 0.8), rgba(30, 58, 95, 0.9)), url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat; padding-top: 100px !important; padding-bottom: 80px !important;">
        <div class="container position-relative z-1">
            <h1 class="display-4 fw-bold text-uppercase" style="color: #c9a66b;">Về Chúng Tôi & Liên Hệ</h1>
            <p class="lead mb-0">Hệ thống Quản lý Vận hành Cao ốc Chuyên nghiệp, Đẳng cấp và Tận tâm.</p>
        </div>
    </section>

    <!-- Phần Giới Thiệu (About Us) -->
    <section class="about-section py-5 my-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Cột trái: Ảnh minh hoạ -->
                <div class="col-lg-6 order-2 order-lg-1">
                    <div class="about-image-wrapper position-relative">
                        <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2069&auto=format&fit=crop" class="img-fluid rounded-4 shadow-lg card-brand" alt="Không gian văn phòng hiện đại" style="object-fit: cover; height: 500px; width: 100%;">
                        <!-- Điểm nhấn trang trí nhỏ -->
                        <div class="position-absolute bottom-0 end-0 bg-white p-4 rounded-3 shadow-lg me-n3 mb-n3 d-none d-md-block" style="border-left: 4px solid #c9a66b; border-radius: 8px;">
                            <h3 class="fw-bold mb-0 text-primary" style="color: #1e3a5f !important;">10+ Năm</h3>
                            <p class="text-muted mb-0">Kinh nghiệm vận hành</p>
                        </div>
                    </div>
                </div>
                <!-- Cột phải: Nội dung -->
                <div class="col-lg-6 order-1 order-lg-2">
                    <h2 class="display-5 fw-bold mb-4" style="color: #1e3a5f;">Tiên phong trong <br><span style="color: #c9a66b;">Quản lý Vận hành</span></h2>
                    <p class="lead text-muted mb-4">Chúng tôi cung cấp giải pháp quản lý cao ốc thông minh, tích hợp công nghệ hiện đại nhằm tối ưu hoá không gian làm việc và nâng cao trải nghiệm sống của các doanh nghiệp.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill me-3 fs-5" style="color: #c9a66b;"></i>
                            <span>Dịch vụ quản lý toà nhà trọn gói 24/7.</span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill me-3 fs-5" style="color: #c9a66b;"></i>
                            <span>Hệ thống an ninh và vệ sinh đạt chuẩn quốc tế.</span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill me-3 fs-5" style="color: #c9a66b;"></i>
                            <span>Ứng dụng công nghệ phần mềm vào quy trình vận hành.</span>
                        </li>
                    </ul>
                    <a href="#contact" class="btn btn-brand--accent btn-lg px-5 rounded-pill shadow-sm text-white border-0" style="background-color: #c9a66b;">Tìm hiểu thêm</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Đường phân cách -->
    <div class="container"><hr class="text-muted opacity-25"></div>

    <!-- Phần Liên Hệ (Contact Area) -->
    <section id="contact" class="contact-section py-5 my-5 bg-light mx-3 mx-lg-5 shadow-sm" style="border-radius: 12px;">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: #1e3a5f;">Thông Tin Liên Hệ</h2>
                <p class="text-muted">Hãy để lại lời nhắn, bộ phận chăm sóc khách hàng của chúng tôi sẽ phản hồi bạn trong thời gian sớm nhất.</p>
            </div>
            
            <div class="row g-5">
                <!-- Cột trái: Thông tin & Form -->
                <div class="col-lg-5">
                    <!-- Thông tin -->
                    <div class="contact-info mb-5">
                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box me-3 p-3 bg-white shadow-sm text-center d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; color: #c9a66b; border-radius: 50%;">
                                <i class="bi bi-geo-alt-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1" style="color: #1e3a5f;">Địa Chỉ Văn Phòng</h5>
                                <p class="text-muted mb-0">Tầng 15, Tòa nhà The Sapphire<br>Quận 1, TP. Hồ Chí Minh</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-box me-3 p-3 bg-white shadow-sm text-center d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; color: #c9a66b; border-radius: 50%;">
                                <i class="bi bi-telephone-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1" style="color: #1e3a5f;">Đường Dây Nóng</h5>
                                <p class="text-muted mb-0">+84 28 3838 8888<br>+84 909 123 456</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start">
                            <div class="icon-box me-3 p-3 bg-white shadow-sm text-center d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; color: #c9a66b; border-radius: 50%;">
                                <i class="bi bi-envelope-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1" style="color: #1e3a5f;">Email Hỗ Trợ</h5>
                                <p class="text-muted mb-0">contact@thesapphire.vn</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Liên Hệ -->
                    <div class="contact-form bg-white p-4 p-md-5 shadow-sm card-brand" style="border-radius: 8px;">
                        <h4 class="fw-bold mb-4" style="color: #1e3a5f;">Gửi Tin Nhắn</h4>
                        <form action="lien_he_submit.php" method="POST">
                            <!-- Chống CSRF -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <div class="mb-3">
                                <label for="fullname" class="form-label fw-semibold">Họ và Tên</label>
                                <input type="text" class="form-control form-control-lg bg-light border-0" id="fullname" name="fullname" placeholder="Nhập họ tên của bạn" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Liên Hệ</label>
                                <input type="email" class="form-control form-control-lg bg-light border-0" id="email" name="email" placeholder="name@example.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label fw-semibold">Tiêu Đề</label>
                                <input type="text" class="form-control form-control-lg bg-light border-0" id="subject" name="subject" placeholder="Bạn cần hỗ trợ vấn đề gì?" required>
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label fw-semibold">Nội Dung Tin Nhắn</label>
                                <textarea class="form-control form-control-lg bg-light border-0" id="message" name="message" rows="4" placeholder="Nhập nội dung chi tiết..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-brand--accent w-100 py-3 fw-bold rounded-3 border-0 text-white" style="background-color: #c9a66b; transition: all 0.3s ease;">GỬI NGAY <i class="bi bi-send-fill ms-2"></i></button>
                        </form>
                    </div>
                </div>

                <!-- Cột phải: Bản đồ Google Maps -->
                <div class="col-lg-7 h-100 min-vh-50">
                    <div class="map-wrapper h-100 overflow-hidden card-brand shadow-sm" style="border-radius: 8px;">
                        <!-- Embed Google Maps -->
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4602324217316!2d106.697415414749!3d10.77601946214571!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f40a3b49e59%3A0xa1bd14e483a602db!2sIndependence%20Palace!5e0!3m2!1sen!2s!4v1689234567890!5m2!1sen!2s" 
                            width="100%" 
                            height="100%" 
                            style="border:0; min-height: 600px;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<style>
/* CSS Tùy chỉnh thêm cho trang Giới thiệu */
.icon-box {
    transition: all 0.3s ease;
}
.icon-box:hover {
    background-color: #1e3a5f !important;
    color: #fff !important;
    transform: translateY(-5px);
}
.card-brand {
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card-brand:hover {
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}
.btn-brand--accent:hover {
    background-color: #b5955f !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(201, 166, 107, 0.4);
}
</style>

<?php include_once 'includes/public/footer.php'; ?>
