<?php
require_admin();
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Білий список ключів — не даємо записати довільний setting_key з форми.
$allowedKeys = [
    'contact_phone',
    'contact_email',
    'contact_telegram',
    'contact_viber',
    'contact_whatsapp',
    'contact_instagram',
];

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($allowedKeys as $key) {
        $value = trim($_POST[$key] ?? '');
        if ($key === 'contact_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo 'Некоректний email.';
            exit;
        }
        $stmt->execute([$key, $value]);
    }
    header('Location: ' . BASE_PATH . '/admin');
    exit;
} catch (Throwable $e) {
    error_log('[settings_save] ' . $e->getMessage());
    http_response_code(500);
    echo 'Не вдалося зберегти контакти.';
    exit;
}
