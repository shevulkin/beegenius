-- Виконати одноразово в phpMyAdmin (вкладка SQL): колонка activities.TEXT створилась
-- у верхньому регістрі (розбіжність зі schema.sql), через що "SELECT *" повертає ключ
-- "TEXT" замість "text" і ламає код, який очікує lowercase. Приводимо до schema.sql.
ALTER TABLE activities CHANGE COLUMN `TEXT` `text` TEXT NOT NULL;
