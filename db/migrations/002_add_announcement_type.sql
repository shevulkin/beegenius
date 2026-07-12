-- Виконати одноразово в phpMyAdmin (вкладка SQL): додає тип "оголошення" до статей.
ALTER TABLE articles MODIFY type ENUM('blog','science','announcement') NOT NULL DEFAULT 'blog';
