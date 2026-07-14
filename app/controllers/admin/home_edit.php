<?php
require_admin();

$fields = [
    'home_kicker' => 'Мітка над заголовком',
    'home_title' => 'Заголовок (H1)',
    'home_subtitle' => 'Підзаголовок',
    'home_about_title' => 'Заголовок блоку "Чим я займаюся"',
    'home_about_text' => 'Текст блоку "Чим я займаюся"',
];
$textareas = ['home_subtitle', 'home_about_text'];
$hints = [
    'home_kicker' => 'Маленький напис над заголовком у верхньому банері (наприклад "Проєкт «З Бджолами по Життю»").',
    'home_title' => 'Головний заголовок (H1) на верхньому банері.',
    'home_subtitle' => 'Короткий опис під заголовком.',
    'home_about_title' => 'Заголовок секції з картками нижче ("Чим я займаюся").',
    'home_about_text' => 'Опис під заголовком цієї секції.',
];

$values = array_fill_keys(array_keys($fields), '');
$heroImage = '';
$activities = [];
try {
    $pdo = db_connect();
    $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('" . implode("','", array_keys($fields)) . "', 'home_hero_image')")->fetchAll();
    foreach ($rows as $r) {
        if ($r['setting_key'] === 'home_hero_image') {
            $heroImage = $r['setting_value'];
        } elseif (array_key_exists($r['setting_key'], $values)) {
            $values[$r['setting_key']] = $r['setting_value'];
        }
    }
    $activities = $pdo->query('SELECT id, title, text FROM activities ORDER BY sort_order ASC')->fetchAll();
} catch (Throwable $e) {
    error_log('[admin/home_edit] DB error: ' . $e->getMessage());
}

$pageTitle = 'Головна сторінка — Адмінка — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section" style="max-width:680px;margin:0 auto">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <h2 class="serif">Головна сторінка</h2>
  <p style="color:var(--ink-soft);font-size:14.5px;margin:0 0 24px">Тексти верхнього банера і блоку "Чим я займаюся". Лічильник підтягується автоматично з проєкту, позначеного прапорцем "Показувати цей лічильник на головній сторінці" (розділ <a href="<?= BASE_PATH ?>/admin/projects">Проєкти</a>). Відгуки — у розділі "Відгуки", банер-оголошення — прапорцем "Показувати банером" у формі оголошення.</p>

  <form method="post" action="<?= BASE_PATH ?>/admin/home/save" id="home-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <div class="field">
      <label>Головне фото (хіро-банер)</label>
      <input type="hidden" name="home_hero_image" id="hero-image-current" value="<?= htmlspecialchars($heroImage) ?>">
      <div id="hero-image-preview" style="margin-bottom:10px">
        <?php if (!empty($heroImage)): ?>
          <img src="<?= htmlspecialchars(BASE_PATH . $heroImage) ?>" style="max-width:220px;border-radius:8px">
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-ghost" id="pick-hero-image-btn">Обрати фото</button>
      <p class="field-hint">Велике фото праворуч у верхньому банері головної сторінки.</p>
    </div>

    <?php foreach ($fields as $key => $label): ?>
      <div class="field">
        <label><?= htmlspecialchars($label) ?></label>
        <?php if (in_array($key, $textareas, true)): ?>
          <textarea name="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($values[$key]) ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($values[$key]) ?>">
        <?php endif; ?>
        <?php if (!empty($hints[$key])): ?><p class="field-hint"><?= htmlspecialchars($hints[$key]) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="btnrow">
      <button class="btn btn-primary" type="submit">Зберегти</button>
      <a class="btn btn-ghost" href="<?= BASE_PATH ?>/admin">Скасувати</a>
    </div>
  </form>

  <div style="margin-top:48px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 8px">
      <h3 class="serif" style="margin:0">Картки "Чим я займаюся"</h3>
      <a class="btn btn-primary" href="<?= BASE_PATH ?>/admin/activities/new">+ Додати картку</a>
    </div>
    <p style="color:var(--ink-soft);font-size:14.5px;margin:0 0 20px">Порядок карток — як у списку нижче.</p>
    <?php if (empty($activities)): ?>
      <p class="empty">Ще немає жодної картки.</p>
    <?php endif; ?>
    <?php foreach ($activities as $a): ?>
      <div class="list-row">
        <a class="list-row-link" href="<?= BASE_PATH ?>/admin/activities/edit/<?= (int)$a['id'] ?>">
          <div class="info">
            <h4><?= htmlspecialchars($a['title']) ?></h4>
            <div class="d"><?= htmlspecialchars(mb_strimwidth($a['text'], 0, 90, '…')) ?></div>
          </div>
        </a>
        <form method="post" action="<?= BASE_PATH ?>/admin/activities/delete" style="display:inline" onsubmit="return confirm('Видалити цю картку?')">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <button class="icon-btn" type="submit" title="Видалити">✕</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Спільна модалка "бібліотека фото" (той самий підхід, що й у статтях/відгуках/проєктах) -->
