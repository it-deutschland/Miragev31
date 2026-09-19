<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/telegram.php';

if (isset($_GET['id'], $_GET['hash'], $_GET['auth_date'])) {
    if (!verifyTelegramAuth($_GET)) {
        http_response_code(403);
        exit('Telegram authentication failed.');
    }

    $telegramId = (int) $_GET['id'];
    $username = isset($_GET['username']) ? mb_substr((string) $_GET['username'], 0, 255) : null;
    $firstName = mb_substr((string) ($_GET['first_name'] ?? ''), 0, 255);
    $lastName = mb_substr((string) ($_GET['last_name'] ?? ''), 0, 255);
    $name = trim($firstName . ' ' . $lastName);
    if ($name === '') {
        $name = $username ?? ('Telegram-' . $telegramId);
    }
    $photo = isset($_GET['photo_url']) ? mb_substr((string) $_GET['photo_url'], 0, 1024) : null;

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE telegram_id = ? LIMIT 1');
    $stmt->execute([$telegramId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $pdo->prepare('UPDATE users SET telegram_username = ?, name = ?, avatar = ?, last_login = NOW(), last_ip = ?, updated_at = NOW() WHERE id = ?');
        $update->execute([$username, $name, $photo, clientIp(), $existing['id']]);
        $userId = (int) $existing['id'];
    } else {
        $insert = $pdo->prepare('INSERT INTO users (telegram_id, telegram_username, name, avatar, last_login, last_ip) VALUES (?, ?, ?, ?, NOW(), ?)');
        $insert->execute([$telegramId, $username, $name, $photo, clientIp()]);
        $userId = (int) $pdo->lastInsertId();
    }

    $fresh = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $fresh->execute([$userId]);
    $user = $fresh->fetch();
    if (!$user) {
        throw new RuntimeException('User fetch failed.');
    }

    loginUser($user);
    redirect('/dashboard');
}

$title = 'Telegram Login';
require __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 login-grid">
    <div class="col-lg-7">
        <section class="app-card hero-banner reveal-up h-100">
            <div class="hero-media">
                <img src="<?= e($mirageHeaderImage) ?>" alt="Mirage VIP Header">
            </div>
            <div class="hero-overlay p-4 p-lg-5">
    <div class="hero-copy">
        <span class="cyber-chip">Private Telegram Gateway</span>
        <h1 class="hero-title mt-3 mb-3">Dein diskreter Zugang zur Mirage VIP Lounge</h1>
        <p class="mb-0">Melde dich bequem über Telegram an und erhalte direkten Zugang zu deinem persönlichen VIP-Bereich. Eine exklusive FSK18-Umgebung für volljährige Gäste – diskret, modern und mit einer ganz besonderen Atmosphäre.</p>
        <div class="feature-stack mt-4">
            <span class="cyber-chip">FSK18 Access</span>
            <span class="cyber-chip">Diskreter Login</span>
            <span class="cyber-chip">Private VIP Lounge</span>
        </div>
    </div>
</div>
</section>
</div>

<div class="col-lg-5">
    <div class="app-card p-4 p-lg-5 fade-in h-100 fx-tilt" data-tilt-card>
        <span class="cyber-chip">VIP Authentication</span>
        <h2 class="h4 mt-3 mb-3">Betritt deine private Lounge</h2>
        <p class="text-secondary">Nutze deinen Telegram-Account für einen schnellen und diskreten Zugang zum Mirage VIP Bereich.</p>


    <div class="muted-divider my-4"></div>

    <div class="surface-panel p-4">
        <script async src="https://telegram.org/js/telegram-widget.js?22"
                data-telegram-login="<?= e((string) cfg('TELEGRAM_BOT_USERNAME', '')) ?>"
                data-size="large"
                data-auth-url="<?= e(appUrl('/login/telegram')) ?>"
                data-request-access="write"></script>
    </div>

    <?php if (!cfg('TELEGRAM_BOT_USERNAME')): ?>
        <div class="alert alert-warning mt-3">TELEGRAM_BOT_USERNAME ist nicht konfiguriert.</div>
    <?php endif; ?>

    <ul class="panel-list mt-4">
        <li>
            <strong>Nur für Volljährige</strong>
            <span class="small-muted">Der Mirage VIP Bereich ist ausschließlich für Nutzer ab 18 Jahren bestimmt.</span>
        </li>
        <li>
            <strong>Diskreter Zugang</strong>
            <span class="small-muted">Der bestehende Telegram-Login bleibt erhalten und führt dich direkt in deinen privaten Bereich.</span>
        </li>
        <li>
            <strong>Exklusive VIP Experience</strong>
            <span class="small-muted">Dunkles Cyber-Luxury-Design, private Atmosphäre und ein Zugang, der sich bewusst vom Gewöhnlichen abhebt.</span>
        </li>
    </ul>
</div>


</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
