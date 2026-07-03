<?php
require_once 'config/database.php';

$sql = "
-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_hash VARCHAR(128) NOT NULL COMMENT 'SHA-512 dari email',
    password_hash VARCHAR(255) NULL COMMENT 'bcrypt password (null jika hanya SSO)',
    no_wa VARCHAR(20) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    google_id VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    UNIQUE KEY uq_email_hash (email_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel otp_log
CREATE TABLE IF NOT EXISTS otp_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expired_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel user_audit (log perubahan)
CREATE TABLE IF NOT EXISTS user_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action ENUM('CREATE','UPDATE','DELETE') NOT NULL,
    field_changed VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_by INT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel webauthn_credentials (biometric)
CREATE TABLE IF NOT EXISTS webauthn_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    credential_id TEXT NOT NULL,
    public_key TEXT NOT NULL,
    counter INT DEFAULT 0,
    device_type VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    google_token TEXT NULL,
    otp_verified TINYINT(1) DEFAULT 0,
    biometric_verified TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    // Migrasi: tambah kolom password_hash jika belum ada
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL COMMENT 'bcrypt password' AFTER email_hash");
        echo "✅ Kolom password_hash ditambahkan<br>";
    } catch (PDOException $e) {
        // Kolom sudah ada, abaikan
    }

    // Seed admin user jika belum ada
    $adminEmail = 'admin@uaskte.local';
    $emailHash = hash('sha512', strtolower(trim($adminEmail)));
    $check = $pdo->prepare("SELECT id FROM users WHERE email_hash = ?");
    $check->execute([$emailHash]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, email_hash, no_wa, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute(['Administrator', $adminEmail, $emailHash, '628123456789']);
        echo "✅ Admin seed berhasil<br>";
    }

    echo "✅ Database setup selesai!<br>";
    echo "<a href='index.php'>Ke Halaman Login</a>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
