<?php
/**
 * includes/tenant/footer.php
 * Footer riêng cho Tenant Portal, kế thừa từ public footer.
 */
require_once __DIR__ . '/../public/footer.php';
?>
<!-- Live-update banner cho Tenant Portal -->
<div id="tenantUpdateBanner" style="
    display:none; position:fixed; top:70px; left:50%; transform:translateX(-50%);
    background:linear-gradient(135deg,#1e3a5f,#2c5282); color:#fff;
    padding:10px 24px; border-radius:30px; box-shadow:0 4px 20px rgba(0,0,0,0.2);
    z-index:9999; font-size:0.88rem; font-weight:600; cursor:pointer;
    display:none; align-items:center; gap:10px;">
    <i class="bi bi-arrow-clockwise"></i>
    <span>Có cập nhật mới — Nhấn để tải lại trang</span>
</div>

<script>
(function() {
    const baseUrl = '<?php echo defined("BASE_URL") ? BASE_URL : "/"; ?>';
    const banner  = document.getElementById('tenantUpdateBanner');
    if (!banner) return;

    // Lấy version ban đầu khi trang load xong
    let currentVersion = null;
    let hasShownBanner = false;

    fetch(baseUrl + 'includes/tenant/check_updates.php')
        .then(r => r.ok ? r.json() : null)
        .then(data => { if (data) currentVersion = data.version; })
        .catch(() => {});

    // Poll mỗi 30 giây
    setInterval(function() {
        if (hasShownBanner) return; // Đã hiện rồi, không cần poll thêm
        fetch(baseUrl + 'includes/tenant/check_updates.php')
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data || !currentVersion) return;
                if (data.version !== currentVersion) {
                    hasShownBanner = true;
                    banner.style.display = 'flex';
                }
            })
            .catch(() => {});
    }, 30000);

    // Click vào banner → reload
    banner.addEventListener('click', function() {
        window.location.reload();
    });
})();
</script>
