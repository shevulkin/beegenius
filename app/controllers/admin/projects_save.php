<?php
require_admin();
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$title = trim($_POST['title'] ?? '');
$subtitle = trim($_POST['subtitle'] ?? '');
$counterValue = trim($_POST['counter_value'] ?? '');
$counterLabel = trim($_POST['counter_label'] ?? '');
$showOnHome = !empty($_POST['show_on_home']) ? 1 : 0;
$description = trim($_POST['description'] ?? '');
$ctaNote = trim($_POST['cta_note'] ?? '');
$status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';
$coverImage = $_POST['cover_image_current'] ?? '';
if ($coverImage !== '' && !preg_match('#^/(uploads|assets)/#i', $coverImage)) {
    $coverImage = '';
}

if ($title === '') {
    http_response_code(400);
    echo 'Назва проєкту обовʼязкова.';
    exit;
}

function project_slugify(string $text): string
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
    return trim($text, '-') ?: 'project-' . time();
}

try {
    $pdo = db_connect();

    // Лічильник на головній підтягується лише з одного проєкту — знімаємо
    // прапорець з усіх інших, перш ніж, за потреби, виставити його цьому.
    if ($showOnHome) {
        $pdo->exec('UPDATE projects SET show_on_home = 0');
    }

    if ($id) {
        $stmt = $pdo->prepare(
            'UPDATE projects SET title=?, subtitle=?, counter_value=?, counter_label=?, show_on_home=?, description=?, cta_note=?, cover_image=?, status=?
             WHERE id = ?'
        );
        $stmt->execute([$title, $subtitle, $counterValue ?: null, $counterLabel ?: null, $showOnHome, $description, $ctaNote, $coverImage ?: null, $status, $id]);
    } else {
        $baseSlug = project_slugify($title);
        $slug = $baseSlug;
        $i = 2;
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE slug = ?');
        while (true) {
            $checkStmt->execute([$slug]);
            if ((int)$checkStmt->fetchColumn() === 0) break;
            $slug = $baseSlug . '-' . $i++;
        }
        $maxOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM projects')->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO projects (sort_order, title, slug, subtitle, counter_value, counter_label, show_on_home, description, cta_note, cover_image, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$maxOrder + 1, $title, $slug, $subtitle, $counterValue ?: null, $counterLabel ?: null, $showOnHome, $description, $ctaNote, $coverImage ?: null, $status]);
    }

    header('Location: ' . BASE_PATH . '/admin');
    exit;
} catch (Throwable $e) {
    error_log('[projects_save] ' . $e->getMessage());
    http_response_code(500);
    echo 'Не вдалося зберегти проєкт. Спробуйте ще раз або зверніться до розробника.';
    exit;
}
