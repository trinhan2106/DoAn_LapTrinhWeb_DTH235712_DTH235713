<?php
/**
 * modules/dashboard/tim_kiem.php
 * =====================================================================
 * MODULE TÌM KIẾM TOÀN HỆ THỐNG — Quản lý Cao ốc
 * =====================================================================
 * Chiến lược truy vấn: UNION MATCH...AGAINST trên 3 bảng có FULLTEXT INDEX:
 *   - KHACH_HANG  → ft_khach_hang_search(tenKH)
 *   - HOP_DONG    → ft_hop_dong_search(soHopDong)
 *   - PHONG       → ft_phong_search(maPhong, tenPhong)
 *
 * Bảo mật:
 *   - kiemTraSession()       : xác thực phiên đăng nhập
 *   - PDO Prepared Statement : chống SQL Injection tuyệt đối
 *   - e() / htmlspecialchars : chống XSS tại tầng View
 *   - deleted_at IS NULL     : loại trừ dữ liệu đã xóa mềm
 * =====================================================================
 */

// ── 1. BOOTSTRAP: Nạp dependencies & kiểm tra phiên ─────────────────
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/functions.php';

kiemTraSession(); // P0 – Blocker: redirect về dangnhap.php nếu chưa đăng nhập

// ── 2. XỬ LÝ THAM SỐ & PHÂN TRANG ──────────────────────────────────
$keyword    = trim($_GET['s'] ?? '');           // Từ khóa tìm kiếm
$pageSize   = 20;                               // Số bản ghi mỗi trang
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $pageSize;

// Mảng kết quả và số tổng
$results    = [];
$totalCount = 0;
$errorMsg   = '';

/**
 * highlight()
 * Bao quanh từ khóa bằng thẻ <mark> có màu accent gold để highlight
 * trên giao diện kết quả. An toàn XSS: escape chuỗi gốc trước,
 * rồi mới wrap tag HTML.
 */
function highlightKeyword(string $text, string $keyword): string
{
    if ($keyword === '') {
        return e($text);
    }
    // Escape cả text lẫn keyword để tránh XSS, sau đó dùng preg_replace
    $safeText    = e($text);
    $safeKeyword = preg_quote(e($keyword), '/');
    return preg_replace(
        '/(' . $safeKeyword . ')/iu',
        '<mark class="search-highlight">$1</mark>',
        $safeText
    );
}

