<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/auth.php';

requireAdmin();

$userModel = new UserModel($pdo);
$message   = null;
$error     = null;

// Handle AJAX CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $currentId = (int)$_SESSION['user_id'];

    try {
        switch ($action) {
            case 'create':
                $id = $userModel->create([
                    'nama'      => trim($_POST['nama']),
                    'email'     => trim($_POST['email']),
                    'no_wa'     => trim($_POST['no_wa']),
                    'role'      => $_POST['role'],
                    'is_active' => (int)($_POST['is_active'] ?? 1),
                ], $currentId);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'User berhasil ditambahkan']);
                break;

            case 'update':
                $id = (int)$_POST['id'];
                if ($id === $currentId && $_POST['role'] !== 'admin') {
                    throw new Exception('Tidak dapat mengubah role diri sendiri.');
                }
                $userModel->update($id, [
                    'nama'      => trim($_POST['nama']),
                    'email'     => trim($_POST['email']),
                    'no_wa'     => trim($_POST['no_wa']),
                    'role'      => $_POST['role'],
                    'is_active' => (int)($_POST['is_active'] ?? 1),
                ], $currentId);
                echo json_encode(['success' => true, 'message' => 'User berhasil diperbarui']);
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                if ($id === $currentId) throw new Exception('Tidak dapat menghapus akun sendiri.');
                $userModel->delete($id, $currentId);
                echo json_encode(['success' => true, 'message' => 'User berhasil dinonaktifkan']);
                break;

            case 'reactivate':
                $id = (int)$_POST['id'];
                $userModel->reactivate($id, $currentId);
                echo json_encode(['success' => true, 'message' => 'User berhasil diaktifkan kembali']);
                break;

            case 'get':
                $id   = (int)$_POST['id'];
                $user = $userModel->findById($id);
                if (!$user) throw new Exception('User tidak ditemukan');
                unset($user['email_hash'], $user['google_id']); // jangan expose hash
                echo json_encode(['success' => true, 'data' => $user]);
                break;

            case 'audit':
                $id   = (int)$_POST['id'];
                $logs = $userModel->getAuditLog($id);
                echo json_encode(['success' => true, 'data' => $logs]);
                break;

            default:
                throw new Exception('Aksi tidak valid');
        }
    } catch (Exception $e) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$viewRole = $_GET['role'] ?? 'user';
if (!in_array($viewRole, ['admin', 'user'])) {
    $viewRole = 'user';
}

