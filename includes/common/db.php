<?php
// Require duy nhất file cấu hình constants từ cấp thư mục tương ứng
require_once __DIR__ . '/../../config/constants.php';

/**
 * Class Database
 * Áp dụng Singleton pattern để đảm bảo chỉ có 1 kết nối (instance) sinh ra xuyên suốt vòng đời gửi Request.
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $conn = null;

    /**
     * Hàm khởi tạo là private để chặn việc khởi tạo DataBase bằng từ khóa 'new' từ bên ngoài
     */
    private function __construct()
    {
        try {
            // Cấu hình DSN sử dụng charset utf8mb4 (hỗ trợ lưu cả emoji nếu cần)
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            // Các tuỳ chọn tối ưu và cấu hình bắt lỗi bảo mật cho PDO
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Task 1.1: Báo lỗi bằng ngoại lệ thay vì lỗi âm thầm
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Mặc định trả về dữ liệu kiểu mảng kết hợp (Associative Array)
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt emulate prepare để tận dụng rào chắn SQL Injection gốc từ DB Engine
            ];

            // Setup kế nối PDO 
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Cảnh báo: Không nên echo trực tiếp lỗi $e->getMessage() ra giao diện trong môi trường thực tế (Gây rò rỉ CSDL)
            error_log("Lỗi kết nối CSDL: " . $e->getMessage());
            die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng liên hệ quản trị viên.");
        }
    }

    /**
     * Chặn clone đối tượng
     */
    private function __clone(): void {}

    /**
     * Chặn unserialize đối tượng
     */
    public function __wakeup(): void
    {
        throw new Exception("Không thể unserialize Database singleton.");
    }

    /**
     * Cung cấp điểm duy nhất để các nơi khác có thể lấy instance DB.
     * Cách dùng: $db = Database::getInstance()->getConnection();
     * 
     * @return Database
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Lấy thực thể PDO đã kết nối để thực thiện truy vấn (prepare, execute, ...)
     * 
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->conn;
    }
}
