<?php
// Список проєктів Валентина.
$projects = [];
$perPage = 12;
[$page, $offset] = paginate_offset($perPage);
$totalPages = 1;
try {
    $pdo = db_connect();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'published'")->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->query(
        "SELECT title, slug, subtitle, cover_image FROM projects
         WHERE status = 'published' ORDER BY sort_order ASC, created_at ASC
         LIMIT $perPage OFFSET $offset"
    );
    foreach ($stmt->fetchAll() as $p) {
        $projects[] = [
            'slug' => $p['slug'],
            'title' => $p['title'],
            'subtitle' => $p['subtitle'],
            'cover' => !empty($p['cover_image']) ? BASE_PATH . $p['cover_image'] : '',
        ];
    }
} catch (Throwable $e) {
    error_log('[projects_list] DB error: ' . $e->getMessage());
}

$pageTitle = 'Проєкти — Bee Genius';
$metaDescription = 'Проєкти та ініціативи Валентина, повʼязані з бджільництвом.';
$active = 'projects';
require __DIR__ . '/../views/layout/header.php';
?>
<section class="section">
  <div class="section-head">
    <span class="kicker">Проєкти</span>
    <h1 class="serif">Проєкти Валентина</h1>
    <p>Ініціативи, які я веду поруч з пасікою.</p>
  </div>
  <div class="card-grid">
    <?php foreach ($projects as $p): ?>
      <a class="post-card" href="<?= BASE_PATH ?>/projects/<?= urlencode($p['slug']) ?>">
        <?php if (!empty($p['cover'])): ?>
          <div class="post-cover"><img src="<?= htmlspecialchars($p['cover']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"></div>
        <?php endif; ?>
        <div class="post-body">
          <h3><?= htmlspecialchars($p['title']) ?></h3>
          <?php if (!empty($p['subtitle'])): ?>
            <p><?= htmlspecialchars($p['subtitle']) ?></p>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (empty($projects)): ?>
    <p class="empty">Поки що немає жодного проєкту.</p>
  <?php endif; ?>
  <?= render_pagination($page, $totalPages, BASE_PATH . '/projects') ?>
</section>
<?php require __DIR__ . '/../views/layout/footer.php'; ?>
