<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/security.php';

$user = requireUser();
if (requestMethod() !== 'POST') {
    redirect('/dashboard/vouchers');
}
verifyCsrf('voucher_submit');

$amount = (int) ($_POST['amount'] ?? 0);
$code = trim((string) ($_POST['voucher_code'] ?? ''));

if (!in_array($amount, ALLOWED_AMOUNTS, true)) {
    http_response_code(422);
    exit('Ungültiger Betrag.');
}

if ($code === '' || mb_strlen($code) < 6 || mb_strlen($code) > 255) {
    http_response_code(422);
    exit('Ungültiger Voucher-Code.');
}

$codeHash = hashVoucherCode($code);
$encrypted = encryptSecret($code);

$stmt = db()->prepare('INSERT INTO cryptovouchers (user_id, telegram_id, telegram_username, name, amount, code_hash, code_encrypted, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, "pending", NOW())');
$stmt->execute([
    (int) $user['id'],
    (int) $user['telegram_id'],
    $user['telegram_username'],
    $user['name'],
    $amount,
    $codeHash,
    $encrypted,
]);

redirect('/dashboard');
