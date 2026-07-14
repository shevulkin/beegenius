<?php
require_admin();
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$type = in_array($_POST['type'] ?? '', ['blog', 'science', 'announcement'], true) ? $_POST['type'] : 'blog';
$title = trim($_POST['title'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';
$isBanner = !empty($_POST['is_banner']) ? 1 : 0;
$tagsInput = trim($_POST['tags'] ?? '');
$bodyRaw = $_POST['body'] ?? '';
$body = sanitize_article_html($bodyRaw);

// Дати банера: приймаємо лише коректний формат YYYY-MM-DD, інакше — порожньо.
function validDateOrNull(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : null;
}
$bannerFrom = validDateOrNull($_POST['banner_from'] ?? '');
$bannerUntil = validDateOrNull($_POST['banner_until'] ?? '');
if ($bannerFrom && $bannerUntil && $bannerFrom > $bannerUntil) {
    http_response_code(400);
    echo 'Дата "показувати з" не може бути пізніше дати "показувати до".';
    exit;
}

if ($title === '' || $body === '') {
    http_response_code(400);
    echo 'Заголовок і текст статті обовʼязкові.';
    exit;
}

// slug: генеруємо з заголовка при створенні, латинізуємо кирилицю простим способом.
function slugify(string $text): string
{
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ye','ж'=>'zh',
        'з'=>'z','и'=>'y','і'=>'i','ї'=>'yi','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n',
        'о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts',
        'ч'=>'ch','ш'=>'sh','щ'=>'shch','ь'=>'','ю'=>'yu','я'=>'ya',
    ];
    $text = mb_strtolower($text);
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'article-' . time();
}

try {
    $pdo = db_connect();

    // Обкладинка: якщо завантажено новий файл — валідуємо і зберігаємо,
    // інакше лишаємо поточну.
    $coverImage = $_POST['cover_image_current'] ?? '';
    if (!empty($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_file'];
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Обкладинка завелика (максимум 5 МБ)');
        }
        $imageInfo = @getimagesize($file['tmp_name']);
        $allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
        if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
            throw new RuntimeException('Обкладинка має бути зображенням (JPG, PNG, WEBP, GIF)');
        }
        $ext = $allowedTypes[$imageInfo[2]];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $uploadDir = UPLOADS_DIR;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename);
        $coverImage = '/uploads/articles/' . $filename;
    }

    if ($id) {
        // Редагування існуючої статті.
        $slugStmt = $pdo->prepare('SELECT slug FROM articles WHERE id = ?');
        $slugStmt->execute([$id]);
        $slug = $slugStmt->fetchColumn() ?: slugify($title);

        $stmt = $pdo->prepare(
            'UPDATE articles SET type=?, title=?, excerpt=?, body=?, cover_image=?, status=?, is_banner=?, banner_from=?, banner_until=?,
             published_at = CASE WHEN ? = \'published\' AND published_at IS NULL THEN NOW() ELSE published_at END
             WHERE id = ?'
        );
        $stmt->execute([$type, $title, $excerpt, $body, $coverImage, $status, $isBanner, $bannerFrom, $bannerUntil, $status, $id]);
        sync_article_tags($pdo, $id, $tagsInput);
    } else {
        // Нова стаття.
        $baseSlug = slugify($title);
        $slug = $baseSlug;
        $i = 2;
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE slug = ?');
        while (true) {
            $checkStmt->execute([$slug]);
            if ((int)$checkStmt->fetchColumn() === 0) break;
            $slug = $baseSlug . '-' . $i++;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO articles (type, title, slug, excerpt, body, cover_image, status, is_banner, banner_from, banner_until, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$type, $title, $slug, $excerpt, $body, $coverImage, $status, $isBanner, $bannerFrom, $bannerUntil, $publishedAt]);
        sync_article_tags($pdo, (int)$pdo->lastInsertId(), $tagsInput);
    }

    header('Location: ' . BASE_PATH . '/admin');
    exit;
} catch (Throwable $e) {
    error_log('[articles_save] ' . $e->getMessage());
    http_response_code(500);
    echo 'Не вдалося зберегти статтю. Спробуйте ще раз або зверніться до розробника.';
    exit;
}
