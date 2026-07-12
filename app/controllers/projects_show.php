<?php
// Сторінка одного проєкту — вступ з назвою, лічильник-результат
// (якщо задано), опис, фото, заклик до дії.
$slug = $_GET['slug'] ?? '';
$project = null;
try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if ($row) {
        $project = [
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'counter_value' => $row['counter_value'],
            'counter_label' => $row['counter_label'],
            'description' => $row['description'],
            'cta_note' => $row['cta_note'],
            'cover' => !empty($row['cover_image']) ? BASE_PATH . $row['cover_image'] : '',
        ];
    }
} catch (Throwable $e) {
    error_log('[projects_show] DB error: ' . $e->getMessage());
}

$pageTitle = ($project['title'] ?? 'Проєкт не знайдено') . ' — Bee Genius';
$active = 'projects';
if ($project) {
    $metaDescription = $project['subtitle'] !== '' && $project['subtitle'] !== null
        ? seo_excerpt($project['subtitle'])
        : ($project['description'] !== '' && $project['description'] !== null ? seo_excerpt($project['description']) : null);
    if ($project['cover'] !== '') {
        $metaImage = absolute_url($project['cover']);
    }
    $metaType = 'article';
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => $project['title'],
        'description' => $metaDescription,
        'image' => $project['cover'] !== '' ? absolute_url($project['cover']) : null,
        'creator' => ['@type' => 'Person', 'name' => 'Валентин'],
        'mainEntityOfPage' => site_url('/projects/' . $slug),
    ];
}
require __DIR__ . '/../views/layout/header.php';
?>
<?php if ($project): ?>
<section class="section" style="text-align:center;padding-bottom:0">
  <a class="back-link" href="<?= BASE_PATH ?>/projects" style="display:block;text-align:left;max-width:720px;margin:0 auto 12px">← До проєктів</a>
  <span class="kicker">Проєкт</span>
  <h1 class="serif" style="margin:0 auto 14px;max-width:720px">«<?= htmlspecialchars($project['title']) ?>»</h1>
  <?php if (!empty($project['subtitle'])): ?>
    <p class="about-text" style="margin:0 auto 32px;max-width:640px"><?= nl2br(htmlspecialchars($project['subtitle'])) ?></p>
  <?php endif; ?>
</section>
<?php if ($project['counter_value'] !== null && $project['counter_value'] !== ''): ?>
<section class="counter-band" style="padding:72px 56px">
  <div class="counter-num"><?= htmlspecialchars($project['counter_value']) ?></div>
  <?php if (!empty($project['counter_label'])): ?>
    <p class="counter-label"><?= htmlspecialchars($project['counter_label']) ?></p>
  <?php endif; ?>
</section>
<?php endif; ?>
<section class="section">
  <?php if (!empty($project['cover'])): ?>
    <div class="counter-photo">
      <img src="<?= htmlspecialchars($project['cover']) ?>" alt="<?= htmlspecialchars($project['title']) ?>">
    </div>
  <?php endif; ?>
  <?php if (!empty($project['description'])): ?>
    <p class="about-text" style="margin:0 auto"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
  <?php endif; ?>
  <?php if (!empty($project['cta_note'])): ?>
    <p class="cta-note" style="margin-top:32px"><?= nl2br(htmlspecialchars($project['cta_note'])) ?></p>
  <?php endif; ?>
</section>
<?php else: ?>
<section class="section"><p>Проєкт не знайдено.</p></section>
<?php endif; ?>
<?php require __DIR__ . '/../views/layout/footer.php'; ?>
