<?php
// Простий front controller. Пізніше можна замінити на Slim, якщо
// маршрутів стане більше, але для старту достатньо цього.

declare(strict_types=1);
error_reporting(E_ALL);

// Сесія: httpOnly + secure (на https) cookie для захисту сесії адмінки.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

// SERVER_NAME сам собою ненадійний — за замовчуванням в Apache (UseCanonicalName Off)
// він може відображати заголовок Host, який клієнт задає довільно. Тому додатково
// звіряємо реальну IP-адресу з'єднання (REMOTE_ADDR), яку підробити заголовком не можна —
// це і є справжній гейт від /dev-login на проді.
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
define('IS_LOCAL', $isLocal);
ini_set('display_errors', $isLocal ? '1' : '0'); // на проді помилки не показуємо (безпека), пишемо в лог
ini_set('log_errors', '1');

// Зазвичай app/ лежить на рівень вище public/ (локально через XAMPP і за
// стандартної схеми деплою document root -> public/). Але на деяких shared-
// хостингах, де document root не можна змінити для головного домену, весь
// вміст public/ копіюється прямо в корінь сайту (поруч з app/, а не в теці
// public/ всередині нього) — тоді app/ опиняється поруч із цим файлом, а не
// на рівень вище.
$root = is_dir(dirname(__DIR__) . '/app') ? dirname(__DIR__) : __DIR__;
require $root . '/app/lib/db.php';
require $root . '/app/lib/helpers.php';
require $root . '/app/lib/auth.php';
require $root . '/app/lib/csrf.php';
require $root . '/app/lib/sanitize.php';
require $root . '/app/lib/tags.php';

// Базовий шлях застосунку відносно кореня домену.
// На проді (document root = /public) це буде '' — сайт у корені домену.
// Локально через XAMPP (http://localhost/beegenius/public/) це буде '/beegenius/public'.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = rtrim($scriptDir, '/');
define('BASE_PATH', $basePath);

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath));
}
$path = rtrim($requestPath, '/');
if ($path === '') $path = '/';

// Контакти потрібні на кожній сторінці (модалка "Написати" в шапці),
// тож дістаємо їх тут один раз, а не в кожному контролері окремо.
$contact = [
    'telegram' => '', 'instagram' => '', 'phone' => '',
    'viber' => '', 'whatsapp' => '', 'email' => '',
];
try {
    $pdo = db_connect();
    $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'contact_%'")->fetchAll();
    foreach ($rows as $r) {
        $key = str_replace('contact_', '', $r['setting_key']);
        if (array_key_exists($key, $contact)) {
            $contact[$key] = $r['setting_value'];
        }
    }
} catch (Throwable $e) {
    error_log('[index] contact settings unavailable: ' . $e->getMessage());
}

