<?php
// Легкий whitelist-санітайзер HTML для тексту статей (Quill-редактор).
// Без зовнішніх бібліотек: DOMDocument + явний список дозволених
// тегів/атрибутів. Усе, чого немає у списку, видаляється.

function sanitize_article_html(string $html): string
{
    if (trim($html) === '') return '';

    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'img',
        'blockquote', 'span',
    ];
    $allowedAttrs = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'span' => ['class'], // Quill інколи додає span class для форматування
    ];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="utf-8"?><div id="__root__">' . $html . '</div>',
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();

    $root = $doc->getElementById('__root__');
    if (!$root) return '';

    clean_node($root, $allowedTags, $allowedAttrs);

    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}

function clean_node(DOMNode $node, array $allowedTags, array $allowedAttrs): void
{
    $children = iterator_to_array($node->childNodes);
    foreach ($children as $child) {
        if ($child instanceof DOMText) continue;

        if (!($child instanceof DOMElement)) {
            $node->removeChild($child);
            continue;
        }

        $tag = strtolower($child->tagName);

        if (!in_array($tag, $allowedTags, true)) {
            // Тег не дозволений — витягуємо його вміст назовні (не втрачаємо текст),
            // але сам тег видаляємо.
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        // Прибрати всі атрибути, крім явно дозволених для цього тега.
        $allowed = $allowedAttrs[$tag] ?? [];
        foreach (iterator_to_array($child->attributes ?? []) as $attr) {
            if (!in_array($attr->name, $allowed, true)) {
                $child->removeAttribute($attr->name);
            }
        }

        // Для посилань — дозволяємо лише http(s)/mailto, забороняємо javascript: тощо.
        if ($tag === 'a' && $child->hasAttribute('href')) {
            $href = $child->getAttribute('href');
            if (!preg_match('#^(https?://|mailto:|/)#i', $href)) {
                $child->removeAttribute('href');
            } else {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }

        // Для фото — дозволяємо лише локальні шляхи (наші завантажені файли),
        // забороняємо data: та зовнішні джерела.
        // BASE_PATH враховуємо тому, що локально (XAMPP) сайт живе в підтеці
        // (/beegenius/public/uploads/...), а на проді — в корені (/uploads/...).
        if ($tag === 'img' && $child->hasAttribute('src')) {
            $src = $child->getAttribute('src');
            $base = defined('BASE_PATH') ? preg_quote(BASE_PATH, '#') : '';
            if (!preg_match('#^' . $base . '/(uploads|assets)/#i', $src)) {
                $node->removeChild($child);
                continue;
            }
        }

        clean_node($child, $allowedTags, $allowedAttrs);
    }
}
