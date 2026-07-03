<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Harus sudah OTP verified
if (empty($_SESSION['user_id']) || empty($_SESSION['otp_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$body = json_decode(file_get_contents('php://input'), true);

// Kirim daftar credential_id milik user agar JS tahu ID yang harus di-challenge
if (!empty($body['get_credentials'])) {
    $stmt = $pdo->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_id = ?");
    $stmt->execute([$userId]);
    $creds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success' => true, 'credentials' => $creds]);
    exit;
}

// Verifikasi: cocokkan credential_id yang dikembalikan oleh authenticator
if (empty($body['credential_id'])) {
    echo json_encode(['success' => false, 'message' => 'Data credential tidak lengkap.']);
    exit;
}

$receivedId = $body['credential_id'];

$stmt = $pdo->prepare("
    SELECT id FROM webauthn_credentials
    WHERE user_id = ? AND credential_id = ?
    LIMIT 1
");
$stmt->execute([$userId, $receivedId]);
$row = $stmt->fetch();

if ($row) {
    $_SESSION['biometric_verified'] = true;
    echo json_encode(['success' => true, 'message' => 'Biometric terverifikasi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Credential tidak dikenali.']);
}
