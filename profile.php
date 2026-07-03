<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/models/UserModel.php';

requireLogin();
$user = getCurrentUser();
$pageTitle = 'Profil Saya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#6C63FF">
<title>Profil – <?= APP_NAME ?></title>
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="app-page">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main-content">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<div class="page-content">
  <div class="page-header"><h2>Profil Saya</h2></div>
  <div class="card" style="max-width:560px">
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1.75rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border)">
        <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:800;color:#fff;flex-shrink:0">
          <?= strtoupper(substr($user['nama'],0,1)) ?>
        </div>
        <div>
          <h3 style="font-size:1.15rem;font-weight:800"><?= htmlspecialchars($user['nama']) ?></h3>
          <p style="color:var(--text-muted);font-size:.875rem"><?= htmlspecialchars($user['email']) ?></p>
          <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
        </div>
      </div>

      <div class="form-group"><label>Nama</label><input class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" disabled></div>
      <div class="form-group"><label>Email <small>(terenkripsi SHA-512 di database)</small></label><input class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
      <div class="form-group"><label>Hash Email (SHA-512)</label>
        <textarea class="form-control" rows="3" disabled style="font-family:monospace;font-size:.7rem;resize:none"><?= hash('sha512', strtolower(trim($user['email']))) ?></textarea>
      </div>
      <div class="form-group"><label>No WhatsApp</label><input class="form-control" value="<?= htmlspecialchars($user['no_wa']) ?>" disabled></div>
      <div class="form-row">
        <div class="form-group"><label>Bergabung</label><input class="form-control" value="<?= date('d F Y', strtotime($user['created_at'])) ?>" disabled></div>
        <div class="form-group"><label>Update Terakhir</label><input class="form-control" value="<?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?>" disabled></div>
      </div>
    </div>
  </div>
</div>
</main>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
