<?php
/**
 * Callback OAuth Google khusus untuk REGISTRASI
 * Berbeda dari /auth/google-callback.php yang untuk LOGIN
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

if (empty($_GET['code'])) {
    $_SESSION['reg_error'] = 'Otorisasi Google dibatalkan.';
    header('Location: ' . APP_URL . '/auth/register.php');
    exit;
}

try {
    // ── STEP 1: Tukar code dengan access token ────────────────
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $_GET['code'],
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => APP_URL . '/auth/register-google-callback.php',
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $tokenRes = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($tokenRes, true);
    if (empty($tokenData['access_token'])) {
        throw new Exception('Gagal mendapatkan access token dari Google.');
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
        throw new Exception('Gagal mengambil profil Google.');
    }

    // ── STEP 3: Cek apakah email sudah terdaftar ──────────────
    $userModel = new UserModel($pdo);
    $existing  = $userModel->findByEmailHash($profile['email']);

    if ($existing) {
        // Sudah terdaftar → arahkan ke login
        $_SESSION['login_error'] = 'Email <strong>' . htmlspecialchars($profile['email']) . '</strong> sudah terdaftar. Silakan login.';
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }

    // ── STEP 4: Simpan data Google di session → lanjut isi form
    $_SESSION['reg_google'] = [
        'nama'      => $profile['name']    ?? '',
        'email'     => strtolower(trim($profile['email'])),
        'google_id' => $profile['sub']     ?? '',
    ];

    header('Location: ' . APP_URL . '/auth/register.php');
    exit;

} catch (Exception $e) {
    $_SESSION['reg_error'] = 'Error: ' . $e->getMessage();
    header('Location: ' . APP_URL . '/auth/register.php');
    exit;
}
