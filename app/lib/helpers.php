<?php
// Дрібні допоміжні функції спільні для контролерів.

/**
 * Форматує дату у стилі "12 берез. 2026" (як в оригінальному макеті).
 */
function format_uk_date(?string $datetime): string
{
    if (!$datetime) return '';
    $months = [
        1 => 'січ.', 2 => 'лют.', 3 => 'берез.', 4 => 'квіт.',
        5 => 'трав.', 6 => 'черв.', 7 => 'лип.', 8 => 'серп.',
        9 => 'верес.', 10 => 'жовт.', 11 => 'листоп.', 12 => 'груд.',
    ];
    $ts = strtotime($datetime);
    if ($ts === false) return $datetime;
    $day = (int)date('j', $ts);
    $month = $months[(int)date('n', $ts)];
    $year = date('Y', $ts);
    return "{$day} {$month} {$year}";
}

/**
 * Абсолютна адреса сторінки сайту (для canonical, OG-тегів, sitemap.xml).
 * Враховує BASE_PATH (локально через XAMPP підпапка, на проді корінь).
 */
function site_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = '/' . ltrim($path, '/');
    return $scheme . '://' . $host . BASE_PATH . $path;
}

/**
 * Те саме, що site_url(), але для шляхів, які вже містять BASE_PATH
 * (наприклад cover_image-поля, зібрані як BASE_PATH.$row['cover_image']) —
 * щоб не додати BASE_PATH удруге.
 */
function absolute_url(string $pathWithBasePath): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = '/' . ltrim($pathWithBasePath, '/');
    return $scheme . '://' . $host . $path;
}

/**
 * Короткий опис для meta description / OG, коли поле excerpt порожнє —
 * бере початок тіла статті, прибирає HTML і обрізає без розриву слова.
 */
function seo_excerpt(string $text, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    if (mb_strlen($text) <= $length) return $text;
    $cut = mb_substr($text, 0, $length);
    $lastSpace = mb_strrpos($cut, ' ');
    if ($lastSpace !== false) $cut = mb_substr($cut, 0, $lastSpace);
    return rtrim($cut, " ,.;:") . '…';
}

/**
 * Виводить тіло статті. Підтримує і старий контент (простий текст,
 * абзаци через порожній рядок — з seed.sql), і новий HTML з
 * Quill-редактора (вже санітизований при збереженні).
 */
function render_article_body(string $body): string
{
    if (strip_tags($body) !== $body) {
        // Є HTML-теги — це вже санітизований HTML з редактора, виводимо як є.
        return $body;
    }
    $out = '';
    foreach (preg_split('/\n\s*\n/', trim($body)) as $para) {
        $out .= '<p>' . nl2br(htmlspecialchars($para)) . '</p>';
    }
    return $out;
}

/**
 * Читає ?page= із запиту (мінімум 1) і повертає [page, offset] для LIMIT/OFFSET.
 */
function paginate_offset(int $perPage): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    return [$page, ($page - 1) * $perPage];
}

/**
 * Малює блок пагінації (‹ 1 … 4 5 6 … 12 ›), зберігаючи інші GET-параметри
 * (пошук, тег, фільтри), крім page. $extraParams — асоціативний масив,
 * що додається до кожного посилання разом з номером сторінки.
 */
function render_pagination(int $page, int $totalPages, string $baseUrl, array $extraParams = []): string
{
    if ($totalPages <= 1) return '';
    $extraParams = array_filter($extraParams, fn($v) => $v !== '' && $v !== null);
    $urlFor = function (int $p) use ($baseUrl, $extraParams) {
        $params = $extraParams;
        $params['page'] = $p;
        return $baseUrl . '?' . http_build_query($params);
    };
    $window = 2;
    $pages = [];
    for ($p = 1; $p <= $totalPages; $p++) {
        if ($p === 1 || $p === $totalPages || abs($p - $page) <= $window) {
            $pages[] = $p;
        } elseif (end($pages) !== '…') {
            $pages[] = '…';
        }
    }
    $html = '<nav class="pagination" aria-label="Сторінки">';
    $html .= $page > 1
        ? '<a class="page-btn page-nav" href="' . htmlspecialchars($urlFor($page - 1)) . '">‹</a>'
        : '<span class="page-btn page-nav disabled">‹</span>';
    foreach ($pages as $p) {
        if ($p === '…') {
            $html .= '<span class="page-dots">…</span>';
        } elseif ($p === $page) {
            $html .= '<span class="page-btn active">' . $p . '</span>';
        } else {
            $html .= '<a class="page-btn" href="' . htmlspecialchars($urlFor($p)) . '">' . $p . '</a>';
        }
    }
    $html .= $page < $totalPages
        ? '<a class="page-btn page-nav" href="' . htmlspecialchars($urlFor($page + 1)) . '">›</a>'
        : '<span class="page-btn page-nav disabled">›</span>';
    $html .= '</nav>';
    return $html;
}
