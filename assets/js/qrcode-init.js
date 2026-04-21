/**
 * assets/js/qrcode-init.js
 * ==================================================================
 * Script render QR Code bảo mật (Task 9.2)
 * Sử dụng thư viện qrcode.js (giả định đã load)
 * ==================================================================
 */

/**
 * Tạo mã QR bảo mật và nhúng vào phần tử chỉ định
 * @param {string} elementId - ID của thẻ div chứa QR
 * @param {string} tokenUrl - URL đã bao gồm JWT token
 */
function generateSecureQR(elementId, tokenUrl) {
    const container = document.getElementById(elementId);
    if (!container) {
        console.error("Không tìm thấy phần tử:", elementId);
        return;
    }

    // Xóa nội dung cũ nếu có
    container.innerHTML = "";

    // Khởi tạo QRCode
    try {
        new QRCode(container, {
            text: tokenUrl,
            width: 180,
            height: 180,
            colorDark: "#1e3a5f", // Màu xanh Navy đồng bộ thương hiệu
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Tối ưu UI: Thêm hiệu ứng hover và padding
        container.style.display = "inline-block";
        container.style.padding = "15px";
        container.style.background = "#fff";
        container.style.borderRadius = "15px";
        container.style.boxShadow = "0 8px 15px rgba(0,0,0,0.05)";
        container.style.transition = "transform 0.3s ease";
        
        container.addEventListener("mouseenter", () => {
            container.style.transform = "scale(1.05)";
        });
        container.addEventListener("mouseleave", () => {
            container.style.transform = "scale(1)";
        });

    } catch (error) {
        console.error("Lỗi khi tạo QR Code:", error);
    }
}
