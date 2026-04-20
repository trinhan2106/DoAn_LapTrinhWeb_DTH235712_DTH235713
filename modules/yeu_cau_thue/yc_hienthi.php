<?php
/**
 * modules/yeu_cau_thue/yc_hienthi.php
 * Hiển thị danh sách yêu cầu thuê phòng từ khách hàng tiềm năng.
 */

// 1. Khởi tạo & Bảo mật
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';
require_once __DIR__ . '/../../includes/common/csrf.php';

// Kiểm tra quyền: Admin (1) hoặc Quản lý Nhà (2)
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

// Kết nối DB
$pdo = Database::getInstance()->getConnection();

// 2. Truy vấn dữ liệu: Lấy danh sách yêu cầu thuê chưa xóa mềm
try {
    $stmt = $pdo->prepare("
        SELECT yc.*, p.tenPhong 
        FROM YEU_CAU_THUE yc 
        JOIN PHONG p ON yc.maPhong = p.maPhong 
        WHERE yc.deleted_at IS NULL 
        ORDER BY yc.ngayYeuCau DESC
    ");
    $stmt->execute();
    $yeuCauList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// ... 
include_once __DIR__ . '/../../includes/admin/admin-header.php';
?>

<style>
    .table-navy thead th {
        background-color: #1e3a5f !important;
        color: #ffffff !important;
        font-weight: 600;
        padding: 0.85rem 1rem !important;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    .table-navy tbody td {
        padding: 0.75rem 1rem !important;
        vertical-align: middle;
    }
    .text-navy { color: #1e3a5f !important; }
    .btn-gold {
        background-color: #c9a66b !important;
        color: #ffffff !important;
        border: none;
        transition: all 0.3s;
    }
    .btn-gold:hover { 
        background-color: #b5925a; 
        transform: translateY(-1px); 
        box-shadow: 0 4px 10px rgba(0,0,0,0.15); 
    }
    .filter-section {
        background-color: #fff;
        border-radius: 12px;
        border-left: 6px solid #c9a66b;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    
    /* Enhanced Badges */
    .badge-status {
        padding: 0.4rem 0.85rem;
        font-weight: 600;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
    }
    .badge-status--warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .badge-status--success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .badge-status--danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    /* Action Buttons */
    .btn-action {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
        border: 1.5px solid transparent;
        background: transparent;
    }
    .btn-action-info { color: #0dcaf0; border-color: #0dcaf0; }
    .btn-action-info:hover { background: #0dcaf0; color: white; }
    .btn-action-success { color: #198754; border-color: #198754; }
    .btn-action-success:hover { background: #198754; color: white; }
    .btn-action-danger { color: #dc3545; border-color: #dc3545; }
    .btn-action-danger:hover { background: #dc3545; color: white; }
    
    /* Timeline Stepper CSS */
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        margin: 40px 0;
        position: relative;
    }
    .stepper-wrapper::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 50px;
        right: 50px;
        height: 2px;
        background-color: #e9ecef;
        z-index: 1;
    }
    .stepper-item {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    .stepper-item .circle {
        width: 42px;
        height: 42px;
        background-color: white;
        border: 2px solid #e0e0e0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    .stepper-item.active .circle {
        border-color: #1e3a5f;
        color: #1e3a5f;
        box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.1);
    }
    .stepper-item.completed .circle {
        background-color: #1e3a5f;
        border-color: #1e3a5f;
        color: white;
    }
    .stepper-item.rejected .circle {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }
    .stepper-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6c757d;
    }
    .stepper-item.active .stepper-label, 
    .stepper-item.completed .stepper-label {
        color: #1e3a5f;
    }
    /* DataTables Custom Styling */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1.5rem;
        padding: 0 0.5rem;
    }
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 0.4rem 1rem;
        margin-left: 0.8rem;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        transition: all 0.2s;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #c9a66b;
        box-shadow: 0 0 0 0.25rem rgba(201, 166, 107, 0.25);
        outline: none;
    }
    .dataTables_wrapper .dataTables_info {
        padding: 1.5rem 0.5rem;
        color: #6c757d;
        font-size: 0.875rem;
    }
    .dataTables_wrapper .dataTables_paginate {
        padding: 1.5rem 0.5rem;
    }
</style>

<div class="admin-layout">
    <?php include_once __DIR__ . '/../../includes/admin/sidebar.php'; ?>

    <div class="admin-main-wrapper flex-grow-1">
        <?php include_once __DIR__ . '/../../includes/admin/topbar.php'; ?>

        <main class="admin-main-content p-4">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/dashboard/admin.php" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
                    <li class="breadcrumb-item active">Quản lý Yêu cầu thuê</li>
                </ol>
            </nav>

            <!-- Header Section -->
            <div class="card filter-section shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-9">
                            <h2 class="h4 mb-0 text-navy fw-bold uppercase">
                                <i class="bi bi-clipboard-check-fill me-2 text-gold"></i>DANH SÁCH YÊU CẦU THUÊ
                            </h2>
                            <p class="text-muted small mb-0 mt-1">Theo dõi và phản hồi nhanh các yêu cầu thuê văn phòng từ khách hàng.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Bảng danh sách DataTables -->
            <div class="card shadow-lg border-0" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="table-responsive rounded-3 overflow-hidden">
                        <table id="tableYeuCau" class="table table-hover align-middle table-navy w-100 border-0 mb-0 table-datatable">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width: 30%;">Thông tin Khách hàng</th>
                                    <th style="width: 25%;">Phòng đăng ký</th>
                                    <th style="width: 15%;">Ngày gửi</th>
                                    <th class="text-center" style="width: 15%;">Trạng thái</th>
                                    <th class="text-center" style="width: 15%;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yeuCauList as $yc): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-light border me-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px; border-radius:50%;">
                                                    <i class="bi bi-person text-navy"></i>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-navy h6 mb-1"><?= e($yc['hoTen']) ?></span>
                                                    <small class="text-muted"><i class="bi bi-telephone text-gold me-1"></i><?= e($yc['sdt']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-white text-navy border px-2 py-1" style="font-weight: 500;">
                                                <i class="bi bi-door-open me-1"></i><?= e($yc['tenPhong']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small text-dark font-monospace"><?= date('d/m/Y H:i', strtotime($yc['ngayYeuCau'])) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($yc['trangThai'] == 0): ?>
                                                <span class="badge-status badge-status--warning"><i class="bi bi-clock-history"></i> Chờ duyệt</span>
                                            <?php elseif ($yc['trangThai'] == 1): ?>
                                                <span class="badge-status badge-status--success"><i class="bi bi-check2-all"></i> Đã xử lý</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-status--danger"><i class="bi bi-x-circle"></i> Đã từ chối</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-info" 
                                                        title="Xem chi tiết"
                                                        style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                        onclick="xemChiTiet(<?= htmlspecialchars(json_encode($yc)) ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if ($yc['trangThai'] == 0): ?>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            title="Duyệt yêu cầu"
                                                            style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                            onclick="xacNhanDuyet('<?= $yc['maYeuCau'] ?>')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            title="Từ chối yêu cầu"
                                                            style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                            onclick="xacNhanTuChoi('<?= $yc['maYeuCau'] ?>')">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-4 text-center">
                 <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Nhấp vào biểu tượng con mắt để xem chi tiết tiến trình của yêu cầu.</small>
            </div>
        </main>
        
        <!-- Modal Chi tiết với Timeline Stepper -->
        <div class="modal fade" id="modalChiTiet" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 card-brand">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-brand-primary"><i class="bi bi-info-circle me-2"></i>Chi tiết Yêu cầu thuê</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Timeline Stepper -->
                        <div class="stepper-wrapper mb-5" id="timelineStepper">
                            <div class="stepper-item" id="step-sent">
                                <div class="circle"><i class="bi bi-send-fill fs-5"></i></div>
                                <div class="stepper-label">Gửi yêu cầu</div>
                            </div>
                            <div class="stepper-item" id="step-review">
                                <div class="circle"><i class="bi bi-search fs-5"></i></div>
                                <div class="stepper-label">Đang xem xét</div>
                            </div>
                            <div class="stepper-item" id="step-final">
                                <div class="circle"><i class="bi bi-check-lg fs-4"></i></div>
                                <div class="stepper-label" id="label-final">Hoàn thành</div>
                            </div>
                        </div>

                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block uppercase tracking-wider font-semibold">Họ tên khách hàng</label>
                                <p class="mb-0 fw-bold fs-5" id="view-hoTen"></p>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted mb-1 d-block">Phòng đăng ký</label>
                                <p class="mb-0 fw-bold fs-5 text-brand-accent" id="view-tenPhong"></p>
                            </div>
                            <div class="col-md-6 border-top pt-3">
                                <label class="small text-muted mb-1 d-block">Số điện thoại</label>
                                <p class="mb-0 fw-bold" id="view-sdt"></p>
                            </div>
                            <div class="col-md-6 border-top pt-3">
                                <label class="small text-muted mb-1 d-block">Email</label>
                                <p class="mb-0 fw-bold" id="view-email"></p>
                            </div>
                            <div class="col-md-12 border-top pt-3">
                                <label class="small text-muted mb-1 d-block">Thời điểm gửi yêu cầu</label>
                                <p class="mb-0" id="view-ngayYeuCau"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Đóng</button>
                        <span id="btnActionArea"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden CSRF Forms for actions -->
        <form id="formApprove" action="yc_xuly_submit.php" method="POST" style="display:none;">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="maYeuCau" id="approve-id">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        </form>
        
        <form id="formReject" action="yc_xuly_submit.php" method="POST" style="display:none;">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="maYeuCau" id="reject-id">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        </form>

        <?php include_once __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div>
</div>


<script>
$(document).ready(function() {
    // 3. Quy tắc 3 cú nhấp chuột: DataTables tiếng Việt & tương tác nhanh
    $('#tableYeuCau').DataTable();
});

function xemChiTiet(data) {
    // Reset Stepper
    $('.stepper-item').removeClass('active completed rejected');
    $('#label-final').text('Hoàn thành');
    $('#step-final .circle').html('<i class="bi bi-check-lg fs-4"></i>');

    // Fill data
    $('#view-hoTen').text(data.hoTen);
    $('#view-sdt').text(data.sdt);
    $('#view-email').text(data.email || 'N/A');
    $('#view-tenPhong').text(data.tenPhong);
    $('#view-ngayYeuCau').text(data.ngayYeuCau);

    // Set Stepper State
    $('#step-sent').addClass('completed');
    if (data.trangThai == 0) {
        $('#step-review').addClass('active');
        $('#btnActionArea').html(`
            <button class="btn btn-success px-4" onclick="xacNhanDuyet('${data.maYeuCau}')">
                <i class="bi bi-check2-circle me-1"></i> Duyệt ngay
            </button>
        `);
    } else if (data.trangThai == 1) {
        $('#step-review').addClass('completed');
        $('#step-final').addClass('completed');
        $('#btnActionArea').html('');
    } else if (data.trangThai == 2) {
        $('#step-review').addClass('completed');
        $('#step-final').addClass('rejected');
        $('#label-final').text('Đã từ chối');
        $('#step-final .circle').html('<i class="bi bi-x-lg fs-4"></i>');
        $('#btnActionArea').html('');
    }

    const modal = new bootstrap.Modal(document.getElementById('modalChiTiet'));
    modal.show();
}

function xacNhanDuyet(id) {
    if (confirm('Bạn có chắc chắn muốn xác nhận đã liên hệ và duyệt yêu cầu này?')) {
        $('#approve-id').val(id);
        $('#formApprove').submit();
    }
}

function xacNhanTuChoi(id) {
    if (confirm('Bạn có chắc chắn muốn từ chối yêu cầu thuê phòng này?')) {
        $('#reject-id').val(id);
        $('#formReject').submit();
    }
}
</script>
