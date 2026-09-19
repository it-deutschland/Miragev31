<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';

$admin = requireRank(3);
requireActiveAdmin($admin);
$flash = null;

if (requestMethod() === 'POST') {
    verifyCsrf('admin_manage_admins');
    $targetId = (int) ($_POST['admin_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($targetId > 0 && $targetId !== (int) $admin['id']) {
        if ($action === 'activate' || $action === 'deactivate') {
            $value = $action === 'activate' ? 1 : 0;
            $stmt = db()->prepare('UPDATE admins SET activate = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$value, $targetId]);
            logAdminAction((int) $admin['id'], $action === 'activate' ? 'admin_activate' : 'admin_deactivate', 'admin', $targetId);
            $flash = ['type' => 'success', 'msg' => 'Status aktualisiert.'];
        } elseif ($action === 'rank' && isset($_POST['rank'])) {
            $rank = (int) $_POST['rank'];
            if (in_array($rank, [1,2,3], true)) {
                $stmt = db()->prepare('UPDATE admins SET rank = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$rank, $targetId]);
                logAdminAction((int) $admin['id'], 'admin_role_change', 'admin', $targetId, ['rank' => $rank]);
                $flash = ['type' => 'success', 'msg' => 'Rolle aktualisiert.'];
            }
        }
    }
}

$admins = db()->query('SELECT id, username, rank, activate, created_at, last_login, last_ip FROM admins ORDER BY id DESC')->fetchAll();
$showSidebar = true;
$title = 'Admin Verwaltung';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="card app-card p-3">
    <h1 class="h5">Admins verwalten</h1>
    <div class="table-responsive"><table class="table table-dark table-hover"><thead><tr><th>ID</th><th>Username</th><th>Rank</th><th>Aktiv</th><th>Last Login</th><th>IP</th><th>Aktion</th></tr></thead><tbody>
        <?php foreach ($admins as $row): ?>
            <tr>
                <td><?= e((string)$row['id']) ?></td><td><?= e($row['username']) ?></td><td><?= e((string)$row['rank']) ?></td><td><?= (int)$row['activate'] === 1 ? 'Ja' : 'Nein' ?></td><td><?= e((string)($row['last_login'] ?? '-')) ?></td><td><?= e((string)($row['last_ip'] ?? '-')) ?></td>
                <td>
                    <?php if ((int)$row['id'] !== (int)$admin['id']): ?>
                    <form method="post" class="d-inline"><?= csrfField('admin_manage_admins') ?><input type="hidden" name="admin_id" value="<?= e((string)$row['id']) ?>"><input type="hidden" name="action" value="<?= (int)$row['activate'] === 1 ? 'deactivate' : 'activate' ?>"><button class="btn btn-sm btn-outline-warning" type="submit"><?= (int)$row['activate'] === 1 ? 'Deaktivieren' : 'Aktivieren' ?></button></form>
                    <form method="post" class="d-inline"><?= csrfField('admin_manage_admins') ?><input type="hidden" name="admin_id" value="<?= e((string)$row['id']) ?>"><input type="hidden" name="action" value="rank"><select class="form-select form-select-sm d-inline w-auto" name="rank"><option value="1">1</option><option value="2">2</option><option value="3">3</option></select><button class="btn btn-sm btn-outline-info" type="submit">Set Rank</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
