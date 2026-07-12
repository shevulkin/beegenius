<?php
// Реальний контент із погодженого макета (буде перенесено в БД на кроці
// підключення адмінки — зараз це тимчасово хардкод, щоб сторінка виглядала
// так само, як у затвердженому дизайні).
$home = $home ?? [
    'kicker' => 'Проєкт «З Бджолами по Життю»',
    'title' => 'Валентин. Бджоли — мій спосіб життя',
    'subtitle' => 'Бджільництво в Альпійських вуликах, апітерапія та вироби з воску — коротко про все, чим я живу.',
    'aboutTitle' => 'Чим я займаюся',
    'aboutText' => 'Кілька років тому бджоли з хобі перетворилися на спосіб життя. Я веду господарство в Альпійських вуликах, займаюся апітерапією та роблю вироби з воску. А ще допомагаю новачкам почати власну пасіку в межах проєкту «З Бджолами по Життю».',
];
$heroImage = !empty($home['heroImage']) ? $home['heroImage'] : BASE_PATH . '/assets/img/279fad5b-5ed2-47ff-8613-fbf8212c6d3a.jpg';
$activities = $activities ?? [
    ['n' => '01', 'title' => '«З Бджолами по Життю»', 'text' => 'Даю новачкам можливість безкоштовно отримати бджолину сім\'ю і почати власну пасіку.'],
    ['n' => '02', 'title' => 'Альпійські вулики', 'text' => 'Веду господарство за системою Альпійських вуликів — компактно і зручно для бджіл.'],
    ['n' => '03', 'title' => 'Вироби з воску', 'text' => 'Створюю свічки та засоби для шкіри з воску власної пасіки.'],
    ['n' => '04', 'title' => 'Апітерапія', 'text' => 'Досліджую і на практиці застосовую лікування продуктами бджільництва.'],
];
$counter = $counter ?? ['value' => '18', 'label' => 'Бджолородин передано новачкам пасічникам', 'link' => BASE_PATH . '/projects/z-bdzholamy-po-zhyttyu'];
$quotes = $quotes ?? [
    ['text' => 'Приєднався до проєкту у 2022, а вже наступного літа мав власний перший рій.', 'year' => '2022'],
    ['text' => 'Валентин допоміг не просто з бджолами, а з розумінням, як взагалі почати.', 'year' => '2023'],
    ['text' => 'Тепер у мене чотири вулики і власний мед для родини.', 'year' => '2024'],
];
$latestPosts = $latestPosts ?? [
    ['slug' => 'pershe-vidkryttya-vulykiv', 'cover' => BASE_PATH . '/assets/img/1a858eef-934c-4172-b189-71ae79c21fc5.jpg', 'date' => '12 берез. 2026', 'title' => 'Перше відкриття вуликів після зими'],
    ['slug' => 'alpiyskyi-vulyk', 'cover' => BASE_PATH . '/assets/img/c04d57bc-9f56-4c0e-aece-1721d8a501a9.jpg', 'date' => '2 лют. 2026', 'title' => 'Альпійський вулик: чому я обрав саме цю систему'],
    ['slug' => 'svichky-z-vosku', 'cover' => BASE_PATH . '/assets/img/80a0467e-31c1-414b-a641-a102f55b9f06.jpg', 'date' => '20 груд. 2025', 'title' => 'Свічки з воску — новий вид продукції'],
];

$banner = $banner ?? null;

$pageTitle = 'Bee Genius — головна';
$active = 'home';
include __DIR__ . '/layout/header.php';
?>
<section class="hero">
  <div class="hero-grid">
    <div>
      <span class="kicker"><?= htmlspecialchars($home['kicker']) ?></span>
      <h1 class="serif"><?= htmlspecialchars($home['title']) ?></h1>
      <p class="sub"><?= htmlspecialchars($home['subtitle']) ?></p>
      <div class="btnrow">
        <a class="btn btn-primary" href="<?= BASE_PATH ?>/blog">Читати блог</a>
        <a class="btn btn-ghost" href="<?= BASE_PATH ?>/science">Наукові статті</a>
        <a class="btn btn-ghost" href="#contact" onclick="return openContactModal(event)">Зв'язатися</a>
      </div>
    </div>
    <div class="hero-photo">
      <img src="<?= htmlspecialchars($heroImage) ?>" alt="Валентин на пасіці">
    </div>
  </div>
</section>

