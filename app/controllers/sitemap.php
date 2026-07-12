<?php
// Динамічний sitemap.xml — генерується з реальних опублікованих
// записів (статті всіх типів + проєкти), оновлюється сам собою.
header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['loc' => site_url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => site_url('/blog'), 'changefreq' => 'daily', 'priority' => '0.8'],
    ['loc' => site_url('/science'), 'changefreq' => 'weekly', 'priority' => '0.8'],
    ['loc' => site_url('/announcements'), 'changefreq' => 'daily', 'priority' => '0.7'],
    ['loc' => site_url('/projects'), 'changefreq' => 'weekly', 'priority' => '0.7'],
];

try {
    $pdo = db_connect();

    $stmt = $pdo->query(
        "SELECT type, slug, COALESCE(updated_at, published_at) AS lastmod
         FROM articles WHERE status = 'published' ORDER BY published_at DESC"
    );
    foreach ($stmt->fetchAll() as $row) {
        $urls[] = [
            'loc' => site_url('/' . ($row['type'] === 'announcement' ? 'announcements' : $row['type']) . '/' . $row['slug']),
            'lastmod' => $row['lastmod'] ? date('c', strtotime($row['lastmod'])) : null,
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ];
    }

    $stmt = $pdo->query(
        "SELECT slug, updated_at FROM projects WHERE status = 'published' ORDER BY created_at ASC"
    );
    foreach ($stmt->fetchAll() as $row) {
        $urls[] = [
            'loc' => site_url('/projects/' . $row['slug']),
            'lastmod' => $row['updated_at'] ? date('c', strtotime($row['updated_at'])) : null,
            'changefreq' => 'monthly',
            'priority' => '0.6',
        ];
    }
} catch (Throwable $e) {
    error_log('[sitemap] DB error: ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc']) . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($u['lastmod']) . "</lastmod>\n";
    }
    if (!empty($u['changefreq'])) {
        echo '    <changefreq>' . htmlspecialchars($u['changefreq']) . "</changefreq>\n";
    }
    if (!empty($u['priority'])) {
        echo '    <priority>' . htmlspecialchars($u['priority']) . "</priority>\n";
    }
    echo "  </url>\n";
}
echo '</urlset>';
