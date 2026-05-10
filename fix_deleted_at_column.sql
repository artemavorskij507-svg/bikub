-- SQL-скрипт для добавления столбца deleted_at в таблицу orders
-- Выполнить вручную в базе данных SQLite

-- Для SQLite:
ALTER TABLE orders ADD COLUMN deleted_at TIMESTAMP NULL;

-- Проверка добавления столбца:
PRAGMA table_info(orders);

-- Альтернативный способ (если ALTER TABLE не работает):
-- 1. Создать новую таблицу с нужной структурой
-- 2. Скопировать данные
-- 3. Удалить старую таблицу
-- 4. Переименовать новую таблицу

-- Пример полной миграции (если нужно пересоздать таблицу):
/*
CREATE TABLE orders_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number VARCHAR(255) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    assigned_to INTEGER NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'pending',
    priority VARCHAR(255) NOT NULL DEFAULT 'normal',
    notes TEXT NULL,
    location JSON NULL,
    scheduled_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'NOK',
    payment_status VARCHAR(255) NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(255) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO orders_new SELECT *, NULL as deleted_at FROM orders;
DROP TABLE orders;
ALTER TABLE orders_new RENAME TO orders;
*/
