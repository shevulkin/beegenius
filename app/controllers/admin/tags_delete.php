<?php
require_admin();
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    try {
        $pdo = db_connect();
        // article_tags має ON DELETE CASCADE на tag_id — видалення тега
        // саме прибере всі звʼязки зі статтями, без ручного циклу.
        $stmt = $pdo->prepare('DELETE FROM tags WHERE id = ?');
        $stmt->execute([$id]);
    } catch (Throwable $e) {
        error_log('[tags_delete] ' . $e->getMessage());
    }
}

header('Location: ' . BASE_PATH . '/admin/tags');
exit;
