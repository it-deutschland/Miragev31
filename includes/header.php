<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/permissions.php';

$title = $title ?? 'Mirage Projekt';
$admin = $admin ?? null;
$user = $user ?? null;
$showSidebar = $showSidebar ?? false;
$sidebarRole = $sidebarRole ?? (is_array($admin) ? 'admin' : (is_array($user) ? 'user' : null));
$layoutHasSidebar = $showSidebar && in_array($sidebarRole, ['admin', 'user'], true);
$mirageHeaderImage = $mirageHeaderImage ?? appUrl('/assets/img/mirage-vip-header.svg');
$mirageLogoImage = $mirageLogoImage ?? appUrl('/assets/img/mirage-vip-logo.svg');
?><!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(appUrl('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="app-dark<?= $layoutHasSidebar ? ' app-admin-layout' : ' app-public-layout' ?>">
<div class="app-chrome">
    <div class="app-grid"></div>
<?php if ($layoutHasSidebar): ?>
<div class="d-flex min-vh-100">
    <aside class="sidebar p-3">
        <div class="brand-panel mb-4">
            <img class="brand-logo" src="<?= e($mirageLogoImage) ?>" alt="Mirage VIP Logo">
            <div>
                <?php if ($sidebarRole === 'admin'): ?>
                    <div class="eyebrow">Control Nexus</div>
                    <h4 class="mb-1">Mirage Admin</h4>
                    <p class="brand-subtitle mb-0">Telegram VIP Access</p>
                <?php else: ?>
                    <div class="eyebrow">VIP Lounge</div>
                    <h4 class="mb-1">User Dashboard</h4>
                    <p class="brand-subtitle mb-0"><?= e((string) ($user['name'] ?? 'Mirage User')) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <nav class="nav flex-column gap-2">
            <?php if ($sidebarRole === 'admin'): ?>
                <a class="nav-link" href="<?= e(appUrl('/admin/dashboard')) ?>">Dashboard</a>
                <a class="nav-link" href="<?= e(appUrl('/admin/vouchers')) ?>">Voucher</a>
                <?php if (canProcessTickets($admin)): ?>
                    <a class="nav-link" href="<?= e(appUrl('/admin/tickets')) ?>">Tickets</a>
                <?php endif; ?>
                <?php if ((int)$admin['rank'] === 3): ?>
                    <a class="nav-link" href="<?= e(appUrl('/admin/users')) ?>">Benutzer</a>
                    <a class="nav-link" href="<?= e(appUrl('/admin/logs')) ?>">Logs</a>
                    <a class="nav-link" href="<?= e(appUrl('/admin/admins')) ?>">Admins</a>
                    <a class="nav-link" href="<?= e(appUrl('/admin/edit')) ?>">Audit Edit</a>
                <?php endif; ?>
                <form method="post" action="<?= e(appUrl('/admin/logout')) ?>">
                    <?php require_once __DIR__ . '/csrf.php'; ?>
                    <?= csrfField('admin_logout') ?>
                    <button class="btn btn-outline-danger w-100 mt-3" type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a class="nav-link" href="<?= e(appUrl('/dashboard')) ?>">Dashboard Start</a>
                <a class="nav-link" href="<?= e(appUrl('/dashboard/vouchers')) ?>">Crypto Voucher einreichen & kaufen</a>
                <a class="nav-link" href="<?= e(appUrl('/dashboard/tickets')) ?>">Support Tickets</a>
            <?php endif; ?>
        </nav>
    </aside>
    <main class="flex-grow-1 p-4">
<?php else: ?>
<header class="topbar container py-3">
    <div class="brand-panel brand-panel--compact">
        <img class="brand-logo" src="<?= e($mirageLogoImage) ?>" alt="Mirage VIP Logo">
        <div>
            <div class="eyebrow">Telegram First</div>
            <h4 class="mb-1">Mirage VIP</h4>
            <p class="brand-subtitle mb-0">Private Access Lounge</p>
        </div>
    </div>
    <div class="topbar-actions">
        <span class="cyber-chip">Secure Login</span>
        <span class="cyber-chip">Premium Flow</span>
    </div>
</header>
<main class="container py-4">
<?php endif; ?>