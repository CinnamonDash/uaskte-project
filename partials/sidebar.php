<?php
$currentUser = $_SESSION['user_nama'] ?? 'User';
$currentRole = $_SESSION['role'] ?? 'user';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg viewBox="0 0 32 32" fill="none">
                <rect width="32" height="32" rx="10" fill="url(#sb-grad)"/>
                <path d="M10 16C10 12.68 12.68 10 16 10C19.32 10 22 12.68 22 16" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <path d="M16 13V16L18 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <defs><linearGradient id="sb-grad" x1="0" y1="0" x2="32" y2="32"><stop stop-color="#6C63FF"/><stop offset="1" stop-color="#A855F7"/></linearGradient></defs>
            </svg>
        </div>
        <span><?= APP_NAME ?></span>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= APP_URL ?>/dashboard.php" class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Dashboard</span>
        </a>

        <?php if ($currentRole === 'admin'): ?>
        <div class="nav-section">Administrator</div>
        <div class="nav-dropdown" style="cursor: pointer;">
            <a class="nav-item <?= strpos($currentPage, 'users') !== false ? 'active' : '' ?>" onclick="let menu = document.getElementById('user-dropdown'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none';">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Pengelolaan User</span>
                <svg viewBox="0 0 24 24" style="width:16px; height:16px; margin-left:auto; stroke:currentColor; stroke-width:2; fill:none;"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </a>
            <div id="user-dropdown" style="display: <?= strpos($currentPage, 'users') !== false ? 'block' : 'none' ?>; padding-left: 2rem; margin-top: 5px;">
                <a href="<?= APP_URL ?>/admin/users.php?role=admin" class="nav-item <?= (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'active' : '' ?>" style="min-height: 40px; margin-bottom: 5px; font-size: 0.9em;">
                    <svg viewBox="0 0 24 24" style="width:16px; height:16px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Admin</span>
                </a>
                <a href="<?= APP_URL ?>/admin/users.php?role=user" class="nav-item <?= (!isset($_GET['role']) || $_GET['role'] === 'user') && strpos($currentPage, 'users') !== false ? 'active' : '' ?>" style="min-height: 40px; font-size: 0.9em;">
                    <svg viewBox="0 0 24 24" style="width:16px; height:16px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>User Biasa</span>
                </a>
            </div>
        </div>
        <a href="<?= APP_URL ?>/admin/audit.php" class="nav-item <?= strpos($currentPage, 'audit') !== false ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Log Audit</span>
        </a>
        <a href="<?= APP_URL ?>/admin/biometric.php" class="nav-item <?= strpos($currentPage, 'biometric') !== false ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
            <span>Biometric</span>
        </a>
        <?php endif; ?>

        <div class="nav-section">Akun</div>
        <a href="<?= APP_URL ?>/profile.php" class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Profil Saya</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($currentUser, 0, 1)) ?></div>
            <div class="user-detail">
                <span class="user-name"><?= htmlspecialchars($currentUser) ?></span>
                <span class="user-role"><?= ucfirst($currentRole) ?></span>
            </div>
        </div>
        <a href="<?= APP_URL ?>/auth/logout.php" class="btn-logout" title="Logout">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
