<?php
declare(strict_types=1);

function verifyTelegramAuth(array $data): bool
{
    $token = cfg('TELEGRAM_BOT_TOKEN');
    if (!$token || !isset($data['hash']) || !isset($data['auth_date'])) {
        return false;
    }

    $hash = (string) $data['hash'];
    unset($data['hash']);
    ksort($data);

    $check = [];
    foreach ($data as $k => $v) {
        if ($v === '' || $v === null) {
            continue;
        }
        $check[] = $k . '=' . $v;
    }

    $secret = hash('sha256', $token, true);
    $calc = hash_hmac('sha256', implode("\n", $check), $secret);

    if (!hash_equals($calc, $hash)) {
        return false;
    }

    $maxAge = 86400;
    return time() - (int) $data['auth_date'] <= $maxAge;
}
