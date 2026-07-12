<?php
require_admin();
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$text = trim($_POST['quote_text'] ?? '');
$year = trim($_POST['quote_year'] ?? '');
$image = trim($_POST['quote_image'] ?? '');

if ($text === '' || $year === '') {
    http_response_code(400);
    echo 'Текст відгуку і рік обовʼязкові.';
    exit;
}
// Захист від довільного значення в прихованому полі — має вказувати
// або на наші завантажені файли, або бути порожнім.
if ($image !== '' && !preg_match('#^/(uploads|assets)/#i', $image)) {
    $image = '';
}

try {
    $pdo = db_connect();
    if ($id) {
        $stmt = $pdo->prepare('UPDATE quotes SET quote_text=?, quote_year=?, quote_image=? WHERE id=?');
        $stmt->execute([$text, $year, $image ?: null, $id]);
    } else {
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM quotes')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO quotes (sort_order, quote_text, quote_year, quote_image) VALUES (?, ?, ?, ?)');
        $stmt->execute([$maxOrder + 1, $text, $year, $image ?: null]);
    }
    header('Location: ' . BASE_PATH . '/admin');
    exit;
} catch (Throwable $e) {
    error_log('[quotes_save] ' . $e->getMessage());
    http_response_code(500);
    echo 'Не вдалося зберегти відгук.';
    exit;
}
