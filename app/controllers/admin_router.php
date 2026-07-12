<?php
// Дашборд адмінки: хаб з картками-блоками (пости, наука, оголошення,
// відгуки, проєкти, теги, контакти). Клік по картці — окрема сторінка
// зі списком саме цього блоку. Гейт доступу — нижче.

$user = current_user();

if (!$user) {
    header('Location: ' . BASE_PATH . '/login');
    exit;
}

if (!is_admin()) {
    $pageTitle = 'Немає доступу — Bee Genius';
    $active = 'admin';
    require __DIR__ . '/../views/layout/header.php';
    echo '<section class="section"><h2>Немає доступу</h2><p>Ви увійшли як ' . htmlspecialchars($user['email']) . ', але ваш акаунт не має прав адміністратора.</p></section>';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

$counts = [
    'blog' => 0, 'science' => 0, 'announcement' => 0,
    'quotes' => 0, 'projects' => 0, 'tags' => 0,
];
try {
    $pdo = db_connect();
    $stmt = $pdo->query("SELECT type, COUNT(*) AS n FROM articles GROUP BY type");
    foreach ($stmt->fetchAll() as $row) {
        if (isset($counts[$row['type']])) $counts[$row['type']] = (int)$row['n'];
    }
    $counts['quotes'] = (int)$pdo->query('SELECT COUNT(*) FROM quotes')->fetchColumn();
    $counts['projects'] = (int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $counts['tags'] = (int)$pdo->query('SELECT COUNT(*) FROM tags')->fetchColumn();
} catch (Throwable $e) {
    error_log('[admin_router] DB error: ' . $e->getMessage());
}

$tiles = [
    ['href' => '/admin/articles?type=blog', 'title' => 'Пости блогу', 'count' => $counts['blog']],
    ['href' => '/admin/articles?type=science', 'title' => 'Наукові статті', 'count' => $counts['science']],
    ['href' => '/admin/articles?type=announcement', 'title' => 'Оголошення', 'count' => $counts['announcement']],
    ['href' => '/admin/quotes', 'title' => 'Відгуки', 'count' => $counts['quotes']],
    ['href' => '/admin/projects', 'title' => 'Проєкти', 'count' => $counts['projects']],
    ['href' => '/admin/tags', 'title' => 'Теги', 'count' => $counts['tags']],
];

$pageTitle = 'Адмінка — Bee Genius';
$active = 'admin';
require __DIR__ . '/../views/layout/header.php';
?>
<section class="section">
  <h2 class="serif">Панель, <?= htmlspecialchars($user['name']) ?></h2>
  <p style="color:var(--ink-soft);font-size:14.5px;margin:0 0 32px">Обери блок, який хочеш редагувати.</p>

  <div class="admin-tiles">
    <?php foreach ($tiles as $t): ?>
      <a class="admin-tile" href="<?= BASE_PATH . $t['href'] ?>">
        <span class="admin-tile-title"><?= htmlspecialchars($t['title']) ?></span>
        <span class="admin-tile-count"><?= (int)$t['count'] ?></span>
      </a>
    <?php endforeach; ?>
    <a class="admin-tile" href="<?= BASE_PATH ?>/admin/home">
      <span class="admin-tile-title">Головна сторінка</span>
      <span class="admin-tile-count">⚙</span>
    </a>
    <a class="admin-tile" href="<?= BASE_PATH ?>/admin/settings">
      <span class="admin-tile-title">Контакти</span>
      <span class="admin-tile-count">⚙</span>
    </a>
  </div>
</section>
<?php require __DIR__ . '/../views/layout/footer.php'; ?>
