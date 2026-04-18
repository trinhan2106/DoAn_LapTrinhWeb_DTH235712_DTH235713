<?php
/**
 * Khai báo các hằng số phân quyền người dùng (4 cấp độ)
 */

// 1: Admin - Quản trị viên hệ thống (cao nhất)
define('ROLE_ADMIN', 1);

// 2: Quản lý Nhà - Quản lý các tòa nhà
define('ROLE_QUAN_LY_NHA', 2);

// 3: Kế toán - Quản lý hóa đơn, thanh toán
define('ROLE_KE_TOAN', 3);

// 4: Khách hàng - Người thuê
define('ROLE_KHACH_HANG', 4);

// Mảng các role để dễ dàng lấy tên tiếng Việt hiển thị (khi in ra giao diện)
const APP_ROLES = [
    ROLE_ADMIN => 'Admin',
    ROLE_QUAN_LY_NHA => 'Quản lý Nhà',
    ROLE_KE_TOAN => 'Kế toán',
    ROLE_KHACH_HANG => 'Khách hàng',
];
