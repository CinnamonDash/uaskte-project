<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'user') {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Dashboard User – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body class="app-page">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-header">
                <h2>Dashboard User</h2>
                <p>Selamat datang kembali, <strong><?= htmlspecialchars($user['nama']) ?></strong></p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Informasi Akun</h3>
                </div>
                <div class="card-body">
                    <p>Halo <strong><?= htmlspecialchars($user['nama']) ?></strong>, Anda login sebagai Pengguna Biasa.</p>
                    <p>Gunakan menu di sebelah kiri untuk melihat profil Anda.</p>
                    
                    <div style="margin-top: 20px;">
                        <a href="<?= APP_URL ?>/profile.php" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Lihat Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
