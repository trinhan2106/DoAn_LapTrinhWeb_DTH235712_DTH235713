<footer class="custom-footer mt-auto py-5" style="background-color: #1e3a5f; color: #dce1e6;">
    <div class="container">
        <div class="row g-4 text-center text-md-start">
            <div class="col-md-4">
                <h5 class="text-white fw-bold mb-3">
                    <i class="fa-solid fa-building me-2" style="color: #c9a66b;"></i>THE SAPPHIRE
                </h5>
                <p class="small text-light" style="opacity: 0.8;">Khu tổ hợp văn phòng cho thuê cao cấp bậc nhất trung tâm thành phố. Kiến trúc thông minh, dịch vụ tận tâm.</p>
            </div>
            <div class="col-md-4">
                <h5 class="text-white fw-bold mb-3">Liên Hệ</h5>
                <ul class="list-unstyled small" style="opacity: 0.9;">
                    <li class="mb-2"><i class="fa-solid fa-location-dot me-2 text-muted"></i>123 Đại lộ Trung Tâm, Quận 1, TP.HCM</li>
                    <li class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i>(028) 3883 9999</li>
                    <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-muted"></i>contact@thesapphire.com</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="text-white fw-bold mb-3">Về Chúng Tôi</h5>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none" style="opacity: 0.9; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">Giới thiệu tòa nhà</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none" style="opacity: 0.9; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">Chính sách thuê</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none" style="opacity: 0.9; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.9'">Bảo mật thông tin</a></li>
                </ul>
            </div>
        </div>
        <hr class="mt-4" style="border-color: rgba(255,255,255,0.1);">
        <div class="text-center small" style="opacity: 0.7;">
            &copy; <?php echo date('Y'); ?> The Sapphire. All Rights Reserved. Development Project DTH235712_DTH235713.
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    window.addEventListener("scroll", function() {
        var navbar = document.querySelector(".navbar.sticky-top");
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        }
    });
});
</script>
<!-- Main JS Hub -->
<script src="assets/js/main.js"></script>

<!-- Global Loading Overlay (Task 9.11) -->
<div id="global-loader" class="d-none">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Đang tải...</span>
    </div>
</div>

<!-- Vùng chứa Toast (Góc dưới bên phải) - Task 9.11 -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 10000;">
    <?php if (isset($_SESSION['flash_msg'])): 
        $type = $_SESSION['flash_type'] ?? 'success'; // success, danger, warning, info
        $icon = $type === 'success' ? 'bi-check-circle-fill' : ($type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
    ?>
    <div id="systemToast" class="toast align-items-center text-white bg-<?php echo $type; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" style="font-size: 15px;">
                <i class="bi <?php echo $icon; ?> me-2 fs-5"></i>
                <?php echo $_SESSION['flash_msg']; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var toastEl = document.getElementById('systemToast');
            if (toastEl) {
                var toast = new bootstrap.Toast(toastEl, { delay: 4000 }); // Tự tắt sau 4s
                toast.show();
            }
        });
    </script>
    <?php 
        // Xóa session sau khi hiển thị để không bị lặp lại khi F5
        unset($_SESSION['flash_msg']); 
        unset($_SESSION['flash_type']); 
    endif; ?>
</div>
<!-- CHATBOT UI -->
<style>
    #chatbot-widget { position: fixed; bottom: 20px; right: 20px; width: 320px; z-index: 9999; display: none; box-shadow: 0 5px 25px rgba(0,0,0,0.2); border-radius: 10px; overflow: hidden; background: #fff; }
    #chatbot-header { background: #1e3a5f; color: #c9a66b; padding: 12px; font-weight: bold; cursor: pointer; display: flex; justify-content: space-between; }
    #chatbot-messages { height: 250px; overflow-y: auto; padding: 10px; background: #f8f9fa; font-size: 14px; }
    .chat-bubble { max-width: 85%; margin-bottom: 10px; padding: 8px 12px; border-radius: 15px; clear: both; }
    .bot-msg { background: #e9ecef; float: left; border-bottom-left-radius: 2px; color: #333; }
    .user-msg { background: #1e3a5f; color: #fff; float: right; border-bottom-right-radius: 2px; }
    #chatbot-input-area { display: flex; border-top: 1px solid #dee2e6; }
    #chatbot-input { flex-grow: 1; border: none; padding: 10px; outline: none; }
    #chatbot-send { background: #1e3a5f; color: #fff; border: none; padding: 0 15px; cursor: pointer; }
    #chatbot-toggler { position: fixed; bottom: 20px; right: 20px; background: #1e3a5f; color: #c9a66b; width: 50px; height: 50px; border-radius: 50%; text-align: center; line-height: 50px; font-size: 24px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 10000; }
</style>

<!-- Nút bật tắt Chatbot -->
<div id="chatbot-toggler" onclick="toggleChat()"><i class="bi bi-chat-dots-fill"></i></div>

<!-- Khung Chatbot -->
<div id="chatbot-widget">
    <div id="chatbot-header" onclick="toggleChat()">
        <span>Trợ lý Ảo Cao Ốc</span>
        <i class="bi bi-x-lg"></i>
    </div>
    <div id="chatbot-messages">
        <div class="chat-bubble bot-msg">Chào bạn! Tôi có thể giúp gì cho bạn? Hãy thử gõ "phòng trống" nhé!</div>
    </div>
    <div id="chatbot-input-area">
        <input type="text" id="chatbot-input" placeholder="Gõ tin nhắn..." onkeypress="handleChatEnter(event)">
        <button id="chatbot-send" onclick="sendChatMessage()"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/chatbot.js"></script>
</body>
</html>
