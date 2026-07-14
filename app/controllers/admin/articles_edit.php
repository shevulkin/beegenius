<?php
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$type = $_GET['type'] ?? 'blog';
$article = [
    'id' => null, 'type' => $type, 'title' => '', 'slug' => '', 'excerpt' => '',
    'body' => '', 'cover_image' => '', 'status' => 'draft', 'is_banner' => 0,
    'banner_from' => '', 'banner_until' => '',
];
$tagsValue = '';

if ($id) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        // array_merge, а не пряме присвоєння: якщо в БД раптом бракує якоїсь
        // колонки (наприклад, через не до кінця виконану міграцію), сторінка
        // не впаде з "Undefined array key", а покаже розумний дефолт.
        if ($row) $article = array_merge($article, $row);
        $tagsValue = get_tags_input_value($pdo, $id);
    } catch (Throwable $e) {
        error_log('[articles_edit] DB error: ' . $e->getMessage());
    }
}

$isNew = $id === null;
$pageTitle = ($isNew ? 'Нова стаття' : 'Редагування') . ' — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section" style="max-width:820px;margin:0 auto">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <h2 class="serif"><?= $isNew ? 'Нова стаття' : 'Редагування статті' ?></h2>

  <form method="post" action="<?= BASE_PATH ?>/admin/articles/save" enctype="multipart/form-data" id="article-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <?php if (!$isNew): ?><input type="hidden" name="id" value="<?= (int)$article['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>Тип</label>
      <select name="type">
        <option value="blog" <?= $article['type'] === 'blog' ? 'selected' : '' ?>>Пост блогу</option>
        <option value="science" <?= $article['type'] === 'science' ? 'selected' : '' ?>>Наукова стаття</option>
        <option value="announcement" <?= $article['type'] === 'announcement' ? 'selected' : '' ?>>Оголошення / пропозиція</option>
      </select>
      <p class="field-hint">Визначає, на якій сторінці сайту з'явиться запис: "Блог", "Наукові статті" чи "Оголошення".</p>
    </div>

    <div class="field">
      <label>Заголовок</label>
      <input type="text" name="title" value="<?= htmlspecialchars($article['title']) ?>" required>
      <p class="field-hint">Показується у списку, у самій статті та у вкладці браузера. З нього ж формується адреса сторінки (URL).</p>
    </div>

    <div class="field">
      <label>Короткий опис (excerpt)</label>
      <textarea name="excerpt" style="min-height:70px"><?= htmlspecialchars($article['excerpt']) ?></textarea>
      <p class="field-hint">Уривок під заголовком у списку статей. Якщо залишити порожнім — Google і соцмережі самі візьмуть початок тексту статті.</p>
    </div>

    <div class="field">
      <label>Теги</label>
      <input type="hidden" name="tags" id="tags-hidden" value="<?= htmlspecialchars($tagsValue) ?>">
      <div id="tag-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px"></div>
      <input type="text" id="tag-input" placeholder="Почни вводити тег…" autocomplete="off">
      <div id="tag-suggestions" style="position:relative">
        <div id="tag-suggestions-list" style="display:none;position:absolute;left:0;right:0;top:4px;background:#fff;border:1.5px solid var(--line);border-radius:9px;max-height:200px;overflow-y:auto;z-index:20"></div>
      </div>
      <p class="field-hint">Обирай з підказок або впиши свій — для нового тега попросимо підтвердження.</p>
    </div>

    <div class="field">
      <label>Обкладинка</label>
      <input type="hidden" name="cover_image_current" id="cover-image-current" value="<?= htmlspecialchars($article['cover_image']) ?>">
      <div id="cover-preview" style="margin-bottom:10px">
        <?php if (!empty($article['cover_image'])): ?>
          <img src="<?= htmlspecialchars(BASE_PATH . $article['cover_image']) ?>" style="max-width:220px;border-radius:8px">
        <?php endif; ?>
      </div>
      <button type="button" class="btn btn-ghost" id="pick-cover-btn">Обрати фото</button>
      <p class="field-hint">Головне фото статті — показується у списку, на самій сторінці та при пересиланні посилання в месенджерах.</p>
    </div>

    <div class="field">
      <label>Текст статті</label>
      <div id="quill-editor" style="background:#fff;min-height:320px"><?= $article['body'] /* вже безпечний HTML з БД (санітизований при збереженні) */ ?></div>
      <textarea name="body" id="body-hidden" style="display:none"></textarea>
      <p class="field-hint">Основний текст статті. Кнопка із зображенням у панелі інструментів дозволяє вставляти фото прямо в текст.</p>
    </div>

    <div class="field">
      <label>Статус</label>
      <select name="status">
        <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Чернетка</option>
        <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Опубліковано</option>
      </select>
      <p class="field-hint">"Чернетка" видно лише вам в адмінці. "Опубліковано" — стаття одразу зʼявляється на сайті для всіх.</p>
    </div>

    <div class="field">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
        <input type="checkbox" name="is_banner" id="is-banner-checkbox" value="1" <?= !empty($article['is_banner']) ? 'checked' : '' ?> style="width:auto">
        Показувати як банер на головній сторінці
      </label>
      <p class="field-hint">Показує статтю карткою-банером на головній сторінці сайту. Актуально для оголошень і пропозицій — на блог/наукові статті це не впливає. Можна позначити кілька — тоді на головній буде каруселька з усіх активних банерів.</p>
    </div>

    <div class="field" id="banner-period-field">
      <label>Період показу банера (необов'язково)</label>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <div>
          <div style="font-size:12px;color:var(--ink-soft);margin-bottom:4px">Показувати з</div>
          <input type="date" name="banner_from" id="banner-from-input" value="<?= htmlspecialchars((string)$article['banner_from']) ?>">
        </div>
        <div>
          <div style="font-size:12px;color:var(--ink-soft);margin-bottom:4px">Показувати до</div>
          <input type="date" name="banner_until" id="banner-until-input" value="<?= htmlspecialchars((string)$article['banner_until']) ?>">
        </div>
      </div>
      <p class="field-hint">Дати, у які банер показується на головній. Якщо не заповнено — показується безстроково, поки не зняти позначку "Показувати як банер" вище.</p>
    </div>
    <script>
    (function () {
      var checkbox = document.getElementById('is-banner-checkbox');
      var field = document.getElementById('banner-period-field');
      var from = document.getElementById('banner-from-input');
      var until = document.getElementById('banner-until-input');
      function sync() {
        var active = checkbox.checked;
        from.disabled = !active;
        until.disabled = !active;
        field.style.opacity = active ? '1' : '0.5';
      }
      checkbox.addEventListener('change', sync);
      sync();
    })();
    </script>

    <div class="btnrow">
      <button class="btn btn-primary" type="submit">Зберегти</button>
      <a class="btn btn-ghost" href="<?= BASE_PATH ?>/admin">Скасувати</a>
    </div>
  </form>
</section>

<!-- Модалка "бібліотека фото": або обрати вже завантажене, або завантажити нове -->
<div class="contact-modal-backdrop" id="media-picker-backdrop" style="display:none" onclick="closeMediaPicker()">
  <div class="contact-modal" style="max-width:640px" onclick="event.stopPropagation()">
    <div class="contact-modal-close" onclick="closeMediaPicker()">✕</div>
    <h3 class="serif">Оберіть фото</h3>
    <p class="contact-modal-sub">Виберіть із уже завантажених, або завантажте нове — воно збережеться в теці сайту.</p>
    <div style="margin-bottom:16px">
      <input type="file" id="media-upload-input" accept="image/jpeg,image/png,image/webp,image/gif">
    </div>
    <div id="media-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;max-height:340px;overflow-y:auto"></div>
  </div>
</div>

<!-- Quill підключається з CDN лише на цій сторінці (не впливає на публічні сторінки) -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
var quill = new Quill('#quill-editor', {
  theme: 'snow',
  modules: {
    toolbar: {
      container: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'link', 'image'],
        ['clean'],
      ],
      handlers: { image: function () { openMediaPicker(insertImageIntoQuill); } },
    },
  },
});