$users     = $userModel->getAll($viewRole);
$pageTitle = ($viewRole === 'admin') ? 'Pengelolaan Admin' : 'Pengelolaan User Biasa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title><?= $pageTitle ?> – <?= APP_NAME ?></title>
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="app-page">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/../partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-header">
                <div>
                    <h2><?= $pageTitle ?></h2>
                    <p>Total <strong id="total-users"><?= count($users) ?></strong> <?= $viewRole === 'admin' ? 'admin' : 'pengguna' ?> terdaftar</p>
                </div>
                <button class="btn-primary" id="btn-add-user" onclick="openModal('create')">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah <?= ucfirst($viewRole) ?>
                </button>
            </div>

            <!-- User Table -->
            <div id="user-management-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Daftar <?= ucfirst($viewRole) ?></h3>
                        <div class="card-actions">
                            <div class="search-box">
                                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                <input type="text" id="search-user" placeholder="Cari user...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body no-pad">
                        <div class="table-responsive">
                            <table class="data-table" id="user-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>No WhatsApp</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th>Diperbarui</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $i => $u): ?>
                                    <tr data-id="<?= $u['id'] ?>">
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-sm"><?= strtoupper(substr($u['nama'], 0, 1)) ?></div>
                                                <span><?= htmlspecialchars($u['nama']) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="text-mono text-sm"><?= htmlspecialchars($u['email']) ?></span></td>
                                        <td><?= htmlspecialchars($u['no_wa']) ?></td>
                                        <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                                        <td><span class="badge badge-<?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                                        <td><small><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></small></td>
                                        <td><small><?= date('d/m/Y H:i', strtotime($u['updated_at'])) ?></small></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon btn-edit" onclick="openModal('edit', <?= $u['id'] ?>)" title="Edit">
                                                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                </button>
                                                <button class="btn-icon btn-history" onclick="showAudit(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama']) ?>')" title="Riwayat">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                </button>
                                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                                    <?php if ($u['is_active']): ?>
                                                    <button class="btn-icon btn-delete" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama']) ?>')" title="Nonaktifkan">
                                                        <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                                    </button>
                                                    <?php else: ?>
                                                    <button class="btn-icon btn-reactivate" onclick="reactivateUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama']) ?>')" title="Aktifkan Kembali" style="color: var(--primary);">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v6h6"/><path d="M21 12A9 9 0 0 0 6 5.3L3 8"/><path d="M21 22v-6h-6"/><path d="M3 12a9 9 0 0 0 15 6.7l3-2.7"/></svg>
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Create/Edit User -->
    <div class="modal-backdrop" id="user-modal" style="display:none">
        <div class="modal glass-card">
            <div class="modal-header">
                <h3 id="modal-title">Tambah User</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <input type="hidden" id="form-user-id" name="id">
                    <input type="hidden" name="action" id="form-action" value="create">

                    <div class="form-group">
                        <label for="form-nama">Nama Lengkap</label>
                        <input type="text" id="form-nama" name="nama" class="form-control" required placeholder="Nama lengkap pengguna">
                    </div>
                    <div class="form-group">
                        <label for="form-email">Email <small>(akan dienkripsi SHA-512)</small></label>
                        <input type="email" id="form-email" name="email" class="form-control" required placeholder="email@domain.com">
                    </div>
                    <div class="form-group">
                        <label for="form-wa">Nomor WhatsApp</label>
                        <input type="text" id="form-wa" name="no_wa" class="form-control" required placeholder="628xxxxxxxxxx">
                        <small class="form-hint">Format: 628xxxxxxxxxx (tanpa +)</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="form-role">Role</label>
                            <select id="form-role" name="role" class="form-control">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="form-status">Status</label>
                            <select id="form-status" name="is_active" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Batal</button>
                <button class="btn-primary" id="btn-save-user" onclick="saveUser()">
                    <span>Simpan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Audit Log -->
    <div class="modal-backdrop" id="audit-modal" style="display:none">
        <div class="modal modal-lg glass-card">
            <div class="modal-header">
                <h3 id="audit-title">Riwayat Perubahan</h3>
                <button class="modal-close" onclick="document.getElementById('audit-modal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body" id="audit-body">
                <div class="loading-spinner"></div>
            </div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal-backdrop" id="delete-modal" style="display:none">
        <div class="modal modal-sm glass-card">
            <div class="modal-header">
                <h3>Konfirmasi Hapus</h3>
                <button class="modal-close" onclick="document.getElementById('delete-modal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirm-icon">
                    <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <p>Nonaktifkan user <strong id="delete-user-name"></strong>?</p>
                <small class="text-muted">User tidak akan dihapus permanen, hanya dinonaktifkan.</small>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="document.getElementById('delete-modal').style.display='none'">Batal</button>
                <button class="btn-danger" onclick="deleteUser()" id="btn-confirm-delete">Ya, Nonaktifkan</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    <script src="<?= APP_URL ?>/assets/js/users.js"></script>
    <script src="<?= APP_URL ?>/assets/js/biometric.js"></script>
    <script>
        function reactivateUser(id, nama) {
            if (confirm(`Apakah Anda yakin ingin mengaktifkan kembali akun ${nama}?`)) {
                // Gunakan validasi biometrik sebelum mengeksekusi aksi
                confirmActionWithBiometric('Aktifkan Kembali User', () => {
                    let formData = new FormData();
                    formData.append('action', 'reactivate');
                    formData.append('id', id);

                    fetch('', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            showToast(res.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast(res.message || 'Gagal mengaktifkan user', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showToast('Terjadi kesalahan server', 'error');
                    });
                });
            }
        }
    </script>
</body>
</html>
