<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/mailer.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect jika tidak ada pending registrasi
if (empty($_SESSION['reg_pending'])) {
    header('Location: ' . APP_URL . '/auth/register.php');
    exit;
}

$reg     = &$_SESSION['reg_pending'];
$error   = null;
$success = null;
$isDebug = !empty($reg['debug']); // Jika email gagal, tampilkan OTP di debug box

// ── Handle Resend OTP ─────────────────────────────────────────
if (isset($_GET['resend'])) {
    $newOtp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $reg['otp'] = $newOtp;
    $reg['expires'] = time() + OTP_LIFETIME;

    $sent = sendOtpEmail($reg['email'], $reg['nama'], $newOtp);
    if (!$sent) {
        $reg['debug'] = true;
        $isDebug = true;
    } else {
        unset($reg['debug']);
        $isDebug = false;
    }
    $success = 'Kode OTP baru telah dikirim ke email Anda.';
}

// ── Handle Verifikasi OTP ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputOtp = trim($_POST['otp'] ?? '');

    // Cek kadaluarsa
    if (time() > $reg['expires']) {
        $error = 'Kode OTP sudah kadaluarsa. Silakan kirim ulang.';
    } elseif (strlen($inputOtp) !== 6 || !ctype_digit($inputOtp)) {
        $error = 'Masukkan 6 digit kode OTP dengan benar.';
    } elseif (!hash_equals($reg['otp'], $inputOtp)) {
        $error = 'Kode OTP salah. Periksa kembali email Anda.';
    } else {
        // ── OTP Valid → Simpan user ke database ───────────────
        try {
            $userModel = new UserModel($pdo);

            // Cek sekali lagi (race condition)
            if ($userModel->findByEmailHash($reg['email'])) {
                $error = 'Email ini sudah terdaftar. Silakan login.';
            } else {
                $newUserId = $userModel->createSelfRegistered([
                    'nama'    => $reg['nama'],
                    'email'   => $reg['email'],
                    'no_wa'   => $reg['no_wa'],
                    'role'    => 'user',
                    'is_active' => 1,
                ]);

                // Bersihkan session registrasi
                unset($_SESSION['reg_pending']);

                // Set session sukses (termasuk user_id untuk tawaran biometric)
                $_SESSION['reg_success'] = [
                    'nama'    => $reg['nama'],
                    'email'   => $reg['email'],
                    'user_id' => $newUserId,
                ];

                header('Location: ' . APP_URL . '/auth/register-success.php');
                exit;
            }
        } catch (\Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

$maskedEmail = substr($reg['email'], 0, 3) . str_repeat('*', max(3, strpos($reg['email'], '@') - 3)) . substr($reg['email'], strpos($reg['email'], '@'));
$sisa        = max(0, $reg['expires'] - time());
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Verifikasi OTP – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="icon" href="<?= APP_URL ?>/assets/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        .auth-container { max-width: 460px; }

        /* Progress Steps */
        .reg-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 2rem;
        }
        .reg-step { display: flex; flex-direction: column; align-items: center; gap: .3rem; }
        .step-circle {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700;
        }
        .step-circle.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; box-shadow: 0 4px 14px rgba(108,99,255,.4); }
        .step-circle.done { background: var(--success); color: #fff; }
        .step-circle.pending { background: rgba(255,255,255,.06); border: 1.5px solid var(--border); color: var(--text-muted); }
        .step-label { font-size: .68rem; color: var(--text-muted); font-weight: 500; }
        .step-line { flex:1; height:2px; background:var(--border); margin:0 .4rem; margin-bottom:1.2rem; max-width:60px; }
        .step-line.done { background: var(--success); }

        /* Email icon animation */
        .email-icon-wrap {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(108,99,255,.2), rgba(168,85,247,.2));
            border: 2px solid rgba(108,99,255,.35);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            animation: float 3s ease-in-out infinite;
        }
        .email-icon-wrap svg { width: 34px; height: 34px; stroke: var(--primary); fill: none; stroke-width: 1.5; }

        .otp-expiry {
            display: flex; align-items: center; gap: .4rem;
            justify-content: center;
            font-size: .8rem; color: var(--text-muted);
            margin-top: .5rem;
        }
        .otp-expiry svg { width: 14px; height: 14px; stroke: currentColor; fill: none; }
        .otp-expiry #countdown { font-weight: 700; color: var(--text); }
        .otp-expiry.expired #countdown { color: var(--danger); }
    </style>
