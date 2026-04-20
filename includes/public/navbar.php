<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<nav id="mainNavbar" class="navbar navbar-expand-lg py-3 sticky-top navbar-dark shadow-sm" style="background-color: #1e3a5f !important; z-index: 1050 !important;">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="<?= BASE_URL ?>index.php" style="letter-spacing: 0.5px;">
            <i class="fa-solid fa-building me-2" style="color: #c9a66b;"></i>THE SAPPHIRE
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMainMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMainMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <?php 
                    $currentUri = $_SERVER['REQUEST_URI'];
                    $isTenant = (strpos($currentUri, 'modules/tenant/') !== false);
                ?>
                <?php if ($isTenant): 
                    $currentFile = basename($_SERVER['PHP_SELF']);
                ?>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?= $currentFile == 'dashboard.php' ? 'active text-white' : 'text-white-50' ?>" href="<?= BASE_URL ?>modules/tenant/dashboard.php">Bảng Điều Khiển</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?= $currentFile == 'hoa_don.php' ? 'active text-white' : 'text-white-50' ?>" href="<?= BASE_URL ?>modules/tenant/hoa_don.php">Hóa Đơn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?= $currentFile == 'hop_dong.php' ? 'active text-white' : 'text-white-50' ?>" href="<?= BASE_URL ?>modules/tenant/hop_dong.php">Hợp Đồng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?= $currentFile == 'maintenance.php' ? 'active text-white' : 'text-white-50' ?>" href="<?= BASE_URL ?>modules/tenant/maintenance.php">Bảo Trì</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold active text-white" href="<?= BASE_URL ?>index.php">Trang Chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-white-50" href="<?= BASE_URL ?>phong_trong.php" onmouseover="this.classList.replace('text-white-50', 'text-white')" onmouseout="this.classList.replace('text-white', 'text-white-50')">Danh Sách Phòng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-white-50" href="<?= BASE_URL ?>tien_ich.php" onmouseover="this.classList.replace('text-white-50', 'text-white')" onmouseout="this.classList.replace('text-white', 'text-white-50')">Tiện Ích</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-white-50" href="<?= BASE_URL ?>gioi_thieu.php" onmouseover="this.classList.replace('text-white-50', 'text-white')" onmouseout="this.classList.replace('text-white', 'text-white-50')">Liên Hệ</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? 0) == 4): ?>
                    <!-- Chuông thông báo cho Khách hàng -->
                    <div class="dropdown">
                        <a class="position-relative d-flex align-items-center justify-content-center rounded-circle" href="#" id="tenantNotiDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:38px;height:38px;background:rgba(201,166,107,0.15);border:1px solid rgba(201,166,107,0.3);text-decoration:none;transition:all 0.3s;">
                            <i class="bi bi-bell-fill" style="color:#c9a66b;font-size:1.1rem;"></i>
                            <span id="tenantNotiBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size:0.6rem;border:2px solid #1e3a5f;padding:0.3em 0.55em;">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-0" aria-labelledby="tenantNotiDropdown" id="tenantNotiList" style="width:320px;border-radius:16px;max-height:480px;overflow-y:auto;background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);">
                            <li><span class="dropdown-item text-center text-muted py-4 small">Đang tải...</span></li>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Giao diện khi đã đăng nhập: Dropdown hiện đại -->
                    <div class="dropdown">
                        <button class="btn d-flex align-items-center gap-2 border-0 bg-transparent text-white p-0 dropdown-toggle" type="button" id="userAccountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(201, 166, 107, 0.2); border: 1px solid rgba(201, 166, 107, 0.4);">
                                <i class="fa-solid fa-user-check" style="color: #c9a66b;"></i>
                            </div>
                            <div class="text-start d-none d-sm-block">
                                <div class="small fw-bold lh-1"><?= htmlspecialchars($_SESSION['ten_user']) ?></div>
                                <div class="text-white-50 lh-1 mt-1" style="font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Thành viên') ?></div>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 animate slideIn" aria-labelledby="userAccountDropdown" style="border-radius: 10px; min-width: 200px;">
                            <li><h6 class="dropdown-header text-uppercase small fw-bold" style="color: #1e3a5f;">Tài khoản của bạn</h6></li>
                            <li>
                                <?php 
                                    $dashboardUrl = 'dangnhap.php'; // Fallback
                                    $roleId = $_SESSION['user_role'] ?? 4;
                                    if ($roleId == 4) {
                                        $dashboardUrl = BASE_URL . 'modules/tenant/dashboard.php';
                                    } elseif (in_array($roleId, [1, 2, 3])) {
                                        $dashboardUrl = BASE_URL . 'modules/dashboard/admin.php';
                                    }
                                ?>
                                <a class="dropdown-item py-2" href="<?= BASE_URL ?>index.php">
                                    <i class="fa-solid fa-house me-2 text-muted"></i> Trang chủ hệ thống
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= $dashboardUrl ?>">
                                    <i class="fa-solid fa-gauge-high me-2 text-muted"></i> Bảng điều khiển
                                </a>
                            </li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>modules/ho_so/index.php"><i class="fa-solid fa-user-pen me-2 text-muted"></i> Hồ sơ cá nhân</a></li>
                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger fw-bold logout-link" href="<?= BASE_URL ?>dangxuat.php">
                                    <i class="fa-solid fa-power-off me-2"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Nút Đăng nhập khi chưa login -->
                    <a href="<?= BASE_URL ?>dangnhap.php" class="btn px-4 fw-bold" style="background-color: #c9a66b; color: #1f2a44; border-radius: 6px; transition: 0.3s;" onmouseover="this.style.backgroundColor='#b5925a'; this.style.color='#fff'" onmouseout="this.style.backgroundColor='#c9a66b'; this.style.color='#1f2a44'">Đăng Nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
