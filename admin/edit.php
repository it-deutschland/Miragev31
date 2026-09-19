<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/security.php';

$admin = requireRank(3);
requireActiveAdmin($admin);
$revealed = null;

if (requestMethod() === 'POST') {
    verifyCsrf('admin_edit_reveal');
    $voucherId = (int) ($_POST['voucher_id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM cryptovouchers WHERE id = ? LIMIT 1');
    $stmt->execute([$voucherId]);
    $voucher = $stmt->fetch();
    if ($voucher) {
        $revealed = [
            'id' => $voucherId,
            'code' => decryptSecret((string) $voucher['code_encrypted']),
        ];
        logAdminAction((int) $admin['id'], 'voucher_reveal', 'voucher', $voucherId, ['head_admin' => true]);
    }
}

$rows = db()->query('SELECT c.*, a1.username AS viewed_admin, a2.username AS processed_admin FROM cryptovouchers c LEFT JOIN admins a1 ON a1.id = c.viewed_by LEFT JOIN admins a2 ON a2.id = c.processed_by WHERE c.status <> "pending" ORDER BY c.submitted_at DESC LIMIT 200')->fetchAll();
$showSidebar = true;
$title = 'Voucher Audit Edit';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($revealed): ?><div class="alert alert-warning">Voucher #<?= e((string)$revealed['id']) ?> Klartext: <?= e($revealed['code']) ?></div><?php endif; ?>
<div class="card app-card p-3">
    <h1 class="h5">Bearbeitete Voucher (Head-Admin)</h1>
    <div class="table-responsive"><table class="table table-dark table-hover"><thead><tr><th>ID</th><th>Telegram ID</th><th>Name</th><th>Username</th><th>Betrag</th><th>Status</th><th>View Admin</th><th>View Zeit</th><th>Processed Admin</th><th>Processed Zeit</th><th>Aktion</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string)$row['id']) ?></td><td><?= e((string)$row['telegram_id']) ?></td><td><?= e($row['name']) ?></td><td><?= e((string)($row['telegram_username'] ?? '-')) ?></td><td><?= e((string)$row['amount']) ?> €</td><td><?= e($row['status']) ?></td>
                <td><?= e((string)($row['viewed_admin'] ?? '-')) ?></td><td><?= e((string)($row['viewed_at'] ?? '-')) ?></td><td><?= e((string)($row['processed_admin'] ?? '-')) ?></td><td><?= e((string)($row['processed_at'] ?? '-')) ?></td>
                <td><form method="post" class="d-inline"><?= csrfField('admin_edit_reveal') ?><input type="hidden" name="voucher_id" value="<?= e((string)$row['id']) ?>"><button class="btn btn-sm btn-outline-warning" type="submit">Code kontrolliert anzeigen</button></form></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
