<?php
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$quote = ['id' => null, 'quote_text' => '', 'quote_year' => date('Y'), 'quote_image' => '', 'sort_order' => 0];

if ($id) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM quotes WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) $quote = $row;
    } catch (Throwable $e) {
        error_log('[quotes_edit] DB error: ' . $e->getMessage());
    }
}

$isNew = $id === null;
$pageTitle = ($isNew ? 'Новий відгук' : 'Редагування відгуку') . ' — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section" style="max-width:620px;margin:0 auto">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <h2 class="serif"><?= $isNew ? 'Новий відгук' : 'Редагування відгуку' ?></h2>

  <form method="post" action="<?= BASE_PATH ?>/admin/quotes/save" id="quote-form" data-track-changes="1">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <?php if (!$isNew): ?><input type="hidden" name="id" value="<?= (int)$quote['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>Текст відгуку</label>
      <textarea name="quote_text" required><?= htmlspecialchars($quote['quote_text']) ?></textarea>
      <p class="field-hint">Показується на головній сторінці в блоці "Що кажуть учасники проєкту".</p>
    </div>

    <div class="field">
      <label>Рік</label>
      <input type="text" name="quote_year" value="<?= htmlspecialchars($quote['quote_year']) ?>" style="max-width:120px" required>
      <p class="field-hint">Виводиться під текстом відгуку (наприклад, рік, коли людина приєдналась до проєкту).</p>
    </div>

    <div class="field">
      <label>Скріншот відгуку (необов'язково)</label>
      <input type="hidden" name="quote_image" id="quote-image-current" value="<?= htmlspecialchars((string)$quote['quote_image']) ?>">
      <div id="quote-image-preview" style="margin-bottom:10px">
        <?php if (!empty($quote['quote_image'])): ?>
          <img src="<?= htmlspecialchars(BASE_PATH . $quote['quote_image']) ?>" style="max-width:220px;border-radius:8px">
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-ghost" id="pick-quote-image-btn">Обрати скріншот</button>
      <?php if (!empty($quote['quote_image'])): ?>
        <button type="button" class="btn btn-ghost" id="remove-quote-image-btn">Прибрати фото</button>
      <?php endif; ?>
      <p class="field-hint">Наприклад, скріншот листування чи фото людини — показується над текстом відгуку. Можна залишити порожнім.</p>
    </div>

    <div class="btnrow">
      <button class="btn btn-primary" type="submit">Зберегти</button>
      <a class="btn btn-ghost" href="<?= BASE_PATH ?>/admin">Скасувати</a>
    </div>
  </form>
</section>

<!-- Спільна модалка "бібліотека фото" (той самий підхід, що й у статтях) -->
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
function formatFileSize(bytes) {
  if (!bytes && bytes !== 0) return '';
  if (bytes < 1024) return bytes + ' Б';
  if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' КБ';
  return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
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
        el.style.cssText = 'width:100%;height:90px;object-fit:cover;border-radius:8px 8px 0 0;cursor:pointer;border:1.5px solid var(--line);border-bottom:none;display:block';
        el.onclick = function () {
          if (mediaPickerCallback) mediaPickerCallback(img.url);
          closeMediaPicker();
        };
        var caption = document.createElement('div');
        caption.textContent = formatFileSize(img.size);
        caption.title = img.name;
        caption.style.cssText = 'font-size:10.5px;color:var(--ink-soft);padding:4px 6px;border:1.5px solid var(--line);border-top:none;border-radius:0 0 8px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer';
        caption.onclick = el.onclick;
        wrap.appendChild(el);
        wrap.appendChild(caption);
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

document.getElementById('pick-quote-image-btn').addEventListener('click', function () {
  openMediaPicker(function (url) {
    var input = document.getElementById('quote-image-current');
    input.value = url.replace('<?= BASE_PATH ?>', '');
    input.dispatchEvent(new Event('change', { bubbles: true }));
    var preview = document.getElementById('quote-image-preview');
    var img = document.createElement('img');
    img.src = url;
    img.style.cssText = 'max-width:220px;border-radius:8px';
    preview.innerHTML = '';
    preview.appendChild(img);
  });
});

var removeBtn = document.getElementById('remove-quote-image-btn');
if (removeBtn) {
  removeBtn.addEventListener('click', function () {
    var input = document.getElementById('quote-image-current');
    input.value = '';
    input.dispatchEvent(new Event('change', { bubbles: true }));
    document.getElementById('quote-image-preview').innerHTML = '';
  });
}
</script>

<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
