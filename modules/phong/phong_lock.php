<?php
// modules/phong/phong_lock.php
/**
 * AJAX endpoint: Lock/Unlock phong tam thoi khi tao hop dong.
 * - Bat buoc authentication + CSRF.
 * - Atomic upsert bang INSERT ... ON DUPLICATE KEY UPDATE de chong race condition.
 * - Sau upsert, SELECT verify ownership.
 * - Khong lo thong tin DB ra JSON response.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/roles.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/csrf.php';
require_once __DIR__ . '/../../includes/common/auth.php';

// Content-Type JSON cho AJAX endpoint
header('Content-Type: application/json; charset=utf-8');

// Authentication: phai dang nhap
try {
    kiemTraSession();
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Chua dang nhap hoac phien het han.']);
    exit();
}

// Role check: chi staff (Admin, QLN, Ke Toan) duoc lock phong
$userRole = (int)($_SESSION['user_role'] ?? 0);
if (!in_array($userRole, [ROLE_ADMIN, ROLE_QUAN_LY_NHA, ROLE_KE_TOAN], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Ban khong co quyen thuc hien thao tac nay.']);
    exit();
}

// Kiem tra phuong thuc HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Chi chap nhan phuong thuc POST.']);
    exit();
}

// Validate CSRF: doc tu header X-CSRF-Token hoac POST field csrf_token
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!$csrf || !validateCSRFToken($csrf)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'CSRF token khong hop le. Vui long tai lai trang.']);
    exit();
}

// Doc input
$action   = $_POST['action'] ?? '';
$maPhong  = trim($_POST['maPhong'] ?? '');
$sessionId = session_id();

if (empty($maPhong)) {
    echo json_encode(['status' => 'error', 'message' => 'Thieu tham so ma phong.']);
    exit();
}

// Validate do dai
if (strlen($maPhong) > 50) {
    echo json_encode(['status' => 'error', 'message' => 'Ma phong khong hop le.']);
    exit();
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Xoa cac lock da het han truoc khi xu ly (don dep)
    $pdo->prepare("DELETE FROM PHONG_LOCK WHERE expire_at < NOW()")->execute();

    if ($action === 'lock') {

        // Atomic upsert: INSERT ... ON DUPLICATE KEY UPDATE
        // Dieu kien: chi cap nhat neu lock da het han HOAC chinh minh dang giu
        // Can UNIQUE index tren maPhong. Neu chua co, dung transaction + FOR UPDATE thay the.
        $pdo->beginTransaction();

        // Lock row hien tai (neu co) de tranh race condition
        $stmtCheck = $pdo->prepare("
            SELECT id, session_id, expire_at 
            FROM PHONG_LOCK 
            WHERE maPhong = ? 
            FOR UPDATE
        ");
        $stmtCheck->execute([$maPhong]);
        $lockData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($lockData) {
            $isExpired = strtotime($lockData['expire_at']) <= time();
            $isOwner   = $lockData['session_id'] === $sessionId;

            if (!$isExpired && !$isOwner) {
                // Phong dang bi nguoi khac giu va chua het han -> reject
                $pdo->rollBack();
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Phong nay dang duoc nhan vien khac giu de tao hop dong. Lock con hieu luc. Vui long chon phong khac.'
                ]);
                exit();
            }

            // Lock het han hoac chinh minh giu -> cap nhat lai
            $stmtUpdate = $pdo->prepare("
                UPDATE PHONG_LOCK 
                SET session_id = ?, expire_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), locked_at = NOW()
                WHERE maPhong = ?
            ");
            $stmtUpdate->execute([$sessionId, $maPhong]);
        } else {
            // Chua co lock -> tao moi
            $lockId = 'LOCK-' . $maPhong . '-' . substr(session_id(), 0, 8);
            $stmtInsert = $pdo->prepare("
                INSERT INTO PHONG_LOCK (id, maPhong, session_id, locked_at, expire_at) 
                VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");
            $stmtInsert->execute([$lockId, $maPhong, $sessionId]);
        }

        // Verify ownership sau khi upsert
        $stmtVerify = $pdo->prepare("SELECT session_id FROM PHONG_LOCK WHERE maPhong = ?");
        $stmtVerify->execute([$maPhong]);
        $currentOwner = $stmtVerify->fetchColumn();

        if ($currentOwner !== $sessionId) {
            $pdo->rollBack();
            echo json_encode([
                'status'  => 'error',
                'message' => 'Khong the giu phong. Phong da bi chiem boi phien khac.'
            ]);
            exit();
        }

        $pdo->commit();

        echo json_encode([
            'status'  => 'success',
            'message' => 'Da giu phong ' . htmlspecialchars($maPhong, ENT_QUOTES, 'UTF-8') . ' trong 10 phut.'
        ]);
        exit();

    } elseif ($action === 'unlock') {
        // Chi xoa lock cua chinh session nay (tranh nguoi khac go lock cua minh)
        $stmtUnlock = $pdo->prepare("DELETE FROM PHONG_LOCK WHERE maPhong = ? AND session_id = ?");
        $stmtUnlock->execute([$maPhong, $sessionId]);
        
        echo json_encode([
            'status'  => 'success',
            'message' => 'Da giai phong phong ' . htmlspecialchars($maPhong, ENT_QUOTES, 'UTF-8') . '.'
        ]);
        exit();

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Action khong hop le. Chi chap nhan: lock, unlock.']);
        exit();
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Ghi log chi tiet, tra ve message generic cho client
    error_log("[phong_lock.php] PDO error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Loi he thong khi xu ly lock phong. Vui long thu lai.']);
    exit();
}
