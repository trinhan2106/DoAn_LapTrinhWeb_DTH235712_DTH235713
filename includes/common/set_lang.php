<?php
// includes/common/set_lang.php
/**
 * API Đổi Global Ngôn Ngữ
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (isset($data['lang']) && in_array($data['lang'], ['vi', 'en'])) {
        $_SESSION['lang'] = $data['lang'];
        echo json_encode(['status' => 'success', 'lang' => $data['lang']]);
        exit;
    }
}
echo json_encode(['status' => 'error', 'message' => 'Lang Code bị từ chối.']);
exit;
