CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('blog','science','announcement') NOT NULL DEFAULT 'blog', -- блог / наука / оголошення-пропозиції
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT NULL,
    body MEDIUMTEXT NOT NULL,
    cover_image VARCHAR(255) NULL,   -- шлях у /uploads
    is_banner TINYINT(1) NOT NULL DEFAULT 0, -- показувати як банер на головній (актуально для оголошень)
    banner_from DATE NULL,  -- банер активний від цієї дати (якщо задано)
    banner_until DATE NULL, -- банер активний до цієї дати включно (якщо задано)
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Теги: гнучке маркування статей (кілька тегів на статтю, один тег
-- може належати кільком статтям). Замінює окрему систему категорій.
CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS article_tags (
    article_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_login_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    success TINYINT(1) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Тексти головної сторінки, лічильник, контакти — щоб редагувалось з адмінки,
-- а не хардкодилось у коді.
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    text TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    quote_text TEXT NOT NULL,
    quote_year VARCHAR(10) NOT NULL,
    quote_image VARCHAR(255) NULL -- необов'язковий скріншот відгуку, шлях у /uploads
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Проєкти Валентина (наприклад "З Бджолами по Життю") — можуть додаватись
-- адміністратором, кожен має власну сторінку /projects/{slug}.
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    subtitle TEXT NULL,              -- короткий опис під заголовком
    counter_value VARCHAR(20) NULL,  -- число-результат (наприклад "18"), необов'язково
    counter_label VARCHAR(255) NULL, -- підпис під числом
    show_on_home TINYINT(1) NOT NULL DEFAULT 0, -- показувати лічильник цього проєкту на головній (лише один проєкт одночасно)
    description TEXT NULL,           -- основний опис проєкту
    cta_note TEXT NULL,              -- заклик до дії внизу сторінки
    cover_image VARCHAR(255) NULL,   -- шлях у /uploads
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
