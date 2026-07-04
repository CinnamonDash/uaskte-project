<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireAdmin();

$logs = $pdo->query("
    SELECT ua.*, u.nama as user_nama, u.email as user_email, cb.nama as changer_nama
    FROM user_audit ua
    LEFT JOIN users u ON u.id = ua.user_id
    LEFT JOIN users cb ON cb.id = ua.changed_by
    ORDER BY ua.changed_at DESC
    LIMIT 500
")->fetchAll();

$pageTitle = 'Log Audit';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Log Audit – <?= APP_NAME ?></title>
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
                <div>
                    <h2>Log Audit Perubahan</h2>
                    <p>Rekam jejak setiap perubahan pada tabel user</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Riwayat Lengkap</h3>
                    <div class="card-actions">
                        <div class="search-box">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <input type="text" id="search-audit" placeholder="Filter log...">
                        </div>
                    </div>
                </div>
                <div class="card-body no-pad">
                    <div class="table-responsive">
                        <table class="data-table" id="audit-table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Aksi</th>
                                    <th>Field</th>
                                    <th>Nilai Lama</th>
                                    <th>Nilai Baru</th>
                                    <th>Oleh</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><span class="text-mono text-sm"><?= date('d/m/Y H:i:s', strtotime($log['changed_at'])) ?></span></td>
                                    <td>
                                        <span><?= htmlspecialchars($log['user_nama'] ?? '-') ?></span>
                                        <br><small class="text-muted"><?= htmlspecialchars($log['user_email'] ?? '') ?></small>
                                    </td>
                                    <td><span class="badge badge-<?= strtolower($log['action']) ?>"><?= $log['action'] ?></span></td>
                                    <td><code><?= htmlspecialchars($log['field_changed'] ?? '-') ?></code></td>
                                    <td><span class="text-old"><?= htmlspecialchars($log['old_value'] ?? '-') ?></span></td>
                                    <td><span class="text-new"><?= htmlspecialchars($log['new_value'] ?? '-') ?></span></td>
                                    <td><?= htmlspecialchars($log['changer_nama'] ?? 'System') ?></td>
                                    <td><span class="text-mono text-sm"><?= htmlspecialchars($log['ip_address'] ?? '') ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    <script>
        document.getElementById('search-audit').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#audit-table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
