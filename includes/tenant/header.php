<?php
/**
 * includes/tenant/header.php
 * Header riêng cho Tenant Portal, kế thừa từ public header và navbar.
 */
require_once __DIR__ . '/../public/header.php';
require_once __DIR__ . '/../public/navbar.php';
?>
<!-- Custom CSS for Tenant Pages -->
<style>
    body { background-color: #f4f7f9; }
    .text-navy { color: #1e3a5f; }
    .bg-navy { background-color: #1e3a5f; }
    .border-gold { border-color: #c9a66b; }
    .rounded-4 { border-radius: 1rem !important; }
    .card { border: none; }
</style>