</head>
<body class="auth-page">
    <div class="auth-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <main class="auth-container" style="position:relative;z-index:1;width:100%;padding:1.5rem">
        <div class="auth-card glass-card">
            <!-- Logo -->
            <div class="auth-logo">
                <div class="email-icon-wrap">
                    <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <h1>Cek Email Anda</h1>
                <p>Kode OTP dikirim ke<br><strong><?= htmlspecialchars($maskedEmail) ?></strong></p>
            </div>

            <!-- Progress Steps -->
            <div class="reg-steps">
                <div class="reg-step">
                    <div class="step-circle done">✓</div>
                    <span class="step-label">Data Diri</span>
                </div>
                <div class="step-line done"></div>
                <div class="reg-step">
                    <div class="step-circle active">2</div>
                    <span class="step-label">Verifikasi</span>
                </div>
                <div class="step-line"></div>
                <div class="reg-step">
                    <div class="step-circle pending">3</div>
                    <span class="step-label">Selesai</span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" style="fill:currentColor;stroke:none;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 20 20" style="fill:currentColor;stroke:none;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <?php if ($isDebug): ?>
            <div class="alert alert-warning">
                <svg viewBox="0 0 20 20" style="fill:currentColor;stroke:none;flex-shrink:0"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>
                    <strong>Mode Debug:</strong> Email tidak terkirim (konfigurasi SMTP belum diatur).<br>
                    Kode OTP Anda: <strong style="font-size:1.1rem;letter-spacing:3px;color:#FCD34D;"><?= htmlspecialchars($reg['otp']) ?></strong>
                </span>
            </div>
            <?php endif; ?>

            <!-- OTP Form -->
            <form method="POST" id="otp-form">
                <div class="otp-inputs" id="otp-container">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" class="otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" <?= $i === 0 ? 'autofocus' : '' ?>>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="otp" id="otp-hidden">

                <button type="submit" class="btn-primary btn-full" id="btn-verify">
                    <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    Verifikasi & Buat Akun
                </button>
            </form>

            <!-- Countdown & Resend -->
            <div class="otp-expiry" id="expiry-wrap">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Kode berlaku <strong id="countdown"><?= gmdate('i:s', $sisa) ?></strong></span>
            </div>
            <div class="otp-timer" style="margin-top:.75rem;">
                <a href="?resend=1" id="resend-btn" class="link-primary" style="display:none;">
                    ↺ Kirim Ulang OTP
                </a>
            </div>

            <div class="auth-back" style="margin-top:1rem;">
                <a href="<?= APP_URL ?>/auth/register.php" class="link-muted">← Kembali & Ubah Data</a>
            </div>
        </div>
    </main>

    <script>
        // ── OTP Input Logic ──────────────────────────────────────
        const digits  = document.querySelectorAll('.otp-digit');
        const hidden  = document.getElementById('otp-hidden');
        const form    = document.getElementById('otp-form');
        const btnV    = document.getElementById('btn-verify');

        digits.forEach((inp, idx) => {
            inp.addEventListener('input', e => {
                const v = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = v;
                if (v && idx < digits.length - 1) digits[idx + 1].focus();
                syncHidden();
            });
            inp.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !inp.value && idx > 0) {
                    digits[idx - 1].focus();
                    digits[idx - 1].value = '';
                    syncHidden();
                }
            });
            inp.addEventListener('paste', e => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
                [...text].forEach((c, i) => { if (digits[i]) digits[i].value = c; });
                syncHidden();
                if (text.length === 6) form.submit();
            });
        });

        function syncHidden() {
            hidden.value = [...digits].map(d => d.value).join('');
        }

        form.addEventListener('submit', e => {
            syncHidden();
            if (hidden.value.length !== 6) {
                e.preventDefault();
                return;
            }
            btnV.disabled = true;
            btnV.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="50" stroke-dashoffset="20"/></svg> Memverifikasi...';
        });

        // ── Countdown Timer ─────────────────────────────────────
        let seconds = <?= $sisa ?>;
        const countEl  = document.getElementById('countdown');
        const exWrap   = document.getElementById('expiry-wrap');
        const resendBtn = document.getElementById('resend-btn');

        const timer = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearInterval(timer);
                countEl.textContent = '0:00';
                exWrap.classList.add('expired');
                exWrap.style.display = 'none';
                resendBtn.style.display = 'inline';
            } else {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                countEl.textContent = `${m}:${s.toString().padStart(2,'0')}`;
                if (seconds <= 60) exWrap.classList.add('expired');
            }
        }, 1000);
    </script>
</body>
</html>
