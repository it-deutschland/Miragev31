<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function logAdminAction(?int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, array $metadata = []): void
{
    $stmt = db()->prepare('INSERT INTO admin_logs (admin_id, action, target_type, target_id, ip_address, user_agent, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $adminId,
        $action,
        $targetType,
        $targetId,
        clientIp(),
        userAgent(),
        $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}

function logAdminLoginAttempt(?int $adminId, string $username, string $result, ?string $reason = null): void
{
    $stmt = db()->prepare('INSERT INTO admin_login_logs (admin_id, username, ip_address, user_agent, result, reason) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $adminId,
        mb_substr($username, 0, 100),
        clientIp(),
        userAgent(),
        $result,
        $reason,
    ]);
}
