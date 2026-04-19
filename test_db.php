<?php
require_once 'config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $sql = "SELECT p.*, t.tenTang AS tang, (SELECT urlHinhAnh FROM PHONG_HINH_ANH pha WHERE pha.maPhong = p.maPhong ORDER BY pha.is_thumbnail DESC LIMIT 1) AS hinhAnh FROM PHONG p JOIN TANG t ON p.maTang = t.maTang WHERE p.deleted_at IS NULL LIMIT 3";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "SUCCESS_FETCH\n";
    var_dump(count($res));
} catch (PDOException $e) {
    echo "ERROR_PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
