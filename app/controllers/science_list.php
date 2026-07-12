<?php
// Список наукових статей, з опційним фільтром за тегом (?tag=slug) і пошуком за заголовком (?q=).
$articles = [];
$allTags = [];
$activeTag = trim($_GET['tag'] ?? '');
$q = trim($_GET['q'] ?? '');
$perPage = 12;
[$page, $offset] = paginate_offset($perPage);
$totalPages = 1;
try {
    $pdo = db_connect();
    $allTags = get_tags_for_type($pdo, 'science');

    $params = [];
    $conditions = ["a.type = 'science'", "a.status = 'published'"];
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
    foreach ($rows as $a) {
        $articles[] = [
            'slug' => $a['slug'],
            'cover' => BASE_PATH . $a['cover_image'],
            'date' => format_uk_date($a['published_at']),
            'title' => $a['title'],
            'excerpt' => $a['excerpt'],
            'tags' => $tagsByArticle[(int)$a['id']] ?? [],
        ];
    }
} catch (Throwable $e) {
    error_log('[science_list] DB error: ' . $e->getMessage());
}

$pageTitle = 'Наукові статті — Bee Genius';
$metaDescription = 'Наукові статті про бджільництво і апітерапію: дослідження, огляди та нотатки.';
$active = 'science';
require __DIR__ . '/../views/layout/header.php';
?>
<section class="section">
  <div class="section-head">
    <span class="kicker">Наука</span>
    <h1 class="serif">Наукові статті</h1>
    <p>Дослідження, огляди та нотатки з бджільництва і апітерапії.</p>
  </div>
  <div class="disclaimer">Матеріали мають ознайомчий характер і не є професійною рекомендацією. Перед застосуванням будь-яких порад щодо здоров'я проконсультуйтеся з фахівцем.</div>
  <form class="search-form" method="get" action="<?= BASE_PATH ?>/science">
    <?php if ($activeTag !== ''): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($activeTag) ?>"><?php endif; ?>
    <input type="text" name="q" placeholder="Пошук за заголовком або тегом…" value="<?= htmlspecialchars($q) ?>">
    <button class="btn btn-secondary" type="submit">Знайти</button>
    <?php if ($q !== ''): ?><a class="search-clear" href="<?= BASE_PATH ?>/science<?= $activeTag !== '' ? '?tag=' . urlencode($activeTag) : '' ?>">Скинути</a><?php endif; ?>
  </form>
  <?php if (!empty($allTags)): ?>
    <div class="tag-filter">
      <a class="tag-chip <?= $activeTag === '' ? 'active' : '' ?>" href="<?= BASE_PATH ?>/science<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">Усі</a>
      <?php foreach ($allTags as $t): ?>
        <a class="tag-chip <?= $activeTag === $t['slug'] ? 'active' : '' ?>" href="<?= BASE_PATH ?>/science?tag=<?= urlencode($t['slug']) ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">#<?= htmlspecialchars($t['name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="card-grid">
    <?php foreach ($articles as $a): ?>
      <a class="post-card" href="<?= BASE_PATH ?>/science/<?= urlencode($a['slug']) ?>">
        <div class="post-cover"><img src="<?= htmlspecialchars($a['cover']) ?>" alt="<?= htmlspecialchars($a['title']) ?>"></div>
        <div class="post-body">
          <div class="post-date"><?= htmlspecialchars($a['date']) ?></div>
          <h3><?= htmlspecialchars($a['title']) ?></h3>
          <p><?= htmlspecialchars($a['excerpt']) ?></p>
          <?php if (!empty($a['tags'])): ?>
            <div class="tag-list">
              <?php foreach ($a['tags'] as $t): ?>
                <span class="tag-chip small">#<?= htmlspecialchars($t['name']) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (empty($articles)): ?>
    <p class="empty"><?= ($activeTag !== '' || $q !== '') ? 'Нічого не знайдено за цим фільтром.' : 'Поки що немає жодної статті.' ?></p>
  <?php endif; ?>
  <?= render_pagination($page, $totalPages, BASE_PATH . '/science', ['tag' => $activeTag, 'q' => $q]) ?>
</section>
<?php require __DIR__ . '/../views/layout/footer.php'; ?>
