<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
kiemTraSession();
$pdo = Database::getInstance()->getConnection();
$role = (int)$_SESSION['user_role'];
$tenUser = htmlspecialchars($_SESSION['ten_user'] ?? 'Nhân viên');
$chucVu = APP_ROLES[$role] ?? 'Thành viên';
?>

<style>
    :root {
        --brand-primary: #1e3a5f;
        --brand-accent: #c9a66b;
        --brand-bg: #f4f7f9;
        --card-bg: #ffffff;
    }

    .dashboard-wrapper {
        background-color: var(--brand-bg);
        padding-bottom: 1.5rem;
    }

    .welcome-banner {
        background: linear-gradient(135deg, var(--brand-primary) 0%, #2c5282 100%);
        color: white;
        border-radius: 0.75rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(30, 58, 95, 0.08);
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(201, 166, 107, 0.1);
        border-radius: 50%;
    }

    .welcome-text h2 {
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .status-badge {
        background-color: rgba(201, 166, 107, 0.2);
        color: var(--brand-accent);
        border: 1px solid rgba(201, 166, 107, 0.3);
        padding: 0.4rem 1rem;
        border-radius: 50rem;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .shortcut-card {
        background: var(--card-bg);
        border: none;
        border-radius: 0.6rem;
        padding: 0.85rem 1rem;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
        border: 1px solid rgba(0,0,0,0.06);
        gap: 0.85rem;
    }

    .shortcut-card:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(30, 58, 95, 0.08) !important;
        border-color: var(--brand-accent);
        background-color: #fff;
    }

    .shortcut-icon-wrapper {
        width: 36px;
        height: 36px;
        min-width: 36px;
        background-color: rgba(30, 58, 95, 0.04);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        color: var(--brand-primary);
    }

    .shortcut-card:hover .shortcut-icon-wrapper {
        background-color: var(--brand-primary);
        color: white;
        transform: rotate(5deg);
    }

    .shortcut-icon {
        font-size: 1.1rem;
    }

    .shortcut-content {
        flex-grow: 1;
    }

    .shortcut-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.1rem;
        line-height: 1.2;
    }

    .shortcut-desc {
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .section-title {
        font-weight: 700;
        color: var(--brand-primary);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
    }

    .section-title::before {
        content: '';
        width: 4px;
        height: 24px;
        background-color: var(--brand-accent);
        margin-right: 12px;
        border-radius: 2px;
    }
</style>

<div class="dashboard-wrapper container-fluid p-4">
    <!-- Header Greeting -->
    <div class="welcome-banner d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="welcome-text">
            <h2 class="mb-1">Xin chào, <?= $tenUser ?>!</h2>
            <p class="mb-0 text-white-50">Chào mừng bạn quay trở lại hệ thống quản lý cao ốc.</p>
        </div>
        <div class="user-status">
            <span class="status-badge">
                <i class="bi bi-shield-check me-1"></i> <?= $chucVu ?>
            </span>
        </div>
    </div>

    <!-- Quick Shortcuts Section -->
    <div class="row">
        <div class="col-12">
            <h4 class="section-title">Chức năng thường dùng</h4>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($role === ROLE_ADMIN): ?>
            <!-- Admin Cards -->
        <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/nhan_vien/" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-people-fill shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Quản lý Nhân sự</div>
                        <p class="shortcut-desc">Quản lý tài khoản, phân quyền.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/cau_hinh/" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-gear-fill shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Cấu hình hệ thống</div>
                        <p class="shortcut-desc">Thiết lập tham số vận hành.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/audit_log/" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-journal-text shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Giao dịch & Nhật ký</div>
                        <p class="shortcut-desc">Xem lịch sử hoạt động (Audit Log).</p>
                    </div>
                </a>
            </div>

        <?php elseif ($role === ROLE_QUAN_LY_NHA): ?>
            <!-- Property Manager Cards -->
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/hop_dong/?action=add" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-file-earmark-plus-fill shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Lập Hợp Đồng Mới</div>
                        <p class="shortcut-desc">Soạn thảo và ký kết hợp đồng.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/hop_dong/?status=expired" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-arrow-repeat shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Gia hạn phòng</div>
                        <p class="shortcut-desc">Xử lý gia hạn hợp đồng.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/tenant/" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-person-lines-fill shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Quản lý Khách hàng</div>
                        <p class="shortcut-desc">Thông tin chi tiết khách thuê.</p>
                    </div>
                </a>
            </div>

        <?php elseif ($role === ROLE_KE_TOAN): ?>
            <!-- Accountant Cards -->
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/thanh_toan/" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-cash-coin shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Thu tiền & Cấn trừ</div>
                        <p class="shortcut-desc">Quản lý các khoản thu và nợ.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/phong/dien_nuoc.php" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-droplet-fill shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Điện Nước</div>
                        <p class="shortcut-desc">Cập nhật chỉ số tiêu thụ.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>modules/thanh_toan/hoa_don.php?status=pending" class="shortcut-card shadow-sm">
                    <div class="shortcut-icon-wrapper">
                        <i class="bi bi-file-earmark-spreadsheet-fill shortcut-icon"></i>
                    </div>
                    <div class="shortcut-content">
                        <div class="shortcut-title">Hóa đơn cần xử lý</div>
                        <p class="shortcut-desc">Duyệt và phát hành hóa đơn.</p>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
