<?php
// Автентифікація через Google OAuth 2.0 + ролі.
// Роль зараз лише одна — "admin" (білий список email у config.php).
// Будь-хто інший, хто увійшов через Google, лишається звичайним
// відвідувачем: бачить те саме, що й неавторизований користувач,
// просто зверху показується його імʼя замість кнопки "Увійти".

// Сесія адміна закінчується сама: за бездіяльністю або в будь-якому разі
// через SESSION_ABSOLUTE_TIMEOUT від моменту входу — навіть якщо вкладка
// лишається відкритою.
const SESSION_IDLE_TIMEOUT = 30 * 60;        // 30 хв без жодного запиту
const SESSION_ABSOLUTE_TIMEOUT = 8 * 60 * 60; // 8 год від входу

function auth_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../../config/config.php';
    }
    return $config;
}

/**
 * Викликати одразу після успішного логіну (Google OAuth або dev-login) —
 * записує користувача в сесію разом з міткою часу для перевірки таймаутів.
 */
function start_user_session(array $user): void
{
    session_regenerate_id(true); // нова сесія після логіну — захист від session fixation
    $now = time();
    $_SESSION['user'] = $user;
    $_SESSION['login_at'] = $now;
    $_SESSION['last_activity'] = $now;
}

function current_user(): ?array
{
    if (empty($_SESSION['user'])) return null;

    $now = time();
    $loginAt = $_SESSION['login_at'] ?? $now;
    $lastActivity = $_SESSION['last_activity'] ?? $now;

    if (($now - $lastActivity) > SESSION_IDLE_TIMEOUT || ($now - $loginAt) > SESSION_ABSOLUTE_TIMEOUT) {
        unset($_SESSION['user'], $_SESSION['login_at'], $_SESSION['last_activity']);
        session_regenerate_id(true);
        $_SESSION['flash_session_expired'] = true;
        return null;
    }

    $_SESSION['last_activity'] = $now;
    return $_SESSION['user'];
}

function is_admin(): bool
{
    $user = current_user();
    if (!$user || empty($user['email'])) return false;
    // Білий список читається з config.php наживо при кожному виклику
    // (не кешується в сесії), тож якщо email прибрали зі списку —
    // права зникають одразу на наступному запиті, без потреби
    // перелогінюватись.
    $whitelist = array_map('strtolower', auth_config()['admin_whitelist'] ?? []);
    return in_array(strtolower($user['email']), $whitelist, true);
}

/**
 * Обовʼязковий гейт-виклик на самому початку КОЖНОГО контролера, що
 * додає, редагує чи видаляє дані (статті, фото, налаштування тощо).
 * Не покладаємось на те, що користувач "уже пройшов" перевірку на
 * сторінці адмінки раніше — роль перевіряється заново на кожну дію.
 */
function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Доступ заборонено: потрібна роль адміністратора.';
        exit;
    }
}

function google_login_url(): string
{
    $cfg = auth_config()['google_oauth'];
    // state — захист від CSRF на OAuth-флоу (RFC 6749 §10.12): без нього
    // зловмисник міг би підсунути власний ?code=... на callback жертви.
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $params = [
        'client_id' => $cfg['client_id'],
        'redirect_uri' => $cfg['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account',
        'state' => $state,
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Обмінює code на токен і повертає дані користувача Google
 * (email, name, picture), або null у разі помилки.
 */
function google_exchange_code(string $code): ?array
{
    $cfg = auth_config()['google_oauth'];
    if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
        error_log('[auth] Google OAuth не налаштований (немає client_id/client_secret у config.php)');
        return null;
    }

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code,
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri' => $cfg['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]),
    ]);
    $response = curl_exec($ch);
    $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    if (!$ok || !$response) return null;

    $token = json_decode($response, true);
    if (empty($token['access_token'])) return null;

    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
    ]);
    $userResponse = curl_exec($ch);
    curl_close($ch);
    if (!$userResponse) return null;

    $userInfo = json_decode($userResponse, true);
    if (empty($userInfo['email'])) return null;

    return [
        'email' => $userInfo['email'],
        'name' => $userInfo['name'] ?? $userInfo['email'],
        'picture' => $userInfo['picture'] ?? null,
    ];
}

function log_login_attempt(string $email, bool $success): void
{
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('INSERT INTO admin_login_log (email, success, ip) VALUES (?, ?, ?)');
        $stmt->execute([$email, $success ? 1 : 0, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Throwable $e) {
        error_log('[auth] Не вдалося записати спробу входу: ' . $e->getMessage());
    }
}
