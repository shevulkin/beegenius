-- Виконати одноразово в phpMyAdmin (вкладка SQL): період показу банера на головній.
ALTER TABLE articles
  ADD COLUMN banner_from DATE NULL AFTER is_banner,
  ADD COLUMN banner_until DATE NULL AFTER banner_from;
