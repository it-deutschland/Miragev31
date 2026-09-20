<?php
declare(strict_types=1);

if (!function_exists('mirage_load_env')) {
    function mirage_load_env(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($key === '' || getenv($key) !== false) {
                continue;
            }
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

mirage_load_env(__DIR__ . '/../.env');

if (!function_exists('cfg')) {
    function cfg(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return (string) $value;
    }
}

$appEnv = cfg('APP_ENV', 'production');
$displayErrors = $appEnv === 'production' ? '0' : '1';
ini_set('display_errors', $displayErrors);
ini_set('log_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('UTC');

$secureCookie = cfg('SESSION_COOKIE_SECURE', '1') === '1' && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$sessionName = 'MIRAGESESSID';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => cfg('SESSION_SAME_SITE', 'Lax') ?? 'Lax',
    ]);
    session_start();
}

const ALLOWED_AMOUNTS = [50, 75, 150];
