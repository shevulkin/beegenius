-- Виконати одноразово в phpMyAdmin (вкладка SQL): прапорець "показувати лічильник
-- цього проєкту на головній сторінці" — замінює ручне дублювання числа в site_settings.
-- Лише один проєкт одночасно може мати show_on_home = 1 (стежить admin/projects_save.php).
ALTER TABLE projects ADD COLUMN show_on_home TINYINT(1) NOT NULL DEFAULT 0 AFTER counter_label;
