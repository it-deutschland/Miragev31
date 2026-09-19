<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'telegram_id' => (string) $user['telegram_id'],
        'telegram_username' => $user['telegram_username'] ?? null,
        'name' => $user['name'],
        'avatar' => $user['avatar'] ?? null,
    ];
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireUser(): array
{
    $user = currentUser();
    if (!$user) {
        redirect('/login/telegram');
    }
    return $user;
}

function loginAdmin(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id' => (int) $admin['id'],
        'username' => $admin['username'],
        'rank' => (int) $admin['rank'],
        'activate' => (int) $admin['activate'],
    ];
}

function currentAdmin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function requireAdmin(): array
{
    $admin = currentAdmin();
    if (!$admin) {
        redirect('/admin/login');
    }
    return $admin;
}

function logoutAll(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
