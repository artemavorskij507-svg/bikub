#!/bin/bash
# Виправлення конфігурації PHP 8.3

set -e

PHP_INI="/etc/php83/php.ini"

echo "🔧 Виправлення конфігурації PHP 8.3"
echo ""

# Крок 1: Видалити дублікати з php.ini (залишити тільки в conf.d)
echo "📋 Крок 1: Видалення дублікатів з php.ini..."

# Закомментувати pdo_pgsql і pgsql в php.ini (якщо вони там є)
sudo sed -i 's/^extension=pdo_pgsql$/;extension=pdo_pgsql/' "$PHP_INI"
sudo sed -i 's/^extension=pgsql$/;extension=pgsql/' "$PHP_INI"

# Перевірити pdo_sqlite і sqlite
if grep -q "^extension=pdo_sqlite" "$PHP_INI"; then
    echo "⚠️  Знайдено extension=pdo_sqlite в php.ini, залишаємо (для тестів)"
else
    # Додати pdo_sqlite для тестів (якщо його немає)
    if ! grep -q "extension=pdo_sqlite" "$PHP_INI"; then
        echo "Додаємо extension=pdo_sqlite для тестів..."
        sudo sed -i '/^;extension=pdo_sqlite/s/^;//' "$PHP_INI" || echo "extension=pdo_sqlite" | sudo tee -a "$PHP_INI" > /dev/null
    fi
fi

if grep -q "^extension=sqlite3" "$PHP_INI"; then
    echo "⚠️  Знайдено extension=sqlite3 в php.ini, залишаємо (для тестів)"
else
    if ! grep -q "extension=sqlite3" "$PHP_INI"; then
        echo "Додаємо extension=sqlite3 для тестів..."
        sudo sed -i '/^;extension=sqlite3/s/^;//' "$PHP_INI" || echo "extension=sqlite3" | sudo tee -a "$PHP_INI" > /dev/null
    fi
fi

echo "✅ Дублікати видалено"
echo ""

# Крок 2: Перевірка порядку завантаження в conf.d
echo "📋 Крок 2: Перевірка порядку завантаження модулів..."
echo "Порядок має бути: pdo (10) -> pdo_pgsql (20) -> pgsql (20)"
ls -la /etc/php83/conf.d/10-pdo.ini /etc/php83/conf.d/20-pdo_pgsql.ini /etc/php83/conf.d/20-pgsql.ini 2>/dev/null && echo "✅ Порядок правильний" || echo "⚠️  Перевірте порядок вручну"
echo ""

# Крок 3: Перевірка модулів
echo "📋 Крок 3: Перевірка модулів..."
php -m 2>&1 | grep -E "^pdo$|^pdo_pgsql$|^pgsql$|^pdo_sqlite$|^sqlite3$" && echo "✅ Модулі працюють" || echo "⚠️  Деякі модулі не знайдено"
echo ""

echo "✅ Конфігурація виправлена!"


