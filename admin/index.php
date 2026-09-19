<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (currentAdmin()) {
    redirect('/admin/dashboard');
}
redirect('/admin/login');
