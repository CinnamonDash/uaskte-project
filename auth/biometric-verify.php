<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Harus sudah OTP verified
if (empty($_SESSION['user_id']) || empty($_SESSION['otp_verified'])) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Kalau sudah biometric verified, langsung ke dashboard
if (!empty($_SESSION['biometric_verified'])) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$userId    = (int)$_SESSION['user_id'];
$userName  = $_SESSION['user_nama']  ?? 'Pengguna';
$userEmail = $_SESSION['user_email'] ?? '';

// Ambil credential milik user
$stmt = $pdo->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_id = ?");
$stmt->execute([$userId]);
$credentials = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Tidak ada credential → user belum daftar biometric, lewati langsung
if (empty($credentials)) {
    $_SESSION['biometric_verified'] = true;
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$credentialsJson = json_encode($credentials);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Verifikasi Biometric – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
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
        .bio-ring-scanning {
            border-color: rgba(108,99,255,.6);
            animation: pulse 1s ease-in-out infinite;
        }
        .status-box { min-height: 52px; display: flex; align-items: center; justify-content: center; margin-top: 1rem; }
        .step-pills { display: flex; align-items: center; justify-content: center; gap: .5rem; margin-bottom: 2rem; font-size: .75rem; flex-wrap: wrap; }
        .step-pill { display: flex; align-items: center; gap: .35rem; padding: .3rem .75rem; border-radius: 20px; font-weight: 600; white-space: nowrap; }
        .step-done  { background: rgba(16,185,129,.15); color: #34D399; border: 1px solid rgba(16,185,129,.25); }
        .step-active{ background: linear-gradient(135deg,rgba(108,99,255,.25),rgba(168,85,247,.2)); color: #fff; border: 1px solid rgba(108,99,255,.35); }
        .step-next  { background: rgba(255,255,255,.05); color: var(--text-muted); border: 1px solid var(--border); }
        .step-sep   { color: var(--text-muted); }
    </style>
</head>
<body class="auth-page">
    <div class="auth-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <main class="auth-container" style="max-width:520px">
        <div class="auth-card glass-card">

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
                <div class="step-pill step-active">✦ Biometric</div>
                <span class="step-sep">›</span>
                <div class="step-pill step-next">Dashboard</div>
            </div>

            <!-- Icon -->
            <div class="bio-icon-wrap" id="bio-icon-wrap">
                <div class="bio-ring" id="bio-ring-1"></div>
                <div class="bio-ring-2" id="bio-ring-2"></div>
                <div class="bio-icon-bg">
                    <svg viewBox="0 0 48 48" fill="none">
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
                <h1 style="font-size:1.4rem">Verifikasi Biometric</h1>
                <p>Halo <strong><?= htmlspecialchars($userName) ?></strong>!<br>
                   Konfirmasi identitas Anda dengan fingerprint atau Windows Hello.</p>
            </div>

            <div id="status-box" class="status-box"></div>

            <button id="btn-verify" class="btn-primary btn-full" onclick="startVerify()">
                <svg viewBox="0 0 24 24"><path d="M17.81 4.47c-.08 0-.16-.02-.23-.06C15.66 3.42 14 3 12.01 3c-1.98 0-3.86.47-5.57 1.41-.24.13-.54.04-.68-.2-.13-.24-.04-.55.2-.68C7.82 2.52 9.86 2 12.01 2c2.13 0 3.99.47 6.03 1.52.25.13.34.43.21.67-.09.18-.26.28-.44.28zM3.5 9.72a.498.498 0 0 1-.4-.2C2.04 8.2 1.5 6.33 1.5 5.45c0-.28.22-.5.5-.5s.5.22.5.5c0 .65.45 2.27 1.54 3.56.18.21.15.53-.06.71-.09.08-.2.12-.48.12zm11.75 6.47c-.11 0-.22-.04-.31-.11-.96-.8-2.15-1.28-3.5-1.28-1.42 0-2.56.45-3.48 1.29-.21.18-.53.16-.71-.05-.18-.21-.16-.53.05-.71C8.28 14.45 9.67 14 11.44 14c1.63 0 3.07.57 4.23 1.46.22.18.25.5.07.72-.1.13-.25.19-.41.19zM8.97 15.59c-.11 0-.22-.04-.31-.11-.2-.17-.24-.49-.06-.7C10 13.33 11.49 12.5 13 12.5c1.57 0 2.95.84 3.98 2.41.16.24.1.56-.14.72-.24.17-.56.1-.72-.14-.82-1.26-1.87-1.99-3.12-1.99-1.28 0-2.44.7-3.61 2.03-.1.1-.23.16-.42.16zm-3.04 4.45c-.08 0-.16-.02-.23-.06C4.55 18.94 3.5 17.2 3.5 15.45c0-1.28.53-2.44 1.5-3.27.22-.19.54-.16.73.06.19.22.16.54-.06.73-.73.62-1.17 1.5-1.17 2.48 0 1.38.83 2.77 2.2 3.7.23.16.29.48.13.71-.11.15-.27.22-.44.22zM12 22c-2.97 0-5-1.53-5-3.5v-.06c.08-2.11 1.77-4.44 5-4.44s4.92 2.33 5 4.41V18.5c0 1.97-2.03 3.5-5 3.5z" fill="currentColor" stroke="none"/></svg>
                Verifikasi Sekarang
            </button>

            <div style="text-align:center;margin-top:1rem">
                <a href="<?= APP_URL ?>/auth/logout.php" class="link-muted">← Batal &amp; Keluar</a>
            </div>
        </div>
    </main>

    <script>
    const API_BASE  = '<?= APP_URL ?>/api';
    const savedCreds = <?= $credentialsJson ?>;

    function setStatus(msg, type = 'info') {
        const colors = { info:'alert-info', success:'alert-success', error:'alert-error', loading:'alert-info' };
        const icons  = {
            info:    `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>`,
            success: `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`,
            error:   `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`,
            loading: `<div class="loading-spinner" style="width:18px;height:18px;margin:0;border-width:2px"></div>`
        };
        document.getElementById('status-box').innerHTML =
            `<div class="alert ${colors[type]}" style="width:100%">${icons[type]} ${msg}</div>`;
    }

    // Decode base64url ke Uint8Array
    function base64urlToUint8Array(b64url) {
        const b64 = b64url.replace(/-/g, '+').replace(/_/g, '/')
                          + '='.repeat((4 - b64url.length % 4) % 4);
        return new Uint8Array([...atob(b64)].map(c => c.charCodeAt(0)));
    }

    async function startVerify() {
        const btn = document.getElementById('btn-verify');

        if (!window.PublicKeyCredential) {
            setStatus('Browser ini tidak mendukung WebAuthn.', 'error');
            return;
        }

        if (!savedCreds || savedCreds.length === 0) {
            setStatus('Tidak ada credential terdaftar untuk akun ini.', 'error');
            return;
        }

        btn.disabled = true;
        setStatus('Menunggu verifikasi biometric...', 'loading');

        // Animasi ring
        document.getElementById('bio-ring-1').classList.add('bio-ring-scanning');

        try {
            const allowCredentials = savedCreds.map(id => ({
                id:         base64urlToUint8Array(id),
                type:       'public-key',
                transports: ['internal', 'hybrid']
            }));

            const credential = await navigator.credentials.get({
                publicKey: {
                    challenge:          crypto.getRandomValues(new Uint8Array(32)),
                    rpId:               window.location.hostname,
                    timeout:            60000,
                    userVerification:   'required',
                    allowCredentials
                }
            });

            // Kirim credential_id ke server untuk divalidasi
            const res  = await fetch(`${API_BASE}/biometric-verify.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ credential_id: credential.id })
            });
            const data = await res.json();

            if (data.success) {
                setStatus('Verifikasi berhasil! Mengalihkan ke dashboard...', 'success');
                setTimeout(() => { window.location.href = '<?= APP_URL ?>/dashboard.php'; }, 1200);
            } else {
                setStatus('Verifikasi gagal: ' + data.message, 'error');
                btn.disabled = false;
                document.getElementById('bio-ring-1').classList.remove('bio-ring-scanning');
            }
        } catch (err) {
            if (err.name === 'NotAllowedError') {
                setStatus('Verifikasi dibatalkan atau timeout. Coba lagi.', 'error');
            } else {
                setStatus('Error: ' + err.message, 'error');
            }
            btn.disabled = false;
            document.getElementById('bio-ring-1').classList.remove('bio-ring-scanning');
        }
    }

    // Auto-mulai verifikasi saat halaman terbuka
    window.addEventListener('load', () => {
        if (savedCreds && savedCreds.length > 0) {
            setTimeout(startVerify, 600);
        }
    });
    </script>
</body>
</html>
