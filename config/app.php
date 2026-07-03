<?php
// ─── Dynamic Base URL (otomatis http/https, hostname & subfolder) ─────────────
$_scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_scheme = 'https';
}
$_host    = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Deteksi otomatis jika URL mengandung subfolder /uaskte (seperti saat akses via IP atau ngrok tanpa host-header)
$_subfolder = '';
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/uaskte') === 0) {
    $_subfolder = '/uaskte';
}
$_base    = $_scheme . '://' . $_host . $_subfolder;

// Load .env variables
$_envFile = __DIR__ . '/../.env';
$_env = file_exists($_envFile) ? parse_ini_file($_envFile) : [];

// Google OAuth Config
define('GOOGLE_CLIENT_ID',     $_env['GOOGLE_CLIENT_ID'] ?? 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', $_env['GOOGLE_CLIENT_SECRET'] ?? 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  $_base . '/auth/google-callback.php');

// Fonnte WhatsApp API
define('FONNTE_TOKEN', $_env['FONNTE_TOKEN'] ?? 'YOUR_FONNTE_TOKEN');
define('FONNTE_URL',   'https://api.fonnte.com/send');

// App Config
define('APP_URL',          $_base);
define('APP_NAME',         'UAS KTE');
define('SESSION_LIFETIME', 3600); // 1 jam
define('OTP_LIFETIME',     300);  // 5 menit
