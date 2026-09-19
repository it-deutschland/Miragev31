<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';

$error = null;
$success = null;

if (requestMethod() === 'POST') {
    verifyCsrf('admin_register');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirmation'] ?? '');

    if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 100) {
        $error = 'Ungültiger Benutzername.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwörter stimmen nicht überein.';
    } elseif (strlen($password) < 10) {
        $error = 'Passwort muss mindestens 10 Zeichen lang sein.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO admins (username, password_hash, rank, activate) VALUES (?, ?, 1, 0)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            logAdminAction(null, 'admin_registration', 'admin', (int) db()->lastInsertId(), ['username' => $username]);
            $success = 'Registrierung erfolgreich. Account wartet auf Freischaltung.';
        } catch (Throwable) {
            $error = 'Registrierung fehlgeschlagen. Benutzername möglicherweise vergeben.';
        }
    }
}

$title = 'Admin Registrierung';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card app-card p-4 fade-in">
            <h1 class="h4 mb-3">Admin Registrierung</h1>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <form method="post">
                <?= csrfField('admin_register') ?>
                <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required maxlength="100"></div>
                <div class="mb-3"><label class="form-label">Passwort</label><input class="form-control" type="password" name="password" required minlength="10"></div>
                <div class="mb-3"><label class="form-label">Passwort bestätigen</label><input class="form-control" type="password" name="password_confirmation" required minlength="10"></div>
                <button class="btn btn-primary" type="submit">Registrieren</button>
                <a class="btn btn-link text-info" href="<?= e(appUrl('/admin/login')) ?>">Zum Login</a>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
