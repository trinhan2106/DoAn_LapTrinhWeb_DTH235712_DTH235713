<?php
// config/messages.php
/**
 * Tổng hợp Data từ thông báo Giao diện của Hệ thống chuẩn mực (Alerts/Toasts).
 */
return [
    'SUCCESS' => [
        'CREATE'  => 'Biên chế dữ liệu xuất bản thành công.',
        'UPDATE'  => 'Record đã hoàn tất xác nhận điều chỉnh.',
        'DELETE'  => 'Bản ghi được dịch chuyển vào Cổng Thung Rác.',
        'RESTORE' => 'Hệ thống đã phục hồi tài nguyên dữ liệu gốc.',
        'AUTH'    => 'Quy trình xác thực an ninh hoàn tất.'
    ],
    'ERROR' => [
        'DB_CONNECT'   => 'Lỗi Máy chủ DB (Database Connection Fault).',
        'UNAUTHORIZED' => 'Từ chối: Truy cập ngoài vùng đặc quyền Role hiện hành.',
        'NOT_FOUND'    => 'Query không tồn tại thông tin Bản Ghi (ID Không hợp lệ).',
        'CSRF_FAIL'    => 'Gian lận bị chặn: Chữ ký bảo vệ Cross-Site Forge không hợp lệ.',
        'LOGIC_CONFLICT'=> 'Xung đột Luồng Nghiệp Vụ - Không được phép tác động do rào cản tính toàn vẹn (Debt, Room Lock).',
        'SYSTEM_FAULT' => 'Service đang gặp Exception kỹ thuật nội bộ.'
    ]
];