function insertImageIntoQuill(url) {
  var range = quill.getSelection(true) || { index: quill.getLength() };
  quill.insertEmbed(range.index, 'image', url);
}

var mediaPickerCallback = null;
var csrfToken = document.querySelector('input[name=csrf_token]').value;

function openMediaPicker(onSelect) {
  mediaPickerCallback = onSelect;
  document.getElementById('media-picker-backdrop').style.display = 'flex';
  loadMediaGrid();
}
function closeMediaPicker() {
  document.getElementById('media-picker-backdrop').style.display = 'none';
}

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

document.getElementById('article-form').addEventListener('submit', function () {
  document.getElementById('body-hidden').value = quill.root.innerHTML;
});

// --- Теги: чіпси + автопідказки з уже наявних, підтвердження для нових ---
(function () {
  var hidden = document.getElementById('tags-hidden');
  var chipsBox = document.getElementById('tag-chips');
  var input = document.getElementById('tag-input');
  var suggestBox = document.getElementById('tag-suggestions-list');
  var allTags = []; // імена всіх наявних тегів у системі
  var selected = (hidden.value || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);

  function syncHidden() {
    hidden.value = selected.join(', ');
  }

  function renderChips() {
    chipsBox.innerHTML = '';
    selected.forEach(function (name) {
      var chip = document.createElement('span');
      chip.className = 'tag-chip small';
      chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;cursor:default';
      chip.textContent = '#' + name;
      var remove = document.createElement('span');
      remove.textContent = '✕';
      remove.style.cssText = 'cursor:pointer;font-weight:700;opacity:0.6';
      remove.addEventListener('click', function () {
        selected = selected.filter(function (n) { return n !== name; });
        syncHidden();
        renderChips();
      });
      chip.appendChild(remove);
      chipsBox.appendChild(chip);
    });
  }

  function addTag(name, isNew) {
    name = name.trim();
    if (!name) return;
    var already = selected.some(function (n) { return n.toLowerCase() === name.toLowerCase(); });
    if (already) { input.value = ''; hideSuggestions(); return; }
    if (isNew) {
      if (!confirm('Тега «' + name + '» ще немає. Створити новий тег?')) return;
    }
    selected.push(name);
    syncHidden();
    renderChips();
    input.value = '';
    hideSuggestions();
  }

  function hideSuggestions() {
    suggestBox.style.display = 'none';
    suggestBox.innerHTML = '';
  }

  function showSuggestions(query) {
    var q = query.trim().toLowerCase();
    var matches = allTags.filter(function (name) {
      return name.toLowerCase().indexOf(q) !== -1 &&
        !selected.some(function (n) { return n.toLowerCase() === name.toLowerCase(); });
    }).slice(0, 8);
    if (!matches.length) { hideSuggestions(); return; }
    suggestBox.innerHTML = '';
    matches.forEach(function (name) {
      var row = document.createElement('div');
      row.textContent = '#' + name;
      row.style.cssText = 'padding:9px 14px;cursor:pointer;font-size:14px';
      row.addEventListener('mousedown', function (e) {
        e.preventDefault(); // не втрачати фокус до кліку
        addTag(name, false);
      });
      row.addEventListener('mouseenter', function () { row.style.background = 'var(--cream2)'; });
      row.addEventListener('mouseleave', function () { row.style.background = ''; });
      suggestBox.appendChild(row);
    });
    suggestBox.style.display = 'block';
  }

  input.addEventListener('input', function () {
    if (input.value.trim() === '') { hideSuggestions(); return; }
    showSuggestions(input.value);
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      var val = input.value.replace(/,$/, '').trim();
      if (!val) return;
      var exists = allTags.some(function (name) { return name.toLowerCase() === val.toLowerCase(); });
      addTag(val, !exists);
    } else if (e.key === 'Escape') {
      hideSuggestions();
    }
  });

  input.addEventListener('blur', function () {
    setTimeout(hideSuggestions, 150); // дати встигнути mousedown на підказці
  });

  fetch('<?= BASE_PATH ?>/admin/tags/list')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      allTags = (data.tags || []).map(function (t) { return t.name; });
    })
    .catch(function () { allTags = []; });

  renderChips();
})();
</script>

<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
