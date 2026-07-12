<?php
// Google повертає сюди з ?code=...&state=...
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$expectedState = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']); // одноразовий — навіть якщо перевірка пройде, повторно не використати

if (!$code || !$expectedState || !hash_equals($expectedState, $state)) {
    header('Location: ' . BASE_PATH . '/?login_error=1');
    exit;
}

$user = google_exchange_code($code);
if (!$user) {
    log_login_attempt('unknown', false);
    header('Location: ' . BASE_PATH . '/?login_error=1');
    exit;
}

start_user_session($user);
log_login_attempt($user['email'], true);

header('Location: ' . BASE_PATH . '/');
exit;