switch (true) {
    case $path === '/':
        require $root . '/app/controllers/home.php';
        break;

    case $path === '/sitemap.xml':
        require $root . '/app/controllers/sitemap.php';
        break;

    case $path === '/robots.txt':
        require $root . '/app/controllers/robots.php';
        break;

    case $path === '/blog':
        require $root . '/app/controllers/blog_list.php';
        break;

    case preg_match('#^/blog/([a-z0-9\-]+)$#', $path, $m):
        $_GET['slug'] = $m[1];
        require $root . '/app/controllers/blog_show.php';
        break;

    case $path === '/science':
        require $root . '/app/controllers/science_list.php';
        break;

    case preg_match('#^/science/([a-z0-9\-]+)$#', $path, $m):
        $_GET['slug'] = $m[1];
        require $root . '/app/controllers/science_show.php';
        break;

    case $path === '/projects':
        require $root . '/app/controllers/projects_list.php';
        break;

    case preg_match('#^/projects/([a-z0-9\-]+)$#', $path, $m):
        $_GET['slug'] = $m[1];
        require $root . '/app/controllers/projects_show.php';
        break;

    case $path === '/z-bdzholamy-po-zhyttyu': // старі адреси — редирект на нову сторінку проєкту
    case $path === '/my-project':
    case $path === '/cases':
        http_response_code(301);
        header('Location: ' . BASE_PATH . '/projects/z-bdzholamy-po-zhyttyu');
        exit;

    case $path === '/announcements':
        require $root . '/app/controllers/announcements_list.php';
        break;

    case preg_match('#^/announcements/([a-z0-9\-]+)$#', $path, $m):
        $_GET['slug'] = $m[1];
        require $root . '/app/controllers/announcements_show.php';
        break;

    case $path === '/login':
        require $root . '/app/controllers/auth_login.php';
        break;

    case $path === '/auth/callback':
        require $root . '/app/controllers/auth_callback.php';
        break;

    case $path === '/logout':
        require $root . '/app/controllers/auth_logout.php';
        break;

    case $path === '/dev-login': // ТІЛЬКИ для локального тестування, див. auth_login.php
        require $root . '/app/controllers/dev_login.php';
        break;

    case $path === '/admin/upload-image':
        require $root . '/app/controllers/admin/upload_image.php';
        break;

    case $path === '/admin/media/list':
        require $root . '/app/controllers/admin/media_list.php';
        break;

    case $path === '/admin/media/delete':
        require $root . '/app/controllers/admin/media_delete.php';
        break;

    case $path === '/admin/articles/save':
        require $root . '/app/controllers/admin/articles_save.php';
        break;

    case $path === '/admin/articles/delete':
        require $root . '/app/controllers/admin/articles_delete.php';
        break;

    case preg_match('#^/admin/articles/edit/(\d+)$#', $path, $m):
        $_GET['id'] = $m[1];
        require $root . '/app/controllers/admin/articles_edit.php';
        break;

    case $path === '/admin/articles/new':
        require $root . '/app/controllers/admin/articles_edit.php';
        break;

    case $path === '/admin/articles':
        require $root . '/app/controllers/admin/articles_list.php';
        break;

    case $path === '/admin/quotes':
        require $root . '/app/controllers/admin/quotes_manage.php';
        break;

    case $path === '/admin/quotes/save':
        require $root . '/app/controllers/admin/quotes_save.php';
        break;

    case $path === '/admin/quotes/delete':
        require $root . '/app/controllers/admin/quotes_delete.php';
        break;

    case preg_match('#^/admin/quotes/edit/(\d+)$#', $path, $m):
        $_GET['id'] = $m[1];
        require $root . '/app/controllers/admin/quotes_edit.php';
        break;

    case $path === '/admin/quotes/new':
        require $root . '/app/controllers/admin/quotes_edit.php';
        break;

    case $path === '/admin/projects':
        require $root . '/app/controllers/admin/projects_manage.php';
        break;

    case $path === '/admin/projects/save':
        require $root . '/app/controllers/admin/projects_save.php';
        break;

    case $path === '/admin/projects/delete':
        require $root . '/app/controllers/admin/projects_delete.php';
        break;

    case preg_match('#^/admin/projects/edit/(\d+)$#', $path, $m):
        $_GET['id'] = $m[1];
        require $root . '/app/controllers/admin/projects_edit.php';
        break;

    case $path === '/admin/projects/new':
        require $root . '/app/controllers/admin/projects_edit.php';
        break;

    case $path === '/admin/tags':
        require $root . '/app/controllers/admin/tags_manage.php';
        break;

    case $path === '/admin/tags/delete':
        require $root . '/app/controllers/admin/tags_delete.php';
        break;

    case $path === '/admin/tags/list':
        require $root . '/app/controllers/admin/tags_list.php';
        break;

    case $path === '/admin/settings':
        require $root . '/app/controllers/admin/settings_edit.php';
        break;

    case $path === '/admin/settings/save':
        require $root . '/app/controllers/admin/settings_save.php';
        break;

    case $path === '/admin/home':
        require $root . '/app/controllers/admin/home_edit.php';
        break;

    case $path === '/admin/home/save':
        require $root . '/app/controllers/admin/home_save.php';
        break;

    case $path === '/admin/activities': // список тепер вбудовано на /admin/home
        header('Location: ' . BASE_PATH . '/admin/home');
        exit;

    case $path === '/admin/activities/save':
        require $root . '/app/controllers/admin/activities_save.php';
        break;

    case $path === '/admin/activities/delete':
        require $root . '/app/controllers/admin/activities_delete.php';
        break;

    case preg_match('#^/admin/activities/edit/(\d+)$#', $path, $m):
        $_GET['id'] = $m[1];
        require $root . '/app/controllers/admin/activities_edit.php';
        break;

    case $path === '/admin/activities/new':
        require $root . '/app/controllers/admin/activities_edit.php';
        break;

    case $path === '/admin' || str_starts_with($path, '/admin/'):
        require $root . '/app/controllers/admin_router.php';
        break;

    default:
        http_response_code(404);
        echo '404 — сторінку не знайдено';
}
