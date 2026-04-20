<?php
// modules/tien_coc/coc_hoan_submit.php
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

$_SESSION['error_msg'] = "Chức năng hoàn cọc đang được bảo trì. Vui lòng quay lại sau.";
header("Location: coc_hienthi.php");
exit();
