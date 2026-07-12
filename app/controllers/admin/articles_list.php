<?php
// Список статей одного типу (блог / наука / оголошення) в адмінці.
require_admin();

$type = $_GET['type'] ?? 'blog';
$labels = [
    'blog' => ['title' => 'Пости блогу', 'add' => '+ Додати пост', 'confirm' => 'Видалити цей пост?'],
    'science' => ['title' => 'Наукові статті', 'add' => '+ Додати статтю', 'confirm' => 'Видалити цю статтю?'],
    'announcement' => ['title' => 'Оголошення', 'add' => '+ Додати оголошення', 'confirm' => 'Видалити це оголошення?'],
];
if (!isset($labels[$type])) {
    http_response_code(404);
    echo '404 — розділ не знайдено';
    exit;
}

$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$from = trim($_GET['from'] ?? '');
$until = trim($_GET['until'] ?? '');

$articles = [];
$perPage = 20;
[$page, $offset] = paginate_offset($perPage);
$totalPages = 1;
try {
    $pdo = db_connect();

    $conditions = ["type = ?"];
    $params = [$type];
    if ($q !== '') {
        $conditions[] = "title LIKE ?";
        $params[] = '%' . $q . '%';
    }
    if ($statusFilter === 'draft' || $statusFilter === 'published') {
        $conditions[] = "status = ?";
        $params[] = $statusFilter;
    }
    if ($from !== '') {
        $conditions[] = "COALESCE(published_at, created_at) >= ?";
        $params[] = $from . ' 00:00:00';
    }
    if ($until !== '') {
        $conditions[] = "COALESCE(published_at, created_at) <= ?";
        $params[] = $until . ' 23:59:59';
    }
    $whereSql = implode(' AND ', $conditions);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT id, title, status, published_at FROM articles WHERE $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[admin/articles_list] DB error: ' . $e->getMessage());
}

$pageTitle = $labels[$type]['title'] . ' — Адмінка — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <div style="display:flex;justify-content:space-between;align-items:center;margin:16px 0 24px">
    <h2 class="serif" style="margin:0"><?= htmlspecialchars($labels[$type]['title']) ?></h2>
    <a class="btn btn-primary" href="<?= BASE_PATH ?>/admin/articles/new?type=<?= urlencode($type) ?>"><?= htmlspecialchars($labels[$type]['add']) ?></a>
  </div>
  <form class="admin-filter" method="get" action="<?= BASE_PATH ?>/admin/articles">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
    <input type="text" name="q" placeholder="Пошук за заголовком…" value="<?= htmlspecialchars($q) ?>">
    <select name="status">
      <option value="">Усі статуси</option>
      <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Чернетка</option>
      <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Опубліковано</option>
    </select>
    <label>З <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
    <label>По <input type="date" name="until" value="<?= htmlspecialchars($until) ?>"></label>
    <button class="btn btn-secondary" type="submit">Фільтрувати</button>
    <?php if ($q !== '' || $statusFilter !== '' || $from !== '' || $until !== ''): ?>
      <a class="search-clear" href="<?= BASE_PATH ?>/admin/articles?type=<?= urlencode($type) ?>">Скинути</a>
    <?php endif; ?>
  </form>
  <?php if (empty($articles)): ?>
    <p class="empty"><?= ($q !== '' || $statusFilter !== '' || $from !== '' || $until !== '') ? 'Нічого не знайдено за цим фільтром.' : 'Ще немає жодного запису.' ?></p>
  <?php endif; ?>
  <?php foreach ($articles as $a): ?>
    <div class="list-row">
      <a class="list-row-link" href="<?= BASE_PATH ?>/admin/articles/edit/<?= (int)$a['id'] ?>">
        <div class="info">
          <h4><?= htmlspecialchars($a['title']) ?></h4>
          <div class="d"><?= htmlspecialchars($a['status']) ?><?= $a['published_at'] ? ' · ' . htmlspecialchars(format_uk_date($a['published_at'])) : '' ?></div>
        </div>
      </a>
      <form method="post" action="<?= BASE_PATH ?>/admin/articles/delete" style="display:inline" onsubmit="return confirm('<?= htmlspecialchars($labels[$type]['confirm']) ?>')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <button class="icon-btn" type="submit" title="Видалити">✕</button>
      </form>
    </div>
  <?php endforeach; ?>
  <?= render_pagination($page, $totalPages, BASE_PATH . '/admin/articles', ['type' => $type, 'q' => $q, 'status' => $statusFilter, 'from' => $from, 'until' => $until]) ?>
</section>
<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
