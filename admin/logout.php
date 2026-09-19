<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';

$admin = currentAdmin();
if (requestMethod() === 'POST') {
    verifyCsrf('admin_logout');
    if ($admin) {
        logAdminAction((int) $admin['id'], 'admin_logout', 'admin', (int) $admin['id']);
    }
    logoutAll();
}
redirect('/admin/login');
