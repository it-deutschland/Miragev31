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

$admin = requireAdmin();
requireActiveAdmin($admin);
if (!canProcessVouchers($admin)) {
    http_response_code(403);
    exit('Forbidden');
}

$flash = null;
$revealedCode = null;
$revealedVoucherId = null;

if (requestMethod() === 'POST') {
    verifyCsrf('admin_voucher_action');
    $action = (string) ($_POST['action'] ?? '');
    $voucherId = (int) ($_POST['voucher_id'] ?? 0);

    $stmt = db()->prepare('SELECT * FROM cryptovouchers WHERE id = ? LIMIT 1');
    $stmt->execute([$voucherId]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        $flash = ['type' => 'danger', 'msg' => 'Voucher nicht gefunden.'];
    } elseif ($action === 'reveal') {
        if ((int) $admin['rank'] < 3 && ($voucher['status'] !== 'pending' || $voucher['viewed_at'] !== null)) {
            $flash = ['type' => 'danger', 'msg' => 'Code darf nicht erneut angezeigt werden.'];
        } else {
            $revealedCode = decryptSecret((string) $voucher['code_encrypted']);
            $revealedVoucherId = $voucherId;
            if ($voucher['viewed_at'] === null) {
                $upd = db()->prepare('UPDATE cryptovouchers SET viewed_at = NOW(), viewed_by = ? WHERE id = ?');
                $upd->execute([(int) $admin['id'], $voucherId]);
            }
            logAdminAction((int) $admin['id'], (int)$admin['rank'] === 3 ? 'voucher_reveal' : 'voucher_view', 'voucher', $voucherId);
            $flash = ['type' => 'warning', 'msg' => 'Code wurde einmalig angezeigt.'];
        }
    } elseif (in_array($action, ['proofed', 'invalid'], true)) {
        $isHead = (int) $admin['rank'] === 3;
        $allowed = $isHead
            || ($voucher['status'] === 'pending' && $voucher['viewed_at'] !== null && (int) $voucher['viewed_by'] === (int) $admin['id'] && $voucher['processed_at'] === null);

        if (!$allowed) {
            $flash = ['type' => 'danger', 'msg' => 'Statusänderung nicht erlaubt.'];
        } else {
            $upd = db()->prepare("UPDATE cryptovouchers SET status = ?, processed_at = CASE WHEN ? = 'proofed' AND status = 'proofed' THEN processed_at ELSE NOW() END, processed_by = ? WHERE id = ?");
            $upd->execute([$action, $action, (int) $admin['id'], $voucherId]);
            logAdminAction((int) $admin['id'], 'voucher_status_change', 'voucher', $voucherId, ['status' => $action]);
            $flash = ['type' => 'success', 'msg' => 'Voucher aktualisiert.'];
        }
    }
}

$list = db()->query('SELECT c.*, a1.username AS viewed_by_name, a2.username AS processed_by_name FROM cryptovouchers c LEFT JOIN admins a1 ON a1.id = c.viewed_by LEFT JOIN admins a2 ON a2.id = c.processed_by ORDER BY c.submitted_at DESC LIMIT 200')->fetchAll();

$showSidebar = true;
$title = 'Admin Vouchers';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<?php if ($revealedCode !== null): ?>
    <div class="alert alert-warning"><strong>Voucher #<?= e((string)$revealedVoucherId) ?>:</strong> <?= e($revealedCode) ?></div>
<?php endif; ?>
<div class="card app-card p-3">
    <h1 class="h5">Voucher Übersicht</h1>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead><tr><th>ID</th><th>Telegram ID</th><th>Name</th><th>Username</th><th>Betrag</th><th>Status</th><th>Eingereicht</th><th>Bearbeitet von</th><th>Aktion</th></tr></thead>
            <tbody>
            <?php foreach ($list as $item): ?>
                <tr>
                    <td><?= e((string)$item['id']) ?></td>
                    <td><?= e((string)$item['telegram_id']) ?></td>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e((string)($item['telegram_username'] ?? '-')) ?></td>
                    <td><?= e((string)$item['amount']) ?> €</td>
                    <td><span class="badge text-bg-<?= $item['status'] === 'proofed' ? 'success' : ($item['status'] === 'invalid' ? 'danger' : 'warning') ?>"><?= e($item['status']) ?></span></td>
                    <td><?= e((string)$item['submitted_at']) ?></td>
                    <td><?= e((string)($item['processed_by_name'] ?? '-')) ?></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <form method="post" class="d-inline"><?= csrfField('admin_voucher_action') ?><input type="hidden" name="voucher_id" value="<?= e((string)$item['id']) ?>"><input type="hidden" name="action" value="reveal"><button class="btn btn-sm btn-outline-info" type="submit">Code einmalig anzeigen</button></form>
                            <form method="post" class="d-inline"><?= csrfField('admin_voucher_action') ?><input type="hidden" name="voucher_id" value="<?= e((string)$item['id']) ?>"><input type="hidden" name="action" value="proofed"><button class="btn btn-sm btn-success" type="submit">Proofed</button></form>
                            <form method="post" class="d-inline"><?= csrfField('admin_voucher_action') ?><input type="hidden" name="voucher_id" value="<?= e((string)$item['id']) ?>"><input type="hidden" name="action" value="invalid"><button class="btn btn-sm btn-danger" type="submit">Ungültig</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
