<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/OtpModel.php';
require_once __DIR__ . '/helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

// Ambil & parse error session
$loginError    = $_SESSION['login_error'] ?? null;
$successMsg    = $_SESSION['reg_success_msg'] ?? null;
unset($_SESSION['login_error'], $_SESSION['reg_success_msg']);

// Build Google OAuth URL
$googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'access_type'   => 'online',
    'state'         => bin2hex(random_bytes(16)),
    'prompt'        => 'select_account',
]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <meta name="description" content="Login ke sistem pengelolaan user UAS KTE dengan SSO Google dan verifikasi OTP WhatsApp">
    <title>Login – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="icon" href="<?= APP_URL ?>/assets/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>


        /* Alur login 3 langkah */
        .flow-steps {
            display: flex; align-items: center; justify-content: center;
            gap: .4rem; margin-top: 1.5rem; flex-wrap: wrap;
        }
        .flow-step {
            display: flex; align-items: center; gap: .35rem;
            font-size: .75rem; color: var(--text-muted);
        }
        .flow-num {
            width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem; font-weight: 800; color: #fff;
        }
        .flow-arrow { color: var(--border); font-size: .9rem; }
    </style>
</head>
<body class="auth-page">
    <div class="auth-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <main class="auth-container">
        <div class="auth-card glass-card">
            <!-- Logo -->
            <div class="auth-logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="12" fill="url(#grad1)"/>
                        <path d="M12 20C12 15.58 15.58 12 20 12C24.42 12 28 15.58 28 20C28 24.42 24.42 28 20 28" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M20 16V20L23 23" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs><linearGradient id="grad1" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse"><stop stop-color="#6C63FF"/><stop offset="1" stop-color="#A855F7"/></linearGradient></defs>
                    </svg>
                </div>
                <h1><?= APP_NAME ?></h1>
                <p>Sistem Pengelolaan User Terintegrasi</p>
            </div>

            <?php if ($loginError): ?>
            <!-- Error umum -->
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" style="fill:currentColor;stroke:none;flex-shrink:0;width:18px;height:18px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <?= $loginError ?>
            </div>
            <?php endif; ?>

            <?php if ($successMsg): ?>
            <!-- Sukses registrasi -->
            <div class="alert alert-success">
                <svg viewBox="0 0 20 20" style="fill:currentColor;stroke:none;flex-shrink:0;width:18px;height:18px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <?= $successMsg ?>
            </div>
            <?php endif; ?>



            <div class="auth-divider"><span>Masuk dengan akun Google Anda</span></div>

            <a href="<?= htmlspecialchars($googleAuthUrl) ?>" class="btn-google" id="btn-google-login">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Masuk dengan Google
            </a>
            
            <!-- Tombol Install PWA (Awalnya Disembunyikan) -->
            <button id="btnInstallPwa" class="btn-secondary" style="display: none; width: 100%; justify-content: center; margin-top: 1rem;">
                <svg viewBox="0 0 24 24" style="width:18px;height:18px;margin-right:8px;stroke:currentColor;fill:none;stroke-width:2;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Install Aplikasi (PWA)
            </button>



            <!-- Alur Login -->
            <div class="flow-steps">
                <div class="flow-step">
                    <div class="flow-num">1</div>
                    <span>Login Google SSO</span>
                </div>
                <span class="flow-arrow">→</span>
                <div class="flow-step">
                    <div class="flow-num">2</div>
                    <span>Verifikasi OTP WA</span>
                </div>
                <span class="flow-arrow">→</span>
                <div class="flow-step">
                    <div class="flow-num">3</div>
                    <span>Dashboard</span>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Daftarkan Service Worker dengan path yang dinamis (mendukung subfolder /uaskte)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')
                .then(r => console.log('SW registered'))
                .catch(e => console.warn('SW error', e));
        }

        // Tangani event instalasi PWA
        let deferredPrompt;
        const btnInstallPwa = document.getElementById('btnInstallPwa');

        window.addEventListener('beforeinstallprompt', (e) => {
            // Mencegah prompt bawaan browser muncul secara otomatis
            e.preventDefault();
            // Simpan event sehingga bisa dipicu nanti
            deferredPrompt = e;
            // Tampilkan tombol instal buatan kita
            btnInstallPwa.style.display = 'flex';
        });

        btnInstallPwa.addEventListener('click', (e) => {
            // Sembunyikan tombol setelah diklik
            btnInstallPwa.style.display = 'none';
            // Tampilkan prompt instalasi bawaan browser
            deferredPrompt.prompt();
            // Tunggu pilihan pengguna (Install atau Batal)
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User menerima instalasi PWA');
                } else {
                    console.log('User membatalkan instalasi PWA');
                }
                deferredPrompt = null;
            });
        });
    </script>
</body>
</html>
