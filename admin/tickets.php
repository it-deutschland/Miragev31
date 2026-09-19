<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';

$admin = requireAdmin();
requireActiveAdmin($admin);
if (!canProcessTickets($admin)) {
    http_response_code(403);
    exit('Forbidden');
}

$flash = null;

if (requestMethod() === 'POST') {
    verifyCsrf('admin_ticket_action');
    $action = (string) ($_POST['action'] ?? '');
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    if ($action === 'set_status') {
        $newStatus = (string) ($_POST['status'] ?? '');
        if (!in_array($newStatus, ['open', 'in_progress', 'closed'], true)) {
            $flash = ['type' => 'danger', 'msg' => 'Ungültiger Status.'];
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $ticketStmt = $pdo->prepare('SELECT id FROM tickets WHERE id = ? LIMIT 1 FOR UPDATE');
                $ticketStmt->execute([$ticketId]);
                $ticket = $ticketStmt->fetch();
                if (!$ticket) {
                    $pdo->rollBack();
                    $flash = ['type' => 'danger', 'msg' => 'Ticket nicht gefunden.'];
                } else {
                    $update = $pdo->prepare(
                        'UPDATE tickets
                         SET status = ?,
                             assigned_admin_id = COALESCE(assigned_admin_id, ?),
                             closed_at = CASE WHEN ? = "closed" THEN COALESCE(closed_at, NOW()) ELSE NULL END,
                             updated_at = NOW()
                         WHERE id = ?'
                    );
                    $update->execute([$newStatus, (int) $admin['id'], $newStatus, $ticketId]);
                    $pdo->commit();
                    logAdminAction((int) $admin['id'], 'ticket_status_change', 'ticket', $ticketId, ['status' => $newStatus]);
                    redirect('/admin/tickets?ticket=' . $ticketId . '&status=1');
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    } elseif ($action === 'send_message') {
        $message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000);
        if (mb_strlen($message) < 2) {
            $flash = ['type' => 'danger', 'msg' => 'Bitte gib eine Nachricht ein.'];
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $ticketStmt = $pdo->prepare('SELECT id, status FROM tickets WHERE id = ? LIMIT 1 FOR UPDATE');
                $ticketStmt->execute([$ticketId]);
                $ticket = $ticketStmt->fetch();

                if (!$ticket) {
                    $pdo->rollBack();
                    $flash = ['type' => 'danger', 'msg' => 'Ticket nicht gefunden.'];
                } elseif ((string) $ticket['status'] === 'closed') {
                    $pdo->rollBack();
                    $flash = ['type' => 'danger', 'msg' => 'Geschlossene Tickets können nicht beantwortet werden.'];
                } else {
                    $msgStmt = $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_type, sender_user_id, sender_admin_id, message) VALUES (?, "admin", NULL, ?, ?)');
                    $msgStmt->execute([$ticketId, (int) $admin['id'], $message]);

                    $upd = $pdo->prepare(
                        'UPDATE tickets
                         SET last_message_at = NOW(),
                             last_message_by = "admin",
                             status = CASE WHEN status = "open" THEN "in_progress" ELSE status END,
                             assigned_admin_id = COALESCE(assigned_admin_id, ?),
                             updated_at = NOW()
                         WHERE id = ?'
                    );
                    $upd->execute([(int) $admin['id'], $ticketId]);
                    $pdo->commit();
                    logAdminAction((int) $admin['id'], 'ticket_reply', 'ticket', $ticketId);
                    redirect('/admin/tickets?ticket=' . $ticketId . '&sent=1');
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    } else {
        $flash = ['type' => 'danger', 'msg' => 'Ungültige Ticket-Aktion.'];
    }
}

if (isset($_GET['status'])) {
    $flash = ['type' => 'success', 'msg' => 'Ticket-Status wurde aktualisiert.'];
} elseif (isset($_GET['sent'])) {
    $flash = ['type' => 'success', 'msg' => 'Antwort wurde gesendet.'];
}

$tickets = db()->query(
    'WITH ticket_scope AS (
        SELECT
            t.id,
            t.subject,
            t.status,
            t.created_at,
            t.last_message_at,
            t.last_message_by,
            t.user_id,
            t.assigned_admin_id
        FROM tickets t
        ORDER BY FIELD(t.status, "open", "in_progress", "closed"), t.last_message_at DESC
        LIMIT 300
    ),
    voucher_scope AS (
        SELECT
            cv.user_id,
            1 AS has_voucher,
            SUBSTRING_INDEX(GROUP_CONCAT(cv.status ORDER BY cv.submitted_at DESC), ",", 1) AS latest_voucher_status
        FROM cryptovouchers cv
        INNER JOIN ticket_scope ts2 ON ts2.user_id = cv.user_id
        GROUP BY cv.user_id
    )
    SELECT
        ts.id,
        ts.subject,
        ts.status,
        ts.created_at,
        ts.last_message_at,
        ts.last_message_by,
        ts.user_id,
        ts.assigned_admin_id,
        u.telegram_id,
        u.telegram_username,
        u.name AS user_name,
        COALESCE(v.has_voucher, 0) AS has_voucher,
        v.latest_voucher_status
    FROM ticket_scope ts
    INNER JOIN users u ON u.id = ts.user_id
    LEFT JOIN voucher_scope v ON v.user_id = ts.user_id
    ORDER BY FIELD(ts.status, "open", "in_progress", "closed"), ts.last_message_at DESC'
)->fetchAll();

$selectedTicket = null;
$selectedTicketId = (int) ($_GET['ticket'] ?? 0);
if ($selectedTicketId > 0) {
    foreach ($tickets as $ticketRow) {
        if ((int) $ticketRow['id'] === $selectedTicketId) {
            $selectedTicket = $ticketRow;
            break;
        }
    }
}
if (!$selectedTicket && $tickets !== []) {
    $selectedTicket = $tickets[0];
}

$messages = [];
if ($selectedTicket) {
    $msgStmt = db()->prepare(
        'SELECT
            tm.id,
            tm.sender_type,
            tm.message,
            tm.created_at,
            a.username AS admin_username
         FROM ticket_messages tm
         LEFT JOIN admins a ON a.id = tm.sender_admin_id
         WHERE tm.ticket_id = ?
         ORDER BY tm.created_at ASC, tm.id ASC'
    );
    $msgStmt->execute([(int) $selectedTicket['id']]);
    $messages = $msgStmt->fetchAll();
}

$showSidebar = true;
$title = 'Admin Tickets';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="app-card p-3">
            <h1 class="h5 mb-3">Ticket-Queue</h1>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead><tr><th>ID</th><th>User</th><th>UserID</th><th>Status</th><th>Voucher Info</th><th>Aktivität</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($tickets === []): ?>
                        <tr><td colspan="7">Keine Tickets vorhanden.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tickets as $item): ?>
                        <?php $status = (string) $item['status']; ?>
                        <tr>
                            <td>#<?= e((string) $item['id']) ?></td>
                            <td><?= e((string) $item['user_name']) ?></td>
                            <td><?= e((string) $item['user_id']) ?></td>
                            <td><span class="badge text-bg-<?= $status === 'open' ? 'warning' : ($status === 'in_progress' ? 'info' : 'secondary') ?>"><?= e($status) ?></span></td>
                            <td>
                                <?= (int) $item['has_voucher'] === 1 ? 'Ja' : 'Nein' ?>
                                <?php if ((string) ($item['latest_voucher_status'] ?? '') !== ''): ?>
                                    <br><span class="small text-secondary">Letzter Status: <?= e((string) $item['latest_voucher_status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) $item['last_message_at']) ?></td>
                            <td><a class="btn btn-sm btn-outline-info" href="<?= e(appUrl('/admin/tickets?ticket=' . (int) $item['id'])) ?>">Öffnen</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="app-card p-4 h-100">
            <?php if (!$selectedTicket): ?>
                <h2 class="h5 mb-0">Ticket-Details</h2>
                <p class="text-secondary mt-3 mb-0">Wähle ein Ticket aus der Liste aus.</p>
            <?php else: ?>
                <?php $selectedStatus = (string) $selectedTicket['status']; ?>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Ticket #<?= e((string) $selectedTicket['id']) ?></h2>
                        <div class="small text-secondary"><?= e((string) $selectedTicket['subject']) ?></div>
                    </div>
                    <span class="badge text-bg-<?= $selectedStatus === 'open' ? 'warning' : ($selectedStatus === 'in_progress' ? 'info' : 'secondary') ?>">
                        <?= e($selectedStatus) ?>
                    </span>
                </div>
                <ul class="panel-list mb-4">
                    <li><strong>User</strong><span class="small-muted"><?= e((string) $selectedTicket['user_name']) ?> (ID <?= e((string) $selectedTicket['user_id']) ?>)</span></li>
                    <li><strong>Telegram</strong><span class="small-muted"><?= e((string) $selectedTicket['telegram_id']) ?> / @<?= e((string) ($selectedTicket['telegram_username'] ?? '-')) ?></span></li>
                    <li><strong>Voucher</strong><span class="small-muted"><?= (int) $selectedTicket['has_voucher'] === 1 ? 'Bereits eingezahlt' : 'Keine Einzahlung gefunden' ?><?= (string) ($selectedTicket['latest_voucher_status'] ?? '') !== '' ? ' (Status: ' . e((string) $selectedTicket['latest_voucher_status']) . ')' : '' ?></span></li>
                </ul>

                <form method="post" class="mb-4">
                    <?= csrfField('admin_ticket_action') ?>
                    <input type="hidden" name="action" value="set_status">
                    <input type="hidden" name="ticket_id" value="<?= e((string) $selectedTicket['id']) ?>">
                    <label class="form-label">Ticket-Status</label>
                    <div class="d-flex gap-2">
                        <select class="form-select" name="status" required>
                            <option value="open"<?= $selectedStatus === 'open' ? ' selected' : '' ?>>open</option>
                            <option value="in_progress"<?= $selectedStatus === 'in_progress' ? ' selected' : '' ?>>in_progress</option>
                            <option value="closed"<?= $selectedStatus === 'closed' ? ' selected' : '' ?>>closed</option>
                        </select>
                        <button class="btn btn-outline-warning" type="submit">Status setzen</button>
                    </div>
                </form>

                <div class="mb-3">
                    <?php foreach ($messages as $message): ?>
                        <div class="border rounded p-3 mb-3 bg-dark-subtle" style="background: #2b0a3d !important; padding: 2px">
                            <div class="small text-secondary mb-2">
                                <?= (string) $message['sender_type'] === 'admin' ? 'Admin @' . e((string) ($message['admin_username'] ?? 'unknown')) : 'User' ?>
                                · <?= e((string) $message['created_at']) ?>
                            </div>
                            <div><?= nl2br(e((string) $message['message']), false) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($selectedStatus !== 'closed'): ?>
                    <form method="post">
                        <?= csrfField('admin_ticket_action') ?>
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="ticket_id" value="<?= e((string) $selectedTicket['id']) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="admin_reply_message">Antwort an User</label>
                            <textarea class="form-control" id="admin_reply_message" name="message" rows="4" maxlength="2000" required></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Antwort senden</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">Dieses Ticket ist geschlossen. Setze den Status auf open oder in_progress, um erneut zu antworten.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
