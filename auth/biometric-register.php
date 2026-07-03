<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Harus sudah OTP verified sebelum sampai sini
if (empty($_SESSION['user_id']) || empty($_SESSION['otp_verified'])) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Kalau sudah biometric verified, langsung ke dashboard
if (!empty($_SESSION['biometric_verified'])) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$userName  = $_SESSION['user_nama']  ?? 'Pengguna';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Daftar Biometric – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        .bio-card { max-width: 460px; }
        .bio-icon-wrap {
            width: 96px; height: 96px; margin: 0 auto 1.75rem;
            position: relative;
        }
        .bio-icon-bg {
            width: 96px; height: 96px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(108,99,255,.25), rgba(168,85,247,.2));
            border: 2px solid rgba(108,99,255,.4);
            display: flex; align-items: center; justify-content: center;
        }
        .bio-icon-bg svg { width: 48px; height: 48px; }
        .bio-ring {
            position: absolute; inset: -10px; border-radius: 50%;
            border: 2px solid rgba(108,99,255,.25);
            animation: pulse 2.5s ease-in-out infinite;
        }
        .bio-ring-2 {
            position: absolute; inset: -22px; border-radius: 50%;
            border: 1px solid rgba(108,99,255,.12);
            animation: pulse 2.5s ease-in-out infinite 0.6s;
        }
        .status-box {
            min-height: 52px; display: flex; align-items: center;
            justify-content: center; margin-top: 1rem;
        }
        .step-pills {
            display: flex; align-items: center; justify-content: center;
            gap: .5rem; margin-bottom: 2rem; font-size: .75rem;
        }
        .step-pill {
            display: flex; align-items: center; gap: .35rem;
            padding: .3rem .75rem; border-radius: 20px;
            font-weight: 600; white-space: nowrap;
        }
        .step-done { background: rgba(16,185,129,.15); color: #34D399; border: 1px solid rgba(16,185,129,.25); }
        .step-active { background: linear-gradient(135deg,rgba(108,99,255,.25),rgba(168,85,247,.2)); color: #fff; border: 1px solid rgba(108,99,255,.35); }
        .step-next { background: rgba(255,255,255,.05); color: var(--text-muted); border: 1px solid var(--border); }
        .step-sep { color: var(--text-muted); }
    </style>
</head>
<body class="auth-page">
    <div class="auth-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <main class="auth-container" style="max-width:520px">
        <div class="auth-card glass-card bio-card">

            <!-- Progress steps -->
            <div class="step-pills">
                <div class="step-pill step-done">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Login
                </div>
                <span class="step-sep">›</span>
                <div class="step-pill step-done">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    OTP WA
                </div>
                <span class="step-sep">›</span>
                <div class="step-pill step-active">
                    ✦ Biometric
                </div>
                <span class="step-sep">›</span>
                <div class="step-pill step-next">Dashboard</div>
            </div>

            <!-- Icon -->
            <div class="bio-icon-wrap">
                <div class="bio-ring"></div>
                <div class="bio-ring-2"></div>
                <div class="bio-icon-bg">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 24C16 19.582 19.582 16 24 16C28.418 16 32 19.582 32 24" stroke="#6C63FF" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M11 24C11 16.82 16.82 11 24 11C31.18 11 37 16.82 37 24" stroke="#A855F7" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M6 24C6 14.059 14.059 6 24 6C33.941 6 42 14.059 42 24" stroke="rgba(108,99,255,0.4)" stroke-width="2" stroke-linecap="round" stroke-dasharray="4 3"/>
                        <circle cx="24" cy="24" r="4" fill="#6C63FF"/>
                        <path d="M24 28V36" stroke="#6C63FF" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M20 36H28" stroke="#A855F7" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <div class="auth-logo" style="margin-bottom:1.25rem">
                <h1 style="font-size:1.4rem">Daftar Biometric</h1>
                <p>Halo <strong><?= htmlspecialchars($userName) ?></strong>! Ini pertama kali Anda login.<br>
                   Daftarkan fingerprint / Windows Hello untuk keamanan tambahan.</p>
            </div>

            <div id="status-box" class="status-box"></div>

            <button id="btn-register" class="btn-primary btn-full" onclick="startRegister()">
                <svg viewBox="0 0 24 24"><path d="M12 11c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor" stroke="none"/></svg>
                Daftarkan Fingerprint / Windows Hello
            </button>

            <p class="biometric-hint" style="text-align:center;margin-top:1rem">
                Biometric digunakan setiap login setelah OTP WhatsApp untuk melindungi akun Anda.
            </p>
        </div>
    </main>

    <script>
    const API_BASE = '<?= APP_URL ?>/api';

    function setStatus(msg, type = 'info') {
        const box = document.getElementById('status-box');
        const colors = {
            info: 'alert-info',
            success: 'alert-success',
            error: 'alert-error',
            loading: 'alert-info'
        };
        const icons = {
            info: `<svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>`,
            success: `<svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`,
            error: `<svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`,
            loading: `<div class="loading-spinner" style="width:18px;height:18px;margin:0;border-width:2px"></div>`
        };
        box.innerHTML = `<div class="alert ${colors[type]}" style="width:100%">${icons[type]} ${msg}</div>`;
    }

    async function startRegister() {
        const btn = document.getElementById('btn-register');

        if (!window.PublicKeyCredential) {
            setStatus('Browser ini tidak mendukung WebAuthn/Biometric.', 'error');
            return;
        }

        const supported = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        if (!supported) {
            setStatus('Perangkat ini tidak memiliki authenticator biometric (fingerprint/face ID).', 'error');
            return;
        }

        btn.disabled = true;
        setStatus('Menunggu konfirmasi biometric...', 'loading');

        try {
            const challenge = crypto.getRandomValues(new Uint8Array(32));
            const userId    = crypto.getRandomValues(new Uint8Array(16));

            const credential = await navigator.credentials.create({
                publicKey: {
                    challenge,
                    rp: { name: '<?= APP_NAME ?>', id: window.location.hostname },
                    user: {
                        id: userId,
                        name: '<?= addslashes($userEmail) ?>',
                        displayName: '<?= addslashes($userName) ?>'
                    },
                    pubKeyCredParams: [
                        { type: 'public-key', alg: -7 },   // ES256
                        { type: 'public-key', alg: -257 }  // RS256
                    ],
                    authenticatorSelection: {
                        authenticatorAttachment: 'platform',
                        userVerification: 'required',
                        residentKey: 'preferred'
                    },
                    timeout: 60000,
                    attestation: 'none'
                }
            });

            // Encode credential
            const credentialId = credential.id; // base64url
            const rawId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));

            // Kirim ke server
            const res  = await fetch(`${API_BASE}/biometric-register.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    credential_id: credentialId,
                    raw_id: rawId,
                    device_type: 'platform'
                })
            });
            const data = await res.json();

            if (data.success) {
                setStatus('Biometric berhasil didaftarkan! Mengalihkan ke dashboard...', 'success');
                setTimeout(() => { window.location.href = '<?= APP_URL ?>/dashboard.php'; }, 1500);
            } else {
                setStatus('Gagal menyimpan: ' + data.message, 'error');
                btn.disabled = false;
            }
        } catch (err) {
            if (err.name === 'NotAllowedError') {
                setStatus('Verifikasi biometric dibatalkan atau timeout.', 'error');
            } else {
                setStatus('Error: ' + err.message, 'error');
            }
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>