// ── 3. THỰC HIỆN TÌM KIẾM (Chỉ khi keyword không rỗng) ──────────────
if ($keyword !== '') {
    try {
        $pdo = Database::getInstance()->getConnection();

        // ── 3a. Xử lý từ khóa để tránh lỗi toán tử BOOLEAN MODE
        $cleanKeyword = preg_replace('/[+\-><()~*\"@]/', ' ', $keyword);
        $words = array_filter(explode(' ', $cleanKeyword));
        $searchTerm = '';
        foreach ($words as $word) {
            if (mb_strlen($word) >= 1) {
                $searchTerm .= '+' . $word . '* ';
            }
        }
        $searchTerm = trim($searchTerm);
        $likeTerm   = '%' . $keyword . '%';

        // ── 3b. Đếm tổng số kết quả (cho phân trang) ─────────────────
        $sqlCount = "
            SELECT COUNT(*) AS total FROM (
                SELECT kh.maKH FROM KHACH_HANG kh
                WHERE (MATCH(kh.tenKH) AGAINST (? IN BOOLEAN MODE) OR kh.maKH LIKE ? OR kh.tenKH LIKE ?)
                  AND kh.deleted_at IS NULL
                UNION ALL
                SELECT hd.soHopDong FROM HOP_DONG hd
                WHERE (MATCH(hd.soHopDong) AGAINST (? IN BOOLEAN MODE) OR hd.soHopDong LIKE ?)
                  AND hd.deleted_at IS NULL
                UNION ALL
                SELECT p.maPhong FROM PHONG p
                WHERE (MATCH(p.maPhong, p.tenPhong) AGAINST (? IN BOOLEAN MODE) OR p.maPhong LIKE ?)
                  AND p.deleted_at IS NULL
            ) AS combined_count
        ";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute([$searchTerm, $likeTerm, $likeTerm, $searchTerm, $likeTerm, $searchTerm, $likeTerm]);
        $totalCount = (int)$stmtCount->fetchColumn();

        // ── 3c. Truy vấn dữ liệu trang hiện tại ─────────────────────
        $sqlUnion = "
            -- Nhóm 1: Khách Hàng (4 dấu ?)
            SELECT 'KhachHang' AS loai_ket_qua, kh.tenKH AS tieu_de,
                CONCAT('Mã KH: ', kh.maKH, ' | SDT: ', IFNULL(kh.sdt,'—')) AS mo_ta_phu,
                CONCAT('modules/khach_hang/kh_lichsu.php?id=', kh.maKH) AS url_lien_ket,
                MATCH(kh.tenKH) AGAINST (? IN BOOLEAN MODE) AS relevance
            FROM KHACH_HANG kh
            WHERE (MATCH(kh.tenKH) AGAINST (? IN BOOLEAN MODE) OR kh.maKH LIKE ? OR kh.tenKH LIKE ?)
              AND kh.deleted_at IS NULL

            UNION ALL

            -- Nhóm 2: Hợp Đồng (3 dấu ?)
            SELECT 'HopDong' AS loai_ket_qua, hd.soHopDong AS tieu_de,
                CONCAT('Ngày BĐ: ', IFNULL(DATE_FORMAT(hd.ngayBatDau,'%d/%m/%Y'),'—')) AS mo_ta_phu,
                CONCAT('modules/hop_dong/hd_chitiet.php?id=', hd.soHopDong) AS url_lien_ket,
                MATCH(hd.soHopDong) AGAINST (? IN BOOLEAN MODE) AS relevance
            FROM HOP_DONG hd
            WHERE (MATCH(hd.soHopDong) AGAINST (? IN BOOLEAN MODE) OR hd.soHopDong LIKE ?)
              AND hd.deleted_at IS NULL

            UNION ALL

            -- Nhóm 3: Phòng (3 dấu ?)
            SELECT 'Phong' AS loai_ket_qua, p.maPhong AS tieu_de,
                CONCAT('Phòng: ', p.tenPhong, ' | Giá: ', FORMAT(p.giaThue,0)) AS mo_ta_phu,
                CONCAT('modules/phong/phong_sua.php?id=', p.maPhong) AS url_lien_ket,
                MATCH(p.maPhong, p.tenPhong) AGAINST (? IN BOOLEAN MODE) AS relevance
            FROM PHONG p
            WHERE (MATCH(p.maPhong, p.tenPhong) AGAINST (? IN BOOLEAN MODE) OR p.maPhong LIKE ?)
              AND p.deleted_at IS NULL

            ORDER BY relevance DESC, tieu_de ASC
            LIMIT ? OFFSET ?
        ";

        $stmtUnion = $pdo->prepare($sqlUnion);
        $stmtUnion->bindValue(1, $searchTerm, PDO::PARAM_STR);   // R1
        $stmtUnion->bindValue(2, $searchTerm, PDO::PARAM_STR);   // W1-MATCH
        $stmtUnion->bindValue(3, $likeTerm,   PDO::PARAM_STR);   // W1-ID
        $stmtUnion->bindValue(4, $likeTerm,   PDO::PARAM_STR);   // W1-NAME

        $stmtUnion->bindValue(5, $searchTerm, PDO::PARAM_STR);   // R2
        $stmtUnion->bindValue(6, $searchTerm, PDO::PARAM_STR);   // W2-MATCH
        $stmtUnion->bindValue(7, $likeTerm,   PDO::PARAM_STR);   // W2-LIKE

        $stmtUnion->bindValue(8, $searchTerm, PDO::PARAM_STR);   // R3
        $stmtUnion->bindValue(9, $searchTerm, PDO::PARAM_STR);   // W3-MATCH
        $stmtUnion->bindValue(10, $likeTerm,   PDO::PARAM_STR);  // W3-LIKE

        $stmtUnion->bindValue(11, (int)$pageSize, PDO::PARAM_INT);
        $stmtUnion->bindValue(12, (int)$offset,   PDO::PARAM_INT);

        $stmtUnion->execute();
        $results = $stmtUnion->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('[tim_kiem.php] PDOException: ' . $e->getMessage());
        $errorMsg = 'Hệ thống tìm kiếm gặp sự cố.';
    }
}

