<?php
// Список уже завантажених фото з /public/uploads/articles — для вибору
// в редакторі статті замість повторного завантаження того самого файлу.
require_admin();

header('Content-Type: application/json; charset=utf-8');

$dir = dirname(__DIR__, 3) . '/public/uploads/articles';
$images = [];

if (is_dir($dir)) {
    $files = scandir($dir);
    // Найновіші файли зверху.
    $files = array_filter($files, fn($f) => !in_array($f, ['.', '..', '.gitkeep'], true));
    usort($files, fn($a, $b) => filemtime("$dir/$b") <=> filemtime("$dir/$a"));

    foreach ($files as $f) {
        $images[] = [
            'url' => BASE_PATH . '/uploads/articles/' . $f,
            'name' => $f,
        ];
    }
}

echo json_encode(['images' => $images]);
