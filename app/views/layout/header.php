<?php
// SEO: усі мета-теги виводяться тут централізовано, кожна сторінка
// лише виставляє змінні перед підключенням header.php (не обов'язково —
// для всього є розумні значення за замовчуванням).
$__seoTitle = $pageTitle ?? 'Bee Genius';
$__seoDescription = $metaDescription ?? "Bee Genius · Валентин — блог та наукові статті про бджільництво й апітерапію.";
$__seoImage = $metaImage ?? site_url('/assets/img/b5c58d9b-b08e-40f0-871d-b0cea3dfb828.jpg');
$__seoType = $metaType ?? 'website';
$__seoPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$__seoCanonical = $metaCanonical ?? site_url(BASE_PATH !== '' && str_starts_with($__seoPath, BASE_PATH) ? substr($__seoPath, strlen(BASE_PATH)) : $__seoPath);
$__seoNoindex = $metaNoindex ?? (($active ?? '') === 'admin');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($__seoTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($__seoDescription) ?>">
<link rel="canonical" href="<?= htmlspecialchars($__seoCanonical) ?>">
<?php if ($__seoNoindex): ?>
<meta name="robots" content="noindex,nofollow">
<?php endif; ?>
<meta property="og:site_name" content="Bee Genius">
<meta property="og:type" content="<?= htmlspecialchars($__seoType) ?>">
<meta property="og:title" content="<?= htmlspecialchars($__seoTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($__seoDescription) ?>">
<meta property="og:image" content="<?= htmlspecialchars($__seoImage) ?>">
<meta property="og:url" content="<?= htmlspecialchars($__seoCanonical) ?>">
<meta property="og:locale" content="uk_UA">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($__seoTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($__seoDescription) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($__seoImage) ?>">
<?php if (!empty($jsonLd)): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<link rel="icon" type="image/jpeg" href="<?= BASE_PATH ?>/assets/img/b5c58d9b-b08e-40f0-871d-b0cea3dfb828.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Petrona:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/styles.css">
</head>
<body>
<div class="site">
  <?php $__user = current_user(); ?>
  <header class="nav">
    <div class="brand" onclick="location.href='<?= BASE_PATH ?>/'">
      <img class="brand-logo" src="<?= BASE_PATH ?>/assets/img/b5c58d9b-b08e-40f0-871d-b0cea3dfb828.jpg" alt="Bee Genius">
      <span class="serif">Bee Genius<span class="bee-sub"> · Валентин</span></span>
    </div>
    <nav class="navlinks">
      <a href="<?= BASE_PATH ?>/" class="navlink <?= ($active ?? '') === 'home' ? 'active' : '' ?>">Головна</a>
      <a href="<?= BASE_PATH ?>/blog" class="navlink <?= ($active ?? '') === 'blog' ? 'active' : '' ?>">Блог</a>
      <a href="<?= BASE_PATH ?>/science" class="navlink <?= ($active ?? '') === 'science' ? 'active' : '' ?>">Наукові статті</a>
      <a href="<?= BASE_PATH ?>/projects" class="navlink <?= ($active ?? '') === 'projects' ? 'active' : '' ?>">Проєкти</a>
      <a href="<?= BASE_PATH ?>/announcements" class="navlink <?= ($active ?? '') === 'announcements' ? 'active' : '' ?>">Оголошення</a>
    </nav>
    <div class="navright">
      <a class="nav-contact" href="#contact" onclick="return openContactModal(event)">Контакти</a>
      <?php if (!$__user): ?>
        <a class="admin-btn" href="<?= BASE_PATH ?>/login" title="Увійти">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0" stroke-linecap="round"></path><circle cx="12" cy="8" r="4"></circle></svg>
        </a>
      <?php else: ?>
        <div class="user-menu">
          <?php $__avatarHref = is_admin() ? (BASE_PATH . '/admin') : null; ?>
          <?php if (!empty($__user['picture'])): ?>
            <?php if ($__avatarHref): ?>
              <a class="admin-btn admin-btn-photo" href="<?= htmlspecialchars($__avatarHref) ?>" title="Адмін-панель">
                <img src="<?= htmlspecialchars($__user['picture']) ?>" alt="<?= htmlspecialchars($__user['name']) ?>">
              </a>
            <?php else: ?>
              <span class="admin-btn admin-btn-photo" title="<?= htmlspecialchars($__user['name']) ?>">
                <img src="<?= htmlspecialchars($__user['picture']) ?>" alt="<?= htmlspecialchars($__user['name']) ?>">
              </span>
            <?php endif; ?>
          <?php else: ?>
            <?php if ($__avatarHref): ?>
              <a class="admin-btn" href="<?= htmlspecialchars($__avatarHref) ?>" title="Адмін-панель">
                <?= htmlspecialchars(mb_substr($__user['name'], 0, 1)) ?>
              </a>
            <?php else: ?>
              <span class="admin-btn" title="<?= htmlspecialchars($__user['name']) ?>">
                <?= htmlspecialchars(mb_substr($__user['name'], 0, 1)) ?>
              </span>
            <?php endif; ?>
          <?php endif; ?>
          <a class="user-logout" href="<?= BASE_PATH ?>/logout">Вийти</a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!empty($_SESSION['flash_session_expired'])): ?>
    <?php unset($_SESSION['flash_session_expired']); ?>
    <div class="disclaimer" style="max-width:1180px;margin:16px auto 0;text-align:center">Сесію завершено через тривалу бездіяльність. Увійдіть знову.</div>
  <?php endif; ?>

  <?php $c = $contact ?? []; ?>
  <div class="contact-modal-backdrop" id="contact-modal-backdrop" style="display:none" onclick="closeContactModal()">
    <div class="contact-modal" onclick="event.stopPropagation()">
      <div class="contact-modal-close" onclick="closeContactModal()">✕</div>
      <h3 class="serif">Зв'язатися з Валентином</h3>
      <p class="contact-modal-sub">Оберіть зручний спосіб зв'язку</p>
      <div class="contact-modal-list">
        <?php if (!empty($c['telegram'])): ?>
        <a class="contact-modal-row" href="https://t.me/<?= htmlspecialchars(ltrim($c['telegram'], '@')) ?>" target="_blank" rel="noopener">
          <div class="cm-icon ic-telegram" style="background:#2AABEE"><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 4 3 11l6 2.2M21 4l-3.2 16L9 13.2M21 4 9 13.2m0 0v5.4l3-3.2" stroke-linecap="round" stroke-linejoin="round"></path></svg></div>
          <div class="contact-text"><div class="contact-label">Telegram</div><div class="contact-value"><?= htmlspecialchars($c['telegram']) ?></div></div>
        </a>
        <?php endif; ?>
        <?php if (!empty($c['viber'])): ?>
        <a class="contact-modal-row" href="viber://chat?number=<?= urlencode($c['viber']) ?>">
          <div class="cm-icon ic-viber" style="background:#7360F2"><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3a8 8 0 0 0-7 11.9L4 21l6.3-1a8 8 0 1 0 1.7-17Z" stroke-linejoin="round"></path><path d="M9 10.5c1 2.2 2.3 3.5 4.5 4.5l1.2-1.2c.2-.2.5-.3.8-.2.9.3 1.8.4 2.5.4.4 0 .7.3.7.7v1.8c0 .4-.3.7-.7.7-6.1 0-11-4.9-11-11 0-.4.3-.7.7-.7H9.5c.4 0 .7.3.7.7 0 .8.1 1.6.4 2.5.1.3 0 .6-.2.8L9 10.5Z" stroke-linecap="round" stroke-linejoin="round"></path></svg></div>
          <div class="contact-text"><div class="contact-label">Viber</div><div class="contact-value"><?= htmlspecialchars($c['viber']) ?></div></div>
        </a>
        <?php endif; ?>
        <?php if (!empty($c['whatsapp'])): ?>
        <a class="contact-modal-row" href="https://wa.me/<?= urlencode(preg_replace('/\D/', '', $c['whatsapp'])) ?>" target="_blank" rel="noopener">
          <div class="cm-icon ic-whatsapp" style="background:#25D366"><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3a8.5 8.5 0 0 0-7.4 12.7L3 21l5.5-1.4A8.5 8.5 0 1 0 12 3Z" stroke-linejoin="round"></path><path d="M8.7 9c.2-.5.4-.5.7-.5h.5c.2 0 .4 0 .6.5s.6 1.5.7 1.6c.1.2.1.4 0 .6-.1.2-.2.3-.4.5s-.3.3-.1.6c.2.3.8 1.3 1.7 2.1 1.1 1 2 1.3 2.3 1.4.3.1.5.1.7-.1s.7-.8.9-1.1c.2-.3.4-.2.6-.1s1.5.7 1.8.9c.2.1.4.2.5.3.1.2.1.9-.2 1.4-.3.6-1.6 1.2-2.4 1.3-.6.1-1.4.2-4.5-1s-5-4.5-5.2-4.7c-.2-.2-1.3-1.8-1.3-3.4s.8-2.4 1.1-2.8c.3-.3.6-.4.8-.4Z"></path></svg></div>
          <div class="contact-text"><div class="contact-label">WhatsApp</div><div class="contact-value"><?= htmlspecialchars($c['whatsapp']) ?></div></div>
        </a>
        <?php endif; ?>
        <?php if (!empty($c['instagram'])): ?>
        <a class="contact-modal-row" href="https://instagram.com/<?= htmlspecialchars(ltrim($c['instagram'], '@')) ?>" target="_blank" rel="noopener">
          <div class="cm-icon ic-instagram" style="background:#C13584"><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="0.8" fill="currentColor"></circle></svg></div>
          <div class="contact-text"><div class="contact-label">Instagram</div><div class="contact-value"><?= htmlspecialchars($c['instagram']) ?></div></div>
        </a>
        <?php endif; ?>
        <?php if (!empty($c['phone'])): ?>
        <a class="contact-modal-row" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $c['phone'])) ?>">
          <div class="cm-icon ic-phone" style="background:var(--terracotta)"><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 3h3l2 5-2.5 1.5a11 11 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2Z" stroke-linecap="round" stroke-linejoin="round"></path></svg></div>
          <div class="contact-text"><div class="contact-label">Телефон</div><div class="contact-value"><?= htmlspecialchars($c['phone']) ?></div></div>
        </a>
        <?php endif; ?>
        <?php if (!empty($c['email'])): ?>
        <a class="contact-modal-row" href="mailto:<?= htmlspecialchars($c['email']) ?>">
          <div class="cm-icon ic-email" style="background:var(--ink-soft)"><svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2.5"></rect><path d="m4 6.5 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"></path></svg></div>
          <div class="contact-text"><div class="contact-label">Email</div><div class="contact-value"><?= htmlspecialchars($c['email']) ?></div></div>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
