<?php
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$project = [
    'id' => null, 'title' => '', 'slug' => '', 'subtitle' => '',
    'counter_value' => '', 'counter_label' => '', 'show_on_home' => 0, 'description' => '',
    'cta_note' => '', 'cover_image' => '', 'status' => 'draft',
];

if ($id) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) $project = array_merge($project, $row);
    } catch (Throwable $e) {
        error_log('[projects_edit] DB error: ' . $e->getMessage());
    }
}

$isNew = $id === null;
$pageTitle = ($isNew ? 'Новий проєкт' : 'Редагування проєкту') . ' — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section" style="max-width:700px;margin:0 auto">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <h2 class="serif"><?= $isNew ? 'Новий проєкт' : 'Редагування проєкту' ?></h2>

  <form method="post" action="<?= BASE_PATH ?>/admin/projects/save" id="project-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <?php if (!$isNew): ?><input type="hidden" name="id" value="<?= (int)$project['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>Назва проєкту</label>
      <input type="text" name="title" value="<?= htmlspecialchars($project['title']) ?>" required>
      <p class="field-hint">Показується у списку проєктів і на сторінці проєкту. З неї формується адреса сторінки (URL) — при першому збереженні.</p>
    </div>

    <div class="field">
      <label>Короткий опис (під заголовком)</label>
      <textarea name="subtitle" style="min-height:70px"><?= htmlspecialchars($project['subtitle']) ?></textarea>
      <p class="field-hint">Короткий підзаголовок одразу під назвою — 1-2 речення, що це за проєкт.</p>
    </div>

    <div class="field">
      <label>Фото</label>
      <input type="hidden" name="cover_image_current" id="cover-image-current" value="<?= htmlspecialchars($project['cover_image']) ?>">
      <div id="cover-preview" style="margin-bottom:10px">
        <?php if (!empty($project['cover_image'])): ?>
          <img src="<?= htmlspecialchars(BASE_PATH . $project['cover_image']) ?>" style="max-width:220px;border-radius:8px">
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-ghost" id="pick-cover-btn">Обрати фото</button>
      <p class="field-hint">Головне фото проєкту — показується у списку проєктів і на сторінці проєкту.</p>
    </div>

    <div class="field">
      <label>Число-результат (необов'язково)</label>
      <input type="text" name="counter_value" value="<?= htmlspecialchars((string)$project['counter_value']) ?>" style="max-width:160px" placeholder="наприклад: 18">
      <p class="field-hint">Велике число-лічильник на сторінці проєкту (наприклад, кількість переданих сімей). Якщо порожнє — блок з числом не показується.</p>
    </div>

    <div class="field">
      <label>Підпис під числом</label>
      <input type="text" name="counter_label" value="<?= htmlspecialchars((string)$project['counter_label']) ?>" placeholder="наприклад: Бджолородин передано новачкам пасічникам">
      <p class="field-hint">Пояснювальний текст під числом-лічильником вище.</p>
    </div>

    <div class="field">
      <label style="display:flex;align-items:center;gap:8px;font-weight:400">
        <input type="checkbox" name="show_on_home" value="1" style="width:auto" <?= !empty($project['show_on_home']) ? 'checked' : '' ?>>
        Показувати цей лічильник на головній сторінці
      </label>
      <p class="field-hint">Лічильник (число + підпис вище) на головній підтягується з проєкту, позначеного цим прапорцем. Одночасно можна позначити лише один проєкт — позначення тут автоматично зніме прапорець з інших. Показується на головній, лише поки проєкт опубліковано — "Чернетка" лічильник ховає.</p>
    </div>

    <div class="field">
      <label>Опис проєкту</label>
      <textarea name="description" style="min-height:140px"><?= htmlspecialchars((string)$project['description']) ?></textarea>
      <p class="field-hint">Основний текст на сторінці проєкту — детальніше про суть і мету.</p>
    </div>

    <div class="field">
      <label>Заклик до дії (внизу сторінки)</label>
      <textarea name="cta_note" style="min-height:70px"><?= htmlspecialchars((string)$project['cta_note']) ?></textarea>
      <p class="field-hint">Текст в самому низу сторінки проєкту — наприклад, запрошення долучитися чи звернутися.</p>
    </div>

    <div class="field">
      <label>Статус</label>
      <select name="status">
        <option value="draft" <?= $project['status'] === 'draft' ? 'selected' : '' ?>>Чернетка</option>
        <option value="published" <?= $project['status'] === 'published' ? 'selected' : '' ?>>Опубліковано</option>
      </select>
      <p class="field-hint">"Чернетка" видно лише вам в адмінці. "Опубліковано" — проєкт одразу зʼявляється в списку проєктів на сайті.</p>
    </div>

    <div class="btnrow">
      <button class="btn btn-primary" type="submit">Зберегти</button>
      <a class="btn btn-ghost" href="<?= BASE_PATH ?>/admin">Скасувати</a>
    </div>
  </form>
</section>

<!-- Спільна модалка "бібліотека фото" (той самий підхід, що й у статтях/відгуках) -->
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

document.getElementById('pick-cover-btn').addEventListener('click', function () {
  openMediaPicker(function (url) {
    document.getElementById('cover-image-current').value = url.replace('<?= BASE_PATH ?>', '');
    var preview = document.getElementById('cover-preview');
    var img = document.createElement('img');
    img.src = url;
    img.style.cssText = 'max-width:220px;border-radius:8px';
    preview.innerHTML = '';
    preview.appendChild(img);
  });
});
</script>

<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
