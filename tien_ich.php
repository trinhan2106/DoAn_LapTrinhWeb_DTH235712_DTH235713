<?php
/**
 * tien_ich.php
 * Trang Tiện ích & Dịch vụ - Hệ thống Quản lý Cao ốc
 */

// Đảm bảo BASE_URL có sẵn
require_once __DIR__ . '/config/constants.php';

$pageTitle = "Tiện ích & Dịch vụ Đẳng cấp - The Sapphire";
include_once __DIR__ . '/includes/public/header.php';
include_once __DIR__ . '/includes/public/navbar.php';
?>

<main class="main-content bg-light">
    <!-- Hero Header -->
    <header class="py-5 text-white position-relative overflow-hidden" style="background: linear-gradient(rgba(30, 58, 95, 0.85), rgba(30, 58, 95, 0.95)), url('https://images.unsplash.com/photo-1574333081543-f59740f994f0?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat; padding-top: 100px !important;">
        <div class="container py-5 text-center position-relative z-1">
            <h1 class="display-4 fw-bold text-uppercase mb-3" style="color: #c9a66b;">Tiện ích Toàn diện</h1>
            <p class="lead mb-0 mx-auto" style="max-width: 800px;">Khám phá hệ sinh thái dịch vụ cao cấp, mang đến sự tiện nghi và an tâm tối đa cho mọi doanh nghiệp tại The Sapphire.</p>
        </div>
    </header>

    <!-- Amenities Grid -->
    <section class="py-5 my-5">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <!-- Gym & Fitness -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm card-amenity">
                        <div class="card-img-top overflow-hidden position-relative" style="height: 200px;">
                            <img src="https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=2070&auto=format&fit=crop" class="w-100 h-100 object-fit-cover transition-transform" alt="Gym">
                            <div class="glass-tag position-absolute top-0 start-0 m-3 px-3 py-1 rounded-pill">
                                <span class="small fw-bold">Free Access</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="icon-circle mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(30, 58, 95, 0.1); color: #1e3a5f; border-radius: 50%;">
                                <i class="bi bi-heart-pulse fs-4"></i>
                            </div>
                            <h5 class="card-title fw-bold text-navy">Gym & Fitness</h5>
                            <p class="card-text text-muted small">Trung tâm thể thao hiện đại với trang thiết bị chuẩn quốc tế, phục vụ sức khỏe cộng đồng nhân viên.</p>
                        </div>
                    </div>
                </div>

                <!-- Infinity Pool -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm card-amenity">
                        <div class="card-img-top overflow-hidden position-relative" style="height: 200px;">
                            <img src="https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=2070&auto=format&fit=crop" class="w-100 h-100 object-fit-cover transition-transform" alt="Pool">
                            <div class="glass-tag position-absolute top-0 start-0 m-3 px-3 py-1 rounded-pill">
                                <span class="small fw-bold">Level 15</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="icon-circle mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(30, 58, 95, 0.1); color: #1e3a5f; border-radius: 50%;">
                                <i class="bi bi-water fs-4"></i>
                            </div>
                            <h5 class="card-title fw-bold text-navy">Bể bơi Vô cực</h5>
                            <p class="card-text text-muted small">Tận hưởng tầm nhìn toàn cảnh thành phố và thư giãn sau giờ làm việc tại bể bơi tầng thượng đẳng cấp.</p>
                        </div>
                    </div>
                </div>

                <!-- Smart Security -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm card-amenity">
                        <div class="card-img-top overflow-hidden position-relative" style="height: 200px;">
                            <img src="https://images.unsplash.com/photo-1557597774-9d273605dfa9?q=80&w=2070&auto=format&fit=crop" class="w-100 h-100 object-fit-cover transition-transform" alt="Security">
                            <div class="glass-tag position-absolute top-0 start-0 m-3 px-3 py-1 rounded-pill">
                                <span class="small fw-bold">24/7 Monitoring</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="icon-circle mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(30, 58, 95, 0.1); color: #1e3a5f; border-radius: 50%;">
                                <i class="bi bi-shield-lock fs-4"></i>
                            </div>
                            <h5 class="card-title fw-bold text-navy">An ninh Đa lớp</h5>
                            <p class="card-text text-muted small">Hệ thống camera AI, kiểm soát ra vào bằng khuôn mặt (FaceID) và đội ngũ bảo vệ chuyên nghiệp.</p>
                        </div>
                    </div>
                </div>

                <!-- Smart Parking -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm card-amenity">
                        <div class="card-img-top overflow-hidden position-relative" style="height: 200px;">
                            <img src="https://images.unsplash.com/photo-1506521781263-d8422e82f27a?q=80&w=2070&auto=format&fit=crop" class="w-100 h-100 object-fit-cover transition-transform" alt="Parking">
                            <div class="glass-tag position-absolute top-0 start-0 m-3 px-3 py-1 rounded-pill">
                                <span class="small fw-bold">Smart RFID</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="icon-circle mb-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(30, 58, 95, 0.1); color: #1e3a5f; border-radius: 50%;">
                                <i class="bi bi-p-circle fs-4"></i>
                            </div>
                            <h5 class="card-title fw-bold text-navy">Hầm Gửi xe Thông minh</h5>
                            <p class="card-text text-muted small">3 tầng hầm diện tích lớn với hệ thống báo chỗ trống tự động và thanh toán không tiền mặt.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Banner -->
    <section class="py-5 bg-white shadow-sm border-top border-bottom">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <h2 class="display-6 fw-bold mb-4 text-navy">Giải pháp Vận hành <span style="color: #c9a66b;">Tối ưu</span></h2>
                    <p class="text-muted lead">The Sapphire không chỉ cung cấp không gian, chúng tôi cung cấp thành công cho doanh nghiệp của bạn thông qua dịch vụ hỗ trợ chuyên sâu.</p>
                    <div class="row g-3 mt-2">
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 border rounded-3 bg-light">
                                <i class="bi bi-lightning-charge text-warning fs-3 me-3"></i>
                                <span class="fw-bold text-navy">Điện 24/7</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 border rounded-3 bg-light">
                                <i class="bi bi-wifi text-primary fs-3 me-3"></i>
                                <span class="fw-bold text-navy">Internet Tốc độ</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="p-5 bg-navy text-white rounded-4 shadow-lg position-relative overflow-hidden" style="background-color: #1e3a5f;">
                        <h4 class="fw-bold mb-3">Liên hệ đặt chỗ ngay</h4>
                        <p class="opacity-75">Gia nhập cộng đồng doanh nghiệp thịnh vượng tại The Sapphire để tận hưởng những đặc quyền này.</p>
                        <a href="gioi_thieu.php" class="btn btn-gold btn-lg mt-3 px-5 fw-bold">Liên Hệ Ngay</a>
                        <i class="bi bi-buildings position-absolute bottom-0 end-0 opacity-10" style="font-size: 150px; margin-bottom: -30px; margin-right: -20px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.card-amenity { transition: all 0.3s cubic-bezier(.25,.8,.25,1); overflow: hidden; }
.card-amenity:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important; }
.card-amenity:hover img { transform: scale(1.1); }
.transition-transform { transition: transform 0.6s ease; }
.glass-tag { background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); color: white; }
.text-navy { color: #1e3a5f; }
.bg-navy { background-color: #1e3a5f; }
.btn-gold { background-color: #c9a66b; color: #1e3a5f !important; border: none; }
.btn-gold:hover { background-color: #b5955f !important; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(201, 166, 107, 0.4); }
</style>

<?php include_once __DIR__ . '/includes/public/footer.php'; ?>
