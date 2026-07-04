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


$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']    ?? '');
    $email    = strtolower(trim($_POST['email']   ?? ''));
    $no_wa    = preg_replace('/[^0-9]/', '', $_POST['no_wa']   ?? '');


    // ── Validasi ──────────────────────────────────────────────
    if (strlen($nama) < 2)                      $errors[] = 'Nama minimal 2 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!preg_match('/^(08|628|62)\d{8,13}$/', $no_wa)) $errors[] = 'Nomor WhatsApp tidak valid (contoh: 081234567890).';

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
                    'password' => null,
                    'no_wa'    => $no_wa,
                    'role'     => 'admin',
                    'is_active'=> 1,
                ]);

                $newUser = $userModel->findByEmailHash($email);
                $newUserId = $newUser ? $newUser['id'] : null;


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


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <meta name="description" content="Daftarkan akun Admin baru di <?= APP_NAME ?>">
    <title>Daftar Admin – <?= APP_NAME ?></title>
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



        .divider-or { display:flex;align-items:center;gap:.75rem;margin:1.25rem 0 }
        .divider-or::before,.divider-or::after { content:'';flex:1;height:1px;background:var(--border) }
        .divider-or span { font-size:.78rem;color:var(--text-muted);white-space:nowrap }



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
                <h1>Buat Akun Admin</h1>
                <p>Daftar untuk mengelola <?= APP_NAME ?></p>
            </div>


            <!-- Error alerts -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" style="fill:currentColor;stroke:none;flex-shrink:0;width:18px;height:18px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div><?= implode('<br>', $errors) ?></div>
            </div>
            <?php endif; ?>

            <!-- Form Registrasi -->
            <form method="POST" id="reg-form" novalidate>

                <!-- Nama Lengkap -->
                <div class="form-group">
                    <label for="reg-nama">Nama Lengkap</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="reg-nama" name="nama" class="form-control"
                               placeholder="Nama lengkap Anda"
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                               autocomplete="name" required>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="reg-email">Alamat Email</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <input type="email" id="reg-email" name="email" class="form-control"
                               placeholder="nama@gmail.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               autocomplete="email" required>
                    </div>
                    <p class="form-hint">Gunakan email yang terhubung dengan akun Google Anda.</p>
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



                <button type="submit" class="btn-primary btn-full" id="btn-reg" style="margin-top:.5rem">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Buat Akun
                </button>
            </form>



            <div class="auth-links">
                Sudah punya akun? <a href="<?= APP_URL ?>/index.php">Masuk di sini</a>
            </div>
        </div>
    </main>

    <script>
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
