<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = cfg('DB_HOST', '127.0.0.1');
    $port = cfg('DB_PORT', '3306');
    $name = cfg('DB_NAME');
    $user = cfg('DB_USER');
    $pass = cfg('DB_PASSWORD');

    if (!$name || !$user) {
        throw new RuntimeException('Database configuration incomplete.');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    $pdo = new PDO($dsn, $user, $pass ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
