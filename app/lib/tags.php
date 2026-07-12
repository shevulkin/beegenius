<?php
// Теги для статей (блог / наука / оголошення): один тег може належати
// кільком статтям, стаття може мати кілька тегів (article_tags).

function tag_slugify(string $text): string
{
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ye','ж'=>'zh',
        'з'=>'z','и'=>'y','і'=>'i','ї'=>'yi','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n',
        'о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts',
        'ч'=>'ch','ш'=>'sh','щ'=>'shch','ь'=>'','ю'=>'yu','я'=>'ya',
    ];
    $text = mb_strtolower(trim($text));
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $slug = trim($text, '-');
    return $slug !== '' ? $slug : 'tag-' . substr(md5($text . microtime()), 0, 8);
}

/**
 * Розбирає рядок "мед, апітерапія, віск" на теги: створює нові (яких ще
 * немає) і перезаписує звʼязки article_tags для конкретної статті.
 * Викликається лише з-під require_admin() (у articles_save.php).
 */
function sync_article_tags(PDO $pdo, int $articleId, string $tagsInput): void
{
    $names = array_filter(array_map('trim', explode(',', $tagsInput)), fn($n) => $n !== '');
    $names = array_values(array_unique($names));

    $tagIds = [];
    foreach ($names as $name) {
        if (mb_strlen($name) > 60) $name = mb_substr($name, 0, 60);
        $slug = tag_slugify($name);

        $stmt = $pdo->prepare('SELECT id FROM tags WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $tagId = $stmt->fetchColumn();

        if (!$tagId) {
            $ins = $pdo->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)');
            $ins->execute([$name, $slug]);
            $tagId = (int)$pdo->lastInsertId();
        }
        $tagIds[] = (int)$tagId;
    }

    $pdo->prepare('DELETE FROM article_tags WHERE article_id = ?')->execute([$articleId]);
    if ($tagIds) {
        $placeholders = implode(',', array_fill(0, count($tagIds), '(?,?)'));
        $params = [];
        foreach ($tagIds as $tagId) {
            $params[] = $articleId;
            $params[] = $tagId;
        }
        $pdo->prepare('INSERT INTO article_tags (article_id, tag_id) VALUES ' . $placeholders)->execute($params);
    }
}

/** Теги однієї статті — [{name, slug}], відсортовані за назвою. */
function get_article_tags(PDO $pdo, int $articleId): array
{
    $stmt = $pdo->prepare(
        'SELECT t.name, t.slug FROM tags t
         JOIN article_tags at ON at.tag_id = t.id
         WHERE at.article_id = ?
         ORDER BY t.name ASC'
    );
    $stmt->execute([$articleId]);
    return $stmt->fetchAll();
}

/** Рядок тегів через кому — для передзаповнення поля у формі редагування. */
function get_tags_input_value(PDO $pdo, int $articleId): string
{
    return implode(', ', array_column(get_article_tags($pdo, $articleId), 'name'));
}

/**
 * Теги для кількох статей одразу (список постів) — щоб не робити N+1
 * запитів. Повертає масив [article_id => [{name, slug}, ...]].
 */
function get_tags_for_articles(PDO $pdo, array $articleIds): array
{
    $articleIds = array_values(array_unique(array_map('intval', $articleIds)));
    if (!$articleIds) return [];
    $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT at.article_id, t.name, t.slug FROM article_tags at
         JOIN tags t ON t.id = at.tag_id
         WHERE at.article_id IN ($placeholders)
         ORDER BY t.name ASC"
    );
    $stmt->execute($articleIds);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['article_id']][] = ['name' => $row['name'], 'slug' => $row['slug']];
    }
    return $result;
}

/** Усі теги, що трапляються серед опублікованих статей певного типу — для чіпсів-фільтра. */
function get_tags_for_type(PDO $pdo, string $type): array
{
    $stmt = $pdo->prepare(
        "SELECT DISTINCT t.name, t.slug FROM tags t
         JOIN article_tags at ON at.tag_id = t.id
         JOIN articles a ON a.id = at.article_id
         WHERE a.type = ? AND a.status = 'published'
         ORDER BY t.name ASC"
    );
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

/** Усі теги системи — для автопідказок в адмінці (незалежно від публікації). */
function get_all_tags(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, slug FROM tags ORDER BY name ASC')->fetchAll();
}

/**
 * Усі теги з кількістю статей, де використані — для сторінки керування
 * тегами в адмінці.
 */
function get_tags_with_usage(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT t.id, t.name, t.slug, COUNT(at.article_id) AS usage_count
         FROM tags t
         LEFT JOIN article_tags at ON at.tag_id = t.id
         GROUP BY t.id, t.name, t.slug
         ORDER BY t.name ASC"
    );
    return $stmt->fetchAll();
}

/** Статті (будь-якого типу/статусу), де використовується конкретний тег — для сторінки керування тегами. */
function get_articles_for_tag(PDO $pdo, int $tagId): array
{
    $stmt = $pdo->prepare(
        "SELECT a.id, a.title, a.type, a.status
         FROM articles a
         JOIN article_tags at ON at.article_id = a.id
         WHERE at.tag_id = ?
         ORDER BY a.title ASC"
    );
    $stmt->execute([$tagId]);
    return $stmt->fetchAll();
}
