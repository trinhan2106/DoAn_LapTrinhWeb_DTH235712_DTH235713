/**
 * assets/js/realtime-calc.js
 * Quản trị Logic tính toán Real-time phía Client-side (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. MODULE HỢP ĐỒNG: TÍNH GIÁ THUÊ TỰ ĐỘNG
    // ==========================================
    /*
     * BE Structure Dependency: 
     * Yêu cầu các inputs sở hữu id tương ứng: #dienTich, #donGiaM2, #heSoGia.
     * Output field map: #giaThue.
     */
    const inpDienTich = document.getElementById('dienTich');
    const inpDonGiaM2 = document.getElementById('donGiaM2');
    const inpHeSoGia = document.getElementById('heSoGia');
    const outGiaThue = document.getElementById('giaThue');

    if (inpDienTich && inpDonGiaM2 && inpHeSoGia && outGiaThue) {
        
        // Block ReadOnly bảo vệ tính toàn vẹn - chống User inject data tay
        outGiaThue.readOnly = true;

        const processRentalFee = () => {
            const dt = parseFloat(inpDienTich.value) || 0;
            const dg = parseFloat(inpDonGiaM2.value) || 0;
            const hs = parseFloat(inpHeSoGia.value) || 1; // Default Hệ số = 1
            
            const rootPrice = dt * dg * hs;

            // Xử lý Render Format Localized Mệnh Giá VNĐ
            outGiaThue.value = new Intl.NumberFormat('vi-VN').format(rootPrice) + ' VNĐ';

            // Injection Hidden value (để phục vụ request POST thô chuẩn nếu BE không tự xử lý tính lại)
            let rawField = document.getElementById('giaThueRaw');
            if (rawField) rawField.value = rootPrice;
        };

        // Gắn listener DOM Input cho chuỗi logic liên kết
        [inpDienTich, inpDonGiaM2, inpHeSoGia].forEach(element => {
            element.addEventListener('input', processRentalFee);
        });
        
        // Khởi động chạy lần định dạng đầu tiên nếu BE truyền sẵn Value Edit
        processRentalFee();
    }


    // ==========================================
    // 2. MODULE THANH TOÁN: RÀNG BUỘC KÉP CHỈ SỐ ĐIỆN NƯỚC (DELTA LIMITS)
    // ==========================================
    /*
     * BE Structure Dependency: 
     * Khối div block (row) wrapper: .dien-nuoc-row 
     * Target Element classes: .chi-so-dau, .chi-so-cuoi, .don-gia-dv, .tong-tien-dv, .delta-warning
     * Global Form Submit Button: .btn-submit-diennuoc
     */
    const danhSachGhiDienNuoc = document.querySelectorAll('.dien-nuoc-row');
    const globalSubmitBtn = document.querySelector('.btn-submit-diennuoc');

    danhSachGhiDienNuoc.forEach(row => {
        const inpCSDau = row.querySelector('.chi-so-dau');
        const inpCSCuoi = row.querySelector('.chi-so-cuoi');
        const inpDonGia = row.querySelector('.don-gia-dv');
        const outTongTien = row.querySelector('.tong-tien-dv');
        const lblWarn = row.querySelector('.delta-warning');

        if (inpCSDau && inpCSCuoi) {
            
            if (outTongTien) outTongTien.readOnly = true;

            const validateUsageDelta = () => {
                const soDau = parseFloat(inpCSDau.value) || 0;
                const soCuoi = parseFloat(inpCSCuoi.value) || 0;
                const donGia = inpDonGia ? (parseFloat(inpDonGia.value) || 0) : 0;
                
                const delta = soCuoi - soDau;

                // Base Reset Context Rules
                if (lblWarn) {
                    lblWarn.className = 'delta-warning mt-1 d-block fw-bold small';
                    lblWarn.textContent = '';
                }
                
                if (globalSubmitBtn) {
                    // Cần cẩn trọng khi có mốc nhiều row điện nước, chỉ một row lỗi cũng phải chặn Submit toàn bộ
                    // Tạm Reset, nếu rẽ nhánh lỗi sẽ chặn lại ngay bên dưới
                    globalSubmitBtn.disabled = false;
                }
                
                // [EXCEPTION CHECK 1]: Delta Âm - Negative Index Regression Lỗi Toán Học
                if (delta < 0) {
                    if (lblWarn) {
                        lblWarn.classList.add('text-danger');
                        lblWarn.textContent = '❌ Sự cố cấu hình: Chỉ số cuối không được nhỏ hơn chỉ số đầu!';
                    }
                    if (globalSubmitBtn) globalSubmitBtn.disabled = true;
                    if (outTongTien) outTongTien.value = 'ERR_DELTA_NEG';
                    return; // Fail Fast
                }

                // [EXCEPTION CHECK 2]: Delta Ảo Tưởng Vượt Ngưỡng Cơ Học
                if (delta > 9999) {
                    if (lblWarn) {
                        lblWarn.classList.add('text-warning', 'text-dark', 'bg-warning', 'px-2', 'rounded');
                        lblWarn.textContent = '⚠️ Cảnh báo bất thường: Độ lệch vượt ngưỡng lưu lượng cao (>9999). Vui lòng check lại rò rỉ đồng hồ!';
                    }
                }

                // Tiến trình Data Pipeline Normal: Calculate Target Price
                if (outTongTien && delta >= 0) {
                    const priceExtracted = delta * donGia;
                    outTongTien.value = new Intl.NumberFormat('vi-VN').format(priceExtracted) + ' VNĐ';
                }
            };

            // Mapping DOM Trigger Tracking
            ['input', 'change'].forEach(evt => {
                inpCSDau.addEventListener(evt, validateUsageDelta);
                inpCSCuoi.addEventListener(evt, validateUsageDelta);
            });

            // Initial Bootstrap Pipeline 
            validateUsageDelta();
        }
    });
});
