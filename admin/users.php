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

$users    = $userModel->getAll();
$pageTitle = 'Pengelolaan User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <title>Pengelolaan User – <?= APP_NAME ?></title>
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
                    <h2>Pengelolaan User</h2>
                    <p>Total <strong id="total-users"><?= count($users) ?></strong> pengguna terdaftar</p>
                </div>
                <button class="btn-primary" id="btn-add-user" onclick="openModal('create')">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah User
                </button>
            </div>

            <!-- Biometric Gate Overlay -->
            <div id="biometric-gate" class="biometric-gate">
                <div class="biometric-card glass-card">
                    <div class="biometric-icon">
                        <svg viewBox="0 0 60 60" fill="none">
                            <circle cx="30" cy="30" r="28" stroke="url(#bio-grad)" stroke-width="3" stroke-dasharray="8 4"/>
                            <path d="M30 18C30 18 20 22 20 30C20 38 30 42 30 42C30 42 40 38 40 30C40 22 30 18 30 18Z" stroke="url(#bio-grad)" stroke-width="2.5" stroke-linejoin="round"/>
                            <circle cx="30" cy="30" r="4" fill="url(#bio-grad)"/>
                            <path d="M24 30C24 26.69 26.69 24 30 24" stroke="url(#bio-grad)" stroke-width="2" stroke-linecap="round"/>
                            <defs><linearGradient id="bio-grad" x1="0" y1="0" x2="60" y2="60"><stop stop-color="#6C63FF"/><stop offset="1" stop-color="#A855F7"/></linearGradient></defs>
                        </svg>
                        <div class="biometric-pulse"></div>
                    </div>
                    <h3>Verifikasi Biometrik</h3>
                    <p>Halaman ini memerlukan verifikasi biometrik untuk akses administrator</p>
                    <button class="btn-primary" id="btn-biometric-verify" onclick="verifyBiometric()">
                        <svg viewBox="0 0 24 24"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0"/></svg>
                        Verifikasi Sekarang
                    </button>
                    <p class="biometric-hint">Gunakan sidik jari atau Face ID perangkat Anda</p>
                    <div id="biometric-status" class="biometric-status"></div>
                </div>
            </div>

            <!-- User Table (hidden until biometric verified) -->
            <div id="user-management-content" class="hidden">
                <div class="card">
                    <div class="card-header">
                        <h3>Daftar Pengguna</h3>
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
                                                    <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                                </button>
                                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                                <button class="btn-icon btn-delete" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama']) ?>')" title="Hapus">
                                                    <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                                </button>
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
</body>
</html>
