<footer class="bg-white border-top py-3 mt-auto">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Bản quyền &copy; Hệ thống Quản lý Cao ốc <?php echo date('Y'); ?></div>
            <div>
                <a href="#" class="text-secondary text-decoration-none me-3">Chính sách bảo mật</a>
                <a href="#" class="text-secondary text-decoration-none">Điều khoản</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal Xem Tất Cả Thông Báo (Global) -->
<div class="modal fade" id="modalAllNotifications" tabindex="-1" aria-labelledby="modalAllNotificationsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-navy" id="modalAllNotificationsLabel">
                    <i class="bi bi-bell-fill me-2 text-warning"></i>Tất cả thông báo hệ thống
                </h5>
                <button type="button" class="btn-close" data-bs-modal="dismiss" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="modalNotiBody">
                <!-- Nội dung sẽ được Clone từ Dropdown vào đây qua JS -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-navy bg-navy border-0 rounded-pill px-4 fw-bold" onclick="location.reload()">Làm mới</button>
            </div>
        </div>
    </div>
</div>

<!-- Dark Mode Toggle Script (Native Code) -->
<!-- Main JS Hub (bao gồm Dark Mode Toggle) -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
