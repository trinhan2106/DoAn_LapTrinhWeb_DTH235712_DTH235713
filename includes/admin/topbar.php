<?php
$ten_user = isset($_SESSION['ten_user']) ? $_SESSION['ten_user'] : 'Admin User';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Quản trị viên';
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';

// Đảm bảo hằng số BASE_URL luôn khả dụng (đặc biệt khi topbar được nạp độc lập)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/constants.php';
}
?>
<header class="admin-topbar bg-white shadow-sm d-flex justify-content-between align-items-center px-4 py-3 sticky-top">
    <div class="d-flex align-items-center gap-3 flex-grow-1">
        <!-- Nút toggle sidebar cho mobile -->
        <button class="btn btn-outline-secondary d-lg-none me-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
            <i class="bi bi-list fs-5"></i>
        </button>


        <!-- Expandable Search Bar (WOW UI) -->
        <div class="expandable-search ms-2" id="searchContainer">
            <button class="search-toggle" id="searchToggle" title="Tìm kiếm">
                <i class="bi bi-search"></i>
            </button>
            
            <form method="GET" action="<?= BASE_URL ?>modules/dashboard/tim_kiem.php" class="search-form" id="searchForm">
                <div class="input-group">
                    <input
                        type="search"
                        name="s"
                        id="topbar-search-input"
                        class="form-control"
                        placeholder="Tìm khách hàng, hợp đồng, phòng..."
                        value="<?php echo isset($_GET['s']) ? htmlspecialchars($_GET['s'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                        autocomplete="off"
                    >
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-search"></i>
                    </button>
                    <button type="button" class="btn-close-search" id="searchClose">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <!-- Nút Toggle Dark Mode -->
        <button class="btn btn-light rounded-circle shadow-sm" id="themeToggle" title="Toggle Dark Mode" data-user-id="<?php echo htmlspecialchars($current_user_id, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-moon"></i>
        </button>

        <!-- Chuông thông báo Dropdown (Glassmorphism & Pulse Style) -->
        <div class="dropdown">
            <a class="btn btn-light rounded-circle shadow-sm position-relative" href="#" id="notiDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                <i class="bi bi-bell-fill text-navy fs-5"></i>
                <span id="notiBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none badge-pulse" style="font-size: 0.65rem; border: 2px solid white; padding: 0.35em 0.6em;">
                    0
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-0" aria-labelledby="notiDropdown" id="notiList">
                <li><span class="dropdown-item text-center text-muted py-4">Đang tải thông báo...</span></li>
            </ul>
        </div>
        
        <!-- User Dropdown Menu -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border-0 shadow-sm" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-4 text-brand-primary"></i>
                <div class="text-start d-none d-md-block">
                    <!-- Bảo vệ chống XSS bằng htmlspecialchars -->
                    <div class="small fw-bold lh-1 mb-1"><?php echo htmlspecialchars($ten_user, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="small text-muted lh-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenuButton">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>index.php"><i class="bi bi-house-door me-2 text-muted"></i>Trang chủ hệ thống</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2 text-muted"></i>Hồ sơ cá nhân</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2 text-muted"></i>Cài đặt</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger logout-link" href="<?= BASE_URL ?>dangxuat.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</header>

<style>
/* ── Expandable Search Styles ────────────────────────── */
.expandable-search {
    position: relative;
    display: flex;
    align-items: center;
    flex-grow: 1;
    max-width: fit-content;
}

.search-toggle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: none;
    background: #f8f9fa;
    color: #1e3a5f;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.search-toggle:hover {
    background: #1e3a5f;
    color: #fff;
    transform: scale(1.05);
}

.search-form {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    z-index: 10;
}

.expandable-search.active .search-form {
    width: 700px;
    opacity: 1;
    visibility: visible;
}

@media (max-width: 1200px) {
    .expandable-search.active .search-form { width: 450px; }
}
@media (max-width: 991px) {
    .expandable-search.active .search-form { width: 300px; }
}

.expandable-search.active .search-toggle {
    opacity: 0;
    visibility: hidden;
}

.search-form .input-group {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(30, 58, 95, 0.15);
    overflow: hidden;
    border: 1px solid #e0e0e0;
}

.search-form .form-control {
    border: none;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    box-shadow: none;
}

