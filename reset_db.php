<?php
require_once 'config/database.php';

// ⚠️ Keamanan sederhana: hanya bisa diakses dari localhost
$allowedIPs = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    http_response_code(403);
    die('❌ Akses ditolak.');
}

// Konfirmasi via POST
$confirmed = ($_POST['confirm'] ?? '') === 'YES_RESET';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Database – UAS KTE</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f0f17; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #1a1a2e; border: 1px solid rgba(255,255,255,.1); border-radius: 16px; padding: 2.5rem; max-width: 520px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.5); }
        h1 { font-size: 1.5rem; font-weight: 800; color: #f87171; margin-bottom: .5rem; }
        .subtitle { color: #94a3b8; font-size: .875rem; margin-bottom: 2rem; }
        .warning-box { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-size: .85rem; line-height: 1.6; }
        .warning-box strong { color: #f87171; }
        .table-list { background: rgba(255,255,255,.04); border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.5rem; font-size: .82rem; }
        .table-list li { padding: .2rem 0; color: #94a3b8; list-style: none; }
        .table-list li::before { content: '🗑 '; }
        .btn-danger { width: 100%; padding: .85rem; background: linear-gradient(135deg, #dc2626, #991b1b); color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: opacity .2s; }
        .btn-danger:hover { opacity: .85; }
        .btn-cancel { display: block; text-align: center; margin-top: .85rem; color: #64748b; font-size: .85rem; text-decoration: none; }
        .btn-cancel:hover { color: #94a3b8; }
        .result { border-radius: 10px; padding: 1.25rem; margin-top: 1.5rem; font-size: .9rem; line-height: 1.8; }
        .result.success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); }
        .result.error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3); }
        .result code { font-family: monospace; font-size: .8rem; color: #a5f3fc; }
        .go-home { display: inline-block; margin-top: 1.25rem; padding: .65rem 1.5rem; background: linear-gradient(135deg, #6C63FF, #A855F7); color: #fff; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: .875rem; }
    </style>
</head>
<body>
<div class="card">

<?php if (!$confirmed): ?>

    <h1>⚠️ Reset Database Users</h1>
    <p class="subtitle">Semua data akan dihapus permanen dan tidak bisa dikembalikan.</p>

    <div class="warning-box">
        <strong>Tabel yang akan direset:</strong>
    </div>
    <ul class="table-list">
        <li>webauthn_credentials (biometric)</li>
        <li>user_audit (log perubahan)</li>
        <li>otp_log (log OTP)</li>
        <li>user_sessions (sesi login)</li>
        <li>users (semua pengguna)</li>
    </ul>

    <div class="warning-box">
        Setelah reset, hanya akan ada 1 akun admin seed:<br>
        <strong>Email:</strong> <code>admin@uaskte.local</code><br>
        <strong>No WA:</strong> <code>628123456789</code>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="YES_RESET">
        <button type="submit" class="btn-danger">🗑 Ya, Reset Semua Data</button>
    </form>
    <a href="index.php" class="btn-cancel">← Batal, kembali ke login</a>

<?php else: ?>

    <?php
    $log     = [];
    $success = true;

    try {
        // Nonaktifkan FK sementara agar truncate tidak error
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $tables = [
            'webauthn_credentials',
            'user_audit',
            'otp_log',
            'user_sessions',
            'users',
        ];

        foreach ($tables as $tbl) {
            $pdo->exec("TRUNCATE TABLE `$tbl`");
            $log[] = "✅ Tabel <strong>$tbl</strong> berhasil direset";
        }

        // Aktifkan kembali FK
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Seed admin
        $adminEmail = 'admin@uaskte.local';
        $emailHash  = hash('sha512', strtolower(trim($adminEmail)));
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, email_hash, no_wa, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)");
        $stmt->execute(['Administrator', $adminEmail, $emailHash, '628123456789']);
        $adminId = (int)$pdo->lastInsertId();

        // Catat di audit
        $pdo->prepare("INSERT INTO user_audit (user_id, action, field_changed, new_value, changed_by, ip_address)
                       VALUES (?, 'CREATE', 'system_reset', 'admin_seed', ?, ?)")
            ->execute([$adminId, $adminId, $_SERVER['REMOTE_ADDR']]);

        $log[] = "✅ Admin seed dibuat <strong>(ID: $adminId)</strong>";

    } catch (PDOException $e) {
        $success = false;
        $log[]   = "❌ Error: " . $e->getMessage();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    ?>

    <h1><?= $success ? '✅ Reset Berhasil' : '❌ Reset Gagal' ?></h1>
    <p class="subtitle"><?= $success ? 'Database telah direset dan admin seed telah dibuat.' : 'Terjadi kesalahan saat reset.' ?></p>

    <div class="result <?= $success ? 'success' : 'error' ?>">
        <?= implode('<br>', $log) ?>
    </div>

    <?php if ($success): ?>
    <div style="margin-top:1.25rem;padding:1rem;background:rgba(108,99,255,.1);border:1px solid rgba(108,99,255,.25);border-radius:10px;font-size:.82rem;line-height:1.7">
        <strong style="color:#a5b4fc">Info akun admin seed:</strong><br>
        Email : <code>admin@uaskte.local</code><br>
        No WA : <code>628123456789</code><br>
        Role  : <code>admin</code><br>
        <small style="color:#64748b">* Untuk login via Google SSO, update google_id di DB sesuai akun Google Anda.</small>
    </div>
    <?php endif; ?>

    <a href="index.php" class="go-home">→ Ke Halaman Login</a>

<?php endif; ?>

</div>
</body>
</html>
