/**
 * PROJECT: Quản lý Cao ốc
 * MODULE: Main JavaScript Hub (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------
    // FEATURE: DARK MODE TOGGLE
    // -------------------------------------------------------------
    const themeToggleBtn = document.getElementById('themeToggle');
    const body = document.body;

    if (themeToggleBtn) {
        // Lấy userID với chuẩn bảo mật từ data attribute đã được escape XSS bằng PHP htmlspecialchars
        const userId = themeToggleBtn.getAttribute('data-user-id') || 'guest';
        const storageKey = `theme_${userId}`;
        
        // Logic khởi tạo: tự động thêm class .dark-theme nếu trước đó đã lưu
        const savedTheme = localStorage.getItem(storageKey);
        if (savedTheme === 'dark') {
            body.classList.add('dark-theme');
            themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
        }

        // Logic xử lý khi click Toggle
        themeToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            body.classList.toggle('dark-theme');
            
            if (body.classList.contains('dark-theme')) {
                localStorage.setItem(storageKey, 'dark');
                themeToggleBtn.innerHTML = '<i class="bi bi-sun"></i>';
            } else {
                localStorage.setItem(storageKey, 'light');
                themeToggleBtn.innerHTML = '<i class="bi bi-moon"></i>';
            }
        });
    }

});

// Hiển thị loader (Task 9.11)
function showLoading() {
    const loader = document.getElementById('global-loader');
    if (loader) loader.classList.remove('d-none');
}

// Ẩn loader (Task 9.11)
function hideLoading() {
    const loader = document.getElementById('global-loader');
    if (loader) loader.classList.add('d-none');
}

// Tự động bật Loader khi submit các Form (Trừ form tìm kiếm)
document.addEventListener("DOMContentLoaded", function() {
    const forms = document.querySelectorAll('form:not(.no-loader)');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Nếu sự kiện đã bị cancel (ví dụ qua confirm() trả về false) thì không hiện loader
            if (e.defaultPrevented) return;
            
            // Chỉ show loader nếu form đã qua vòng validate của HTML5
            if (this.checkValidity()) {
                showLoading();
            }
        });
    });
});
