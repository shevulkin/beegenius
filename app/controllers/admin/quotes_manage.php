<?php
// Список відгуків в адмінці.
require_admin();

$filterQ = trim($_GET['q'] ?? '');

$quotes = [];
$perPage = 20;
[$page, $offset] = paginate_offset($perPage);
$totalPages = 1;
try {
    $pdo = db_connect();
    if ($filterQ !== '') {
        $total = (int)(function () use ($pdo, $filterQ) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE quote_text LIKE ? OR quote_year LIKE ?");
            $s->execute(['%' . $filterQ . '%', '%' . $filterQ . '%']);
            return $s->fetchColumn();
        })();
    } else {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();
    }
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    if ($filterQ !== '') {
        $stmt = $pdo->prepare("SELECT id, quote_text, quote_year FROM quotes WHERE quote_text LIKE ? OR quote_year LIKE ? ORDER BY sort_order ASC LIMIT $perPage OFFSET $offset");
        $stmt->execute(['%' . $filterQ . '%', '%' . $filterQ . '%']);
    } else {
        $stmt = $pdo->query("SELECT id, quote_text, quote_year FROM quotes ORDER BY sort_order ASC LIMIT $perPage OFFSET $offset");
    }
    $quotes = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[admin/quotes_manage] DB error: ' . $e->getMessage());
}

$pageTitle = 'Відгуки — Адмінка — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <div style="display:flex;justify-content:space-between;align-items:center;margin:16px 0 24px">
    <h2 class="serif" style="margin:0">Відгуки</h2>
    <a class="btn btn-primary" href="<?= BASE_PATH ?>/admin/quotes/new">+ Додати відгук</a>
  </div>
  <form class="admin-filter" method="get" action="<?= BASE_PATH ?>/admin/quotes">
    <input type="text" name="q" placeholder="Пошук за текстом або роком…" value="<?= htmlspecialchars($filterQ) ?>">
    <button class="btn btn-secondary" type="submit">Фільтрувати</button>
    <?php if ($filterQ !== ''): ?>
      <a class="search-clear" href="<?= BASE_PATH ?>/admin/quotes">Скинути</a>
    <?php endif; ?>
  </form>
  <?php if (empty($quotes)): ?>
    <p class="empty"><?= $filterQ !== '' ? 'Нічого не знайдено за цим фільтром.' : 'Ще немає жодного відгуку.' ?></p>
  <?php endif; ?>
  <?php foreach ($quotes as $q): ?>
    <div class="list-row">
      <a class="list-row-link" href="<?= BASE_PATH ?>/admin/quotes/edit/<?= (int)$q['id'] ?>">
        <div class="info">
          <h4><?= htmlspecialchars(mb_strimwidth($q['quote_text'], 0, 80, '…')) ?></h4>
          <div class="d"><?= htmlspecialchars($q['quote_year']) ?></div>
        </div>
      </a>
      <form method="post" action="<?= BASE_PATH ?>/admin/quotes/delete" style="display:inline" onsubmit="return confirm('Видалити цей відгук?')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
        <button class="icon-btn" type="submit" title="Видалити">✕</button>
      </form>
    </div>
  <?php endforeach; ?>
  <?= render_pagination($page, $totalPages, BASE_PATH . '/admin/quotes', ['q' => $filterQ]) ?>
</section>
<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