<?php if (!empty($banners)): ?>
<div class="home-banner-wrap">
  <div class="home-banner-carousel" id="home-banner-carousel">
    <div class="home-banner-track">
      <?php foreach ($banners as $i => $b): ?>
        <a class="home-banner" href="<?= BASE_PATH ?>/announcements/<?= urlencode($b['slug']) ?>">
          <?php if (!empty($b['cover'])): ?>
            <div class="home-banner-photo"><img src="<?= htmlspecialchars($b['cover']) ?>" alt="<?= htmlspecialchars($b['title']) ?>"></div>
          <?php endif; ?>
          <div class="home-banner-body">
            <span class="home-banner-kicker">Спеціальна пропозиція</span>
            <h3 class="home-banner-title"><?= htmlspecialchars($b['title']) ?></h3>
            <?php if (!empty($b['excerpt'])): ?>
              <p class="home-banner-text"><?= htmlspecialchars($b['excerpt']) ?></p>
            <?php endif; ?>
          </div>
          <span class="home-banner-cta">Переглянути пропозицію <span class="home-banner-arrow">→</span></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (count($banners) > 1): ?>
      <div class="home-banner-dots">
        <?php foreach ($banners as $i => $b): ?>
          <button type="button" class="home-banner-dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>" aria-label="Пропозиція <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php if (count($banners) > 1): ?>
<script>
(function () {
  var root = document.getElementById('home-banner-carousel');
  if (!root) return;
  var track = root.querySelector('.home-banner-track');
  var slides = track.children;
  var dots = root.querySelectorAll('.home-banner-dot');
  var index = 0;
  var total = slides.length;
  var timer = null;

  function show(i) {
    index = (i + total) % total;
    track.style.transform = 'translateX(-' + (index * 100) + '%)';
    for (var d = 0; d < dots.length; d++) {
      dots[d].classList.toggle('active', d === index);
    }
  }
  function next() { show(index + 1); }
  function restart() {
    if (timer) clearInterval(timer);
    timer = setInterval(next, 5000);
  }

  dots.forEach(function (dot) {
    dot.addEventListener('click', function () {
      show(parseInt(dot.dataset.index, 10));
      restart();
    });
  });

  show(0);
  restart();
})();
</script>
<?php endif; ?>
<?php endif; ?>

<div class="bleed-strip">
  <img src="<?= BASE_PATH ?>/assets/img/6db72486-be10-4522-9b75-689533047c40.jpg" alt="Бджолиний рій">
  <span class="bleed-cap">«Кожна сім'я тримається одна одної — так само, як наша спільнота пасічників»</span>
</div>

<section class="section">
  <div class="section-head about-centered"><h2 class="serif"><?= htmlspecialchars($home['aboutTitle']) ?></h2></div>
  <p class="about-text about-centered"><?= htmlspecialchars($home['aboutText']) ?></p>
  <div class="activities">
    <?php foreach ($activities as $a): ?>
      <div class="activity-card">
        <div class="activity-num"><?= htmlspecialchars($a['n']) ?></div>
        <h3><?= htmlspecialchars($a['title']) ?></h3>
        <p><?= htmlspecialchars($a['text']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if (!empty($counter)): ?>
<section class="counter-band">
  <div class="counter-num"><?= htmlspecialchars($counter['value']) ?></div>
  <?php if (!empty($counter['label'])): ?><p class="counter-label"><?= htmlspecialchars($counter['label']) ?></p><?php endif; ?>
  <?php if (!empty($counter['link'])): ?>
    <a class="btn btn-primary" href="<?= htmlspecialchars($counter['link']) ?>">Детальніше про проєкт</a>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="section">
  <div class="section-head">
    <span class="kicker">Відгуки</span>
    <h2 class="serif">Що кажуть учасники проєкту</h2>
  </div>
  <div class="quotes">
    <?php foreach ($quotes as $q): ?>
      <div class="quote-card">
        <?php if (!empty($q['image'])): ?>
          <div class="quote-image"><img src="<?= htmlspecialchars($q['image']) ?>" alt="Відгук, <?= htmlspecialchars($q['year']) ?>"></div>
        <?php endif; ?>
        <p>«<?= htmlspecialchars($q['text']) ?>»</p>
        <div class="quote-year">— <?= htmlspecialchars($q['year']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-alt">
  <div class="section-head">
    <span class="kicker">Блог</span>
    <h2 class="serif">Останні записи</h2>
  </div>
  <div class="card-grid">
    <?php if (empty($latestPosts)): ?>
      <p>Ще немає жодної статті. Додайте першу через адмінку.</p>
    <?php endif; ?>
    <?php foreach ($latestPosts as $p): ?>
      <a class="post-card" href="<?= BASE_PATH ?>/blog/<?= urlencode($p['slug']) ?>">
        <div class="post-cover"><img src="<?= htmlspecialchars($p['cover']) ?>" alt="<?= htmlspecialchars($p['title']) ?>"></div>
        <div class="post-body">
          <div class="post-date"><?= htmlspecialchars($p['date']) ?></div>
          <h3><?= htmlspecialchars($p['title']) ?></h3>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/layout/footer.php'; ?>
