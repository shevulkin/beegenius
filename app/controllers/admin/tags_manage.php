<?php
require_admin();

$tags = [];
$articlesByTag = [];
try {
    $pdo = db_connect();
    $tags = get_tags_with_usage($pdo);
    foreach ($tags as $t) {
        $articlesByTag[(int)$t['id']] = get_articles_for_tag($pdo, (int)$t['id']);
    }
} catch (Throwable $e) {
    error_log('[tags_manage] DB error: ' . $e->getMessage());
}

$typeLabels = ['blog' => 'Блог', 'science' => 'Наука', 'announcement' => 'Оголошення'];

$pageTitle = 'Керування тегами — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section" style="max-width:820px;margin:0 auto">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <h2 class="serif">Керування тегами</h2>
  <p style="color:var(--ink-soft);font-size:14.5px;margin:0 0 28px">Видалення тега прибирає його з усіх статей, де він був використаний.</p>

  <?php if (empty($tags)): ?>
    <p class="empty">Ще немає жодного тега. Теги створюються автоматично при редагуванні статей.</p>
  <?php endif; ?>

  <?php foreach ($tags as $t): ?>
    <div class="list-row" style="align-items:flex-start;flex-wrap:wrap">
      <div class="info">
        <h4>#<?= htmlspecialchars($t['name']) ?></h4>
        <div class="d">
          <?= (int)$t['usage_count'] ?> <?= (int)$t['usage_count'] === 1 ? 'стаття' : 'статей' ?>
          <?php $articles = $articlesByTag[(int)$t['id']] ?? []; ?>
          <?php $shown = array_slice($articles, 0, 10); $rest = count($articles) - count($shown); ?>
          <?php if (!empty($shown)): ?>
            <div style="margin-top:6px;display:flex;flex-direction:column;gap:2px">
              <?php foreach ($shown as $a): ?>
                <span>— <?= htmlspecialchars($a['title']) ?> <span style="color:var(--ink-soft)">(<?= htmlspecialchars($typeLabels[$a['type']] ?? $a['type']) ?>, <?= $a['status'] === 'published' ? 'опубліковано' : 'чернетка' ?>)</span></span>
              <?php endforeach; ?>
              <?php if ($rest > 0): ?>
                <span style="color:var(--ink-soft)">… і ще <?= $rest ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <form method="post" action="<?= BASE_PATH ?>/admin/tags/delete" onsubmit="return confirm('Видалити тег «<?= htmlspecialchars(addslashes($t['name'])) ?>»? Він зникне з усіх статей (<?= (int)$t['usage_count'] ?>).')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <button class="icon-btn" type="submit" title="Видалити тег">✕</button>
      </form>
    </div>
  <?php endforeach; ?>
</section>
<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
