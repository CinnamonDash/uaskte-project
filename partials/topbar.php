<?php
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<header class="topbar">
    <button class="topbar-menu-btn" onclick="toggleSidebar()" aria-label="Menu">
        <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="topbar-breadcrumb">
        <span class="breadcrumb-app"><?= APP_NAME ?></span>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-page"><?= htmlspecialchars($pageTitle) ?></span>
    </div>

    <div class="topbar-actions">
        <div class="topbar-user">
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'] ?? 'U', 0, 1)) ?></div>
            <div class="topbar-user-info">
                <span><?= htmlspecialchars($_SESSION['user_nama'] ?? '') ?></span>
                <small><?= ucfirst($_SESSION['role'] ?? '') ?></small>
            </div>
        </div>
    </div>
</header>