.btn-submit, .btn-close-search {
    border: none;
    background: transparent;
    padding: 0 1rem;
    color: #6c757d;
    transition: color 0.2s;
}
.btn-submit:hover { color: #1e3a5f; }
.btn-close-search { border-left: 1px solid #eee; }
.btn-close-search:hover { color: #dc3545; }

    /* ── Glassmorphism Notification Dropdown ── */
    #notiList {
        width: 350px;
        max-height: 520px;
        overflow-y: auto;
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 15px 45px rgba(30, 58, 95, 0.2) !important;
        border-radius: 16px;
        padding-top: 0;
        padding-bottom: 0;
    }

    #notiList::-webkit-scrollbar { width: 5px; }
    #notiList::-webkit-scrollbar-thumb { background: #d0d0d0; border-radius: 10px; }

    #notiList .dropdown-header {
        background: rgba(30, 58, 95, 0.03);
        color: #1e3a5f;
    }

    #notiList .dropdown-item {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border-bottom: 1px solid rgba(0,0,0,0.04) !important;
        white-space: normal;
    }
    #notiList .notification-item:hover {
        background-color: rgba(30, 58, 95, 0.04) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    /* Animation cho Chuống & Badge */
    .badge-pulse {
        animation: pulse-red 2s infinite;
    }
    @keyframes pulse-red {
        0% { transform: translate(100%, -50%) scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { transform: translate(100%, -50%) scale(1.1); box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        100% { transform: translate(100%, -50%) scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }

    #notiDropdown:hover {
        transform: scale(1.08) rotate(5deg);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchContainer = document.getElementById('searchContainer');
    const searchToggle = document.getElementById('searchToggle');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('topbar-search-input');

    searchToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        searchContainer.classList.add('active');
        setTimeout(() => searchInput.focus(), 100);
    });

    searchClose.addEventListener('click', function(e) {
        e.stopPropagation();
        searchContainer.classList.remove('active');
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchContainer.contains(e.target)) {
            searchContainer.classList.remove('active');
        }
    });

    // ── Notifications System (AJAX Polling) ──
    const notiBadge = document.getElementById('notiBadge');
    const notiList = document.getElementById('notiList');
    const baseUrl = '<?php echo BASE_URL; ?>';

    function fetchNotifications() {
        fetch(baseUrl + 'includes/admin/notifications.php')
            .then(response => {
                if (response.status === 403) {
                    window.location.href = baseUrl + 'dangnhap.php?error=timeout';
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;

                if (data.total > 0) {
                    notiBadge.innerText = data.total > 99 ? '99+' : data.total;
                    notiBadge.classList.remove('d-none');
                } else {
                    notiBadge.classList.add('d-none');
                }

                notiList.innerHTML = '';
                if (data.items.length === 0) {
                    notiList.innerHTML = `
                        <div class="text-center py-5 px-3">
                            <i class="bi bi-bell-slash text-muted fs-1 mb-3 d-block opacity-25"></i>
                            <span class="text-muted fw-semibold">Không có thông báo mới</span>
                        </div>
                    `;
                } else {
                    notiList.innerHTML = `
                        <li class="dropdown-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-navy fs-6">Thông báo hệ thống</span>
                        </li>
                    `;
                    
                    data.items.forEach(item => {
                        let gradient = "linear-gradient(135deg, #6c757d 0%, #495057 100%)";
                        if(item.color === 'text-primary') gradient = "linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)";
                        if(item.color === 'text-danger') gradient = "linear-gradient(135deg, #dc3545 0%, #a71d2a 100%)";
                        if(item.color === 'text-warning') gradient = "linear-gradient(135deg, #ffc107 0%, #e0a800 100%)";
                        if(item.color === 'text-info') gradient = "linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%)";

                        notiList.innerHTML += `
                            <li>
                                <a class="dropdown-item py-3 px-4 border-bottom notification-item" href="${item.link}" onclick="event.stopPropagation();">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle shadow-sm d-flex align-items-center justify-content-center text-white" 
                                                 style="width: 42px; height: 42px; background: ${gradient};">
                                                <i class="bi ${item.icon} fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="text-wrap lh-sm fw-semibold text-dark mb-1" style="font-size: 0.88rem;">${item.title}</div>
                                            <div class="d-flex align-items-center gap-1 opacity-75" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock"></i> <span>Vừa cập nhật</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        `;
                    });
                    
                    notiList.innerHTML += `
                        <li class="p-2" id="btnSeeAllLi">
                            <a class="dropdown-item text-center py-2 rounded-3 small text-primary fw-bold" 
                               href="javascript:void(0)" onclick="openAllNotificationsModal()"
                               style="background: rgba(13, 110, 253, 0.05);">
                                Xem tất cả thông báo <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </li>
                    `;
                }

                document.querySelectorAll('.notification-item').forEach(el => {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.location.href = this.getAttribute('href');
                    });
                });
            })
            .catch(err => console.error('Error fetching notifications:', err));
    }

    fetchNotifications();
    setInterval(fetchNotifications, 60000);

    window.openAllNotificationsModal = function() {
        const notiList = document.getElementById('notiList');
        const cloneList = notiList.cloneNode(true);
        const seeAllBtn = cloneList.querySelector('#btnSeeAllLi');
        if (seeAllBtn) seeAllBtn.remove();
        
        const modalBody = document.getElementById('modalNotiBody');
        let htmlContent = cloneList.innerHTML;
        htmlContent = htmlContent.replace(/<li class="dropdown-header.*?<\/li>/g, '');
        
        modalBody.innerHTML = `<div class="list-group list-group-flush">${htmlContent.replace(/dropdown-item/g, 'list-group-item list-group-item-action')}</div>`;
        
        const modalEl = document.getElementById('modalAllNotifications');
        if (!modalEl.classList.contains('show')) {
            const myModal = new bootstrap.Modal(modalEl);
            myModal.show();
        }
    };
});
</script>

<style>
/* ── Toast-like Flash Messages ── */
.admin-flash-messages {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    max-width: 450px;
    pointer-events: none; /* Let clicks pass through the container */
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.admin-flash-messages .alert {
    pointer-events: auto; /* Re-enable clicks for the alert itself (close button) */
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
    margin-bottom: 0 !important;
    animation: slideInRight 0.3s ease-out forwards;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<!-- Flash Messages Area -->
<div class="admin-flash-messages">
    <!-- Màu Xanh cho thành công -->
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['success_msg'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <!-- Màu Đỏ cho lỗi -->
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['error_msg'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>
    
    <!-- Màu Cam cho cảnh báo -->
    <?php if (isset($_SESSION['warning_msg'])): ?>
        <div class="alert alert-warning alert-dismissible fade show border-0" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['warning_msg'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['warning_msg']); ?>
    <?php endif; ?>
</div>

<script>
// Auto-dismiss Flash Messages sau 5 giây
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.admin-flash-messages .alert');
    flashMessages.forEach(function(alertEl) {
        setTimeout(function() {
            if (alertEl && alertEl.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alertEl);
                bsAlert.close();
            }
        }, 5000); // 5 seconds
    });
});
</script>
