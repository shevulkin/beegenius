<?php
// Скопіюйте цей файл у config.php і заповніть реальними даними.
// config.php додано у .gitignore і НЕ потрапляє у git.

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'beegenius',
        'user' => 'db_user',
        'pass' => 'db_password',
        'charset' => 'utf8mb4',
    ],
    'google_oauth' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => 'https://example.com/auth/callback',
    ],
    // Список email-адрес, які отримують роль admin (можуть редагувати сайт).
    // Увійти через Google може будь-хто — але без email у цьому списку
    // людина лишається звичайним відвідувачем без доступу до адмінки.
    'admin_whitelist' => [
        // 'you@gmail.com',
    ],
    'site' => [
        'name' => 'Bee Genius',
        'contact' => [
            'telegram' => '',
            'viber' => '',
            'whatsapp' => '',
            'email' => '',
            'phone' => '',
        ],
    ],
];
