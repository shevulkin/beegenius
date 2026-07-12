<?php
// JSON-список усіх наявних тегів — для автопідказок у полі "Теги" форми статті.
require_admin();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db_connect();
    $tags = get_all_tags($pdo);
} catch (Throwable $e) {
    error_log('[tags_list] ' . $e->getMessage());
    $tags = [];
}

echo json_encode(['tags' => array_map(fn($t) => ['name' => $t['name']], $tags)]);
