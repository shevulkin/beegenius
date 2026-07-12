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
        $stmt = $pdo->prepare('DELETE FROM quotes WHERE id = ?');
        $stmt->execute([$id]);
    } catch (Throwable $e) {
        error_log('[quotes_delete] ' . $e->getMessage());
    }
}

header('Location: ' . BASE_PATH . '/admin');
exit;
