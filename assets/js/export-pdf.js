/**
 * PROJECT: Hệ thống Quản lý Cao ốc
 * MODULE: Core PDF Export (Task 9.1)
 * DESCRIPTION: Xuất nội dung HTML thành file PDF chất lượng cao cho Hợp đồng & Hóa đơn.
 * Cải tiến UX với Loading Spinner và Config chuẩn hóa SRM.
 */

/**
 * Xuất nội dung HTML thành file PDF
 * @param {string} containerId - ID của thẻ div chứa nội dung cần xuất (mặc định: 'pdf-content')
 * @param {string} fileName - Tên file PDF khi tải xuống (VD: 'Hoa_Don_01.pdf')
 * @param {boolean} autoClose - Tự động đóng cửa sổ sau khi xuất xong (mặc định: false)
 */
function exportToPDF(containerId = 'pdf-content', fileName = 'TaiLieu.pdf', autoClose = false) {
    const element = document.getElementById(containerId);
    const overlay = document.getElementById('loading-overlay');
    
    if (!element) {
        console.error("PDF Export Error: Container ID '" + containerId + "' not found.");
        alert("Không tìm thấy nội dung để xuất PDF!");
        return;
    }

    // Hiển thị loading spinner nếu có
    if (overlay) {
        overlay.style.display = 'flex';
    } else {
        document.body.style.cursor = 'wait';
    }

    // Cấu hình html2pdf tối ưu theo chuẩn FIX-01
    const opt = {
        margin:       10, // 10mm
        filename:     fileName,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, // Độ nét cao
            useCORS: true, 
            letterRendering: true 
        },
        jsPDF:        { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait' 
        },
        pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
    };

    // Thực thi luồng xử lý Promise của html2pdf
    if (typeof html2pdf === 'undefined') {
        console.error("html2pdf library is missing!");
        alert("Thư viện html2pdf chưa được tải. Vui lòng kiểm tra lại kết nối mạng.");
        if (overlay) overlay.style.display = 'none';
        document.body.style.cursor = 'default';
        return;
    }

    html2pdf().set(opt).from(element).save().then(() => {
        // Tắt loading
        if (overlay) overlay.style.display = 'none';
        document.body.style.cursor = 'default';
        
        // Thông báo thành công (Có thể dùng Toast nếu hệ thống có sẵn)
        // alert("Tài liệu đã được xuất thành công!");

        if (autoClose) {
            setTimeout(() => window.close(), 1000);
        }
    }).catch(err => {
        console.error("Lỗi xuất PDF:", err);
        if (overlay) overlay.style.display = 'none';
        document.body.style.cursor = 'default';
        alert("Có lỗi xảy ra trong quá trình tạo PDF. Vui lòng thử lại.");
    });
}
