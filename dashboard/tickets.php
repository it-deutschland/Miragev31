<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = requireUser();
$flash = null;

if (requestMethod() === 'POST') {
    verifyCsrf('user_ticket_action');
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_ticket') {
        $subject = mb_substr(trim((string) ($_POST['subject'] ?? '')), 0, 160);
        $message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000);

        if (mb_strlen($subject) < 5 || mb_strlen($message) < 3) {
            $flash = ['type' => 'danger', 'msg' => 'Bitte gib einen aussagekräftigen Betreff und eine Nachricht ein.'];
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO tickets (user_id, subject, status, last_message_at, last_message_by) VALUES (?, ?, "open", NOW(), "user")');
                $stmt->execute([(int) $user['id'], $subject]);
                $ticketId = (int) $pdo->lastInsertId();

                $msgStmt = $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_type, sender_user_id, sender_admin_id, message) VALUES (?, "user", ?, NULL, ?)');
                $msgStmt->execute([$ticketId, (int) $user['id'], $message]);
                $pdo->commit();
                redirect('/dashboard/tickets?ticket=' . $ticketId . '&created=1');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    } elseif ($action === 'send_message') {
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000);

        if (mb_strlen($message) < 2) {
            $flash = ['type' => 'danger', 'msg' => 'Bitte gib eine Nachricht ein.'];
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $ticketStmt = $pdo->prepare('SELECT id, status FROM tickets WHERE id = ? AND user_id = ? LIMIT 1 FOR UPDATE');
                $ticketStmt->execute([$ticketId, (int) $user['id']]);
                $ticket = $ticketStmt->fetch();

                if (!$ticket) {
                    $pdo->rollBack();
                    $flash = ['type' => 'danger', 'msg' => 'Ticket nicht gefunden.'];
                } elseif ((string) $ticket['status'] === 'closed') {
                    $pdo->rollBack();
                    $flash = ['type' => 'danger', 'msg' => 'Dieses Ticket ist geschlossen.'];
                } else {
                    $msgStmt = $pdo->prepare('INSERT INTO ticket_messages (ticket_id, sender_type, sender_user_id, sender_admin_id, message) VALUES (?, "user", ?, NULL, ?)');
                    $msgStmt->execute([$ticketId, (int) $user['id'], $message]);

                    $upd = $pdo->prepare('UPDATE tickets SET last_message_at = NOW(), last_message_by = "user", updated_at = NOW() WHERE id = ?');
                    $upd->execute([$ticketId]);
                    $pdo->commit();
                    redirect('/dashboard/tickets?ticket=' . $ticketId . '&sent=1');
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    }
}

if (isset($_GET['created'])) {
    $flash = ['type' => 'success', 'msg' => 'Ticket wurde erstellt.'];
} elseif (isset($_GET['sent'])) {
    $flash = ['type' => 'success', 'msg' => 'Nachricht wurde gesendet.'];
}

$ticketsStmt = db()->prepare('SELECT id, subject, status, created_at, updated_at, last_message_at FROM tickets WHERE user_id = ? ORDER BY FIELD(status, "open", "in_progress", "closed"), last_message_at DESC');
$ticketsStmt->execute([(int) $user['id']]);
$tickets = $ticketsStmt->fetchAll();

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
        'SELECT tm.id, tm.sender_type, tm.message, tm.created_at, a.username AS admin_username
         FROM ticket_messages tm
         LEFT JOIN admins a ON a.id = tm.sender_admin_id
         WHERE tm.ticket_id = ?
         ORDER BY tm.created_at ASC, tm.id ASC'
    );
    $msgStmt->execute([(int) $selectedTicket['id']]);
    $messages = $msgStmt->fetchAll();
}

$title = 'Ticket Center';
$showSidebar = true;
$sidebarRole = 'user';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="app-card p-4 h-100">
            <span class="cyber-chip">Support</span>
            <h1 class="h4 mt-3">Neues Ticket</h1>
            <form method="post" class="mt-3">
                <?= csrfField('user_ticket_action') ?>
                <input type="hidden" name="action" value="create_ticket">
                <div class="mb-3">
                    <label class="form-label" for="ticket_subject">Betreff</label>
                    <input class="form-control" id="ticket_subject" name="subject" maxlength="160" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ticket_message">Nachricht</label>
                    <textarea class="form-control" id="ticket_message" name="message" rows="5" maxlength="2000" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Ticket erstellen</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="app-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center gap-3">
                <h2 class="h5 mb-0">Deine Tickets</h2>
                <a class="btn btn-sm btn-outline-light" href="<?= e(appUrl('/dashboard')) ?>">Zurück zum Dashboard</a>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead><tr><th>ID</th><th>Betreff</th><th>Status</th><th>Letzte Aktivität</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($tickets === []): ?>
                        <tr><td colspan="5">Noch keine Tickets vorhanden.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>#<?= e((string) $ticket['id']) ?></td>
                            <td><?= e($ticket['subject']) ?></td>
                            <td>
                                <?php $status = (string) $ticket['status']; ?>
                                <span class="badge text-bg-<?= $status === 'open' ? 'warning' : ($status === 'in_progress' ? 'info' : 'secondary') ?>">
                                    <?= e($status) ?>
                                </span>
                            </td>
                            <td><?= e((string) $ticket['last_message_at']) ?></td>
                            <td><a class="btn btn-sm btn-outline-info" href="<?= e(appUrl('/dashboard/tickets?ticket=' . (int) $ticket['id'])) ?>">Öffnen</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="app-card p-4">
            <?php if (!$selectedTicket): ?>
                <h2 class="h5 mb-0">Ticket-Verlauf</h2>
                <p class="text-secondary mt-3 mb-0">Wähle ein Ticket aus oder erstelle ein neues Ticket.</p>
            <?php else: ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h2 class="h5 mb-0">Ticket #<?= e((string) $selectedTicket['id']) ?> - <?= e($selectedTicket['subject']) ?></h2>
                    <?php $selectedStatus = (string) $selectedTicket['status']; ?>
                    <span class="badge text-bg-<?= $selectedStatus === 'open' ? 'warning' : ($selectedStatus === 'in_progress' ? 'info' : 'secondary') ?>">
                        <?= e($selectedStatus) ?>
                    </span>
                </div>
                <div class="mt-3">
                    <?php foreach ($messages as $message): ?>
                        <div class="border rounded p-3 mb-3 bg-dark-subtle" style="background: #2b0a3d !important; padding: 2px">
                            <div class="small text-secondary mb-2">
                                <?= (string) $message['sender_type'] === 'admin' ? 'Support' . ((string) ($message['admin_username'] ?? '') !== '' ? ' @' . e((string) $message['admin_username']) : '') : 'Du' ?>
                                · <?= e((string) $message['created_at']) ?>
                            </div>
                            <div><?= nl2br(e((string) $message['message']), false) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($selectedStatus !== 'closed'): ?>
                    <form method="post">
                        <?= csrfField('user_ticket_action') ?>
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="ticket_id" value="<?= e((string) $selectedTicket['id']) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="reply_message">Antwort</label>
                            <textarea class="form-control" id="reply_message" name="message" rows="4" maxlength="2000" required></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Nachricht senden</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">Dieses Ticket ist geschlossen.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
