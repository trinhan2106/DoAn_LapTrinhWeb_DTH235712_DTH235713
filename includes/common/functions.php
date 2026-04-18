<?php
/**
 * includes/common/functions.php
 * ==================================================================
 * Các hàm helper dùng chung toàn hệ thống:
 *   - ghiAuditLog()     : ghi vết thao tác quan trọng vào bảng AUDIT_LOG
 *   - formatTien()      : định dạng tiền VNĐ
 *   - e()               : escape HTML (shortcut htmlspecialchars)
 *   - sinhMaNgauNhien() : sinh mã ngẫu nhiên an toàn (chống trùng)
 *   - layIP()           : lấy IP thật của client (xử lý proxy)
 * ==================================================================
 */

/**
 * Ghi một dòng vào bảng AUDIT_LOG. Gọi hàm này SAU mỗi thao tác nghiệp vụ
 * quan trọng (INSERT/UPDATE HOP_DONG, thanh toán, void, hủy, ký, gia hạn...)
 * theo checklist trong PhanChiaCongViec.md.
 *
 * Lưu ý: hàm này tự try/catch để không làm gãy luồng chính nếu
 * bảng AUDIT_LOG gặp sự cố. Nên gọi SAU $pdo->commit() (không nằm trong
 * transaction nghiệp vụ) để tránh rollback log khi nghiệp vụ đã thành công.
 *
 * @param PDO         $pdo           Kết nối PDO hiện hành
 * @param string|null $maNguoiDung   maNV hoặc maKH của người thao tác (session user_id)
 * @param string      $hanhDong      Tên hành động, ví dụ: 'CREATE_HD', 'VOID_INVOICE'
 * @param string      $bangBiTacDong Tên bảng DB bị ảnh hưởng, ví dụ: 'HOP_DONG'
 * @param string|null $recordId      Khóa chính bản ghi bị tác động (soHopDong, soHoaDon...)
 * @param string|null $chiTiet       Mô tả thêm, giá trị cũ -> giá trị mới, lý do...
 * @return bool True nếu ghi thành công, false nếu lỗi (đã log vào error_log)
 */
function ghiAuditLog(
    PDO $pdo,
    ?string $maNguoiDung,
    string $hanhDong,
    string $bangBiTacDong,
    ?string $recordId = null,
    ?string $chiTiet = null
): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO AUDIT_LOG (maNguoiDung, hanhDong, bangBiTacDong, recordId, chiTiet, thoiGian)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $maNguoiDung,
            $hanhDong,
            $bangBiTacDong,
            $recordId,
            $chiTiet,
        ]);
        return true;
    } catch (PDOException $e) {
        // Không throw - audit log lỗi không được làm gãy nghiệp vụ chính
        error_log("[AUDIT_LOG FAIL] action={$hanhDong}, table={$bangBiTacDong}, record={$recordId}: " . $e->getMessage());
        return false;
    }
}

/**
 * Định dạng số tiền VNĐ: 1500000 -> "1.500.000"
 */
function formatTien($soTien): string
{
    return number_format((float)$soTien, 0, ',', '.');
}

/**
 * Shortcut htmlspecialchars - dùng ở tầng View để tránh XSS.
 *   <?= e($tenKH) ?>
 */
function e($chuoi): string
{
    return htmlspecialchars((string)$chuoi, ENT_QUOTES, 'UTF-8');
}

/**
 * Sinh mã ngẫu nhiên an toàn (dùng random_int - CSPRNG - thay str_shuffle).
 * Có prefix và độ dài tùy chỉnh. Luôn là A-Z0-9.
 *
 * @param string $prefix  Tiền tố, ví dụ: 'HD-2026-'
 * @param int    $doDai   Số ký tự ngẫu nhiên sau prefix
 * @return string
 */
function sinhMaNgauNhien(string $prefix = '', int $doDai = 8): string
{
    $kyTu = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $max = strlen($kyTu) - 1;
    $chuoi = '';
    for ($i = 0; $i < $doDai; $i++) {
        // random_int là CSPRNG, an toàn cryptography
        $chuoi .= $kyTu[random_int(0, $max)];
    }
    return $prefix . $chuoi;
}

/**
 * Lấy IP thật của client, xử lý trường hợp sau Proxy/Load Balancer.
 * Ưu tiên X-Forwarded-For, sau đó REMOTE_ADDR, fallback 0.0.0.0.
 */
function layIP(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For có thể chứa nhiều IP, lấy IP đầu tiên (client gốc)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
