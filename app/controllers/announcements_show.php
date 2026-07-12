<?php
// Одне оголошення за slug.
$slug = $_GET['slug'] ?? '';
$post = null;
try {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        "SELECT id, title, excerpt, body, cover_image, published_at
         FROM articles WHERE type = 'announcement' AND slug = ? AND status = 'published' LIMIT 1"
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if ($row) {
        $post = [
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
    error_log('[announcements_show] DB error: ' . $e->getMessage());
}

$pageTitle = ($post['title'] ?? 'Оголошення не знайдено') . ' — Bee Genius';
$active = 'announcements';
if ($post) {
    $metaDescription = $post['description'];
    $metaImage = absolute_url($post['cover']);
    $metaType = 'article';
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post['title'],
        'description' => $post['description'],
        'image' => absolute_url($post['cover']),
        'datePublished' => $post['published_iso'],
        'author' => ['@type' => 'Person', 'name' => 'Валентин'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Bee Genius'],
        'mainEntityOfPage' => site_url('/announcements/' . $slug),
    ];
}
require __DIR__ . '/../views/layout/header.php';
?>
<?php if ($post): ?>
<div class="detail-wrap">
  <a class="back-link" href="<?= BASE_PATH ?>/announcements">← До оголошень</a>
  <div class="post-date"><?= htmlspecialchars($post['date']) ?></div>
  <h1 class="serif"><?= htmlspecialchars($post['title']) ?></h1>
  <div class="detail-cover"><img src="<?= htmlspecialchars($post['cover']) ?>" alt="<?= htmlspecialchars($post['title']) ?>"></div>
  <?php if (!empty($post['tags'])): ?>
    <div class="tag-list" style="margin:0 0 20px">
      <?php foreach ($post['tags'] as $t): ?>
        <a class="tag-chip small" href="<?= BASE_PATH ?>/announcements?tag=<?= urlencode($t['slug']) ?>">#<?= htmlspecialchars($t['name']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="detail-body"><?= $post['body'] ?></div>
</div>
<?php else: ?>
<section class="section"><p>Оголошення не знайдено.</p></section>
<?php endif; ?>
<?php require __DIR__ . '/../views/layout/footer.php'; ?>
