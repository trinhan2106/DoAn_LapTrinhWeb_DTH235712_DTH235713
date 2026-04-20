/**
 * assets/js/qrcode-init.js
 * ==================================================================
 * Script khởi tạo và vẽ QR Code tự động cho hóa đơn/hợp đồng.
 * Yêu cầu: Đã nhúng thư viện qrcode.js phía trước.
 * ==================================================================
 */

document.addEventListener("DOMContentLoaded", function() {
    const qrContainer = document.getElementById("qrcode-container");
    
    if (qrContainer) {
        const url = qrContainer.getAttribute("data-url");
        
        if (!url) {
            console.error("[QR-Init] Lỗi: Không tìm thấy data-url trong container.");
            return;
        }

        // Tạo instance QRCode mới
        new QRCode(qrContainer, {
            text: url,
            width: 128,            // Độ rộng (px)
            height: 128,           // Độ cao (px)
            colorDark : "#1e3a5f",  // Màu Navy chuẩn thương hiệu (Dấu chấm)
            colorLight : "#ffffff", // Màu nền trắng
            correctLevel : QRCode.CorrectLevel.H // Cấp độ sửa lỗi cao nhất (30%)
        });
        
        console.log("[QR-Init] Đã vẽ QR Code thành công cho URL: " + url);
    }
});
