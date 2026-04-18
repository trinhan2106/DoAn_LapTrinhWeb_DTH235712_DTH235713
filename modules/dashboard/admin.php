<?php
// Tích hợp Core PHP hệ thống (Lùi để móc nối)
require_once __DIR__ . '/../../includes/common/auth.php';

// Kiểm tra quyền Session (Bắt buộc phải qua cửa Verify Đăng nhập)
kiemTraSession();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q-Admin - Dashboard Báo Cáo</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* =========================================
           CUSTOM BRAND CSS PROPERTIES
           ========================================= */
        :root {
            --primary: #1e3a5f;      /* Xanh navy (Sidebar) */
            --accent: #c9a66b;       /* Vàng gold (Hover/Highlight) */
            --bg-color: #f4f7f9;     /* Màu nền Website */
            --text-color: #1f2a44;   /* Chữ */
            --danger-color: #e74c3c; /* Đỏ cảnh báo nợ */
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            overflow-x: hidden; /* Chặn cuộn ngang do flex */
        }

        /* -------------------------------------
           LAYOUT BỐ CỤC CHÍNH (SIDEBAR + MAIN)
           ------------------------------------- */
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
            min-height: 100vh;
        }

        /* KHU VỰC: SIDEBAR BÊN TRÁI */
        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background-color: var(--primary);
            color: #fff;
            transition: all 0.3s;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        #sidebar .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul li a {
            padding: 15px 25px;
            font-size: 1.05rem;
            display: block;
            color: #dce1e6; /* Trắng xám dịu mắt */
            text-decoration: none;
            transition: 0.3s ease;
            font-weight: 500;
        }

        #sidebar ul li a:hover, #sidebar ul li.active > a {
            color: var(--primary);
            background: var(--accent); /* Đổi màu Vàng Gold khi Hover */
        }

        #sidebar ul li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* KHU VỰC: MAIN CONTENT (BÊN PHẢI) */
        #content {
            width: 100%;
            padding: 25px 40px;
            display: flex;
            flex-direction: column;
        }

        /* HEADER BOX Ở TRÊN CÙNG MAIN CONTENT */
        .top-navbar {
            background: #fff;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* -------------------------------------
           KHU VỰC HÀNG 1: 4 THẺ KPI CARDS
           ------------------------------------- */
        .kpi-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border-left: 5px solid var(--primary); /* Line viền Primary */
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s;
        }
        
        .kpi-card:hover {
            transform: translateY(-3px);
        }

        .kpi-card.is-danger {
            border-left-color: var(--danger-color); /* Thẻ cảnh báo Nợ viền đỏ */
        }

        .kpi-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .kpi-info h3 {
            margin: 8px 0 0;
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--text-color);
        }

        .kpi-card.is-danger .kpi-info h3 {
            color: var(--danger-color); /* Đổi màu text thành Đỏ */
        }

        .kpi-icon {
            font-size: 2.5rem;
            color: rgba(30, 58, 95, 0.2); /* Icon in chìm */
        }

        .kpi-card.is-danger .kpi-icon {
            color: rgba(231, 76, 60, 0.2);
        }

        /* -------------------------------------
           KHU VỰC HÀNG 2: CHART & TABLE
           ------------------------------------- */
        .content-box {
            background-color: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            height: 100%;
        }

        .box-title {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 20px;
            font-size: 1.15rem;
            text-transform: uppercase;
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 12px;
        }

        /* Custom Table Bootstrap */
        .table-custom thead {
            background-color: #f8f9fa;
        }
        .table-custom th {
            color: var(--primary);
            font-weight: 700;
            border-bottom: none;
        }
        .table-custom td {
            vertical-align: middle;
            color: var(--text-color);
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- SIDEBAR LEFT -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-hotel me-2 text-warning"></i> BLUE SKY QLN
        </div>

        <ul class="list-unstyled components d-flex flex-column" style="height: 100%">
            <!-- Gắn link routing PHP sau này vào href -->
            <li class="active">
                <a href="#"><i class="fa-solid fa-chart-pie"></i> Tổng Quan (Dash)</a>
            </li>
            <li>
                <a href="#"><i class="fa-solid fa-building"></i> Quản Lý Phòng</a>
            </li>
            <li>
                <a href="#"><i class="fa-solid fa-users"></i> Khách Hàng</a>
            </li>
            <li>
                <a href="#"><i class="fa-solid fa-file-contract"></i> Hợp Đồng</a>
            </li>
            <li>
                <a href="#"><i class="fa-solid fa-file-invoice-dollar"></i> Hóa Đơn & Thu Phí</a>
            </li>
            <li>
                <a href="#"><i class="fa-solid fa-screwdriver-wrench"></i> Bảo Trì Kỹ Thuật</a>
            </li>
            
            <li class="sidebar-footer">
                <a href="../../dangxuat.php" class="btn btn-outline-light w-100 rounded-pill">
                    <i class="fa-solid fa-power-off"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </nav>

    <!-- MAIN CONTENT RIGHT -->
    <div id="content">

        <!-- TOP NAVBAR TOOLBAR -->
        <div class="top-navbar">
            <div class="text-muted fw-bold">
                <i class="fa-regular fa-calendar me-2"></i> <?= date('d/m/Y') ?>
            </div>
            <div class="user-profile fw-bold text-primary">
                <!-- Data đổ từ $_SESSION ra -->
                <i class="fa-solid fa-circle-user fs-4 align-middle me-2"></i> 
                Xin chào, <?= htmlspecialchars($_SESSION['HoVaTen'] ?? 'Admin') ?>
            </div>
        </div>

        <!-- HÀNG 1: 4 THẺ KPI (ĐỔ PHP CÂU FETCH COUNT SUM VÀO ĐÂY) -->
        <div class="row g-4 mb-4">
            <!-- Thẻ 1: Rooms -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-info">
                        <p>Tổng Số Phòng</p>
                        <h3>142</h3>
                    </div>
                    <i class="fa-solid fa-door-open kpi-icon"></i>
                </div>
            </div>

            <!-- Thẻ 2: Occupancy -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-info">
                        <p>Tỷ lệ lấp đầy</p>
                        <h3>85.5%</h3>
                    </div>
                    <i class="fa-solid fa-chart-line kpi-icon"></i>
                </div>
            </div>

            <!-- Thẻ 3: Revenue -->
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-info">
                        <p>Dự kiến tháng này</p>
                        <h3>2,450T</h3>
                    </div>
                    <i class="fa-solid fa-money-bill-trend-up kpi-icon"></i>
                </div>
            </div>

            <!-- Thẻ 4: Debt (Danger) -->
            <div class="col-md-3">
                <div class="kpi-card is-danger">
                    <div class="kpi-info">
                        <p>Tổng nợ quá hạn</p>
                        <h3>345T</h3>
                    </div>
                    <i class="fa-solid fa-triangle-exclamation kpi-icon"></i>
                </div>
            </div>
        </div>

        <!-- HÀNG 2: CHART VÀ TABLE -->
        <div class="row g-4">
            <!-- CỘT TRÁI (8): CHART THỐNG KÊ -->
            <div class="col-md-8">
                <div class="content-box">
                    <h5 class="box-title"><i class="fa-solid fa-chart-column me-2"></i>BIỂU ĐỒ DOANH THU & LỢI NHUẬN (2026)</h5>
                    <!-- Canvas Box. Chừa chỗ JS nhúng Chart.js vẽ tại đây -->
                    <div style="height: 350px; display: flex; align-items: center; justify-content: center; border: 2px dashed #dce1e6; border-radius: 8px;">
                        <canvas id="revenueChart"></canvas>
                        <p class="text-muted"><i class="fa-solid fa-chart-pie me-2"></i>Khu vực tích hợp Chart.js Backend Data</p>
                    </div>
                </div>
            </div>

            <!-- CỘT PHẢI (4): TABLE CẢNH BÁO -->
            <div class="col-md-4">
                <div class="content-box">
                    <h5 class="box-title text-danger"><i class="fa-solid fa-clock-rotate-left me-2"></i>SẮP HẾT HẠN HỢP ĐỒNG</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Khách Hàng</th>
                                    <th>Phòng</th>
                                    <th>Ngày KT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Điền vòng lặp Code lấy DB tại đây -->
                                <tr>
                                    <td class="fw-bold">Cty Phúc Vinh</td>
                                    <td><span class="badge bg-secondary">P302</span></td>
                                    <td class="text-danger fw-bold">12/05/26</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tech Asia JSC</td>
                                    <td><span class="badge bg-secondary">P105</span></td>
                                    <td class="text-danger fw-bold">25/05/26</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Lê Trọng Tấn</td>
                                    <td><span class="badge bg-secondary">P401</span></td>
                                    <td class="text-warning fw-bold">10/06/26</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Tập đoàn Alpha</td>
                                    <td><span class="badge bg-secondary">P603</span></td>
                                    <td class="text-warning fw-bold">18/06/26</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-sm btn-outline-primary w-100">Xem tất cả hợp đồng <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- End Main Content -->
</div> <!-- End Wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chèn link JS cho Chart.js CDN ở đây nếu cần -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
<!-- Viết đoạn Fetch Data để Draw lên #revenueChart -->
</body>
</html>
