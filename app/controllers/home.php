<?php
// Контролер головної сторінки: тягне реальні дані з БД
// (site_settings, activities, quotes, останні пости блогу).
// Якщо БД недоступна — home.php сам підставить приклад-контент (через ??).

$home = $activities = $quotes = $latestPosts = $counter = null;
$banners = [];

try {
    $pdo = db_connect();

    // --- тексти головної сторінки ---
    $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    if ($settings) {
        $home = [
            'kicker' => $settings['home_kicker'] ?? '',
            'title' => $settings['home_title'] ?? '',
            'subtitle' => $settings['home_subtitle'] ?? '',
            'aboutTitle' => $settings['home_about_title'] ?? '',
            'aboutText' => $settings['home_about_text'] ?? '',
            'heroImage' => !empty($settings['home_hero_image']) ? BASE_PATH . $settings['home_hero_image'] : '',
        ];
    }

    // --- лічильник: підтягується з опублікованого проєкту, позначеного
    //     "показувати на головній" (адмін/projects) — чернетки на головній
    //     не показуються, як і решта неопублікованого контенту. ---
    $counterRow = $pdo->query(
        "SELECT slug, counter_value, counter_label FROM projects WHERE show_on_home = 1 AND status = 'published' ORDER BY id LIMIT 1"
    )->fetch();
    $counter = [];
    if ($counterRow && $counterRow['counter_value'] !== null && $counterRow['counter_value'] !== '') {
        $counter = [
            'value' => $counterRow['counter_value'],
            'label' => $counterRow['counter_label'],
            'link' => BASE_PATH . '/projects/' . $counterRow['slug'],
        ];
    }

    // --- напрямки діяльності ---
    $stmt = $pdo->query("SELECT title, text FROM activities ORDER BY sort_order ASC");
    $activitiesRows = $stmt->fetchAll();
    if ($activitiesRows) {
        $activities = [];
        foreach ($activitiesRows as $i => $a) {
            $activities[] = [
                'n' => str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT),
                'title' => $a['title'],
                'text' => $a['text'],
            ];
        }
    }

    // --- відгуки (каруселька на головній — обмежуємо, повний список в адмінці) ---
    $stmt = $pdo->query("SELECT quote_text, quote_year, quote_image FROM quotes ORDER BY sort_order ASC LIMIT 12");
    $quoteRows = $stmt->fetchAll();
    if ($quoteRows) {
        $quotes = [];
        foreach ($quoteRows as $q) {
            $quotes[] = [
                'text' => $q['quote_text'],
                'year' => $q['quote_year'],
                'image' => !empty($q['quote_image']) ? BASE_PATH . $q['quote_image'] : '',
            ];
        }
    }

    // --- банери: усі опубліковані оголошення з позначкою is_banner,
    //     активні за датами (banner_from/banner_until, якщо задані).
    //     Якщо їх кілька — на головній показується каруселька. ---
    $stmt = $pdo->query(
        "SELECT title, slug, excerpt, cover_image
         FROM articles
         WHERE type = 'announcement' AND status = 'published' AND is_banner = 1
           AND (banner_from IS NULL OR banner_from <= CURDATE())
           AND (banner_until IS NULL OR banner_until >= CURDATE())
         ORDER BY published_at DESC
         LIMIT 10"
    );
    foreach ($stmt->fetchAll() as $bannerRow) {
        $banners[] = [
            'slug' => $bannerRow['slug'],
            'title' => $bannerRow['title'],
            'excerpt' => $bannerRow['excerpt'],
            'cover' => !empty($bannerRow['cover_image']) ? BASE_PATH . $bannerRow['cover_image'] : '',
        ];
    }

    // --- останні 3 опубліковані пости блогу ---
    $stmt = $pdo->prepare(
        "SELECT title, slug, excerpt, cover_image, published_at
         FROM articles
         WHERE type = 'blog' AND status = 'published'
         ORDER BY published_at DESC
         LIMIT 3"
    );
    $stmt->execute();
    $postRows = $stmt->fetchAll();
    if ($postRows) {
        $latestPosts = [];
        foreach ($postRows as $p) {
            $latestPosts[] = [
                'slug' => $p['slug'],
                'cover' => BASE_PATH . $p['cover_image'],
                'date' => format_uk_date($p['published_at']),
                'title' => $p['title'],
            ];
        }
    } else {
        $latestPosts = []; // БД працює, просто постів ще нема — показати порожній стан, не приклад
    }
} catch (Throwable $e) {
    error_log('[home controller] DB unavailable, falling back to placeholder content: ' . $e->getMessage());
    // $home/$activities/$quotes лишаються null -> home.php підставить приклад-контент
    // $latestPosts лишається null -> home.php підставить приклад-пости
}

$metaDescription = !empty($home['subtitle'])
    ? seo_excerpt($home['subtitle'])
    : "Bee Genius · Валентин — блог та наукові статті про бджільництво й апітерапію.";
$metaType = 'website';
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'Bee Genius',
    'url' => site_url('/'),
    'logo' => site_url('/assets/img/b5c58d9b-b08e-40f0-871d-b0cea3dfb828.jpg'),
    'founder' => ['@type' => 'Person', 'name' => 'Валентин'],
];

require __DIR__ . '/../views/home.php';
