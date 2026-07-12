-- Виконати одноразово в phpMyAdmin (вкладка SQL), кожен запит окремо.

-- КРОК 1. Нова таблиця проєктів.
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    subtitle TEXT NULL,
    counter_value VARCHAR(20) NULL,
    counter_label VARCHAR(255) NULL,
    description TEXT NULL,
    cta_note TEXT NULL,
    cover_image VARCHAR(255) NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- КРОК 2. Переносимо існуючий проєкт "З Бджолами по Життю" (раніше жив
-- у site_settings + хардкоді сторінки) у новий запис. INSERT IGNORE —
-- безпечно перезапускати, при повторному виконанні нічого не зламає.
INSERT IGNORE INTO projects
    (sort_order, title, slug, subtitle, counter_value, counter_label, description, cta_note, cover_image, status)
SELECT
    0,
    'З Бджолами по Життю',
    'z-bdzholamy-po-zhyttyu',
    'Допомагаю новачкам почати власну пасіку — даю бджолину сім\'ю безкоштовно та підтримую перші кроки.',
    (SELECT setting_value FROM site_settings WHERE setting_key = 'counter_value'),
    (SELECT setting_value FROM site_settings WHERE setting_key = 'counter_label'),
    (SELECT setting_value FROM site_settings WHERE setting_key = 'counter_description'),
    (SELECT setting_value FROM site_settings WHERE setting_key = 'home_cta_note'),
    '/assets/img/d40cbc6b-66c6-4c6b-b27e-f05213168298.jpg',
    'published';
