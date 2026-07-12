<?php
// CSRF-токени для форм адмінки.

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$expected || !hash_equals($expected, $sent)) {
        http_response_code(403);
        echo 'Недійсний CSRF-токен. Оновіть сторінку і спробуйте ще раз.';
        exit;
    }
}
