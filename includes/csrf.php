<?php
declare(strict_types=1);

function csrfToken(string $scope = 'default'): string
{
    if (!isset($_SESSION['_csrf'][$scope])) {
        $_SESSION['_csrf'][$scope] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'][$scope];
}

function csrfField(string $scope = 'default'): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken($scope)) . '">';
}

function verifyCsrf(string $scope = 'default'): void
{
    $provided = $_POST['_csrf'] ?? '';
    $expected = $_SESSION['_csrf'][$scope] ?? '';
    if (!is_string($provided) || !is_string($expected) || $provided === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}
