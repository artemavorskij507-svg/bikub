#!/bin/bash
# Завершення налаштування після видалення SQLite

set -e

cd '/home/dima/Стільниця/glfbikube (1-я копия)'

echo "🚀 Завершення налаштування"
echo ""

# 1. Перевірити та встановити DOM/tokenizer
echo "📋 1. Перевірка DOM/tokenizer..."
if ! php -m 2>&1 | grep -q "^dom$"; then
    echo "⚠️  DOM не завантажено"
    # Перевірити, чи є dom.so
    if [ -f "/usr/lib/php83/modules/dom.so" ]; then
        echo "✅ dom.so знайдено, додаємо в конфігурацію..."
        echo "extension=dom.so" | sudo tee /etc/php83/conf.d/20-dom.ini > /dev/null
    else
        echo "⚠️  dom.so не знайдено, потрібно встановити php83-dom"
    fi
else
    echo "✅ DOM вже завантажено"
fi

if ! php -m 2>&1 | grep -q "^tokenizer$"; then
    echo "⚠️  tokenizer не завантажено"
    # Перевірити, чи є tokenizer.so
    if [ -f "/usr/lib/php83/modules/tokenizer.so" ]; then
        echo "✅ tokenizer.so знайдено, додаємо в конфігурацію..."
        echo "extension=tokenizer.so" | sudo tee /etc/php83/conf.d/20-tokenizer.ini > /dev/null
    else
        echo "⚠️  tokenizer.so не знайдено, потрібно встановити php83-tokenizer"
    fi
else
    echo "✅ tokenizer вже завантажено"
fi
echo ""

# 2. Створити тестову БД
echo "📋 2. Створення тестової БД..."
if sudo -u postgres psql -lqt 2>/dev/null | cut -d \| -f 1 | grep -qw glfbikube_test; then
    echo "✅ База glfbikube_test вже існує"
else
    echo "Створюємо базу glfbikube_test..."
    sudo -u postgres createdb -O dima glfbikube_test
    echo "✅ База glfbikube_test створена"
fi
echo ""

# 3. Composer install
echo "📋 3. Composer install..."
composer install --no-interaction
echo "✅ Composer залежності встановлено"
echo ""

# 4. Очищення кешів
echo "📋 4. Очищення кешів Laravel..."
php artisan optimize:clear
echo "✅ Кеші очищено"
echo ""

# 5. Фінальна перевірка
echo "📋 5. Фінальна перевірка..."
php -v 2>&1 | head -3
echo ""
php -m 2>&1 | grep -E "^pdo_pgsql$|^pgsql$" && echo "✅ PostgreSQL модулі працюють"
php -m 2>&1 | grep -E "sqlite" && echo "⚠️  SQLite все ще завантажено!" || echo "✅ SQLite не завантажено"
php -m 2>&1 | grep -E "^dom$|^tokenizer$" && echo "✅ DOM/tokenizer працюють" || echo "⚠️  DOM/tokenizer не завантажені"
echo ""

echo "✅ Налаштування завершено!"

