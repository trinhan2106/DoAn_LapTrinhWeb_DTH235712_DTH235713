<?php
require_once __DIR__ . '/../../includes/common/db.php';
require_once __DIR__ . '/../../includes/common/auth.php';
kiemTraSession();
kiemTraRole([1, 2]); // Chỉ Role 1: Admin và Role 2: Quản lý nhà

$type = $_GET['type'] ?? '';
$pdo = Database::getInstance()->getConnection();

$filename = "Bao_Cao_" . $type . "_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
// Bắt buộc in BOM để Excel nhận diện UTF-8 Tiếng Việt
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

switch ($type) {
    case 'phong_trong':
        fputcsv($output, ['Cao Ốc', 'Tầng', 'Mã Phòng', 'Tên Phòng', 'Diện Tích (m2)', 'Giá Thuê (VNĐ)']);
        // Đã sửa t.soTang thành t.tenTang để khớp với Schema
        $stmt = $pdo->query("SELECT co.tenCaoOc, t.tenTang, p.maPhong, p.tenPhong, p.dienTich, ROUND(p.donGiaM2 * p.dienTich * t.heSoGia, 0) as giaThue FROM PHONG p JOIN TANG t ON p.maTang = t.maTang JOIN CAO_OC co ON t.maCaoOc = co.maCaoOc WHERE p.trangThai = 1 AND p.deleted_at IS NULL ORDER BY co.maCaoOc, t.tenTang, p.maPhong");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['tenCaoOc'], $row['tenTang'], $row['maPhong'], $row['tenPhong'], $row['dienTich'], number_format($row['giaThue'], 0, ',', '.')]);
        }
        break;

    case 'phong_thue':
        fputcsv($output, ['Cao Ốc', 'Tầng', 'Mã Phòng', 'Tên Phòng', 'Diện Tích (m2)']);
        $stmt = $pdo->query("SELECT co.tenCaoOc, t.tenTang, p.maPhong, p.tenPhong, p.dienTich FROM PHONG p JOIN TANG t ON p.maTang = t.maTang JOIN CAO_OC co ON t.maCaoOc = co.maCaoOc WHERE p.trangThai = 2 AND p.deleted_at IS NULL ORDER BY co.maCaoOc, t.tenTang, p.maPhong");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['tenCaoOc'], $row['tenTang'], $row['maPhong'], $row['tenPhong'], $row['dienTich']]);
        }
        break;

    case 'hd_hethan':
        fputcsv($output, ['Số HĐ', 'Tên Khách Hàng', 'SĐT', 'Ngày Hết Hạn']);
        $stmt = $pdo->query("SELECT hd.soHopDong, kh.tenKH, kh.sdt, hd.ngayHetHanCuoiCung FROM HOP_DONG hd JOIN KHACH_HANG kh ON hd.maKH = kh.maKH WHERE hd.trangThai = 1 AND hd.deleted_at IS NULL AND MONTH(hd.ngayHetHanCuoiCung) = MONTH(CURRENT_DATE()) AND YEAR(hd.ngayHetHanCuoiCung) = YEAR(CURRENT_DATE()) ORDER BY hd.ngayHetHanCuoiCung ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['soHopDong'], $row['tenKH'], $row['sdt'], date('d/m/Y', strtotime($row['ngayHetHanCuoiCung']))]);
        }
        break;

    case 'nhan_su':
        fputcsv($output, ['Mã NV', 'Họ Tên', 'Chức Vụ', 'Số Điện Thoại', 'Email']);
        $stmt = $pdo->query("SELECT maNV, tenNV, chucVu, sdt, email FROM NHAN_VIEN WHERE deleted_at IS NULL ORDER BY role_id ASC, tenNV ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['maNV'], $row['tenNV'], $row['chucVu'], $row['sdt'], $row['email']]);
        }
        break;
}

fclose($output);
exit();
?>
