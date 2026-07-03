<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../models/UserModel.php';

requireAdmin();
$pageTitle = 'Biometric Management';
$user = getCurrentUser();

// Cek credentials terdaftar untuk user ini
$creds = $pdo->prepare("SELECT * FROM webauthn_credentials WHERE user_id = ? ORDER BY created_at DESC");
$creds->execute([$user['id']]);
$myCredentials = $creds->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#6C63FF">
<title>Biometric – <?= APP_NAME ?></title>
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="app-page">
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
<?php include __DIR__ . '/../partials/topbar.php'; ?>
<div class="page-content">
  <div class="page-header">
    <div><h2>Manajemen Biometrik</h2><p>Daftarkan sidik jari / Face ID untuk keamanan halaman admin</p></div>
  </div>

  <div class="card" style="max-width:560px">
    <div class="card-header"><h3>Kredensial Terdaftar</h3></div>
    <div class="card-body">
      <?php if (empty($myCredentials)): ?>
      <div class="empty-state" style="padding:1.5rem">
        <svg viewBox="0 0 24 24"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0"/></svg>
        <p>Belum ada biometrik terdaftar</p>
      </div>
      <?php else: ?>
      <?php foreach ($myCredentials as $cr): ?>
      <div class="stat-card" style="margin-bottom:.75rem">
        <div class="stat-icon stat-purple">
          <svg viewBox="0 0 24 24"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-value" style="font-size:1rem"><?= htmlspecialchars($cr['device_type'] ?? 'Perangkat') ?></span>
          <span class="stat-label">Didaftarkan: <?= date('d/m/Y H:i', strtotime($cr['created_at'])) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <button class="btn-primary btn-full" id="btn-register-bio" onclick="registerBiometric()" style="margin-top:1rem">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Daftarkan Biometrik Baru
      </button>
      <div id="bio-reg-status" style="margin-top:.85rem;font-size:.875rem;text-align:center"></div>
    </div>
  </div>
</div>
</main>

<script>
async function registerBiometric() {
  const btn = document.getElementById('btn-register-bio');
  const status = document.getElementById('bio-reg-status');
  btn.disabled = true;
  status.innerHTML = '<span class="text-info">🔐 Memulai registrasi biometrik...</span>';

  try {
    const challenge = new Uint8Array(32);
    crypto.getRandomValues(challenge);

    const credential = await navigator.credentials.create({
      publicKey: {
        challenge,
        rp: { name: '<?= APP_NAME ?>', id: window.location.hostname },
        user: {
          id: new TextEncoder().encode('<?= $user['id'] ?>'),
          name: '<?= addslashes($user['email']) ?>',
          displayName: '<?= addslashes($user['nama']) ?>'
        },
        pubKeyCredParams: [
          { type: 'public-key', alg: -7 },
          { type: 'public-key', alg: -257 }
        ],
        authenticatorSelection: {
          authenticatorAttachment: 'platform',
          userVerification: 'required'
        },
        timeout: 60000,
        attestation: 'none'
      }
    });

    if (credential) {
      // Simpan credential id ke DB via AJAX
      const credId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));
      const res = await fetch('<?= APP_URL ?>/api/save-credential.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ credential_id: credId, device_type: navigator.platform || 'Unknown' })
      });
      const data = await res.json();
      if (data.success) {
        status.innerHTML = '<span class="text-success">✅ Biometrik berhasil didaftarkan!</span>';
        setTimeout(() => location.reload(), 1500);
      } else {
        status.innerHTML = `<span class="text-error">❌ ${data.message}</span>`;
      }
    }
  } catch(err) {
    status.innerHTML = `<span class="text-error">❌ ${err.message}</span>`;
  }
  btn.disabled = false;
}
</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
