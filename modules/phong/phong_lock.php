<?php
// modules/phong/phong_lock.php
/**
 * AJAX endpoint: Lock/Unlock phong tam thoi khi tao hop dong.
 * Atomic upsert nho UNIQUE(maPhong) — khong can transaction/FOR UPDATE.
 * INSERT ... ON DUPLICATE KEY UPDATE chi cap nhat neu cung session hoac lock het han.
 * Sau upsert, SELECT verify ownership.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php'; // phải trước auth.php
require_once __DIR__ . '/../../includes/common/auth.php';

header('Content-Type: application/json; charset=utf-8');

// --- Authentication (AJAX-safe): kiểm tra thủ công thay vì gọi kiểmTraSession() ---
// kiểmTraSession() dùng header(Location)+exit() không phù hợp AJAX — nó sẽ redirect thay vì JSON
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Chua dang nhap hoac phien het han.']);
    exit();
}

// Kiểm tra session timeout thủ công
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Phien lam viec het han. Vui long dang nhap lai.']);
    exit();
}
$_SESSION['last_activity'] = time(); // Cập nhật timestamp

$userRole = (int)($_SESSION['user_role'] ?? 0);
if (!in_array($userRole, [ROLE_ADMIN, ROLE_QUAN_LY_NHA, ROLE_KE_TOAN], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Khong co quyen thuc hien thao tac nay.']);
    exit();
}

// --- Method check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Chi chap nhan POST.']);
    exit();
}

// --- CSRF Validation ---
// DEBUG: Log để xác nhận token nhận được khớp với session
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu CSRF token. Vui lòng tải lại trang.']);
    exit();
}
if (!isset($_SESSION['csrf_token'])) {
    // Session có user_id nhưng không có csrf_token — có thể session mới re-generate
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Session chưa có CSRF token. Vui lòng tải lại trang lập hợp đồng.']);
    exit();
}
if (!validateCSRFToken($token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'CSRF token không hợp lệ. Vui lòng tải lại trang.']);
    exit();
}

// --- Input ---
$action    = $_POST['action'] ?? '';
$maPhong   = trim($_POST['maPhong'] ?? '');
$sessionId = session_id();

if (empty($maPhong) || strlen($maPhong) > 50) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ma phong khong hop le.']);
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Don dep lock het han truoc moi request
    $pdo->prepare("DELETE FROM PHONG_LOCK WHERE expire_at < NOW()")->execute();

    if ($action === 'lock') {

        // Atomic upsert: UNIQUE(maPhong) dam bao 1 phong chi co 1 lock.
        // Chi cap nhat expire_at neu:
        //   - session_id trung (chinh minh gia han) HOAC
        //   - lock da het han (expire_at < NOW)
        // Neu nguoi khac dang giu va chua het han -> giu nguyen row cu (khong cap nhat).
        $lockId = 'LK-' . $maPhong . '-' . substr($sessionId, 0, 8);
        $stmtUpsert = $pdo->prepare("
            INSERT INTO PHONG_LOCK (id, maPhong, session_id, locked_at, expire_at)
            VALUES (:id, :phong, :sess, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ON DUPLICATE KEY UPDATE
                session_id = IF(expire_at < NOW() OR session_id = VALUES(session_id), VALUES(session_id), session_id),
                expire_at  = IF(expire_at < NOW() OR session_id = VALUES(session_id), VALUES(expire_at), expire_at),
                locked_at  = IF(expire_at < NOW() OR session_id = VALUES(session_id), NOW(), locked_at)
        ");
        $stmtUpsert->execute([
            ':id'    => $lockId,
            ':phong' => $maPhong,
            ':sess'  => $sessionId
        ]);

        // Verify ownership: ai la chu lock sau upsert?
        $stmtVerify = $pdo->prepare("SELECT session_id FROM PHONG_LOCK WHERE maPhong = ?");
        $stmtVerify->execute([$maPhong]);
        $owner = $stmtVerify->fetchColumn();

        if ($owner === $sessionId) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Da giu phong ' . htmlspecialchars($maPhong, ENT_QUOTES, 'UTF-8') . ' trong 10 phut.'
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Phong dang duoc nhan vien khac giu. Vui long chon phong khac.'
            ]);
        }
        exit();

    } elseif ($action === 'unlock') {

        // Chi xoa lock cua chinh session nay
        $stmtUnlock = $pdo->prepare("DELETE FROM PHONG_LOCK WHERE maPhong = ? AND session_id = ?");
        $stmtUnlock->execute([$maPhong, $sessionId]);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Da giai phong phong ' . htmlspecialchars($maPhong, ENT_QUOTES, 'UTF-8') . '.'
        ]);
        exit();

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Action khong hop le. Chi chap nhan: lock, unlock.']);
        exit();
    }

} catch (PDOException $e) {
    error_log("[phong_lock.php] PDO error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Loi he thong. Vui long thu lai.']);
    exit();
}
