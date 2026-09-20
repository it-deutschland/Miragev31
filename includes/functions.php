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
        'confirmed' => ['label' => 'Bestätigt', 'badge' => 'success', 'description' => 'Der Voucher wurde erfolgreich bestätigt und aktiviert.'],
        'invalid' => ['label' => 'Ungültig', 'badge' => 'danger', 'description' => 'Der Voucher konnte nicht bestätigt werden.'],
        'pending' => ['label' => 'In Bearbeitung', 'badge' => 'warning', 'description' => 'Der Voucher wird aktuell geprüft.'],
    ];
}

function normalizeVoucherStatus(string $status): string
{
    return $status === 'proofed' ? 'confirmed' : $status;
}

function voucherStatusLabel(string $status): string
{
    $meta = voucherStatusMeta();
    $normalized = normalizeVoucherStatus($status);
    return $meta[$normalized]['label'] ?? $meta['pending']['label'];
}

function voucherStatusBadge(string $status): string
{
    $meta = voucherStatusMeta();
    $normalized = normalizeVoucherStatus($status);
    return $meta[$normalized]['badge'] ?? $meta['pending']['badge'];
}

function buildVipOverview(array $vouchers): array
{
    $depositedTotal = 0;
    $hasLifetime = false;
    $timedVouchers = [];
    $nowTs = time();

    foreach ($vouchers as $voucher) {
        $amount = (int) ($voucher['amount'] ?? 0);
        $rule = VOUCHER_ACCESS_RULES[$amount] ?? null;
        if (normalizeVoucherStatus((string) ($voucher['status'] ?? '')) !== 'confirmed') {
            continue;
        }

        $depositedTotal += $amount;

        if (!is_array($rule)) {
            continue;
        }

        if (!empty($rule['lifetime'])) {
            $hasLifetime = true;
            continue;
        }

        $durationDays = (int) ($rule['days'] ?? 0);
        if ($durationDays === 0) {
            continue;
        }

        $start = (string) ($voucher['processed_at'] ?? '');
        if ($start === '') {
            continue;
        }
        $startTs = strtotime($start);
        if ($startTs === false) {
            continue;
        }

        $timedVouchers[] = ['start_ts' => $startTs, 'duration_days' => $durationDays];
    }

    usort(
        $timedVouchers,
        static fn (array $a, array $b): int => $a['start_ts'] <=> $b['start_ts']
    );

    $activeUntilTs = 0;
    foreach ($timedVouchers as $timedVoucher) {
        $startTs = (int) $timedVoucher['start_ts'];
        $durationDays = (int) $timedVoucher['duration_days'];
        $effectiveStart = max($startTs, $activeUntilTs);
        $activeUntilTs = $effectiveStart + ($durationDays * 86400);
    }

    $remainingDays = $hasLifetime
        ? null
        : ($activeUntilTs > $nowTs ? (int) ceil(($activeUntilTs - $nowTs) / 86400) : 0);

    $remainingLabel = $hasLifetime
        ? 'Lifetime'
        : ($remainingDays > 0 ? $remainingDays . ' Tage verbleibend' : 'Kein aktiver VIP-Zugang');

    return [
        'deposited_total' => $depositedTotal,
        'remaining_days' => $remainingDays,
        'has_lifetime' => $hasLifetime,
        'access_type' => $hasLifetime ? 'lifetime' : ($remainingDays > 0 ? 'timed' : 'none'),
        'remaining_label' => $remainingLabel,
    ];
}


function voucherAccessRuleEntries(): array
{
    $entries = [];
    foreach (VOUCHER_ACCESS_RULES as $amount => $rule) {
        $amountLabel = $amount . ' €';
        $accessLabel = (string) ($rule['label'] ?? 'VIP Zugang');
        $entries[] = [
            'amount' => (int) $amount,
            'amount_label' => $amountLabel,
            'access_label' => $accessLabel,
            'line' => $amountLabel . ' = ' . $accessLabel,
        ];
    }
    return $entries;
}

function voucherShopProviders(): array
{
    return [
        [
            'name' => 'Dundle – Crypto Voucher Deutschland',
            'url' => 'https://dundle.com/de/cryptovoucher/',
            'description' => '5 €, 10 €, 25 €, 50 €, 100 €, 150 €, 200 €, 250 € · Sofortige Lieferung per E-Mail · Offizieller Crypto-Voucher-Vertriebspartner laut Anbieter.',
            'methods' => ['paypal', 'apple-pay', 'klarna'],
            'methods_label' => 'PayPal, Apple Pay, Klarna u. a.',
        ],
        [
            'name' => 'Recharge.com – Crypto Voucher',
            'url' => 'https://www.recharge.com/de/lu/crypto-vouchers',
            'description' => '5 € bis 200 € · Code direkt per E-Mail.',
            'methods' => ['paypal', 'paysafecard', 'klarna'],
            'methods_label' => 'PayPal, Paysafecard, Klarna u. a.',
        ],
        [
            'name' => 'AufladenKarte – Crypto Voucher',
            'url' => 'https://aufladenkarte.de/shop/crypto-voucher',
            'description' => 'Deutschland ausgerichteter Shop · Code laut Anbieter direkt nach dem Kauf per E-Mail.',
            'methods' => ['paypal', 'visa-mastercard'],
            'methods_label' => 'PayPal, Visa/Mastercard',
        ],
        [
            'name' => 'Skine – Crypto Voucher',
            'url' => 'https://skine.com/de-de/cryptovoucher',
            'description' => 'z. B. 50 € und 100 € · Digitale Abwicklung.',
            'methods' => ['paypal', 'apple-pay', 'klarna', 'visa-mastercard'],
            'methods_label' => 'PayPal und zahlreiche weitere Zahlungsmethoden',
        ],
    ];
}
