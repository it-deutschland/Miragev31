<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

if (currentAdmin()) {
    redirect('/admin/dashboard');
}

$error = null;
$maxAttempts = (int) (cfg('ADMIN_LOGIN_MAX_ATTEMPTS', '5') ?? 5);
$windowSeconds = (int) (cfg('ADMIN_LOGIN_WINDOW_SECONDS', '900') ?? 900);
$lockSeconds = (int) (cfg('ADMIN_LOGIN_LOCK_SECONDS', '900') ?? 900);

function isRateLimited(string $username, string $ip, int $maxAttempts, int $windowSeconds): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM admin_login_logs WHERE result = "failed" AND created_at >= (NOW() - INTERVAL ? SECOND) AND (ip_address = ? OR username = ?)');
    $stmt->execute([$windowSeconds, $ip, $username]);
    return (int) $stmt->fetchColumn() >= $maxAttempts;
}

if (requestMethod() === 'POST') {
    verifyCsrf('admin_login');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip = clientIp();

    if (isRateLimited($username, $ip, $maxAttempts, $windowSeconds)) {
        logAdminLoginAttempt(null, $username, 'failed', 'rate_limited');
        $error = sprintf('Zu viele Versuche. Bitte in %d Sekunden erneut versuchen.', $lockSeconds);
    } else {
        $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
            logAdminLoginAttempt(null, $username, 'failed', 'invalid_credentials');
            $error = 'Login fehlgeschlagen.';
        } elseif ((int) $admin['activate'] !== 1) {
            logAdminLoginAttempt((int) $admin['id'], $username, 'failed', 'not_activated');
            $error = 'Account wartet auf Freischaltung.';
        } else {
            $update = db()->prepare('UPDATE admins SET last_login = NOW(), last_ip = ? WHERE id = ?');
            $update->execute([$ip, (int) $admin['id']]);
            loginAdmin($admin);
            logAdminLoginAttempt((int) $admin['id'], $username, 'success');
            logAdminAction((int) $admin['id'], 'admin_login', 'admin', (int) $admin['id']);
            redirect('/admin/dashboard');
        }
    }
}

$title = 'Admin Login';
require __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 login-grid justify-content-center">
    <div class="col-lg-5">
        <div class="app-card p-4 p-lg-5 fade-in h-100 fx-tilt" data-tilt-card>
            <span class="cyber-chip">Restricted Access</span>
            <h1 class="h4 mt-3 mb-3">Admin Login</h1>
            <p class="text-secondary">Nur Administratoren haben Zugang zu diesem Bereich</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <div class="muted-divider my-4"></div>
            <form method="post">
                <?= csrfField('admin_login') ?>
                <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required maxlength="100"></div>
                <div class="mb-3"><label class="form-label">Passwort</label><input class="form-control" type="password" name="password" required></div>
                <button class="btn btn-primary" type="submit">Einloggen</button>
                <a class="btn btn-link text-info" href="<?= e(appUrl('/admin/register')) ?>">Registrieren</a>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <section class="app-card hero-banner reveal-up h-100">
            <div class="hero-media">
                <img src="<?= e($mirageHeaderImage) ?>" alt="Mirage VIP Header">
            </div>
            <div class="hero-overlay p-4 p-lg-5">
                <div class="hero-copy">
                    <span class="cyber-chip">Backoffice Access</span>
                    <h2 class="hero-title mt-3 mb-3">Admin Gateway</h2>
                    <p class="mb-0">Für das Mirage Team entsteht ein leicht bedienbares Admin-Panel mit hervorragendem Design, glänzenden Flächen und animierten Akzenten – ohne Änderungen am eigentlichen Login-Ablauf.</p>
                    <div class="feature-stack mt-4">
                        <span class="cyber-chip">Glass Panels</span>
                        <span class="cyber-chip">Neon Sidebar</span>
                        <span class="cyber-chip">Live Glow</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
