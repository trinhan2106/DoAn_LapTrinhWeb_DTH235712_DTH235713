<?php
/**
 * modules/export/xuat_pdf.php
 * Tính năng xuất PDF cho Hợp đồng và Hóa đơn.
 * Sử dụng thư viện html2pdf.js để render từ HTML.
 */

// 1. KHỞI TẠO & BẢO MẬT (P0)
require_once __DIR__ . '/../../includes/common/auth.php';
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/functions.php';

// Xác thực session và quyền truy cập
kiemTraSession();
kiemTraRole([ROLE_ADMIN, ROLE_QUAN_LY_NHA, ROLE_KE_TOAN, ROLE_KHACH_HANG]);

$pdo = Database::getInstance()->getConnection();
$currentUserRole = (int)$_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Lấy tham số yêu cầu
$type = $_GET['type'] ?? ''; // 'contract' hoặc 'invoice'
$id = $_GET['id'] ?? '';     // soHopDong hoặc soHoaDon

// 1.1 XỬ LÝ KHI THIẾU THAM SỐ (HƯỚNG DẪN NGƯỜI DÙNG)
if (!$type || !$id): ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xuất PDF - Hướng dẫn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .instruction-card { max-width: 500px; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(30, 58, 95, 0.1); border-top: 5px solid #c9a66b; }
        .icon-box { width: 80px; height: 80px; background: rgba(201, 166, 107, 0.1); color: #c9a66b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; }
        .btn-home { background: #1e3a5f; color: #fff; border-radius: 50px; padding: 10px 30px; font-weight: 600; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn-home:hover { opacity: 0.9; color: #fff; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="instruction-card text-center">
        <div class="icon-box"><i class="bi bi-file-earmark-pdf"></i></div>
        <h4 class="fw-bold text-navy mb-3">Trung tâm Xuất Bản In (PDF)</h4>
        <p class="text-muted mb-4">Bạn vừa truy cập trực tiếp vào hệ thống render PDF. Để xuất tài liệu, vui lòng sử dụng nút <span class="text-warning fw-bold">"Xuất PDF"</span> có trong mục Hợp đồng hoặc Hóa đơn.</p>
        <div class="alert alert-light text-start small border">
            <i class="bi bi-info-circle me-2"></i> <strong>Cấu trúc URL hợp lệ:</strong><br>
            <code>?type=contract&id=[SoHopDong]</code><br>
            <code>?type=invoice&id=[SoHoaDon]</code>
        </div>
        <a href="../../index.php" class="btn-home mt-3"><i class="bi bi-house me-2"></i> Quay lại trang chủ</a>
    </div>
</body>
</html>
<?php exit; endif; ?>

<?php
// Chống IDOR (Cực kỳ quan trọng)
if ($currentUserRole === ROLE_KHACH_HANG) {
    if ($type === 'contract') {
        $stmtCheck = $pdo->prepare("SELECT 1 FROM HOP_DONG WHERE soHopDong = ? AND maKH = ? AND deleted_at IS NULL");
        $stmtCheck->execute([$id, $currentUserId]);
        if (!$stmtCheck->fetch()) {
            http_response_code(403);
            die("403 Forbidden: Bạn không có quyền truy cập hợp đồng này.");
        }
    } elseif ($type === 'invoice') {
        $stmtCheck = $pdo->prepare("SELECT 1 FROM HOA_DON hd JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong WHERE hd.soHoaDon = ? AND h.maKH = ? AND hd.deleted_at IS NULL");
        $stmtCheck->execute([$id, $currentUserId]);
        if (!$stmtCheck->fetch()) {
            http_response_code(403);
            die("403 Forbidden: Bạn không có quyền truy cập hóa đơn này.");
        }
    } else {
        die("Loại tài liệu không hợp lệ.");
    }
}

// 2. TRUY VẤN DỮ LIỆU
$data = null;
$items = [];

if ($type === 'contract') {
    // Truy vấn Hợp đồng (JOIN HOP_DONG, KHACH_HANG, CHI_TIET_HOP_DONG, PHONG)
    $stmt = $pdo->prepare("
        SELECT hd.*, kh.tenKH, kh.sdt, kh.email, kh.diaChi as kh_diaChi
        FROM HOP_DONG hd
        JOIN KHACH_HANG kh ON hd.maKH = kh.maKH
        WHERE hd.soHopDong = ? AND hd.deleted_at IS NULL
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $stmtItems = $pdo->prepare("
            SELECT cthd.*, p.tenPhong, p.dienTich, p.donGiaM2
            FROM CHI_TIET_HOP_DONG cthd
            JOIN PHONG p ON cthd.maPhong = p.maPhong
            WHERE cthd.soHopDong = ?
        ");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }
    $fileName = "HopDong_" . $id . "_" . date('Ymd') . ".pdf";
    $title = "HỢP ĐỒNG THUÊ VĂN PHÒNG";

} elseif ($type === 'invoice') {
    // Truy vấn Hóa đơn (JOIN HOA_DON, HOP_DONG, KHACH_HANG)
    $stmt = $pdo->prepare("
        SELECT hd.*, h.soHopDong, kh.tenKH, kh.sdt, kh.diaChi as kh_diaChi
        FROM HOA_DON hd
        JOIN HOP_DONG h ON hd.soHopDong = h.soHopDong
        JOIN KHACH_HANG kh ON h.maKH = kh.maKH
        WHERE hd.soHoaDon = ? AND hd.deleted_at IS NULL
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $fileName = "HoaDon_" . $id . "_" . date('Ymd') . ".pdf";
    $title = "HÓA ĐƠN THANH TOÁN";
}

if (!$data) {
    die("Không tìm thấy dữ liệu yêu cầu.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xuất PDF - <?= e($id) ?></title>
    <!-- Bootstrap 5 cho layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts: Inter cho font chữ hiện đại, hỗ trợ tiếng Việt tốt -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-navy: #1e3a5f;
            --accent-gold: #c9a66b;
            --text-dark: #333;
            --border-light: #dee2e6;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            color: var(--text-dark);
        }

        /* Preview Container */
        .preview-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px;
            position: relative;
        }

        /* PDF Content Styling */
        #pdf-content {
            background: #fff;
            color: #000;
            line-height: 1.6;
        }

        .pdf-header {
            border-bottom: 2px solid var(--primary-navy);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .co-logo {
            font-weight: 700;
            font-size: 24px;
            color: var(--primary-navy);
        }
        .co-logo span {
            color: var(--accent-gold);
        }

        .pdf-title {
            text-align: center;
            font-weight: 700;
            font-size: 20px;
            color: var(--primary-navy);
            margin: 30px 0;
            text-transform: uppercase;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-label {
            font-weight: 600;
            width: 150px;
            display: inline-block;
        }

        /* Table Styling */
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .pdf-table th {
            background-color: var(--primary-navy);
            color: #fff;
            text-align: left;
            padding: 10px;
            font-size: 14px;
        }

        .pdf-table td {
            border: 1px solid var(--border-light);
            padding: 10px;
            font-size: 14px;
        }

        .pdf-footer {
            margin-top: 50px;
            border-top: 1px solid var(--border-light);
            padding-top: 20px;
            font-style: italic;
            font-size: 12px;
            color: #666;
        }

        .signature-block {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .signature-item {
            text-align: center;
            width: 200px;
        }

        .signature-space {
            height: 100px;
        }

        /* Controls */
        .controls {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }

        .btn-export {
            background-color: var(--accent-gold);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(201, 166, 107, 0.4);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(201, 166, 107, 0.6);
            color: #fff;
        }

        /* Loading Spinner */
        #loading-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .spinner-gold {
            width: 3rem;
            height: 3rem;
            border-color: var(--accent-gold) transparent transparent transparent;
        }

        @media print {
            .controls, #loading-overlay {
                display: none !important;
            }
            .preview-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>

    <div class="controls">
        <button id="btn-do-export" class="btn btn-export">
            <i class="bi bi-file-earmark-pdf"></i> Xuất PDF
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-x-lg"></i> Đóng
        </button>
    </div>

    <div id="loading-overlay">
        <div class="text-center">
            <div class="spinner-border spinner-gold" role="status"></div>
            <p class="mt-3 fw-bold text-navy">Đang khởi tạo bản in...</p>
        </div>
    </div>

    <div class="preview-container">
        <div id="pdf-content">
            <!-- Header -->
            <div class="pdf-header d-flex justify-content-between align-items-center">
                <div class="co-logo">
                    THE <span>SAPPHIRE</span> TOWER
                </div>
                <div class="text-end small">
                    <p class="mb-0 fw-bold">CÔNG TY QUẢN LÝ BẤT ĐỘNG SẢN SAPPHIRE</p>
                    <p class="mb-0">123 Đường Sắc Lam, Quận Sapphire, TP. HCM</p>
                    <p class="mb-0">Hotline: 1900 8888 | Email: contact@sapphire.vn</p>
                </div>
            </div>

            <div class="pdf-title"><?= e($title) ?></div>

            <!-- Body Contents -->
            <div class="row g-4">
                <div class="col-6">
                    <div class="info-section">
                        <p class="mb-1"><span class="info-label">Đối tượng:</span> <?= e($data['tenKH']) ?></p>
                        <p class="mb-1"><span class="info-label">Điện thoại:</span> <?= e($data['sdt']) ?></p>
                        <p class="mb-1"><span class="info-label">Địa chỉ:</span> <?= e($data['kh_diaChi']) ?></p>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <div class="info-section">
                        <?php if ($type === 'contract'): ?>
                            <p class="mb-1"><span class="info-label">Số Hợp đồng:</span> <?= e($data['soHopDong']) ?></p>
                            <p class="mb-1"><span class="info-label">Ngày ký:</span> <?= date('d/m/Y', strtotime($data['ngayLap'])) ?></p>
                            <p class="mb-1"><span class="info-label">Ngày hết hạn:</span> <?= $data['ngayHetHanCuoiCung'] ? date('d/m/Y', strtotime($data['ngayHetHanCuoiCung'])) : 'N/A' ?></p>
                        <?php else: ?>
                            <p class="mb-1"><span class="info-label">Số Hóa đơn:</span> <?= e($data['soHoaDon']) ?></p>
                            <p class="mb-1"><span class="info-label">Kỳ thanh toán:</span> <?= e($data['kyThanhToan']) ?></p>
                            <p class="mb-1"><span class="info-label">Ngày lập:</span> <?= date('d/m/Y H:i', strtotime($data['ngayLap'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Table Data -->
            <?php if ($type === 'contract'): ?>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên Phòng</th>
                            <th class="text-end">Diện tích</th>
                            <th class="text-end">Đơn giá/m²</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                            <tr>
                                <td class="text-center"><?= $index + 1 ?></td>
                                <td><?= e($item['tenPhong']) ?></td>
                                <td class="text-end"><?= number_format($item['dienTich'], 1, ',', '.') ?> m²</td>
                                <td class="text-end"><?= number_format($item['donGiaM2'], 0, ',', '.') ?> VNĐ</td>
                                <td class="text-end fw-bold"><?= number_format($item['giaThue'], 0, ',', '.') ?> VNĐ</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Tổng tiền thuê tháng:</td>
                            <td class="text-end fw-bold text-primary" style="font-size: 16px;">
                                <?= number_format(array_sum(array_column($items, 'giaThue')), 0, ',', '.') ?> VNĐ
                            </td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>Nội dung thanh toán</th>
                            <th class="text-end">Số tiền (VNĐ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Tiền thuê văn phòng kỳ <?= e($data['kyThanhToan']) ?> (HĐ <?= e($data['soHopDong']) ?>)</td>
                            <td class="text-end"><?= number_format($data['tongTien'], 0, ',', '.') ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="text-end fw-bold">Đã nộp:</td>
                            <td class="text-end"><?= number_format($data['soTienDaNop'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">Còn nợ:</td>
                            <td class="text-end text-danger fw-bold"><?= number_format($data['soTienConNo'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold">TRẠNG THÁI:</td>
                            <td class="text-end fw-bold">
                                <?php 
                                    $st = $data['trangThai'];
                                    echo $st === 'DaThu' ? 'ĐÃ THANH TOÁN' : ($st === 'ConNo' ? 'CHƯA THANH TOÁN' : 'THANH TOÁN MỘT PHẦN');
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>

            <div class="signature-block">
                <div class="signature-item">
                    <p class="fw-bold mb-0">Đại diện Khách hàng</p>
                    <p class="small">(Ký và ghi rõ họ tên)</p>
                    <div class="signature-space"></div>
                </div>
                <div class="signature-item">
                    <p class="fw-bold mb-0">Người lập phiếu</p>
                    <p class="small">(Ký và xác nhận)</p>
                    <div class="signature-space"></div>
                </div>
                <div class="signature-item">
                    <p class="fw-bold mb-0">Giám đốc Tòa nhà</p>
                    <p class="small">(Đóng dấu & ký tên)</p>
                    <div class="signature-space"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="pdf-footer">
                Dữ liệu trích xuất từ hệ thống quản lý cao ốc THE SAPPHIRE. <br>
                Mọi thắc mắc vui lòng liên hệ bộ phận Kế toán của tòa nhà. <br>
                Ngày in: <?= date('d/m/Y H:i:s') ?>
            </div>
        </div>
    </div>

    <!-- html2pdf Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script>
        document.getElementById('btn-do-export').addEventListener('click', function() {
            const overlay = document.getElementById('loading-overlay');
            const element = document.getElementById('pdf-content');
            
            overlay.style.display = 'flex';

            const opt = {
                margin:       10,
                filename:     '<?= $fileName ?>',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Thực hiện render
            html2pdf().set(opt).from(element).save().then(() => {
                overlay.style.display = 'none';
                alert('Tài liệu đã được xuất thành công!');
                // Tự động đóng nếu muốn
                // window.close();
            }).catch(err => {
                console.error(err);
                overlay.style.display = 'none';
                alert('Có lỗi xảy ra khi tạo PDF. Vui lòng thử lại.');
            });
        });
    </script>
</body>
</html>
