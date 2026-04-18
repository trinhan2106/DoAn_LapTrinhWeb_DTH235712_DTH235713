<?php
// config/status.php
/**
 * Ánh xạ Trạng Thái Hệ Thống vào Giao Diện (Label Mapping Core)
 */
return [
    'PHONG' => [
        'Trong'    => 'Phòng Trống',
        'DangThue' => 'Đang Cho Thuê',
        'BaoTri'   => 'Đang Bảo Trì'
    ],
    'HOP_DONG' => [
        'Nhap'        => 'Lưu Nháp',
        'ChoDuyet'    => 'Chờ Ký Duyệt',
        'DangHieuLuc' => 'Đang Hiệu Lực',
        'HetHan'      => 'Đã Hết Hạn',
        'DaHuy'       => 'Đã Thanh Lý/Hủy'
    ],
    'HOA_DON' => [
        'ChuaThanhToan'    => 'Dư Nợ (Chưa TT)',
        'ThanhToanMotPhan' => 'TT Một Phần (Còn nợ)',
        'DaThanhToan'      => 'Hoàn Tất Đối Soát',
        'Void'             => 'Vô Hiệu Hóa (Void)'
    ],
    'MAINTENANCE' => [
        0 => 'Chờ Tiếp Nhận (Open)',
        1 => 'Đang Xử Lý (In-Progress)',
        2 => 'Đóng Ticket (Resolved)',
        3 => 'Hủy Lệnh (Closed)'
    ],
    'TIEN_COC' => [
        1 => 'Đã Thu Đủ',
        2 => 'Đã Hoàn Trả',
        3 => 'Tịch Thu Cọc'
    ]
];