// ── 4. TÍNH TOÁN PHÂN TRANG ──────────────────────────────────────────
$totalPages  = ($totalCount > 0) ? (int)ceil($totalCount / $pageSize) : 1;
$page        = min($page, $totalPages); // Đảm bảo trang không vượt tổng trang

// Nhóm kết quả theo loại để render theo section
$grouped = [
    'KhachHang' => [],
    'HopDong'   => [],
    'Phong'     => [],
];
foreach ($results as $row) {
    $loai = $row['loai_ket_qua'] ?? '';
    if (array_key_exists($loai, $grouped)) {
        $grouped[$loai][] = $row;
    }
}

// ── 5. CẤU HÌNH BADGE & ICON THEO LOẠI ──────────────────────────────
$badgeConfig = [
    'KhachHang' => [
        'label'  => 'Khách hàng',
        'badge'  => 'bg-success',
        'icon'   => 'bi-person-fill',
        'header' => 'Kết quả: Khách hàng',
    ],
    'HopDong' => [
        'label'  => 'Hợp đồng',
        'badge'  => 'bg-warning text-dark',
        'icon'   => 'bi-file-earmark-text-fill',
        'header' => 'Kết quả: Hợp đồng',
    ],
    'Phong' => [
        'label'  => 'Phòng',
        'badge'  => 'bg-info text-dark',
        'icon'   => 'bi-door-open-fill',
        'header' => 'Kết quả: Phòng',
    ],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require_once __DIR__ . '/../../includes/admin/admin-header.php'; ?>
    <title>Tìm kiếm: <?= e($keyword) ?> — <?= APP_NAME ?></title>
    <meta name="description" content="Tìm kiếm toàn hệ thống Quản lý Cao ốc theo khách hàng, hợp đồng và phòng.">
    <style>
        /* ── Brand Tokens ────────────────────────────────────────── */
        :root {
            --navy:       #1e3a5f;
            --navy-dark:  #162d4a;
            --navy-light: #2a4f7c;
            --gold:       #c9a66b;
            --gold-light: #dfc08e;
            --gold-dark:  #b5925a;
        }

        /* ── Stats Bar ───────────────────────────────────────────── */
        .search-stats {
            background: rgba(30,58,95,0.06);
            border-left: 4px solid var(--gold);
            border-radius: 0 8px 8px 0;
            padding: 0.65rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: var(--navy);
            font-weight: 500;
        }

        /* ── Result Group Header ──────────────────────────────────── */
        .result-group-header {
            background: var(--navy);
            color: #fff;
            border-radius: 10px 10px 0 0;
            padding: 0.75rem 1.25rem;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .result-group-header .count-pill {
            background: var(--gold);
            color: #fff;
            border-radius: 20px;
            font-size: 0.78rem;
            padding: 0.15rem 0.65rem;
            font-weight: 700;
            margin-left: auto;
        }

        /* ── Result List Item ─────────────────────────────────────── */
        .result-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.15s;
            text-decoration: none;
            color: inherit;
        }
        .result-item:last-child { border-bottom: none; }
        .result-item:hover {
            background: #f7f9fc;
        }
        .result-item__icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .result-item__icon--KhachHang { background: #d1fae5; color: #065f46; }
        .result-item__icon--HopDong   { background: #fef9c3; color: #92400e; }
        .result-item__icon--Phong     { background: #e0f2fe; color: #075985; }

        .result-item__body { flex: 1; min-width: 0; }
        .result-item__title {
            font-weight: 700;
            color: var(--navy);
            font-size: 0.97rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .result-item__desc {
            font-size: 0.82rem;
            color: #6b7280;
            margin-top: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .result-item__arrow {
            color: #d1d5db;
            font-size: 1rem;
            align-self: center;
            transition: color 0.15s, transform 0.15s;
        }
        .result-item:hover .result-item__arrow {
            color: var(--gold);
            transform: translateX(3px);
        }

        /* ── Highlight Keyword ───────────────────────────────────── */
        mark.search-highlight {
            background: rgba(201,166,107,0.30);
            color: var(--gold-dark);
            font-weight: 700;
            padding: 0 2px;
            border-radius: 3px;
        }

        /* ── Empty State ─────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        .empty-state__icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        .empty-state__title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.5rem;
        }
        .empty-state__msg { color: #6b7280; font-size: 0.95rem; }

        /* ── Pagination ────────────────────────────────────────────  */
        .pagination .page-link {
            color: var(--navy);
            border-radius: 8px !important;
            margin: 0 2px;
            border: 1px solid #e5e7eb;
        }
        .pagination .page-item.active .page-link {
            background: var(--navy);
            border-color: var(--navy);
            color: #fff;
        }
        .pagination .page-link:hover:not(.disabled) {
            background: var(--gold);
            border-color: var(--gold);
            color: #fff;
        }

        /* ── Card Container ──────────────────────────────────────── */
        .result-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(30,58,95,0.06);
            margin-bottom: 1.5rem;
        }

        /* ── Quick Stats Chips ──────────────────────────────────── */
        .quick-stat {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 0.85rem 1.25rem;
            text-align: center;
            box-shadow: 0 1px 4px rgba(30,58,95,0.06);
        }
        .quick-stat__num  { font-size: 1.6rem; font-weight: 800; color: var(--navy); line-height: 1; }
        .quick-stat__label { font-size: 0.78rem; color: #6b7280; margin-top: 0.25rem; }
    </style>
</head>
<body class="bg-light">

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/admin/sidebar.php'; ?>

    <div class="admin-main-wrapper flex-grow-1">
        <?php require_once __DIR__ . '/../../includes/admin/topbar.php'; ?>

        <main class="admin-main-content p-4">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>modules/dashboard/admin.php" class="text-decoration-none">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="bi bi-search me-1"></i>Tìm kiếm toàn hệ thống
                    </li>
                </ol>
            </nav>

            <!-- ── SEARCH HERO ──────────────────────────────────────── -->
            <h2 class="h4 fw-bold text-navy mb-4">
                <i class="bi bi-search me-2"></i>Kết quả tìm kiếm toàn hệ thống
            </h2>

            <?php if ($errorMsg !== ''): ?>
            <!-- Lỗi hệ thống -->
            <div class="alert alert-danger d-flex align-items-center shadow-sm border-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= e($errorMsg) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($keyword !== '' && $errorMsg === ''): ?>

            <!-- ── STATS BAR ──────────────────────────────────────── -->
            <div class="search-stats">
                <?php if ($totalCount > 0): ?>
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    Tìm thấy <strong><?= number_format($totalCount) ?></strong> kết quả cho từ khóa
                    <strong class="text-gold">"<?= e($keyword) ?>"</strong>
                    <?php if ($totalPages > 1): ?>
                        — Trang <strong><?= $page ?></strong> / <strong><?= $totalPages ?></strong>
                    <?php endif; ?>
                <?php else: ?>
                    <i class="bi bi-info-circle-fill text-warning me-1"></i>
                    Không tìm thấy kết quả nào cho từ khóa <strong>"<?= e($keyword) ?>"</strong>
                <?php endif; ?>
            </div>

            <?php if ($totalCount > 0): ?>

            <!-- ── QUICK STATS CHIPS ──────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="quick-stat">
                        <div class="quick-stat__num text-success"><?= count($grouped['KhachHang']) ?></div>
                        <div class="quick-stat__label"><i class="bi bi-person me-1"></i>Khách hàng</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="quick-stat">
                        <div class="quick-stat__num" style="color:var(--gold-dark);"><?= count($grouped['HopDong']) ?></div>
                        <div class="quick-stat__label"><i class="bi bi-file-earmark-text me-1"></i>Hợp đồng</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="quick-stat">
                        <div class="quick-stat__num text-info"><?= count($grouped['Phong']) ?></div>
                        <div class="quick-stat__label"><i class="bi bi-door-open me-1"></i>Phòng</div>
                    </div>
                </div>
            </div>

            <!-- ── KẾT QUẢ THEO NHÓM ──────────────────────────────── -->
            <?php
            $sectionOrder = ['KhachHang', 'HopDong', 'Phong'];
            foreach ($sectionOrder as $loai):
                $items  = $grouped[$loai];
                if (empty($items)) continue;
                $cfg    = $badgeConfig[$loai];
            ?>
            <div class="result-card mb-4" id="section-<?= e($loai) ?>">
                <!-- Group Header -->
                <div class="result-group-header">
                    <i class="bi <?= e($cfg['icon']) ?> fs-5"></i>
                    <?= e($cfg['header']) ?>
                    <span class="count-pill"><?= count($items) ?></span>
                </div>

                <!-- Result Items -->
                <div class="bg-white">
                    <?php foreach ($items as $item):
                        $url = BASE_URL . e($item['url_lien_ket']);
                    ?>
                    <a href="<?= $url ?>"
                       class="result-item"
                       role="listitem"
                       title="<?= e($item['tieu_de']) ?>">

                        <!-- Type Icon -->
                        <div class="result-item__icon result-item__icon--<?= e($loai) ?>">
                            <i class="bi <?= e($cfg['icon']) ?>"></i>
                        </div>

                        <!-- Text Content -->
                        <div class="result-item__body">
                            <div class="result-item__title">
                                <!-- Badge phân loại -->
                                <span class="badge <?= e($cfg['badge']) ?> me-2"
                                      style="font-size:0.7rem; vertical-align: middle;">
                                    <?= e($cfg['label']) ?>
                                </span>
                                <!-- Tieu de với highlight từ khóa -->
                                <?= highlightKeyword($item['tieu_de'], $keyword) ?>
                            </div>
                            <div class="result-item__desc">
                                <?= e($item['mo_ta_phu']) ?>
                            </div>
                        </div>

                        <!-- Arrow -->
                        <div class="result-item__arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- ── PHÂN TRANG ──────────────────────────────────────── -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Phân trang kết quả tìm kiếm" class="mt-4">
                <ul class="pagination justify-content-center flex-wrap gap-1">

                    <!-- Trang trước -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="?s=<?= urlencode($keyword) ?>&page=<?= max(1, $page - 1) ?>"
                           aria-label="Trang trước">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    // Hiển thị tối đa 7 trang quanh trang hiện tại
                    $startPage = max(1, $page - 3);
                    $endPage   = min($totalPages, $page + 3);

                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?s=<?= urlencode($keyword) ?>&page=1">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                        <a class="page-link"
                           href="?s=<?= urlencode($keyword) ?>&page=<?= $p ?>"
                           <?= ($p === $page) ? 'aria-current="page"' : '' ?>>
                            <?= $p ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?s=<?= urlencode($keyword) ?>&page=<?= $totalPages ?>">
                                <?= $totalPages ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Trang sau -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="?s=<?= urlencode($keyword) ?>&page=<?= min($totalPages, $page + 1) ?>"
                           aria-label="Trang sau">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
                <p class="text-center text-muted small mt-2">
                    Hiển thị <?= ($offset + 1) ?>–<?= min($offset + $pageSize, $totalCount) ?> trong tổng số <?= number_format($totalCount) ?> kết quả
                </p>
            </nav>
            <?php endif; ?>

            <?php else: /* $totalCount === 0 */ ?>
            <!-- ── EMPTY STATE ──────────────────────────────────────── -->
            <div class="bg-white rounded-3 shadow-sm border p-0 overflow-hidden">
                <div class="empty-state">
                    <div class="empty-state__icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="empty-state__title">Không tìm thấy dữ liệu phù hợp</div>
                    <p class="empty-state__msg">
                        Không tìm thấy dữ liệu phù hợp với từ khóa
                        <strong class="text-navy">"<?= e($keyword) ?>"</strong>.<br>
                        Hãy thử từ khóa khác hoặc kiểm tra lại chính tả.
                    </p>
                    <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                        <a href="?s=" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Xóa từ khóa
                        </a>
                        <a href="<?= BASE_URL ?>modules/khach_hang/kh_hienthi.php"
                           class="btn btn-sm"
                           style="background:var(--navy);color:#fff;">
                            <i class="bi bi-people me-1"></i>Xem danh sách Khách hàng
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php elseif ($keyword === '' && $errorMsg === ''): ?>
            <!-- ── LANDING STATE (Chưa nhập từ khóa) ─────────────── -->
            <div class="bg-white rounded-3 shadow-sm border p-0 overflow-hidden">
                <div class="empty-state">
                    <div class="empty-state__icon" style="color: var(--navy); opacity: 0.2;">
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="empty-state__title">Tìm kiếm toàn hệ thống</div>
                    <p class="empty-state__msg">
                        Nhập từ khóa vào ô tìm kiếm phía trên để tìm kiếm đồng thời<br>
                        trên <strong>Khách hàng</strong>, <strong>Hợp đồng</strong> và <strong>Phòng</strong>.
                    </p>
                    <div class="row g-3 mt-3 text-start" style="max-width:560px; margin:0 auto;">
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:#d1fae5;">
                                <i class="bi bi-person-fill text-success fs-4 mb-2 d-block"></i>
                                <div class="fw-bold text-success small">Khách Hàng</div>
                                <div class="text-muted" style="font-size:0.78rem;">Tìm theo tên khách hàng</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:#fef9c3;">
                                <i class="bi bi-file-earmark-text-fill fs-4 mb-2 d-block" style="color:var(--gold-dark);"></i>
                                <div class="fw-bold small" style="color:var(--gold-dark);">Hợp Đồng</div>
                                <div class="text-muted" style="font-size:0.78rem;">Tìm theo số hợp đồng</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:#e0f2fe;">
                                <i class="bi bi-door-open-fill text-info fs-4 mb-2 d-block"></i>
                                <div class="fw-bold text-info small">Phòng</div>
                                <div class="text-muted" style="font-size:0.78rem;">Tìm theo mã / tên phòng</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>

        <?php require_once __DIR__ . '/../../includes/admin/admin-footer.php'; ?>
    </div><!-- /.admin-main-wrapper -->
</div><!-- /.admin-layout -->

<script>
/* ── Gõ Enter = Submit form ────────────────────────────────────── */
document.getElementById('search-input').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('global-search-form').submit();
    }
});

/* ── Live search indicator ─────────────────────────────────────── */
document.getElementById('global-search-form').addEventListener('submit', function () {
    const btn = document.getElementById('btn-search-submit');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Đang tìm…';
    btn.disabled = true;
});

/* ── Scroll to section khi click vào chip ──────────────────────── */
document.querySelectorAll('[data-scroll-to]').forEach(function (el) {
    el.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.getElementById(el.getAttribute('data-scroll-to'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

</body>
</html>
