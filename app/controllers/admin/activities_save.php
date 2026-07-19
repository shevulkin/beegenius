<?php
require_admin();
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$title = trim($_POST['title'] ?? '');
$text = trim($_POST['text'] ?? '');

if ($title === '' || $text === '') {
    http_response_code(400);
    echo 'Назва і опис обовʼязкові.';
    exit;
}

try {
    $pdo = db_connect();
    if ($id) {
        $stmt = $pdo->prepare('UPDATE activities SET title=?, text=? WHERE id=?');
        $stmt->execute([$title, $text, $id]);
        $savedId = $id;
    } else {
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM activities')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO activities (sort_order, title, text) VALUES (?, ?, ?)');
        $stmt->execute([$maxOrder + 1, $title, $text]);
        $savedId = (int)$pdo->lastInsertId();
    }
    $_SESSION['flash_saved'] = true;
    header('Location: ' . BASE_PATH . '/admin/activities/edit/' . $savedId);
    exit;
} catch (Throwable $e) {
    error_log('[activities_save] ' . $e->getMessage());
    http_response_code(500);
    echo 'Не вдалося зберегти картку.';
    exit;
}
