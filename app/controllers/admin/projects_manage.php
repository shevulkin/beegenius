<?php
// Список проєктів в адмінці.
require_admin();

$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$from = trim($_GET['from'] ?? '');
$until = trim($_GET['until'] ?? '');

$projects = [];
$perPage = 20;
[$page, $offset] = paginate_offset($perPage);
$totalPages = 1;
try {
    $pdo = db_connect();

    $conditions = ["1=1"];
    $params = [];
    if ($q !== '') {
        $conditions[] = "title LIKE ?";
        $params[] = '%' . $q . '%';
    }
    if ($statusFilter === 'draft' || $statusFilter === 'published') {
        $conditions[] = "status = ?";
        $params[] = $statusFilter;
    }
    if ($from !== '') {
        $conditions[] = "created_at >= ?";
        $params[] = $from . ' 00:00:00';
    }
    if ($until !== '') {
        $conditions[] = "created_at <= ?";
        $params[] = $until . ' 23:59:59';
    }
    $whereSql = implode(' AND ', $conditions);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT id, title, status FROM projects WHERE $whereSql ORDER BY sort_order ASC, created_at ASC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[admin/projects_manage] DB error: ' . $e->getMessage());
}

$pageTitle = 'Проєкти — Адмінка — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <div style="display:flex;justify-content:space-between;align-items:center;margin:16px 0 24px">
    <h2 class="serif" style="margin:0">Проєкти</h2>
    <a class="btn btn-primary" href="<?= BASE_PATH ?>/admin/projects/new">+ Додати проєкт</a>
  </div>
  <form class="admin-filter" method="get" action="<?= BASE_PATH ?>/admin/projects">
    <input type="text" name="q" placeholder="Пошук за назвою…" value="<?= htmlspecialchars($q) ?>">
    <select name="status">
      <option value="">Усі статуси</option>
      <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Чернетка</option>
      <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Опубліковано</option>
    </select>
    <label>З <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
    <label>По <input type="date" name="until" value="<?= htmlspecialchars($until) ?>"></label>
    <button class="btn btn-secondary" type="submit">Фільтрувати</button>
    <?php if ($q !== '' || $statusFilter !== '' || $from !== '' || $until !== ''): ?>
      <a class="search-clear" href="<?= BASE_PATH ?>/admin/projects">Скинути</a>
    <?php endif; ?>
  </form>
  <?php if (empty($projects)): ?>
    <p class="empty"><?= ($q !== '' || $statusFilter !== '' || $from !== '' || $until !== '') ? 'Нічого не знайдено за цим фільтром.' : 'Ще немає жодного проєкту.' ?></p>
  <?php endif; ?>
  <?php foreach ($projects as $p): ?>
    <div class="list-row">
      <a class="list-row-link" href="<?= BASE_PATH ?>/admin/projects/edit/<?= (int)$p['id'] ?>">
        <div class="info">
          <h4><?= htmlspecialchars($p['title']) ?></h4>
          <div class="d"><?= htmlspecialchars($p['status']) ?></div>
        </div>
      </a>
      <form method="post" action="<?= BASE_PATH ?>/admin/projects/delete" style="display:inline" onsubmit="return confirm('Видалити цей проєкт?')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <button class="icon-btn" type="submit" title="Видалити">✕</button>
      </form>
    </div>
  <?php endforeach; ?>
  <?= render_pagination($page, $totalPages, BASE_PATH . '/admin/projects', ['q' => $q, 'status' => $statusFilter, 'from' => $from, 'until' => $until]) ?>
</section>
<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
