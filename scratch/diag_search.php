<?php
require_once __DIR__ . '/includes/common/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "--- CHECKING INDICES ---\n";
    
    $tables = ['PHONG', 'KHACH_HANG', 'HOP_DONG'];
    foreach ($tables as $table) {
        echo "Indices for $table:\n";
        $stmt = $pdo->query("SHOW INDEX FROM $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['Key_name']} (Type: {$row['Index_type']}, Column: {$row['Column_name']})\n";
        }
    }
    
    echo "\n--- TESTING MATCH AGAINST ---\n";
    $testKeyword = 'A01*';
    $stmtTest = $pdo->prepare("SELECT maPhong FROM PHONG WHERE MATCH(maPhong, tenPhong) AGAINST (? IN BOOLEAN MODE) LIMIT 1");
    $stmtTest->execute([$testKeyword]);
    $result = $stmtTest->fetch();
    echo "Search 'A01*' in PHONG: " . ($result ? "FOUND" : "NOT FOUND") . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
