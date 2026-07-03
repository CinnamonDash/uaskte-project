<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect jika tidak ada data sukses
if (empty($_SESSION['reg_success'])) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$regData   = $_SESSION['reg_success'];
$newUserId = (int)($regData['user_id'] ?? 0);
unset($_SESSION['reg_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10B981">
    <title>Registrasi Berhasil – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="icon" href="<?= APP_URL ?>/assets/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        .auth-container { max-width: 460px; }

        .success-icon-wrap {
            width: 96px; height: 96px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(16,185,129,.2), rgba(5,150,105,.3));
            border: 2px solid rgba(16,185,129,.4);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            animation: successPop .6s cubic-bezier(.36,.07,.19,.97) both;
        }
        .success-icon-wrap svg {
            width: 48px; height: 48px; stroke: var(--success);
            fill: none; stroke-width: 2.5;
            stroke-dasharray: 100; stroke-dashoffset: 100;
            animation: drawCheck .8s ease .3s forwards;
        }
        @keyframes successPop { 0%{opacity:0;transform:scale(.5)} 70%{transform:scale(1.1)} 100%{opacity:1;transform:scale(1)} }
        @keyframes drawCheck  { to { stroke-dashoffset: 0; } }

        .success-steps {
            background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.15);
            border-radius: 12px; padding: 1.25rem; margin: 1.5rem 0;
        }
        .success-step { display:flex; align-items:flex-start; gap:.75rem; padding:.5rem 0; }
        .success-step:not(:last-child) { border-bottom: 1px solid rgba(16,185,129,.1); }
        .ss-num {
            width:24px; height:24px; flex-shrink:0; border-radius:50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display:flex; align-items:center; justify-content:center;
            font-size:.7rem; font-weight:800; color:#fff;
        }
        .ss-text { font-size:.85rem; color:var(--text-muted); line-height:1.5; }
        .ss-text strong { color:var(--text); }

        .confetti { position:fixed; inset:0; pointer-events:none; overflow:hidden; z-index:0; }
        .confetti-piece {
            position:absolute; width:8px; height:8px; border-radius:2px;
            animation: confettiFall linear forwards;
        }
        @keyframes confettiFall {
            0%   { transform:translateY(-20px) rotate(0deg); opacity:1; }
            100% { transform:translateY(100vh) rotate(720deg); opacity:0; }
        }

        /* ── Biometric Modal ── */
        .bio-modal-backdrop {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(0,0,0,.6); backdrop-filter: blur(8px);
            align-items: center; justify-content: center; padding: 1rem;
        }
        .bio-modal-backdrop.show { display: flex; }

        .bio-modal {
            background: rgba(22,21,31,.97);
            border: 1px solid rgba(108,99,255,.3);
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(0,0,0,.6), 0 0 0 1px rgba(108,99,255,.1);
            width: 100%; max-width: 420px;
            padding: 2.25rem;
            text-align: center;
            animation: scaleIn .3s cubic-bezier(.34,1.56,.64,1);
        }

        .bio-modal-icon {
            width: 88px; height: 88px; margin: 0 auto 1.5rem; position: relative;
        }
        .bio-icon-ring {
            position: absolute; inset: -10px; border-radius: 50%;
            border: 2px solid rgba(108,99,255,.3);
            animation: pulse 2.5s ease-in-out infinite;
        }
        .bio-icon-ring-2 {
            position: absolute; inset: -22px; border-radius: 50%;
            border: 1px solid rgba(108,99,255,.12);
            animation: pulse 2.5s ease-in-out infinite .6s;
        }
        .bio-icon-circle {
            width: 88px; height: 88px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(108,99,255,.2), rgba(168,85,247,.2));
            border: 2px solid rgba(108,99,255,.45);
            display: flex; align-items: center; justify-content: center;
        }
        .bio-icon-circle svg { width: 44px; height: 44px; }

        .bio-modal h2 {
            font-size: 1.35rem; font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: .6rem;
        }
        .bio-modal p {
            color: var(--text-muted); font-size: .875rem;
            line-height: 1.65; margin-bottom: 1.5rem;
        }
        .bio-modal p strong { color: var(--text); }

        .bio-feature-list {
            display: flex; flex-direction: column; gap: .5rem;
            background: rgba(108,99,255,.07); border: 1px solid rgba(108,99,255,.15);
            border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; text-align: left;
        }
        .bio-feature { display: flex; align-items: center; gap: .6rem; font-size: .82rem; color: var(--text-muted); }
        .bio-feature svg { width: 14px; height: 14px; flex-shrink: 0; color: var(--primary); stroke: currentColor; fill: none; }
        .bio-feature strong { color: var(--text); }

        .bio-modal-actions { display: flex; flex-direction: column; gap: .75rem; }
        .btn-bio-yes {
            display: flex; align-items: center; justify-content: center; gap: .6rem;
            width: 100%; padding: .95rem; border-radius: 12px; border: none;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff; font-size: .95rem; font-weight: 700;
            font-family: 'Inter', sans-serif; cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 6px 24px rgba(108,99,255,.35);
        }
        .btn-bio-yes:hover { opacity: .9; transform: translateY(-1px); }
        .btn-bio-yes svg { width: 18px; height: 18px; }
        .btn-bio-skip {
            background: none; border: 1px solid var(--border); border-radius: 10px;
            color: var(--text-muted); font-size: .85rem; font-family: 'Inter', sans-serif;
            padding: .65rem; cursor: pointer; transition: var(--transition); width: 100%;
        }
        .btn-bio-skip:hover { background: rgba(255,255,255,.04); color: var(--text); }

        .bio-status { min-height: 44px; margin-top: 1rem; }

        /* Scanning animation */
        .bio-scanning .bio-icon-ring {
            border-color: rgba(108,99,255,.7);
            animation: pulse .8s ease-in-out infinite;
        }
    </style>
