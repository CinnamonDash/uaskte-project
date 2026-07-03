<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireAdmin();
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
if (empty($body['credential_id'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("
    INSERT INTO webauthn_credentials (user_id, credential_id, public_key, device_type)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([
    $userId,
    $body['credential_id'],
    $body['credential_id'], // simplified – store as public key placeholder
    $body['device_type'] ?? 'Unknown'
]);

echo json_encode(['success' => true]);
