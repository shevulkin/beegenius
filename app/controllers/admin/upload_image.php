<?php
// Завантаження фото прямо з редактора статті (Quill вставляє картинку —
// вона одразу летить сюди, а назад повертається посилання).
require_admin(); // перевірка ролі на кожну дію, а не лише на вході в адмінку

header('Content-Type: application/json; charset=utf-8');

$sentToken = $_POST['csrf_token'] ?? '';
if (!$sentToken || !hash_equals($_SESSION['csrf_token'] ?? '', $sentToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Недійсний CSRF-токен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Немає файлу']);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Помилка завантаження']);
    exit;
}

// Обмеження розміру — 5 МБ.
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Файл завеликий (максимум 5 МБ)']);
    exit;
}

// Перевірка реального вмісту файлу (не лише розширення/MIME із заголовка).
$imageInfo = @getimagesize($file['tmp_name']);
$allowedTypes = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG => 'png',
    IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_GIF => 'gif',
];
if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
    http_response_code(400);
    echo json_encode(['error' => 'Це не зображення (дозволено JPG, PNG, WEBP, GIF)']);
    exit;
}

$ext = $allowedTypes[$imageInfo[2]];
$filename = bin2hex(random_bytes(16)) . '.' . $ext;

$uploadDir = dirname(__DIR__, 3) . '/public/uploads/articles';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'Не вдалося зберегти файл']);
    exit;
}

echo json_encode(['url' => BASE_PATH . '/uploads/articles/' . $filename]);