</head>
<body class="auth-page">
    <!-- Confetti -->
    <div class="confetti" id="confetti"></div>

    <div class="auth-bg">
        <div class="orb orb-1" style="background:var(--success)"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3" style="background:var(--primary)"></div>
    </div>

    <main class="auth-container" style="position:relative;z-index:1;width:100%;padding:1.5rem">
        <div class="auth-card glass-card" style="text-align:center;">
            <!-- Success Icon -->
            <div class="success-icon-wrap">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </div>

            <h1 style="font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,var(--success),#34D399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:.5rem;">
                Registrasi Berhasil!
            </h1>
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;margin-bottom:0;">
                Halo, <strong style="color:var(--text);"><?= htmlspecialchars($regData['nama']) ?></strong>!<br>
                Akun Anda berhasil dibuat dengan email<br>
                <strong style="color:var(--primary);"><?= htmlspecialchars($regData['email']) ?></strong>
            </p>

            <!-- Next Steps -->
            <div class="success-steps">
                <div class="success-step">
                    <div class="ss-num">1</div>
                    <div class="ss-text"><strong>Login dengan Google</strong><br>Gunakan akun Google yang terhubung ke email di atas.</div>
                </div>
                <div class="success-step">
                    <div class="ss-num">2</div>
                    <div class="ss-text"><strong>Verifikasi OTP WhatsApp</strong><br>Kode OTP akan dikirim ke nomor WhatsApp yang Anda daftarkan.</div>
                </div>
                <div class="success-step">
                    <div class="ss-num">3</div>
                    <div class="ss-text"><strong>Verifikasi Biometric</strong><br>Fingerprint / Windows Hello melindungi akun Anda dari akses tidak sah.</div>
                </div>
            </div>

            <a href="<?= APP_URL ?>/index.php" class="btn-primary btn-full" id="btn-login" style="text-decoration:none;display:flex;">
                <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Masuk Sekarang
            </a>

            <p id="redirect-text" style="margin-top:1rem;font-size:.8rem;color:var(--text-muted);">
                Halaman akan otomatis diarahkan dalam <strong id="redirect-count">8</strong> detik...
            </p>
        </div>
    </main>

    <!-- ══════════════════════════════════════════════════
         BIOMETRIC ENROLLMENT MODAL
    ══════════════════════════════════════════════════ -->
    <div class="bio-modal-backdrop" id="bio-modal">
        <div class="bio-modal">
            <!-- Icon -->
            <div class="bio-modal-icon" id="bio-modal-icon-wrap">
                <div class="bio-icon-ring" id="bio-ring"></div>
                <div class="bio-icon-ring-2"></div>
                <div class="bio-icon-circle">
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

            <h2>Aktifkan Keamanan Biometric</h2>
            <p>
                Daftarkan <strong>fingerprint</strong> atau <strong>Windows Hello</strong> sekarang untuk mempermudah dan mengamankan login berikutnya.<br><br>
                Setiap kali login, setelah OTP WhatsApp Anda akan diminta verifikasi biometric ini.
            </p>

            <!-- Feature list -->
            <div class="bio-feature-list">
                <div class="bio-feature">
                    <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span><strong>Login lebih cepat</strong> — tidak perlu ketik password</span>
                </div>
                <div class="bio-feature">
                    <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span><strong>Keamanan berlapis</strong> — hanya perangkat Anda</span>
                </div>
                <div class="bio-feature">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><strong>Opsional</strong> — bisa dilewati, aktifkan nanti</span>
                </div>
            </div>

            <div id="bio-status" class="bio-status"></div>

            <div class="bio-modal-actions">
                <button class="btn-bio-yes" id="btn-enroll" onclick="enrollBiometric()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 11c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" fill="currentColor" stroke="none"/>
                        <path d="M18 21a6 6 0 0 0-12 0"/>
                        <path d="M20 13l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Daftarkan Sekarang
                </button>
                <button class="btn-bio-skip" onclick="skipBiometric()">
                    Lewati, saya akan daftarkan nanti
                </button>
            </div>
        </div>
    </div>

    <script>
    const APP_URL  = '<?= APP_URL ?>';
    const USER_ID  = <?= $newUserId ?: 0 ?>;
    const USER_EMAIL = '<?= addslashes($regData['email']) ?>';
    const USER_NAME  = '<?= addslashes($regData['nama']) ?>';

    // ── Auto redirect countdown ─────────────────────────────────
    let redirectCount = 8;
    let redirectInterval = null;
    const countEl = document.getElementById('redirect-count');

    function startRedirectTimer() {
        redirectInterval = setInterval(() => {
            redirectCount--;
            countEl.textContent = redirectCount;
            if (redirectCount <= 0) {
                clearInterval(redirectInterval);
                window.location.href = APP_URL + '/index.php';
            }
        }, 1000);
    }

    // ── Tunda redirect lalu tampilkan modal ─────────────────────
    window.addEventListener('load', () => {
        // Cek apakah perangkat mendukung biometric
        if (window.PublicKeyCredential) {
            PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(supported => {
                if (supported && USER_ID > 0) {
                    // Tampilkan modal setelah 1.5 detik (beri waktu animasi sukses)
                    setTimeout(() => showBioModal(), 1500);
                    return;
                }
                // Tidak didukung → langsung redirect timer
                startRedirectTimer();
            }).catch(() => startRedirectTimer());
        } else {
            startRedirectTimer();
        }
    });

    function showBioModal() {
        document.getElementById('bio-modal').classList.add('show');
        // Jeda redirect selama modal terbuka
    }

    function hideBioModal() {
        document.getElementById('bio-modal').classList.remove('show');
        startRedirectTimer();
    }

    function skipBiometric() {
        hideBioModal();
    }

    function setBioStatus(msg, type = 'info') {
        const colors = { info:'alert-info', success:'alert-success', error:'alert-error', loading:'alert-info' };
        const icons  = {
            info:    `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>`,
            success: `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`,
            error:   `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`,
            loading: `<div class="loading-spinner" style="width:18px;height:18px;margin:0;border-width:2px;flex-shrink:0"></div>`
        };
        document.getElementById('bio-status').innerHTML =
            `<div class="alert ${colors[type]}" style="text-align:left">${icons[type]} <span>${msg}</span></div>`;
    }

    async function enrollBiometric() {
        const btn = document.getElementById('btn-enroll');
        btn.disabled = true;
        document.getElementById('bio-modal-icon-wrap').classList.add('bio-scanning');
        setBioStatus('Menunggu konfirmasi biometric dari perangkat Anda...', 'loading');

        try {
            const challenge = crypto.getRandomValues(new Uint8Array(32));
            const userId    = crypto.getRandomValues(new Uint8Array(16));

            const credential = await navigator.credentials.create({
                publicKey: {
                    challenge,
                    rp: { name: '<?= APP_NAME ?>', id: window.location.hostname },
                    user: {
                        id:          userId,
                        name:        USER_EMAIL,
                        displayName: USER_NAME
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

            const credentialId = credential.id;
            const rawId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));

            // Kirim ke API khusus pendaftaran biometric saat registrasi
            const res  = await fetch(APP_URL + '/api/biometric-enroll.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id:       USER_ID,
                    credential_id: credentialId,
                    raw_id:        rawId,
                    device_type:   'platform'
                })
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById('bio-modal-icon-wrap').classList.remove('bio-scanning');
                setBioStatus('✅ Biometric berhasil didaftarkan! Login pertama Anda akan lebih aman.', 'success');
                document.querySelector('.bio-modal-actions').innerHTML =
                    `<a href="${APP_URL}/index.php" class="btn-bio-yes" style="text-decoration:none">
                        Lanjut ke Login
                    </a>`;
            } else {
                throw new Error(data.message || 'Gagal menyimpan credential.');
            }
        } catch (err) {
            document.getElementById('bio-modal-icon-wrap').classList.remove('bio-scanning');
            if (err.name === 'NotAllowedError') {
                setBioStatus('Verifikasi dibatalkan. Anda bisa daftarkan biometric kapan saja setelah login.', 'error');
            } else {
                setBioStatus('Gagal: ' + err.message, 'error');
            }
            btn.disabled = false;
        }
    }

    // ── Confetti ────────────────────────────────────────────────
    const colors = ['#6C63FF','#A855F7','#10B981','#F59E0B','#EF4444','#3B82F6'];
    const container = document.getElementById('confetti');
    for (let i = 0; i < 60; i++) {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        el.style.cssText = `
            left: ${Math.random()*100}%;
            top: ${-Math.random()*40}px;
            background: ${colors[Math.floor(Math.random()*colors.length)]};
            width: ${Math.random()*10+5}px;
            height: ${Math.random()*10+5}px;
            border-radius: ${Math.random()>.5?'50%':'2px'};
            animation-duration: ${Math.random()*2+2}s;
            animation-delay: ${Math.random()*1.5}s;
            opacity: ${Math.random()*.8+.2};
        `;
        container.appendChild(el);
    }
    </script>
</body>
</html>
