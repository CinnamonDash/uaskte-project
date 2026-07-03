<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/OtpModel.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Validasi code dari Google
if (empty($_GET['code'])) {
    $_SESSION['login_error'] = 'Otorisasi Google dibatalkan.';
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

try {
    // ── STEP 1: Tukar code dengan access token via cURL ───────
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $_GET['code'],
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT    => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $tokenRes  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenData = json_decode($tokenRes, true);
    if (empty($tokenData['access_token'])) {
        $errDesc = $tokenData['error_description'] ?? $tokenData['error'] ?? 'Token tidak diterima';
        throw new Exception('Gagal mendapatkan access token: ' . $errDesc);
    }

    // ── STEP 2: Ambil profil Google ───────────────────────────
    $ch2 = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenData['access_token']],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $profileRes = curl_exec($ch2);
    curl_close($ch2);

    $profile = json_decode($profileRes, true);
    if (empty($profile['email'])) {
        throw new Exception('Gagal mengambil profil Google. Pastikan izin email disetujui.');
    }

    // ── STEP 3: Cari user di DB via email hash SHA-512 ────────
    $userModel = new UserModel($pdo);
    $user = $userModel->findByEmailHash($profile['email']);

    if (!$user) {
        // Email tidak terdaftar → arahkan untuk hubungi administrator
        $_SESSION['login_error'] = 'Email <strong>' . htmlspecialchars($profile['email']) . '</strong> belum terdaftar. Silakan hubungi Administrator untuk mendaftarkan akun Anda.';
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }

    // Cek status akun aktif
    if (empty($user['is_active'])) {
        $_SESSION['login_error'] = 'Akun Anda telah dinonaktifkan. Hubungi administrator.';
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }

    // Update Google ID jika belum tersimpan
    if (empty($user['google_id']) && !empty($profile['sub'])) {
        $userModel->updateGoogleId($user['id'], (string)$profile['sub']);
    }

    // ── STEP 4: Set session sementara (OTP belum diverifikasi) ─
    setUserSession($user);
    $_SESSION['pending_otp_user_id'] = $user['id'];

    // ── STEP 5: Generate & kirim OTP ke WhatsApp ─────────────
    $otpModel = new OtpModel($pdo);
    $otp  = $otpModel->generate($user['id']);
    $sent = $otpModel->sendWhatsApp($user['no_wa'], $otp, $user['nama']);

    if (!$sent) {
        // Mode debug: simpan OTP di session jika WhatsApp gagal
        $_SESSION['_debug_otp'] = $otp;
    }

    $_SESSION['otp_user_nama'] = $user['nama'];
    $_SESSION['otp_user_wa']   = substr($user['no_wa'], 0, 4)
        . str_repeat('*', max(0, strlen($user['no_wa']) - 7))
        . substr($user['no_wa'], -3);

    // ── STEP 6: Redirect ke halaman verifikasi OTP ───────────
    header('Location: ' . APP_URL . '/auth/otp-verify.php');
    exit;

} catch (Exception $e) {
    $_SESSION['login_error'] = 'Error: ' . $e->getMessage();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}
