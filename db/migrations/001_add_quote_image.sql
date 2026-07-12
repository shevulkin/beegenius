-- Виконати одноразово в phpMyAdmin (вкладка SQL), якщо таблиця quotes вже створена.
ALTER TABLE quotes ADD COLUMN quote_image VARCHAR(255) NULL AFTER quote_year;
