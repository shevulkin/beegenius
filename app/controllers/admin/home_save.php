<?php
require_admin();
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Білий список ключів — не даємо записати довільний setting_key з форми.
$allowedKeys = [
    'home_kicker',
    'home_title',
    'home_subtitle',
    'home_about_title',
    'home_about_text',
    'home_bleed_caption',
];

$heroImage = trim($_POST['home_hero_image'] ?? '');
if ($heroImage !== '' && !preg_match('#^/(uploads|assets)/#i', $heroImage)) {
    $heroImage = '';
}

$bleedImage = trim($_POST['home_bleed_image'] ?? '');
if ($bleedImage !== '' && !preg_match('#^/(uploads|assets)/#i', $bleedImage)) {
    $bleedImage = '';
}

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($allowedKeys as $key) {
        $value = trim($_POST[$key] ?? '');
        $stmt->execute([$key, $value]);
    }
    $stmt->execute(['home_hero_image', $heroImage]);
    $stmt->execute(['home_bleed_image', $bleedImage]);
    $_SESSION['flash_saved'] = true;
    header('Location: ' . BASE_PATH . '/admin/home');
    exit;
} catch (Throwable $e) {
    error_log('[home_save] ' . $e->getMessage());
    http_response_code(500);
    echo 'Не вдалося зберегти головну сторінку.';
    exit;
}
