<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

$admin = requireAdmin();
requireActiveAdmin($admin);
$showSidebar = true;
$title = 'Admin Dashboard';

$stats = [
    'pending' => 0,
    'proofed' => 0,
    'invalid' => 0,
    'users' => 0,
];

foreach (['pending', 'proofed', 'invalid'] as $status) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM cryptovouchers WHERE status = ?');
    $stmt->execute([$status]);
    $stats[$status] = (int) $stmt->fetchColumn();
}
$stats['users'] = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();

require __DIR__ . '/../includes/header.php';
?>
<section class="app-card hero-banner reveal-up mb-4">
    <div class="hero-media">
        <img src="<?= e($mirageHeaderImage) ?>" alt="Mirage VIP Header">
    </div>
    <div class="hero-overlay p-4 p-lg-5">
        <div class="hero-copy">
            <span class="cyber-chip">Admin Command</span>
            <h1 class="hero-title mt-3 mb-3">Mirage Control Deck</h1>
            <p class="mb-0">Alle bestehenden Admin-Funktionen bleiben identisch – das Dashboard erhält jedoch einen deutlich luxuriöseren Cyberpunk-Look mit Neonflächen, Motion und mehr visueller Priorisierung.</p>
            <div class="hero-meta">
                <span class="cyber-chip">Live Voucher Queue</span>
                <span class="cyber-chip">Premium Backoffice</span>
                <span class="cyber-chip">Telegram Operations</span>
            </div>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-md-6 col-xl-3">
        <div class="app-card stat-card reveal-up">
            <div class="stat-icon">⌛</div>
            <div class="small text-secondary">Pending</div>
            <div class="display-6"><?= e((string)$stats['pending']) ?></div>
            <div class="small-muted">Offene Voucher in der Warteschlange</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="app-card stat-card reveal-up">
            <div class="stat-icon">✓</div>
            <div class="small text-secondary">Proofed</div>
            <div class="display-6 text-success"><?= e((string)$stats['proofed']) ?></div>
            <div class="small-muted">Freigegebene Einreichungen</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="app-card stat-card reveal-up">
            <div class="stat-icon">✕</div>
            <div class="small text-secondary">Invalid</div>
            <div class="display-6 text-danger"><?= e((string)$stats['invalid']) ?></div>
            <div class="small-muted">Abgelehnte oder fehlerhafte Codes</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="app-card stat-card reveal-up">
            <div class="stat-icon">👥</div>
            <div class="small text-secondary">Users</div>
            <div class="display-6 text-info"><?= e((string)$stats['users']) ?></div>
            <div class="small-muted">Telegram Nutzer im System</div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="app-card p-4 h-100 reveal-up">
            <span class="cyber-chip">Workflow</span>
            <h2 class="h4 mt-3">Operativer Fokus</h2>
            <ul class="panel-list mt-4">
                <li><strong>Voucher prüfen</strong><span class="small-muted">Direkte Sicht auf offene Vorgänge und deren Status.</span></li>
                <li><strong>Nutzer im Blick</strong><span class="small-muted">Klare Priorisierung der aktiven Telegram-Basis.</span></li>
                <li><strong>Admin Backoffice</strong><span class="small-muted">Navigationsbereich und Stat-Kacheln wirken deutlich hochwertiger.</span></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="app-card p-4 h-100 reveal-up">
            <span class="cyber-chip">Visual Upgrade</span>
            <h2 class="h4 mt-3">Neon Signals</h2>
            <p class="text-secondary mb-4">Das Admin Dashboard setzt nun auf kräftige violette Verläufe, lebendige Schatten, animierte Oberflächen und eine stärkere VIP-Anmutung passend zum Mirage Branding.</p>
            <div class="feature-stack">
                <span class="cyber-chip">Glass Surface</span>
                <span class="cyber-chip">Animated Glow</span>
                <span class="cyber-chip">Luxury Motion</span>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
