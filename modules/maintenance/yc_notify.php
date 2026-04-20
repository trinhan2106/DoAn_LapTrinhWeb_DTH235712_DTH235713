<?php
// modules/maintenance/yc_notify.php
/**
 * Chức năng Đang Bảo Trì / Xây Dựng
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA]);

$_SESSION['error_msg'] = "Tính năng thông báo tự động Notification hiện đang bảo trì.";
header("Location: ../dashboard/admin.php");
exit();
