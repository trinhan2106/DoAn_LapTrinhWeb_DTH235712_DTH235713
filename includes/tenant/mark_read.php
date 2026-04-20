<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/auth.php';

kiemTraSession();

if ((int)($_SESSION['user_role'] ?? 0) !== 4) {
    http_response_code(403);
    exit();
}

$pdo = Database::getInstance()->getConnection();
$pdo->prepare("UPDATE THONG_BAO SET daDoc = 1 WHERE nguoiNhan = ? AND daDoc = 0")
    ->execute([$_SESSION['user_id']]);

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
