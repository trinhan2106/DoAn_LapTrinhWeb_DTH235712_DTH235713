// assets/js/lang.js
/**
 * Switch logic Language thông qua async POST fetch request API
 */
document.addEventListener('DOMContentLoaded', () => {
    const btnLangs = document.querySelectorAll('.lang-toggle');
    if (!btnLangs) return;

    btnLangs.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('data-lang');
            
            if (!target) return;

            // Truy tìm Root Path Xampp project thông qua index string parsing (Safe cross-environment strategy)
            const parts = window.location.pathname.split('/');
            const rootDomain = '/' + (parts[1] || '') + '/';

            fetch(rootDomain + 'includes/common/set_lang.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ lang: target })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.reload(); // Reload giao diện để render Array Localized Mới
                } else {
                    console.error('Logic đổi ngôn ngữ bị chặn đứng:', data.message);
                }
            })
            .catch(err => {
                console.error('Core Exception mạng Internet:', err);
            });
        });
    });
});
