<?php
// Список оголошень / пропозицій, з опційним фільтром за тегом (?tag=slug) і пошуком за заголовком (?q=).
$posts = [];
$allTags = [];
$activeTag = trim($_GET['tag'] ?? '');
$q = trim($_GET['q'] ?? '');
$perPage = 12;
[$page, $offset] = paginate_offset($perPage);
$totalPages = 1;
try {
    $pdo = db_connect();
    $allTags = get_tags_for_type($pdo, 'announcement');

    $params = [];
    $conditions = ["a.type = 'announcement'", "a.status = 'published'"];
    $join = '';
    if ($activeTag !== '') {
        $join = "JOIN article_tags at ON at.article_id = a.id JOIN tags t ON t.id = at.tag_id";
        $conditions[] = "t.slug = ?";
        $params[] = $activeTag;
    }
    if ($q !== '') {
        $conditions[] = "(a.title LIKE ? OR EXISTS (
            SELECT 1 FROM article_tags at2 JOIN tags t2 ON t2.id = at2.tag_id
            WHERE at2.article_id = a.id AND t2.name LIKE ?
        ))";
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }
    $whereSql = implode(' AND ', $conditions);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles a $join WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT a.id, a.title, a.slug, a.excerpt, a.cover_image, a.published_at
            FROM articles a $join
            WHERE $whereSql
            ORDER BY a.published_at DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $tagsByArticle = get_tags_for_articles($pdo, array_column($rows, 'id'));
    foreach ($rows as $p) {
        $posts[] = [
            'slug' => $p['slug'],
            'cover' => BASE_PATH . $p['cover_image'],
            'date' => format_uk_date($p['published_at']),
            'title' => $p['title'],
            'excerpt' => $p['excerpt'],
            'tags' => $tagsByArticle[(int)$p['id']] ?? [],
        ];
    }
} catch (Throwable $e) {
    error_log('[announcements_list] DB error: ' . $e->getMessage());
}

$pageTitle = 'Оголошення — Bee Genius';
$metaDescription = "Актуальні оголошення й пропозиції Валентина: бджолосім'ї, вироби з воску та інше.";
$active = 'announcements';
require __DIR__ . '/../views/layout/header.php';
?>
<section class="section">
  <div class="section-head">
    <span class="kicker">Оголошення</span>
    <h1 class="serif">Оголошення та пропозиції</h1>
    <p>Актуальні пропозиції Валентина: бджолосім'ї, вироби з воску та інше.</p>
  </div>
  <form class="search-form" method="get" action="<?= BASE_PATH ?>/announcements">
    <?php if ($activeTag !== ''): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($activeTag) ?>"><?php endif; ?>
    <input type="text" name="q" placeholder="Пошук за заголовком або тегом…" value="<?= htmlspecialchars($q) ?>">
    <button class="btn btn-secondary" type="submit">Знайти</button>
    <?php if ($q !== ''): ?><a class="search-clear" href="<?= BASE_PATH ?>/announcements<?= $activeTag !== '' ? '?tag=' . urlencode($activeTag) : '' ?>">Скинути</a><?php endif; ?>
  </form>
  <?php if (!empty($allTags)): ?>
    <div class="tag-filter">
      <a class="tag-chip <?= $activeTag === '' ? 'active' : '' ?>" href="<?= BASE_PATH ?>/announcements<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">Усі</a>
      <?php foreach ($allTags as $t): ?>
        <a class="tag-chip <?= $activeTag === $t['slug'] ? 'active' : '' ?>" href="<?= BASE_PATH ?>/announcements?tag=<?= urlencode($t['slug']) ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">#<?= htmlspecialchars($t['name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="card-grid">
    <?php foreach ($posts as $p): ?>
      <a class="post-card" href="<?= BASE_PATH ?>/announcements/<?= urlencode($p['slug']) ?>">
        <div class="post-cover"><img src="<?= htmlspecialchars($p['cover']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"></div>
        <div class="post-body">
          <div class="post-date"><?= htmlspecialchars($p['date']) ?></div>
          <h3><?= htmlspecialchars($p['title']) ?></h3>
          <p><?= htmlspecialchars($p['excerpt']) ?></p>
          <?php if (!empty($p['tags'])): ?>
            <div class="tag-list">
              <?php foreach ($p['tags'] as $t): ?>
                <span class="tag-chip small">#<?= htmlspecialchars($t['name']) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (empty($posts)): ?>
    <p class="empty"><?= ($activeTag !== '' || $q !== '') ? 'Нічого не знайдено за цим фільтром.' : 'Поки що немає жодного оголошення.' ?></p>
  <?php endif; ?>
  <?= render_pagination($page, $totalPages, BASE_PATH . '/announcements', ['tag' => $activeTag, 'q' => $q]) ?>
</section>
<?php require __DIR__ . '/../views/layout/footer.php'; ?>
