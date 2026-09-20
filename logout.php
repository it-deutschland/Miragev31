<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (requestMethod() !== 'POST') {
    redirect('/dashboard');
}

verifyCsrf('user_logout');
logoutAll();
redirect('/');
