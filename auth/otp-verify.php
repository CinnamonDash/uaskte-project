<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/OtpModel.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Cek apakah ada pending OTP
if (empty($_SESSION['pending_otp_user_id'])) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $userId = (int)$_SESSION['pending_otp_user_id'];
    $otpModel = new OtpModel($pdo);

    if ($otpModel->verify($userId, $otp)) {
        // OTP valid - set session resmi
        $_SESSION['otp_verified'] = true;
        unset($_SESSION['pending_otp_user_id'], $_SESSION['otp_user_wa'], $_SESSION['otp_user_nama'], $_SESSION['_debug_otp']);

        // Arahkan ke dashboard (biometric digunakan saat CRUD admin)
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    } else {
        $error = 'Kode OTP salah atau sudah kadaluarsa. Coba lagi.';
    }
}

if (isset($_GET['resend'])) {
    $userId = (int)$_SESSION['pending_otp_user_id'];
    $otpModel = new OtpModel($pdo);
    $otp = $otpModel->generate($userId);

    // Ambil data user
    require_once __DIR__ . '/../models/UserModel.php';
    $userModel = new UserModel($pdo);
    $user = $userModel->findById($userId);
    $sent = $otpModel->sendWhatsApp($user['no_wa'], $otp, $user['nama']);
    if (!$sent) $_SESSION['_debug_otp'] = $otp;
    $success = 'OTP baru telah dikirim ke WhatsApp Anda.';
}

$maskedWa = $_SESSION['otp_user_wa'] ?? '****';
$userName = $_SESSION['otp_user_nama'] ?? 'Pengguna';
$debugOtp = $_SESSION['_debug_otp'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Verifikasi OTP – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <main class="auth-container">
        <div class="auth-card glass-card">
            <div class="auth-logo">
                <div class="logo-icon logo-otp">
                    <svg viewBox="0 0 40 40" fill="none">
                        <rect width="40" height="40" rx="12" fill="url(#gradOtp)"/>
                        <path d="M8 14h24M8 20h16M8 26h12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                        <circle cx="30" cy="26" r="6" fill="#25D366"/>
                        <path d="M27.5 26l1.5 1.5 3-3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs><linearGradient id="gradOtp" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#25D366"/><stop offset="1" stop-color="#128C7E"/></linearGradient></defs>
                    </svg>
                </div>
                <h1>Verifikasi OTP</h1>
                <p>Halo <strong><?= htmlspecialchars($userName) ?></strong>! Kode OTP telah dikirim ke WhatsApp<br><strong><?= htmlspecialchars($maskedWa) ?></strong></p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <?php if ($debugOtp): ?>
            <div class="alert alert-info">
                🔧 <strong>Mode Debug:</strong> OTP Anda adalah <strong><?= $debugOtp ?></strong> (Fonnte belum dikonfigurasi)
            </div>
            <?php endif; ?>

            <form method="POST" id="otp-form">
                <div class="otp-inputs" id="otp-container">
                    <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" autofocus>
                    <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
                </div>
                <input type="hidden" name="otp" id="otp-hidden">
                <button type="submit" class="btn-primary btn-full" id="btn-verify">
                    Verifikasi OTP
                </button>
            </form>

            <div class="otp-timer">
                <span id="timer-text">Kirim ulang dalam <strong id="countdown">5:00</strong></span>
                <a href="?resend=1" id="resend-btn" style="display:none;" class="link-primary">Kirim Ulang OTP</a>
            </div>

            <div class="auth-back">
                <a href="<?= APP_URL ?>/auth/logout.php" class="link-muted">← Batal &amp; Keluar</a>
            </div>
        </div>
    </main>

    <script>
        // OTP input auto-advance
        const digits = document.querySelectorAll('.otp-digit');
        const hidden = document.getElementById('otp-hidden');
        const form = document.getElementById('otp-form');

        digits.forEach((input, idx) => {
            input.addEventListener('input', e => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = val;
                if (val && idx < digits.length - 1) digits[idx + 1].focus();
                updateHidden();
            });
            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !input.value && idx > 0) {
                    digits[idx - 1].focus();
                    digits[idx - 1].value = '';
                    updateHidden();
                }
            });
            input.addEventListener('paste', e => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
                [...text].forEach((c, i) => { if (digits[i]) digits[i].value = c; });
                updateHidden();
                if (text.length === 6) form.submit();
            });
        });

        function updateHidden() {
            hidden.value = [...digits].map(d => d.value).join('');
        }

        form.addEventListener('submit', e => {
            updateHidden();
            if (hidden.value.length !== 6) {
                e.preventDefault();
                alert('Masukkan 6 digit OTP');
            }
        });

        // Countdown timer
        let seconds = 300;
        const countEl = document.getElementById('countdown');
        const timerText = document.getElementById('timer-text');
        const resendBtn = document.getElementById('resend-btn');

        const timer = setInterval(() => {
            seconds--;
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            countEl.textContent = `${m}:${s.toString().padStart(2,'0')}`;
            if (seconds <= 0) {
                clearInterval(timer);
                timerText.style.display = 'none';
                resendBtn.style.display = 'inline';
            }
        }, 1000);
    </script>
</body>
</html>
