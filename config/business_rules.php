<?php
// config/business_rules.php
/**
 * Định nghĩa Hằng số Quy tắc Nghiệp vụ Kinh doanh (Business Rules)
 * Dùng làm mốc chuẩn cho toàn hệ thống để kiểm soát các tác vụ nhạy cảm.
 */
return [
    'THOI_GIAN_THUE_TOI_THIEU' => 6,  // Hợp đồng thuê bắt buộc tối thiểu (tháng)
    'LOCK_PHONG_PHUT'          => 10, // Thời gian trói phòng trong Wizard tạo HĐ (phút)
    'MAX_LOGIN_ATTEMPTS'       => 5,  // Giới hạn max lỗi Brute Force
    'LOCKOUT_TIME_MINUTES'     => 15, // Thời gian phạt khóa đăng nhập (phút)
    'VAT_PERCENT'              => 10, // Thuế VAT mặc định tính trên biên lai (%)
    'NGAY_THANH_TOAN_MAX'      => 5,  // Hạn thanh toán sau khi ra hóa đơn hàng tháng (ngày)
    'SLA_PRIORITY_LOW'         => 72, // Hạn định giờ xử lý Maintenance Request: Thấp (h)
    'SLA_PRIORITY_MID'         => 48, // Hạn định giờ xử lý Maintenance Request: Trung bình (h)
    'SLA_PRIORITY_HIGH'        => 24, // Hạn định giờ xử lý Maintenance Request: Cao (h)
    'SLA_PRIORITY_URGENT'      => 4,  // Hạn định giờ xử lý Maintenance Request: Khẩn cấp (h)
];
