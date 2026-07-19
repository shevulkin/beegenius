<?php
require_admin();

$fields = [
    'contact_phone' => 'Телефон',
    'contact_email' => 'Email',
    'contact_telegram' => 'Telegram',
    'contact_viber' => 'Viber',
    'contact_whatsapp' => 'WhatsApp',
    'contact_instagram' => 'Instagram',
];
$hints = [
    'contact_phone' => 'У форматі +380XXXXXXXXX — за кнопкою одразу відкриється дзвінок.',
    'contact_email' => 'За кнопкою відкриється поштова програма з готовою адресою.',
    'contact_telegram' => 'Username без @ (наприклад valentyn_bee) або посилання виду t.me/...',
    'contact_viber' => 'Номер телефону, привʼязаний до Viber, у форматі +380XXXXXXXXX.',
    'contact_whatsapp' => 'Номер телефону, привʼязаний до WhatsApp, у форматі +380XXXXXXXXX.',
    'contact_instagram' => 'Username без @ (наприклад bee.genius).',
];

$values = array_fill_keys(array_keys($fields), '');
try {
    $pdo = db_connect();
    $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'contact_%'")->fetchAll();
    foreach ($rows as $r) {
        if (array_key_exists($r['setting_key'], $values)) {
            $values[$r['setting_key']] = $r['setting_value'];
        }
    }
} catch (Throwable $e) {
    error_log('[settings_edit] DB error: ' . $e->getMessage());
}

$pageTitle = 'Контакти — Bee Genius';
$active = 'admin';
require __DIR__ . '/../../views/layout/header.php';
?>
<section class="section" style="max-width:620px;margin:0 auto">
  <a class="back-link" href="<?= BASE_PATH ?>/admin">← До панелі</a>
  <h2 class="serif">Контакти</h2>
  <p style="color:var(--ink-soft);font-size:14.5px;margin:0 0 24px">Ці дані показуються у модальному вікні "Зв'язатися" на сайті. Залиште поле порожнім, щоб цей спосіб зв'язку не показувався.</p>

  <form method="post" action="<?= BASE_PATH ?>/admin/settings/save" data-track-changes="1">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
    <?php foreach ($fields as $key => $label): ?>
      <div class="field">
        <label><?= htmlspecialchars($label) ?></label>
        <input type="text" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($values[$key]) ?>">
        <?php if (!empty($hints[$key])): ?><p class="field-hint"><?= htmlspecialchars($hints[$key]) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="btnrow">
      <button class="btn btn-primary" type="submit">Зберегти</button>
      <a class="btn btn-ghost" href="<?= BASE_PATH ?>/admin">Скасувати</a>
    </div>
  </form>
</section>
<?php require __DIR__ . '/../../views/layout/footer.php'; ?>
