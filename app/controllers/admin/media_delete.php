<?php
// Видалення фото з медіатеки (/public/uploads/articles) — лише якщо воно
// зараз ніде не використовується (обкладинки статей/проєктів, скріншот
// відгуку, хіро-фото головної).
require_admin();
verify_csrf();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не підтримується']);
    exit;
}

$name = trim($_POST['name'] ?? '');
// Лише bare-ім'я файлу — без шляху, без "..", щоб виключити вихід за межі теки.
if ($name === '' || !preg_match('/^[a-zA-Z0-9_.-]+$/', $name) || str_contains($name, '..')) {
    http_response_code(400);
    echo json_encode(['error' => 'Некоректна назва файлу']);
    exit;
}

$path = '/uploads/articles/' . $name;

try {
    $pdo = db_connect();

    $usedIn = [];

    $stmt = $pdo->prepare("SELECT title FROM articles WHERE cover_image = ?");
    $stmt->execute([$path]);
    foreach ($stmt->fetchAll() as $row) {
        $usedIn[] = 'стаття «' . $row['title'] . '»';
    }

    $stmt = $pdo->prepare("SELECT title FROM projects WHERE cover_image = ?");
    $stmt->execute([$path]);
    foreach ($stmt->fetchAll() as $row) {
        $usedIn[] = 'проєкт «' . $row['title'] . '»';
    }

    $stmt = $pdo->prepare("SELECT quote_year FROM quotes WHERE quote_image = ?");
    $stmt->execute([$path]);
    foreach ($stmt->fetchAll() as $row) {
        $usedIn[] = 'відгук (' . $row['quote_year'] . ')';
    }

    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'home_hero_image' AND setting_value = ?");
    $stmt->execute([$path]);
    if ($stmt->fetch()) {
        $usedIn[] = 'фото хіро-банера на головній';
    }

    if (!empty($usedIn)) {
        http_response_code(409);
        echo json_encode(['error' => 'Це фото зараз використовується: ' . implode(', ', $usedIn) . '. Спершу приберіть його звідти.']);
        exit;
    }

    $fullPath = dirname(__DIR__, 3) . '/public/uploads/articles/' . $name;
    $realDir = realpath(dirname(__DIR__, 3) . '/public/uploads/articles');
    $realFile = realpath($fullPath);

    // Додаткова перевірка: реальний шлях файлу справді лежить у теці uploads/articles.
    if ($realFile === false || $realDir === false || !str_starts_with($realFile, $realDir)) {
        http_response_code(404);
        echo json_encode(['error' => 'Файл не знайдено']);
        exit;
    }

    if (!unlink($realFile)) {
        http_response_code(500);
        echo json_encode(['error' => 'Не вдалося видалити файл']);
        exit;
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[media_delete] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Помилка сервера']);
}
