<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function requireRank(int $required): array
{
    $admin = requireAdmin();
    if ((int) $admin['rank'] < $required) {
        http_response_code(403);
        exit('Forbidden');
    }
    return $admin;
}

function canProcessVouchers(array $admin): bool
{
    return in_array((int) $admin['rank'], [1, 2, 3], true);
}

function canProcessTickets(array $admin): bool
{
    return in_array((int) $admin['rank'], [1, 2, 3], true);
}

function canViewUsers(array $admin): bool
{
    return (int) $admin['rank'] === 3;
}

function canViewLogs(array $admin): bool
{
    return (int) $admin['rank'] === 3;
}

function canManageAdmins(array $admin): bool
{
    return (int) $admin['rank'] === 3;
}

function canRevealProcessedVoucher(array $admin): bool
{
    return (int) $admin['rank'] === 3;
}

function requireActiveAdmin(array $admin): void
{
    if ((int) $admin['activate'] !== 1) {
        http_response_code(403);
        exit('Admin account inactive.');
    }
}