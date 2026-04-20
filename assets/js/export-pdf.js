/**
 * PROJECT: Hệ thống Quản lý Cao ốc
 * MODULE: Core PDF Export (Task 9.1)
 * DESCRIPTION: Xuất nội dung HTML thành file PDF chất lượng cao cho Hợp đồng & Hóa đơn.
 */

/**
 * Xuất nội dung HTML thành file PDF
 * @param {string} containerId - ID của thẻ div chứa nội dung cần xuất (VD: 'invoice-content')
 * @param {string} fileName - Tên file PDF khi tải xuống (VD: 'Hoa_Don_01.pdf')
 */
function exportToPDF(containerId, fileName) {
    const element = document.getElementById(containerId);
    
    if (!element) {
        console.error("PDF Export Error: Container ID '" + containerId + "' not found.");
        alert("Không tìm thấy nội dung để xuất PDF!");
        return;
    }

    // Đổi con trỏ chuột sang trạng thái loading để UX tốt hơn
    document.body.style.cursor = 'wait';

    // Cấu hình html2pdf tối ưu cho giấy A4 và tiếng Việt
    const opt = {
        margin:       [10, 10, 10, 10], // Lề [top, left, bottom, right] (mm)
        filename:     fileName || 'TaiLieu.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, // Tăng độ nét gấp đôi cho in ấn
            useCORS: true, // Cho phép tải ảnh từ domain khác
            logging: false,
            letterRendering: true // Cải thiện việc hiển thị ký tự tiếng Việt
        },
        jsPDF:        { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait' 
        },
        pagebreak:    { mode: ['css', 'legacy'] } // Tự động ngắt trang thông minh
    };

    // Thực thi luồng xử lý Promise của html2pdf
    html2pdf().set(opt).from(element).save().then(() => {
        // Trả lại trạng thái chuột bình thường
        document.body.style.cursor = 'default';
    }).catch(err => {
        console.error("Lỗi xuất PDF:", err);
        document.body.style.cursor = 'default';
        alert("Có lỗi xảy ra trong quá trình tạo PDF. Vui lòng kiểm tra console để biết chi tiết.");
    });
}
