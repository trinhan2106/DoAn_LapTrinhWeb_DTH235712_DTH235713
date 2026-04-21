<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/common/jwt_helper.php';

$payload = [
    'iat' => time(),
    'exp' => time() + 900,
    'data' => [
        'type' => 'invoice',
        'id' => 'INV123',
        'maKH' => 'KH001'
    ]
];

$token = SapphireAuth::encode($payload, JWT_SECRET_KEY);
echo "Token: " . $token . "\n";

$decoded = SapphireAuth::decode($token, JWT_SECRET_KEY);
echo "Decoded: " . ($decoded ? "SUCCESS" : "FAILED") . "\n";
print_r($decoded);
