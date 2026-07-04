<?php
require_once __DIR__ . '/../config/database.php';

class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Hash email dengan SHA-512
    public static function hashEmail(string $email): string {
        return hash('sha512', strtolower(trim($email)));
    }

    // Cari user berdasarkan email hash
    public function findByEmailHash(string $email): ?array {
        $hash = self::hashEmail($email);
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email_hash = ? AND is_active = 1");
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    // Cari user berdasarkan Google ID
    public function findByGoogleId(string $googleId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? AND is_active = 1");
        $stmt->execute([$googleId]);
        return $stmt->fetch() ?: null;
    }

    // Cari user berdasarkan ID
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Update Google ID
    public function updateGoogleId(int $userId, string $googleId): void {
        $stmt = $this->pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        $stmt->execute([$googleId, $userId]);
    }

    // Ambil semua user (bisa difilter berdasarkan role)
    public function getAll(?string $role = null): array {
        if ($role) {
            $stmt = $this->pdo->prepare("SELECT id, nama, email, no_wa, role, is_active, created_at, updated_at FROM users WHERE role = ? ORDER BY id DESC");
            $stmt->execute([$role]);
            return $stmt->fetchAll();
        } else {
            $stmt = $this->pdo->query("SELECT id, nama, email, no_wa, role, is_active, created_at, updated_at FROM users ORDER BY id DESC");
            return $stmt->fetchAll();
        }
    }

    // Buat user baru (oleh admin)
    public function create(array $data, int $createdBy): int {
        $emailHash = self::hashEmail($data['email']);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (nama, email, email_hash, no_wa, role, is_active, created_by, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nama'],
            $data['email'],
            $emailHash,
            $data['no_wa'],
            $data['role'] ?? 'user',
            $data['is_active'] ?? 1,
            $createdBy,
            $createdBy
        ]);
        $newId = (int)$this->pdo->lastInsertId();
        $this->audit($newId, 'CREATE', null, null, null, $createdBy);
        return $newId;
    }

    // Registrasi mandiri (self-register)
    public function createSelfRegistered(array $data): int {
        $emailHash    = self::hashEmail($data['email']);
        $passwordHash = !empty($data['password'])
            ? password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12])
            : null;

        $stmt = $this->pdo->prepare("
            INSERT INTO users (nama, email, email_hash, password_hash, no_wa, role, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nama'],
            strtolower(trim($data['email'])),
            $emailHash,
            $passwordHash,
            $data['no_wa'],
            $data['role']     ?? 'user',
            $data['is_active'] ?? 1,
        ]);
        $newId = (int)$this->pdo->lastInsertId();
        $this->audit($newId, 'CREATE', 'self_register', null, $data['email'], null);
        return $newId;
    }

    // Verifikasi password (untuk login email jika diperlukan)
    public function verifyPassword(string $email, string $password): ?array {
        $hash = self::hashEmail($email);
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email_hash = ? AND is_active = 1");
        $stmt->execute([$hash]);
        $user = $stmt->fetch() ?: null;
        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    // Update user
    public function update(int $id, array $data, int $updatedBy): bool {
        $old = $this->findById($id);
        if (!$old) return false;

        $emailHash = self::hashEmail($data['email']);
        $stmt = $this->pdo->prepare("
            UPDATE users SET nama=?, email=?, email_hash=?, no_wa=?, role=?, is_active=?, updated_by=?
            WHERE id=?
        ");
        $result = $stmt->execute([
            $data['nama'],
            $data['email'],
            $emailHash,
            $data['no_wa'],
            $data['role'],
            $data['is_active'],
            $updatedBy,
            $id
        ]);

        // Catat setiap field yang berubah
        $fields = ['nama', 'email', 'no_wa', 'role', 'is_active'];
        foreach ($fields as $field) {
            $oldVal = $old[$field] ?? '';
            $newVal = $data[$field] ?? '';
            if ($field === 'email') $newVal = strtolower(trim($newVal));
            if ((string)$oldVal !== (string)$newVal) {
                $this->audit($id, 'UPDATE', $field, $oldVal, $newVal, $updatedBy);
            }
        }
        return $result;
    }

    // Hapus user (soft delete)
    public function delete(int $id, int $deletedBy): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = 0, updated_by = ? WHERE id = ?");
        $result = $stmt->execute([$deletedBy, $id]);
        $this->audit($id, 'DELETE', 'is_active', '1', '0', $deletedBy);
        return $result;
    }

    // Aktifkan kembali user (undo soft delete)
    public function reactivate(int $id, int $updatedBy): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1, updated_by = ? WHERE id = ?");
        $result = $stmt->execute([$updatedBy, $id]);
        $this->audit($id, 'UPDATE', 'is_active', '0', '1', $updatedBy);
        return $result;
    }

    // Catat audit log
    private function audit(int $userId, string $action, ?string $field, ?string $oldVal, ?string $newVal, ?int $changedBy): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_audit (user_id, action, field_changed, old_value, new_value, changed_by, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $action, $field, $oldVal, $newVal, $changedBy,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    // Ambil audit log untuk user tertentu
    public function getAuditLog(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT ua.*, u.nama as changed_by_name
            FROM user_audit ua
            LEFT JOIN users u ON u.id = ua.changed_by
            WHERE ua.user_id = ?
            ORDER BY ua.changed_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