/* Hiệu ứng mượt cho dropdown */
.animate { animation-duration: 0.2s; -webkit-animation-duration: 0.2s; animation-fill-mode: both; -webkit-animation-fill-mode: both; }
@keyframes slideIn { 0% { transform: translateY(10px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
.dropdown-item { transition: all 0.2s; font-size: 0.9rem; }
.dropdown-item:hover { background-color: #f8f9fa; color: #1e3a5f; padding-left: 1.25rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? 0) == 4): ?>
    // Tenant notification bell
    const tenantBadge = document.getElementById('tenantNotiBadge');
    const tenantList  = document.getElementById('tenantNotiList');
    const baseUrl     = '<?php echo defined("BASE_URL") ? BASE_URL : "/"; ?>';

    function fetchTenantNoti() {
        fetch(baseUrl + 'includes/tenant/notifications.php')
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                if (data.total > 0) {
                    tenantBadge.textContent = data.total > 99 ? '99+' : data.total;
                    tenantBadge.classList.remove('d-none');
                } else {
                    tenantBadge.classList.add('d-none');
                }
                tenantList.innerHTML = '';
                if (data.items.length === 0) {
                    tenantList.innerHTML = `<li><span class="dropdown-item text-center text-muted py-4 small"><i class="bi bi-bell-slash d-block mb-2 fs-4 opacity-25"></i>Không có thông báo mới</span></li>`;
                } else {
                    let html = `<li><div class="px-4 py-3 border-bottom fw-bold small" style="color:#1e3a5f;">Thông báo của bạn</div></li>`;
                    const gradients = {
                        'text-primary': 'linear-gradient(135deg,#0d6efd,#0a58ca)',
                        'text-danger':  'linear-gradient(135deg,#dc3545,#a71d2a)',
                        'text-warning': 'linear-gradient(135deg,#ffc107,#e0a800)',
                        'text-info':    'linear-gradient(135deg,#0dcaf0,#0aa2c0)'
                    };
                    data.items.forEach(item => {
                        const grad = gradients[item.color] || 'linear-gradient(135deg,#6c757d,#495057)';
                        html += `<li>
                            <a class="dropdown-item py-3 px-4 border-bottom d-flex align-items-start gap-3" href="${item.link}" style="white-space:normal;">
                                <div class="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center text-white" style="width:38px;height:38px;background:${grad};">
                                    <i class="bi ${item.icon}"></i>
                                </div>
                                <div style="font-size:0.85rem;" class="fw-semibold text-dark">${item.title}</div>
                            </a>
                        </li>`;
                    });
                    tenantList.innerHTML = html;
                }
            })
            .catch(() => {});
    }

    fetchTenantNoti();
    setInterval(fetchTenantNoti, 30000);

    // Khi mở dropdown: đánh dấu THONG_BAO đã đọc + xóa badge
    document.getElementById('tenantNotiDropdown').addEventListener('click', function() {
        if (tenantBadge && !tenantBadge.classList.contains('d-none')) {
            fetch(baseUrl + 'includes/tenant/mark_read.php').catch(() => {});
            tenantBadge.classList.add('d-none');
        }
    });
    <?php endif; ?>

    // Tự động ẩn các thông báo thành công sau 5 giây
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Sử dụng Bootstrap native API để ẩn alert nếu có sẵn
            if (window.bootstrap && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                // Fallback nếu không có bootstrap instance
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });
});
</script>
