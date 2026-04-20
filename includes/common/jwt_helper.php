<?php
/**
 * includes/common/jwt_helper.php
 * ==================================================================
 * Thư viện xử lý JSON Web Token (JWT) tùy chỉnh dành cho Task 9.2.
 * Hỗ trợ thuật toán: HS256 (HMAC-SHA256)
 * Tuân thủ chuẩn RFC 7519 với Base64Url Encoding.
 * ==================================================================
 */

namespace Firebase\JWT;

class JWT {
    /**
     * Mã hóa payload thành chuỗi JWT (Header.Payload.Signature)
     * 
     * @param array $payload Dữ liệu cần mã hóa
     * @param string $key Khóa bí mật
     * @param string $alg Thuật toán (Mặc định HS256)
     * @return string
     */
    public static function encode(array $payload, string $key, string $alg = 'HS256'): string {
        $header = ['typ' => 'JWT', 'alg' => $alg];
        
        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode($header));
        $segments[] = self::base64UrlEncode(json_encode($payload));
        
        $signing_input = implode('.', $segments);
        $signature = self::sign($signing_input, $key, $alg);
        $segments[] = self::base64UrlEncode($signature);
        
        return implode('.', $segments);
    }

    /**
     * Giải mã chuỗi JWT và xác thực chữ ký + thời hạn
     * 
     * @param string $jwt Chuỗi Token
     * @param string $key Khóa bí mật
     * @return object Payload dữ liệu
     * @throws \Exception Nếu token lỗi hoặc hết hạn
     */
    public static function decode(string $jwt, string $key): object {
        $tks = explode('.', $jwt);
        if (count($tks) !== 3) {
            throw new \Exception('Token không đúng định dạng');
        }
        
        list($headb64, $payloadb64, $sigb64) = $tks;
        
        $header = json_decode(self::base64UrlDecode($headb64));
        $payload = json_decode(self::base64UrlDecode($payloadb64));
        $sig = self::base64UrlDecode($sigb64);
        
        if ($header === null || $payload === null) {
            throw new \Exception('Payload hoặc Header không hợp lệ');
        }

        // 1. Kiểm tra thuật toán (Chống thuật toán 'none' bypass)
        if (empty($header->alg) || $header->alg !== 'HS256') {
            throw new \Exception('Thuật toán ký không được hỗ trợ');
        }

        // 2. Xác thực chữ ký
        $signing_input = $headb64 . '.' . $payloadb64;
        if (!self::verify($signing_input, $sig, $key, $header->alg)) {
            throw new \Exception('Chữ ký không hợp lệ (Signature mismatch)');
        }

        // 3. Kiểm tra thời hạn (exp)
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new ExpiredException('Token đã hết hạn bảo mật');
        }

        return $payload;
    }

    private static function sign(string $msg, string $key, string $alg): string {
        return hash_hmac('sha256', $msg, $key, true);
    }

    private static function verify(string $msg, string $signature, string $key, string $alg): bool {
        $hash = self::sign($msg, $key, $alg);
        return hash_equals($signature, $hash);
    }

    /**
     * Mã hóa Base64Url (RFC 4648)
     */
    public static function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Giải mã Base64Url
     */
    public static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}

/**
 * Exception class cho Token hết hạn
 */
class ExpiredException extends \Exception {}