<div class="contact-modal-backdrop" id="media-picker-backdrop" style="display:none" onclick="closeMediaPicker()">
  <div class="contact-modal" style="max-width:640px" onclick="event.stopPropagation()">
    <div class="contact-modal-close" onclick="closeMediaPicker()">✕</div>
    <h3 class="serif">Оберіть фото</h3>
    <p class="contact-modal-sub">Виберіть із уже завантажених, або завантажте нове.</p>
    <div style="margin-bottom:16px">
      <input type="file" id="media-upload-input" accept="image/jpeg,image/png,image/webp,image/gif">
    </div>
    <div id="media-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;max-height:340px;overflow-y:auto"></div>
  </div>
</div>

<script>
var csrfToken = document.querySelector('input[name=csrf_token]').value;
var mediaPickerCallback = null;

function openMediaPicker(onSelect) {
  mediaPickerCallback = onSelect;
  document.getElementById('media-picker-backdrop').style.display = 'flex';
  loadMediaGrid();
}
function closeMediaPicker() {
  document.getElementById('media-picker-backdrop').style.display = 'none';
}
function loadMediaGrid() {
  var grid = document.getElementById('media-grid');
  grid.innerHTML = 'Завантаження…';
  fetch('<?= BASE_PATH ?>/admin/media/list')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      grid.innerHTML = '';
      (data.images || []).forEach(function (img) {
        var wrap = document.createElement('div');
        wrap.style.cssText = 'position:relative';
        var el = document.createElement('img');
        el.src = img.url;
        el.style.cssText = 'width:100%;height:90px;object-fit:cover;border-radius:8px;cursor:pointer;border:1.5px solid var(--line);display:block';
        el.onclick = function () {
          if (mediaPickerCallback) mediaPickerCallback(img.url);
          closeMediaPicker();
        };
        var del = document.createElement('button');
        del.type = 'button';
        del.textContent = '✕';
        del.title = 'Видалити фото';
        del.style.cssText = 'position:absolute;top:4px;right:4px;width:22px;height:22px;border-radius:50%;border:none;background:rgba(0,0,0,0.6);color:#fff;font-size:12px;cursor:pointer;line-height:1';
        del.onclick = function (e) {
          e.stopPropagation();
          if (!confirm('Видалити це фото назавжди?')) return;
          var fd = new FormData();
          fd.append('name', img.name);
          fd.append('csrf_token', csrfToken);
          fetch('<?= BASE_PATH ?>/admin/media/delete', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (res.ok) {
                loadMediaGrid();
              } else {
                alert(res.error || 'Не вдалося видалити фото');
              }
            });
        };
        wrap.appendChild(el);
        wrap.appendChild(del);
        grid.appendChild(wrap);
      });
      if (!data.images || !data.images.length) {
        grid.innerHTML = '<p style="grid-column:1/-1;color:var(--ink-soft)">Ще немає завантажених фото — завантажте перше вище.</p>';
      }
    });
}

document.getElementById('media-upload-input').addEventListener('change', function () {
  var file = this.files[0];
  if (!file) return;
  var formData = new FormData();
  formData.append('image', file);
  formData.append('csrf_token', csrfToken);
  fetch('<?= BASE_PATH ?>/admin/upload-image', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.url) {
        if (mediaPickerCallback) mediaPickerCallback(data.url);
        closeMediaPicker();
      } else {
        alert(data.error || 'Помилка завантаження фото');
      }
    });
  this.value = '';
});

document.getElementById('pick-hero-image-btn').addEventListener('click', function () {
  openMediaPicker(function (url) {
    document.getElementById('hero-image-current').value = url.replace('<?= BASE_PATH ?>', '');
    var preview = document.getElementById('hero-image-preview');
    var img = document.createElement('img');
    img.src = url;
    img.style.cssText = 'max-width:220px;border-radius:8px';
    preview.innerHTML = '';
    preview.appendChild(img);
  });
});
</script>

<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
