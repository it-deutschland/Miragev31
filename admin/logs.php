<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

$admin = requireRank(3);
requireActiveAdmin($admin);
$logs = db()->query('SELECT l.*, a.username FROM admin_logs l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.created_at DESC LIMIT 300')->fetchAll();
$loginLogs = db()->query('SELECT * FROM admin_login_logs ORDER BY created_at DESC LIMIT 300')->fetchAll();

$showSidebar = true;
$title = 'Admin Logs';
require __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
    <div class="col-12">
        <div class="card app-card p-3">
            <h1 class="h5">Audit Logs</h1>
            <div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Zeit</th><th>Admin</th><th>Action</th><th>Target</th><th>IP</th><th>Metadata</th></tr></thead><tbody>
                <?php foreach ($logs as $log): ?><tr><td><?= e((string)$log['created_at']) ?></td><td><?= e((string)($log['username'] ?? '-')) ?></td><td><?= e($log['action']) ?></td><td><?= e((string)($log['target_type'] ?? '-')) ?>#<?= e((string)($log['target_id'] ?? '-')) ?></td><td><?= e((string)($log['ip_address'] ?? '-')) ?></td><td><code><?= e((string)($log['metadata'] ?? '{}')) ?></code></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </div>
    </div>
    <div class="col-12">
        <div class="card app-card p-3">
            <h2 class="h5">Login Logs</h2>
            <div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Zeit</th><th>Username</th><th>Result</th><th>IP</th><th>User Agent</th><th>Reason</th></tr></thead><tbody>
                <?php foreach ($loginLogs as $row): ?><tr><td><?= e((string)$row['created_at']) ?></td><td><?= e($row['username']) ?></td><td><?= e($row['result']) ?></td><td><?= e((string)($row['ip_address'] ?? '-')) ?></td><td><?= e((string)($row['user_agent'] ?? '-')) ?></td><td><?= e((string)($row['reason'] ?? '-')) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
