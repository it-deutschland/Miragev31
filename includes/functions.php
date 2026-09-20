<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function appUrl(string $path = ''): string
{
    $base = rtrim(cfg('APP_URL', '' ) ?? '', '/');
    $path = '/' . ltrim($path, '/');
    return $base !== '' ? $base . $path : $path;
}

function redirect(string $path): never
{
    header('Location: ' . appUrl($path));
    exit;
}

function requestMethod(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function clientIp(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function userAgent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 1024);
}

function currentPath(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    return strtok($uri, '?') ?: '/';
}


function voucherStatusMeta(): array
{
    return [
        'pending' => ['label' => 'In Bearbeitung', 'badge' => 'warning', 'description' => 'Der Voucher wird aktuell geprüft.'],
        'proofed' => ['label' => 'Bestätigt', 'badge' => 'success', 'description' => 'Der Voucher wurde erfolgreich bestätigt und aktiviert.'],
        'invalid' => ['label' => 'Ungültig', 'badge' => 'danger', 'description' => 'Der Voucher konnte nicht bestätigt werden.'],
    ];
}

function voucherStatusLabel(string $status): string
{
    $meta = voucherStatusMeta();
    return $meta[$status]['label'] ?? $meta['pending']['label'];
}

function voucherStatusBadge(string $status): string
{
    $meta = voucherStatusMeta();
    return $meta[$status]['badge'] ?? $meta['pending']['badge'];
}
