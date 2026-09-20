<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireUser();

$voucherStmt = db()->prepare('SELECT amount, status, submitted_at, processed_at FROM cryptovouchers WHERE user_id = ? ORDER BY submitted_at DESC');
$voucherStmt->execute([(int) $user['id']]);
$vouchers = $voucherStmt->fetchAll();
$vipOverview = buildVipOverview($vouchers);
$depositedTotal = (int) ($vipOverview['deposited_total'] ?? 0);
$remainingLabel = (string) ($vipOverview['remaining_label'] ?? 'Kein aktiver VIP-Zugang');

$title = 'Dashboard';
$showSidebar = true;
$sidebarRole = 'user';
require __DIR__ . '/../includes/header.php';
?>
<section class="app-card hero-banner reveal-up mb-4">
    <div class="hero-media">
        <img src="<?= e($mirageHeaderImage) ?>" alt="Mirage VIP Header">
    </div>
    <div class="hero-overlay p-4 p-lg-5">
        <div class="hero-copy">
            <span class="cyber-chip">VIP Dashboard</span>
            <h1 class="hero-title mt-3 mb-3">Willkommen zurück, <?= e($user['name']) ?></h1>
            <p class="mb-0">Name, Telegram-ID, bestätigte Einzahlungen und verbleibende VIP-Laufzeit stehen für dich direkt auf einen Blick bereit.</p>
            <div class="hero-meta">
                <span class="cyber-chip">Telegram ID <?= e((string) $user['telegram_id']) ?></span>
                <span class="cyber-chip">Eingezahlt (bestätigt): <?= e((string) $depositedTotal) ?> €</span>
                <span class="cyber-chip">Laufzeit: <?= e($remainingLabel) ?></span>
            </div>
        </div>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="app-card p-4 h-100">
            <span class="cyber-chip">Profil</span>
            <h2 class="h4 mt-3">Dein Zugang</h2>
            <ul class="panel-list mt-4">
                <li><strong>Name</strong><span class="small-muted"><?= e($user['name']) ?></span></li>
                <li><strong>Telegram ID</strong><span class="small-muted"><?= e((string) $user['telegram_id']) ?></span></li>
                <li><strong>Eingezahlt (bestätigt)</strong><span class="small-muted"><?= e((string) $depositedTotal) ?> €</span></li>
                <li><strong>Verbleibende Laufzeit</strong><span class="small-muted"><?= e($remainingLabel) ?></span></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="app-card p-4 h-100">
            <span class="cyber-chip">3-Step Guide</span>
            <h2 class="h4 mt-3 mb-4">So bekommst du deinen VIP-Zugang</h2>

            <div class="step-flow">
                <article class="step-box">
                    <h3>1) Crypto Voucher Code kaufen</h3>
                    <p class="text-secondary">Nach Bekanntheitsgrad sortiert. Nutze den Filter nach Zahlungsmethode:</p>
                    <div data-payment-scope>
                    <div class="mb-3">
                        <label class="form-label" for="dashboard_payment_filter">Zahlungsmethode filtern</label>
                        <select class="form-select" id="dashboard_payment_filter" data-payment-filter>
                            <option value="all">Alle Zahlungsmethoden</option>
                            <option value="paypal">PayPal</option>
                            <option value="apple-pay">Apple Pay</option>
                            <option value="klarna">Klarna</option>
                            <option value="paysafecard">Paysafecard</option>
                            <option value="visa-mastercard">Visa/Mastercard</option>
                        </select>
                    </div>
                    <div class="voucher-shops">
                        <?php foreach (voucherShopProviders() as $provider): ?>
                            <div class="voucher-shop" data-payment-card data-methods="<?= e(implode(',', $provider['methods'])) ?>">
                                <h4><a href="<?= e($provider['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($provider['name']) ?></a></h4>
                                <p class="mb-2"><?= e($provider['description']) ?></p>
                                <details><summary>Akzeptierte Zahlungsmethoden</summary><p class="mb-0 mt-2"><?= e($provider['methods_label']) ?></p></details>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-secondary mt-3 mb-0">Hinweis für Einreichung im Dashboard: unterstützt sind aktuell nur 50 €, 75 € und 150 €.</p>
                </div>
                </article>

                <div class="step-arrow">↓</div>

                <article class="step-box">
                    <h3>2) Voucher Code einreichen</h3>
                    <p class="text-secondary mb-2">Reiche deinen Code über die Voucher-Seite ein und verfolge den Status:</p>
                    <ul class="panel-list">
                        <?php foreach (voucherStatusMeta() as $statusCode => $statusConfig): ?>
                            <li>
                                <strong><?= e(voucherStatusLabel($statusCode)) ?></strong>
                                <span class="small-muted"><?= e((string) ($statusConfig['description'] ?? '')) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-secondary mt-3 mb-3">
                        VIP-Zugang je bestätigtem Voucher:
                        <?= e(implode(', ', voucherAccessRuleLines())) ?>.
                    </p>
                    <a class="btn btn-primary" href="<?= e(appUrl('/dashboard/vouchers')) ?>">Zur Voucher-Seite</a>
                </article>

                <div class="step-arrow">↓</div>

                <article class="step-box">
                    <h3>3) VIP Einladung erhalten</h3>
                    <p class="text-secondary mb-0">Nach erfolgreicher Bestätigung wird dein Zugang aktiviert. Danach kannst du die VIP-Unterhaltung genießen.</p>
                </article>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
