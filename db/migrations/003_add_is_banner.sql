-- Виконати одноразово в phpMyAdmin (вкладка SQL): позначка "показувати як банер на головній".
ALTER TABLE articles ADD COLUMN is_banner TINYINT(1) NOT NULL DEFAULT 0 AFTER cover_image;
