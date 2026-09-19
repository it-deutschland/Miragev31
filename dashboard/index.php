<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = requireUser();
$title = 'Dashboard';
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
            <p class="mb-0">Dein Zugang und der Voucher-Ablauf bleiben unverändert, werden nun aber in einer futuristischen Mirage-Oberfläche mit mehr Tiefe, Licht und Bewegung präsentiert.</p>
            <div class="hero-meta">
                <span class="cyber-chip">@<?= e((string)($user['telegram_username'] ?? 'guest')) ?></span>
                <span class="cyber-chip">Telegram ID <?= e((string) $user['telegram_id']) ?></span>
            </div>
        </div>
    </div>
</section>

<div class="row g-4 dashboard-grid">
    <div class="col-lg-5">
        <div class="app-card p-4 fade-in h-100 fx-tilt" data-tilt-card>
            <span class="cyber-chip">Profil</span>
            <h2 class="h4 mt-3">Dein Zugang</h2>
            <ul class="panel-list mt-4">
                <li><strong>Name</strong><span class="small-muted"><?= e($user['name']) ?></span></li>
                <li><strong>Username</strong><span class="small-muted">@<?= e($user['telegram_username'] ?? '-') ?></span></li>
                <li><strong>Telegram ID</strong><span class="small-muted"><?= e((string) $user['telegram_id']) ?></span></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="app-card p-4 fade-in h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <span class="cyber-chip">Voucher Flow</span>
                    <h2 class="h4 mt-3 mb-0">Cryptovoucher einlösen</h2>
                </div>
                <div class="feature-stack">
                    <span class="cyber-chip">Secure Submit</span>
                    <span class="cyber-chip">Instant Review</span>
                </div>
            </div>
            <p class="text-secondary">Wählen Sie Ihren Betrag und reichen Sie den Code wie gewohnt ein.</p>
            <form method="post" action="<?= e(appUrl('/payment/voucher')) ?>">
                <?= csrfField('voucher_submit') ?>
                <div class="mb-3">
                    <label class="form-label">Betrag</label>
                    <div class="amount-grid">
                        <?php foreach (ALLOWED_AMOUNTS as $amount): ?>
                            <?php $amountId = 'amount_' . $amount; ?>
                            <input class="amount-input" type="radio" id="<?= e($amountId) ?>" name="amount" value="<?= e((string)$amount) ?>" required>
                            <label class="amount-card" for="<?= e($amountId) ?>">
                                <span><?= e((string)$amount) ?> €</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="voucher_code">Cryptovoucher Code</label>
                    <input class="form-control" id="voucher_code" name="voucher_code" maxlength="255" minlength="6" required placeholder="Voucher Code eingeben">
                </div>
                <button class="btn btn-primary" type="submit">Voucher einreichen</button>
            </form>
            <div class="muted-divider my-4"></div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="cyber-chip">Support</span>
                    <p class="text-secondary mb-0 mt-2">Hast du Fragen? Erstelle und verwalte deine Tickets direkt im Dashboard.</p>
                </div>
                <a class="btn btn-outline-info" href="<?= e(appUrl('/dashboard/tickets')) ?>">Zum Ticket-Center</a>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>