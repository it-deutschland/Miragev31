<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = requireUser();

$voucherStmt = db()->prepare('SELECT id, amount, status, submitted_at, processed_at FROM cryptovouchers WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 100');
$voucherStmt->execute([(int) $user['id']]);
$vouchers = $voucherStmt->fetchAll();

$vipOverview = buildVipOverview($vouchers);
$depositedTotal = (int) ($vipOverview['deposited_total'] ?? 0);
$remainingLabel = (string) ($vipOverview['remaining_label'] ?? 'Kein aktiver VIP-Zugang');

$title = 'Crypto Voucher einreichen & kaufen';
$showSidebar = true;
$sidebarRole = 'user';
require __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-xl-6">
        <div class="app-card p-4 h-100">
            <span class="cyber-chip">Voucher einreichen</span>
            <h1 class="h4 mt-3">Code einreichen</h1>
            <p class="text-secondary">Wähle den Betrag und reiche deinen Crypto Voucher Code ein.</p>
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
            <ul class="panel-list mb-0">
                <?php foreach (VOUCHER_ACCESS_RULES as $amount => $rule): ?>
                    <li><strong><?= e((string) $amount) ?> €</strong><span class="small-muted"><?= e((string) ($rule['label'] ?? 'VIP Zugang')) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="app-card p-4 h-100">
            <span class="cyber-chip">Status</span>
            <h2 class="h4 mt-3">Aktueller Überblick</h2>
            <ul class="panel-list mt-3">
                <li><strong>Bestätigt eingezahlt</strong><span class="small-muted"><?= e((string) $depositedTotal) ?> €</span></li>
                <li><strong>Verbleibende Laufzeit</strong><span class="small-muted"><?= e($remainingLabel) ?></span></li>
            </ul>
            <div class="table-responsive mt-4">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead><tr><th>ID</th><th>Betrag</th><th>Status</th><th>Eingereicht</th></tr></thead>
                    <tbody>
                    <?php if ($vouchers === []): ?>
                        <tr><td colspan="4">Noch keine Voucher eingereicht.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($vouchers as $voucher): ?>
                        <?php $status = (string) ($voucher['status'] ?? 'pending'); ?>
                        <tr>
                            <td>#<?= e((string) $voucher['id']) ?></td>
                            <td><?= e((string) $voucher['amount']) ?> €</td>
                            <td><span class="badge text-bg-<?= e(voucherStatusBadge($status)) ?>"><?= e(voucherStatusLabel($status)) ?></span></td>
                            <td><?= e((string) $voucher['submitted_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="app-card p-4 mt-4">
    <span class="cyber-chip">Crypto Voucher kaufen</span>
    <h2 class="h4 mt-3">Anbieter (nach Bekanntheitsgrad)</h2>
    <p class="text-secondary mt-2 mb-0">Für Einreichungen in diesem Dashboard werden nur Voucher mit 50 €, 75 € und 150 € akzeptiert.</p>
    <div data-payment-scope>
    <div class="mb-3 mt-3">
        <label class="form-label" for="voucher_payment_filter">Nach Zahlungsmethode filtern</label>
        <select class="form-select" id="voucher_payment_filter" data-payment-filter>
            <option value="all">Alle Zahlungsmethoden</option>
            <option value="paypal">PayPal</option>
            <option value="apple-pay">Apple Pay</option>
            <option value="klarna">Klarna</option>
            <option value="paysafecard">Paysafecard</option>
            <option value="visa-mastercard">Visa/Mastercard</option>
        </select>
    </div>
    <div class="voucher-shops">
        <div class="voucher-shop" data-payment-card data-methods="paypal,apple-pay,klarna">
            <h3 class="h5"><a href="https://dundle.com/de/cryptovoucher/" target="_blank" rel="noopener noreferrer">Dundle – Crypto Voucher Deutschland</a></h3>
            <p class="mb-2">5 €, 10 €, 25 €, 50 €, 100 €, 150 €, 200 €, 250 € · Sofortige Lieferung per E-Mail · Laut Anbieter offizieller Vertriebspartner.</p>
            <details><summary>Akzeptierte Zahlungsmethoden</summary><p class="mb-0 mt-2">PayPal, Apple Pay, Klarna etc.</p></details>
        </div>
        <div class="voucher-shop" data-payment-card data-methods="paypal,paysafecard,klarna">
            <h3 class="h5"><a href="https://www.recharge.com/de/lu/crypto-vouchers" target="_blank" rel="noopener noreferrer">Recharge.com – Crypto Voucher</a></h3>
            <p class="mb-2">5 € bis 200 € · Code direkt per E-Mail.</p>
            <details><summary>Akzeptierte Zahlungsmethoden</summary><p class="mb-0 mt-2">PayPal, Paysafecard, Klarna u. a.</p></details>
        </div>
        <div class="voucher-shop" data-payment-card data-methods="paypal,visa-mastercard">
            <h3 class="h5"><a href="https://aufladenkarte.de/shop/crypto-voucher" target="_blank" rel="noopener noreferrer">AufladenKarte – Crypto Voucher</a></h3>
            <p class="mb-2">Deutschland ausgerichteter Shop · Code laut Anbieter direkt nach dem Kauf per E-Mail.</p>
            <details><summary>Akzeptierte Zahlungsmethoden</summary><p class="mb-0 mt-2">PayPal, Visa/Mastercard</p></details>
        </div>
        <div class="voucher-shop" data-payment-card data-methods="paypal,apple-pay,klarna,visa-mastercard">
            <h3 class="h5"><a href="https://skine.com/de-de/cryptovoucher" target="_blank" rel="noopener noreferrer">Skine – Crypto Voucher</a></h3>
            <p class="mb-2">z. B. 50 € und 100 € · Digitale Abwicklung.</p>
            <details><summary>Akzeptierte Zahlungsmethoden</summary><p class="mb-0 mt-2">PayPal und zahlreiche weitere Zahlungsmethoden</p></details>
        </div>
    </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
