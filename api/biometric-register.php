<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Harus sudah OTP verified, tapi belum biometric verified (sedang proses registrasi)
if (empty($_SESSION['user_id']) || empty($_SESSION['otp_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['credential_id']) || empty($body['raw_id'])) {
    echo json_encode(['success' => false, 'message' => 'Data credential tidak lengkap.']);
    exit;
}

$userId       = (int)$_SESSION['user_id'];
$credentialId = $body['credential_id'];   // base64url string
$rawId        = $body['raw_id'];          // base64 string
$deviceType   = $body['device_type'] ?? 'platform';

try {
    // Cek apakah credential_id sudah terdaftar (hindari duplikat)
    $check = $pdo->prepare("SELECT id FROM webauthn_credentials WHERE credential_id = ? AND user_id = ?");
    $check->execute([$credentialId, $userId]);
    if ($check->fetch()) {
        // Sudah ada – anggap registrasi berhasil, lanjut
        $_SESSION['biometric_verified'] = true;
        echo json_encode(['success' => true, 'message' => 'Credential sudah terdaftar.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO webauthn_credentials (user_id, credential_id, public_key, device_type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $credentialId, $rawId, $deviceType]);

    // Set session biometric verified setelah registrasi
    $_SESSION['biometric_verified'] = true;

    echo json_encode(['success' => true, 'message' => 'Biometric berhasil didaftarkan.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan credential: ' . $e->getMessage()]);
}
