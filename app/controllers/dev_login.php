<?php
// ⚠️ ЛИШЕ ДЛЯ ЛОКАЛЬНОГО ТЕСТУВАННЯ. Симулює вхід через Google без
// реального OAuth, щоб можна було перевірити адмінку до того, як буде
// зареєстрований застосунок у Google Cloud Console. На проді (не localhost)
// цей маршрут завжди повертає 404 — жодного обходу автентифікації поза
// локальною машиною розробника.

if (!IS_LOCAL) {
    http_response_code(404);
    echo '404 — сторінку не знайдено';
    exit;
}

start_user_session([
    'email' => 'test-admin@local.dev',
    'name' => 'Тестовий адміністратор',
    'picture' => null,
]);
log_login_attempt('test-admin@local.dev', true);

header('Location: ' . BASE_PATH . '/admin');
exit;
