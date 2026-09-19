<?php
declare(strict_types=1);

function encryptionKey(): string
{
    $raw = cfg('ENCRYPTION_KEY');
    if (!$raw) {
        throw new RuntimeException('Missing ENCRYPTION_KEY');
    }

    if (str_starts_with($raw, 'base64:')) {
        $decoded = base64_decode(substr($raw, 7), true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid ENCRYPTION_KEY base64');
        }
        $raw = $decoded;
    }

    if (strlen($raw) !== 32) {
        throw new RuntimeException('ENCRYPTION_KEY must be 32 bytes');
    }

    return $raw;
}

function encryptSecret(string $plainText): string
{
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plainText, 'AES-256-CBC', encryptionKey(), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        throw new RuntimeException('Encryption failed');
    }

    $mac = hash_hmac('sha256', $iv . $cipher, encryptionKey(), true);
    return base64_encode($iv . $mac . $cipher);
}

function decryptSecret(string $payload): string
{
    $raw = base64_decode($payload, true);
    if ($raw === false || strlen($raw) < 48) {
        throw new RuntimeException('Invalid encrypted payload');
    }

    $iv = substr($raw, 0, 16);
    $mac = substr($raw, 16, 32);
    $cipher = substr($raw, 48);
    $expected = hash_hmac('sha256', $iv . $cipher, encryptionKey(), true);

    if (!hash_equals($expected, $mac)) {
        throw new RuntimeException('Payload tampering detected');
    }

    $plain = openssl_decrypt($cipher, 'AES-256-CBC', encryptionKey(), OPENSSL_RAW_DATA, $iv);
    if ($plain === false) {
        throw new RuntimeException('Decryption failed');
    }

    return $plain;
}

function hashVoucherCode(string $code): string
{
    return hash('sha256', $code);
}
