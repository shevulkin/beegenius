-- Виконати одноразово в phpMyAdmin (вкладка SQL). Робимо ПОКРОКОВО,
-- кожен запит окремою кнопкою "Виконати" — так одразу видно, якщо щось
-- піде не так.

-- КРОК 1. Нові таблиці тегів.
CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- КРОК 2.
CREATE TABLE IF NOT EXISTS article_tags (
    article_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- КРОК 3. Прибираємо невикористану колонку category_id разом з її
-- зовнішнім ключем. ВАЖЛИВО: спочатку виконай `SHOW CREATE TABLE articles;`
-- і подивись точну назву FOREIGN KEY (зазвичай articles_ibfk_1) — якщо
-- назва інша, підстав свою замість articles_ibfk_1 у рядку нижче.
ALTER TABLE articles DROP FOREIGN KEY articles_ibfk_1;

-- КРОК 4.
ALTER TABLE articles DROP COLUMN category_id;

-- КРОК 5. Стара невикористана таблиця категорій — тепер точно можна прибрати.
DROP TABLE IF EXISTS categories;
