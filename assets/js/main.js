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
