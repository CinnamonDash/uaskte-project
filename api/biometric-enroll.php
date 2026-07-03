<?php
/**
 * API: Simpan credential biometric saat proses REGISTRASI
 * Berbeda dari biometric-register.php yang memerlukan session login.
 * Endpoint ini menerima user_id dari body JSON (hanya boleh dipanggil
 * setelah akun baru dibuat, sehingga user_id baru saja di-insert).
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);

$userId       = (int)($body['user_id']       ?? 0);
$credentialId = $body['credential_id'] ?? '';
$rawId        = $body['raw_id']        ?? '';
$deviceType   = $body['device_type']   ?? 'platform';

if ($userId <= 0 || empty($credentialId) || empty($rawId)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

try {
    // Pastikan user_id benar-benar ada di tabel users
    $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $check->execute([$userId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
        exit;
    }

    // Cegah duplikat credential untuk user yang sama
    $dup = $pdo->prepare("SELECT id FROM webauthn_credentials WHERE user_id = ? AND credential_id = ?");
    $dup->execute([$userId, $credentialId]);
    if ($dup->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Credential sudah terdaftar.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO webauthn_credentials (user_id, credential_id, public_key, device_type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $credentialId, $rawId, $deviceType]);

    echo json_encode(['success' => true, 'message' => 'Biometric berhasil didaftarkan.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
