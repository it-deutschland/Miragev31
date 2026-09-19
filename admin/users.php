<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/logger.php';

$admin = requireRank(3);
requireActiveAdmin($admin);
logAdminAction((int) $admin['id'], 'user_view', 'users', null);
$users = db()->query('SELECT id, telegram_id, telegram_username, name, created_at, updated_at, last_login, last_ip FROM users ORDER BY id DESC LIMIT 300')->fetchAll();

$showSidebar = true;
$title = 'Admin Users';
require __DIR__ . '/../includes/header.php';
?>
<div class="card app-card p-3">
    <h1 class="h5">Benutzer</h1>
    <div class="table-responsive"><table class="table table-dark table-hover"><thead><tr><th>ID</th><th>Telegram ID</th><th>Username</th><th>Name</th><th>Last Login</th><th>Last IP</th></tr></thead><tbody>
        <?php foreach ($users as $u): ?><tr><td><?= e((string)$u['id']) ?></td><td><?= e((string)$u['telegram_id']) ?></td><td><?= e((string)($u['telegram_username'] ?? '-')) ?></td><td><?= e($u['name']) ?></td><td><?= e((string)($u['last_login'] ?? '-')) ?></td><td><?= e((string)($u['last_ip'] ?? '-')) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
