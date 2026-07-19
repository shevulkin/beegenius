<?php
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$activity = ['id' => null, 'title' => '', 'text' => '', 'sort_order' => 0];

if ($id) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM activities WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) $activity = $row;
    } catch (Throwable $e) {
        error_log('[activities_edit] DB error: ' . $e->getMessage());
    }
}

$isNew = $id === null;
$pageTitle = ($isNew ? 'Нова картка' : 'Редагування картки') . ' — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section" style="max-width:620px;margin:0 auto">
  <a class="back-link" href="<?= BASE_PATH ?>/admin/home">← До головної сторінки</a>
  <h2 class="serif"><?= $isNew ? 'Нова картка' : 'Редагування картки' ?></h2>

  <form method="post" action="<?= BASE_PATH ?>/admin/activities/save" data-track-changes="1">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <?php if (!$isNew): ?><input type="hidden" name="id" value="<?= (int)$activity['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>Назва</label>
      <input type="text" name="title" value="<?= htmlspecialchars($activity['title']) ?>" required>
      <p class="field-hint">Заголовок картки (наприклад "Альпійські вулики").</p>
    </div>

    <div class="field">
      <label>Опис</label>
      <textarea name="text" required><?= htmlspecialchars($activity['text']) ?></textarea>
      <p class="field-hint">Короткий опис під заголовком картки.</p>
    </div>

    <div class="btnrow">
      <button class="btn btn-primary" type="submit">Зберегти</button>
      <a class="btn btn-ghost" href="<?= BASE_PATH ?>/admin/home">Скасувати</a>
    </div>
  </form>
</section>
<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
