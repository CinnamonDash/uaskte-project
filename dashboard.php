<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/helpers/auth.php';

requireLogin();
$user = getCurrentUser();
if ($user['role'] === 'user') {
    header('Location: ' . APP_URL . '/user/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Dashboard – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body class="app-page">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-header">
                <h2>Dashboard</h2>
                <p>Selamat datang kembali, <strong><?= htmlspecialchars($user['nama']) ?></strong></p>
            </div>


                <!-- ============================================== -->
                <!-- TAMPILAN DASHBOARD ADMIN                       -->
                <!-- ============================================== -->
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <?php
                    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
                    $totalAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
                    $todayChanges = $pdo->query("SELECT COUNT(*) FROM user_audit WHERE DATE(changed_at)=CURDATE()")->fetchColumn();
                    $totalOtp = $pdo->query("SELECT COUNT(*) FROM otp_log WHERE DATE(created_at)=CURDATE()")->fetchColumn();
                    ?>
                    <div class="stat-card">
                        <div class="stat-icon stat-blue">
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $totalUsers ?></span>
                            <span class="stat-label">Total User Aktif</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-purple">
                            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $totalAdmin ?></span>
                            <span class="stat-label">Administrator</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-green">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1-1-4 9.5-9.5z"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $todayChanges ?></span>
                            <span class="stat-label">Perubahan Hari Ini</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon stat-orange">
                            <svg viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $totalOtp ?></span>
                            <span class="stat-label">Login OTP Hari Ini</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Audit Log -->
                <div class="card">
                    <div class="card-header">
                        <h3>Aktivitas Terbaru</h3>
                        <a href="<?= APP_URL ?>/admin/users.php" class="btn-sm btn-primary-sm">Kelola User</a>
                    </div>
                    <div class="card-body no-pad">
                        <?php
                        $logs = $pdo->query("
                            SELECT ua.*, u.nama as user_nama, cb.nama as changer_nama
                            FROM user_audit ua
                            LEFT JOIN users u ON u.id = ua.user_id
                            LEFT JOIN users cb ON cb.id = ua.changed_by
                            ORDER BY ua.changed_at DESC LIMIT 10
                        ")->fetchAll();
                        ?>
                        <?php if (empty($logs)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p>Belum ada aktivitas</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>Field</th>
                                        <th>Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><span class="text-mono text-sm"><?= date('d/m/Y H:i:s', strtotime($log['changed_at'])) ?></span></td>
                                        <td><?= htmlspecialchars($log['user_nama'] ?? '-') ?></td>
                                        <td><span class="badge badge-<?= strtolower($log['action']) ?>"><?= $log['action'] ?></span></td>
                                        <td><?= htmlspecialchars($log['field_changed'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($log['changer_nama'] ?? 'System') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

        </div>
    </main>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
