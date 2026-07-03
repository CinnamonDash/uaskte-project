<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

// Prefill dari Google SSO (jika datang dari register-google-callback.php)
$fromGoogle   = !empty($_SESSION['reg_google']);
$prefillNama  = htmlspecialchars($_SESSION['reg_google']['nama']  ?? $_GET['nama']  ?? '');
$prefillEmail = htmlspecialchars($_SESSION['reg_google']['email'] ?? $_GET['email'] ?? '');

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']    ?? '');
    $email    = strtolower(trim($_POST['email']   ?? ''));
    $no_wa    = preg_replace('/[^0-9]/', '', $_POST['no_wa']   ?? '');
    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm_pw'] ?? '';
    $isGoogle = !empty($_POST['from_google']);

    // ── Validasi ──────────────────────────────────────────────
    if (strlen($nama) < 2)                      $errors[] = 'Nama minimal 2 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!preg_match('/^(08|628|62)\d{8,13}$/', $no_wa)) $errors[] = 'Nomor WhatsApp tidak valid (contoh: 081234567890).';

    if (!$isGoogle) {
        if (strlen($password) < 8)              $errors[] = 'Password minimal 8 karakter.';
        if ($password !== $confirm)             $errors[] = 'Konfirmasi password tidak cocok.';
    }

    // Normalisasi nomor WA
    if (str_starts_with($no_wa, '08')) $no_wa = '62' . substr($no_wa, 1);

    if (empty($errors)) {
        $userModel = new UserModel($pdo);

        if ($userModel->findByEmailHash($email)) {
            $errors[] = 'Email <strong>' . htmlspecialchars($email) . '</strong> sudah terdaftar. Silakan <a href="' . APP_URL . '/index.php" style="color:var(--primary)">login</a>.';
        } else {
            try {
                $userModel->createSelfRegistered([
                    'nama'     => $nama,
                    'email'    => $email,
                    'password' => $isGoogle ? null : $password,
                    'no_wa'    => $no_wa,
                    'role'     => 'user',
                    'is_active'=> 1,
                ]);

                $newUser = $userModel->findByEmailHash($email);
                $newUserId = $newUser ? $newUser['id'] : null;

                // Bersihkan session Google prefill
                unset($_SESSION['reg_google']);

                // Set session sukses dengan user_id untuk tawaran biometric
                $_SESSION['reg_success'] = [
                    'nama'    => $nama,
                    'email'   => $email,
                    'user_id' => $newUserId,
                ];
                header('Location: ' . APP_URL . '/auth/register-success.php');
                exit;

            } catch (\Exception $e) {
                $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Build Google OAuth URL (untuk opsi daftar via Google)
$regGoogleUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => APP_URL . '/auth/register-google-callback.php',
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
    <meta name="description" content="Daftarkan akun baru di <?= APP_NAME ?>">
    <title>Daftar Akun – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="icon" href="<?= APP_URL ?>/assets/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        .auth-container { max-width: 480px; }

        .input-wrap { position: relative; }
        .input-wrap .input-icon {
            position: absolute; left: .9rem; top: 50%;
            transform: translateY(-50%);
            width: 17px; height: 17px; color: var(--text-muted);
            pointer-events: none; stroke: currentColor; fill: none;
        }
        .input-wrap .form-control { padding-left: 2.6rem; }
        .toggle-pw {
            position: absolute; right: .75rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); padding: .2rem;
        }
        .toggle-pw:hover { color: var(--text); }
        .toggle-pw svg { width: 17px; height: 17px; stroke: currentColor; fill: none; }

        .google-prefill {
            background: rgba(16,185,129,.08);
            border: 1px solid rgba(16,185,129,.25);
            border-radius: 12px; padding: 1rem 1.1rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: flex-start; gap: .75rem;
        }
        .google-prefill svg { width: 18px; height: 18px; flex-shrink: 0; stroke: var(--success); fill: none; margin-top:.1rem }
        .google-prefill p { font-size: .83rem; color: #6EE7B7; line-height: 1.5; margin: 0; }
        .google-prefill strong { color: #fff; }

        .field-readonly .form-control {
            background: rgba(16,185,129,.06);
            border-color: rgba(16,185,129,.25);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .divider-or { display:flex;align-items:center;gap:.75rem;margin:1.25rem 0 }
        .divider-or::before,.divider-or::after { content:'';flex:1;height:1px;background:var(--border) }
        .divider-or span { font-size:.78rem;color:var(--text-muted);white-space:nowrap }

        .pw-strength { margin-top: .35rem; }
        .pw-bars { display: flex; gap: 3px; margin-top: .25rem; }
        .pw-bar {
            flex: 1; height: 3px; border-radius: 99px;
            background: var(--border); transition: background .3s;
        }
        .pw-bar.weak   { background: var(--danger); }
        .pw-bar.medium { background: var(--warning); }
        .pw-bar.strong { background: var(--success); }
        .pw-label { font-size: .68rem; color: var(--text-muted); margin-top: .2rem; }

        .auth-links { text-align:center;margin-top:1.1rem;font-size:.85rem;color:var(--text-muted) }
        .auth-links a { color:var(--primary);font-weight:600 }
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
                    <svg viewBox="0 0 40 40" fill="none">
                        <rect width="40" height="40" rx="12" fill="url(#g1)"/>
                        <path d="M20 12v8l4 4" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 20a8 8 0 1 0 8-8" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                        <defs><linearGradient id="g1" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6C63FF"/><stop offset="1" stop-color="#A855F7"/></linearGradient></defs>
                    </svg>
                </div>
                <h1>Buat Akun</h1>
                <p>Daftar untuk mengakses <?= APP_NAME ?></p>
            </div>

            <!-- Info prefill dari Google -->
            <?php if ($fromGoogle): ?>
            <div class="google-prefill">
                <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <p>Data dari akun Google Anda sudah diisi otomatis.<br>
                <strong><?= $prefillEmail ?></strong> — lengkapi nomor WhatsApp dan password untuk melanjutkan.</p>
            </div>
            <?php endif; ?>

            <!-- Error alerts -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" style="fill:currentColor;stroke:none;flex-shrink:0;width:18px;height:18px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div><?= implode('<br>', $errors) ?></div>
            </div>
            <?php endif; ?>

            <!-- Form Registrasi -->
            <form method="POST" id="reg-form" novalidate>
                <?php if ($fromGoogle): ?>
                <input type="hidden" name="from_google" value="1">
                <?php endif; ?>

                <!-- Nama Lengkap -->
                <div class="form-group">
                    <label for="reg-nama">Nama Lengkap</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="reg-nama" name="nama" class="form-control"
                               placeholder="Nama lengkap Anda"
                               value="<?= htmlspecialchars($_POST['nama'] ?? $prefillNama) ?>"
                               <?= $fromGoogle ? 'readonly' : '' ?>
                               autocomplete="name" required>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group <?= $fromGoogle ? 'field-readonly' : '' ?>">
                    <label for="reg-email">Alamat Email</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <input type="email" id="reg-email" name="email" class="form-control"
                               placeholder="nama@gmail.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? $prefillEmail) ?>"
                               <?= $fromGoogle ? 'readonly' : '' ?>
                               autocomplete="email" required>
                    </div>
                    <?php if (!$fromGoogle): ?>
                    <p class="form-hint">Gunakan email yang terhubung dengan akun Google Anda.</p>
                    <?php endif; ?>
                </div>

                <!-- Nomor WhatsApp -->
                <div class="form-group">
                    <label for="reg-wa">Nomor WhatsApp</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.59 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16v.92"/></svg>
                        <input type="tel" id="reg-wa" name="no_wa" class="form-control"
                               placeholder="081234567890"
                               value="<?= htmlspecialchars($_POST['no_wa'] ?? '') ?>"
                               autocomplete="tel" required>
                    </div>
                    <p class="form-hint">Digunakan untuk verifikasi OTP WhatsApp saat login.</p>
                </div>

                <!-- Password (tersembunyi jika dari Google) -->
                <?php if (!$fromGoogle): ?>
                <div class="form-group">
                    <label for="reg-pw">Password</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="reg-pw" name="password" class="form-control"
                               placeholder="Minimal 8 karakter"
                               autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('reg-pw', this)">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="pw-strength">
                        <div class="pw-bars">
                            <div class="pw-bar" id="bar1"></div>
                            <div class="pw-bar" id="bar2"></div>
                            <div class="pw-bar" id="bar3"></div>
                            <div class="pw-bar" id="bar4"></div>
                        </div>
                        <span class="pw-label" id="pw-label">Masukkan password</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-confirm">Konfirmasi Password</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                        <input type="password" id="reg-confirm" name="confirm_pw" class="form-control"
                               placeholder="Ulangi password"
                               autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('reg-confirm', this)">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-primary btn-full" id="btn-reg" style="margin-top:.5rem">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Buat Akun
                </button>
            </form>

            <?php if (!$fromGoogle): ?>
            <div class="divider-or"><span>atau daftar dengan</span></div>

            <a href="<?= htmlspecialchars($regGoogleUrl) ?>" class="btn-google" id="btn-google-reg">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Daftar dengan Google
            </a>
            <?php endif; ?>

            <div class="auth-links">
                Sudah punya akun? <a href="<?= APP_URL ?>/index.php">Masuk di sini</a>
            </div>
        </div>
    </main>

    <script>
    function togglePw(id, btn) {
        const inp = document.getElementById(id);
        const isHidden = inp.type === 'password';
        inp.type = isHidden ? 'text' : 'password';
        btn.querySelector('svg').innerHTML = isHidden
            ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
            : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }

    // Password strength
    const pwInput = document.getElementById('reg-pw');
    if (pwInput) {
        pwInput.addEventListener('input', () => {
            const v = pwInput.value;
            const bars = [1,2,3,4].map(i => document.getElementById('bar'+i));
            const label = document.getElementById('pw-label');
            bars.forEach(b => b.className = 'pw-bar');

            let score = 0;
            if (v.length >= 8)  score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            const cls  = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
            const lbl  = score <= 1 ? '⚠️ Lemah' : score <= 2 ? '🔶 Sedang' : score <= 3 ? '✅ Kuat' : '🔒 Sangat Kuat';
            for (let i = 0; i < score; i++) bars[i].classList.add(cls);
            label.textContent = v ? lbl : 'Masukkan password';
        });
    }

    // WA input — only digits
    document.getElementById('reg-wa')?.addEventListener('input', e => {
        e.target.value = e.target.value.replace(/[^0-9+]/g, '');
    });

    // Submit loading state
    document.getElementById('reg-form').addEventListener('submit', () => {
        const btn = document.getElementById('btn-reg');
        btn.disabled = true;
        btn.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="50" stroke-dashoffset="20"/></svg> Menyimpan...';
    });
    </script>
</body>
</html>
