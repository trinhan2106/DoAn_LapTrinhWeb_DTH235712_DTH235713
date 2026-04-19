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

<!-- Dark Mode Toggle Script (Native Code) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggleBtn = document.getElementById('themeToggle');
    if (!themeToggleBtn) return;
    
    const bodyElement = document.body;
    const currentTheme = localStorage.getItem('theme');
    
    // Khôi phục trạng thái dark mode
    if (currentTheme === 'dark') {
        bodyElement.classList.add('dark-mode');
        themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
    }
    
    // Bắt sự kiện Toggle
    themeToggleBtn.addEventListener('click', function() {
        bodyElement.classList.toggle('dark-mode');
        let theme = 'light';
        if (bodyElement.classList.contains('dark-mode')) {
            theme = 'dark';
            themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
        } else {
            themeToggleBtn.innerHTML = '<i class="bi bi-moon"></i>';
        }
        localStorage.setItem('theme', theme);
    });
});
</script>
