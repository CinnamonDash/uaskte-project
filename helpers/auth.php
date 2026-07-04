<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['user_id']) || empty($_SESSION['otp_verified'])) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['otp_verified']);
}

function getCurrentUser(): ?array {
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    $model = new UserModel($pdo);
    return $model->findById((int)$_SESSION['user_id']);
}

function setUserSession(array $user): void {
    $_SESSION['user_id']             = $user['id'];
    $_SESSION['user_nama']           = $user['nama'];
    $_SESSION['user_email']          = $user['email'];
    $_SESSION['role']                = $user['role'];
    $_SESSION['otp_verified']        = false;
    $_SESSION['biometric_verified']  = false;
}

function logout(): void {
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    header('Location: ' . APP_URL . '/index.php');
    exit;
}
