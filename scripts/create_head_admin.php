#!/usr/bin/env php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

$username = trim((string) readline('Head-Admin Username: '));
if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 100) {
    fwrite(STDERR, "Invalid username\n");
    exit(1);
}
$password = (string) readline('Head-Admin Password (min 12): ');
if (strlen($password) < 12) {
    fwrite(STDERR, "Password too short\n");
    exit(1);
}

$stmt = db()->prepare('INSERT INTO admins (username, password_hash, rank, activate) VALUES (?, ?, 3, 1)');
try {
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
} catch (Throwable $e) {
    fwrite(STDERR, "Failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Head-Admin created successfully.\n";
