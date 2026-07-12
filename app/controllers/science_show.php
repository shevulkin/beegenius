<?php
// Одна наукова стаття за slug.
$slug = $_GET['slug'] ?? '';
$article = null;
try {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        "SELECT id, title, excerpt, body, cover_image, published_at
         FROM articles WHERE type = 'science' AND slug = ? AND status = 'published' LIMIT 1"
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if ($row) {
        $article = [
            'title' => $row['title'],
            'date' => format_uk_date($row['published_at']),
            'published_iso' => $row['published_at'] ? date('c', strtotime($row['published_at'])) : null,
            'cover' => BASE_PATH . $row['cover_image'],
            'body' => render_article_body($row['body']),
            'description' => $row['excerpt'] !== '' && $row['excerpt'] !== null ? $row['excerpt'] : seo_excerpt($row['body']),
            'tags' => get_article_tags($pdo, (int)$row['id']),
        ];
    }
} catch (Throwable $e) {
    error_log('[science_show] DB error: ' . $e->getMessage());
}

$pageTitle = ($article['title'] ?? 'Статтю не знайдено') . ' — Bee Genius';
$active = 'science';
if ($article) {
    $metaDescription = $article['description'];
    $metaImage = absolute_url($article['cover']);
    $metaType = 'article';
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article['title'],
        'description' => $article['description'],
        'image' => absolute_url($article['cover']),
        'datePublished' => $article['published_iso'],
        'author' => ['@type' => 'Person', 'name' => 'Валентин'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Bee Genius'],
        'mainEntityOfPage' => site_url('/science/' . $slug),
    ];
}
require __DIR__ . '/../views/layout/header.php';
?>
<?php if ($article): ?>
<div class="detail-wrap">
  <a class="back-link" href="<?= BASE_PATH ?>/science">← До наукових статей</a>
  <div class="post-date"><?= htmlspecialchars($article['date']) ?></div>
  <h1 class="serif"><?= htmlspecialchars($article['title']) ?></h1>
  <div class="detail-cover"><img src="<?= htmlspecialchars($article['cover']) ?>" alt="<?= htmlspecialchars($article['title']) ?>"></div>
  <div class="disclaimer">Матеріал ознайомчий і не є професійною рекомендацією.</div>
  <?php if (!empty($article['tags'])): ?>
    <div class="tag-list" style="margin:0 0 20px">
      <?php foreach ($article['tags'] as $t): ?>
        <a class="tag-chip small" href="<?= BASE_PATH ?>/science?tag=<?= urlencode($t['slug']) ?>">#<?= htmlspecialchars($t['name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="detail-body"><?= $article['body'] ?></div>
</div>
<?php else: ?>
<section class="section"><p>Статтю не знайдено.</p></section>
<?php endif; ?>
<?php require __DIR__ . '/../views/layout/footer.php'; ?>
