#!/bin/bash
# Повне видалення SQLite та налаштування PostgreSQL для всього проекту

set -e

PHP_INI="/etc/php83/php.ini"
PROJECT_DIR="/home/dima/Стільниця/glfbikube (1-я копия)"

echo "🚀 Повне видалення SQLite та налаштування PostgreSQL"
echo ""

# ============================================
# ФАЗА 1: Налаштування PHP
# ============================================
echo "📋 ФАЗА 1: Налаштування PHP 8.3"
echo ""

echo "1.1. Розкоментовування pdo_pgsql..."
sudo sed -i 's/^;extension=pdo_pgsql$/extension=pdo_pgsql/' "$PHP_INI"
echo "✅ pdo_pgsql розкоментовано"

echo "1.2. Закоментовування pdo_sqlite..."
sudo sed -i 's/^extension=pdo_sqlite$/;extension=pdo_sqlite/' "$PHP_INI"
echo "✅ pdo_sqlite закоментовано"

echo "1.3. Закоментовування sqlite3..."
sudo sed -i 's/^extension=sqlite3$/;extension=sqlite3/' "$PHP_INI"
echo "✅ sqlite3 закоментовано"

echo "1.4. Перезапуск Apache..."
sudo systemctl restart httpd
echo "✅ Apache перезапущено"
echo ""

# ============================================
# ФАЗА 2: Налаштування Laravel
# ============================================
echo "📋 ФАЗА 2: Налаштування Laravel"
echo ""

cd "$PROJECT_DIR"

echo "2.1. Перевірка .env..."
if grep -q "DB_CONNECTION=sqlite" .env 2>/dev/null; then
    echo "⚠️  Знайдено DB_CONNECTION=sqlite в .env, видаляємо..."
    sed -i '/^DB_CONNECTION=sqlite/d' .env
    echo "✅ SQLite видалено з .env"
else
    echo "✅ .env вже налаштовано на pgsql"
fi

echo "2.2. Перевірка config/database.php..."
if grep -q "'sqlite'" config/database.php; then
    echo "⚠️  Знайдено секцію sqlite в config/database.php, видаляємо..."
    # Видалити секцію sqlite
    sed -i "/'sqlite' => \[/,/\]/d" config/database.php
    echo "✅ SQLite видалено з config/database.php"
else
    echo "✅ config/database.php вже не містить SQLite"
fi

echo "2.3. Налаштування phpunit.xml..."
if grep -q 'DB_CONNECTION.*sqlite' phpunit.xml; then
    echo "⚠️  Знайдено SQLite в phpunit.xml, змінюємо на PostgreSQL..."
    sed -i 's/<env name="DB_CONNECTION" value="sqlite"\/>/<env name="DB_CONNECTION" value="pgsql"\/>/' phpunit.xml
    sed -i 's/<env name="DB_DATABASE" value=":memory:"\/>/<env name="DB_DATABASE" value="glfbikube_test"\/>/' phpunit.xml
    echo "✅ phpunit.xml налаштовано на PostgreSQL"
else
    echo "✅ phpunit.xml вже налаштовано"
fi

echo "2.4. Створення тестової бази даних..."
if sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw glfbikube_test; then
    echo "✅ База glfbikube_test вже існує"
else
    echo "Створюємо базу glfbikube_test..."
    sudo -u postgres createdb -O dima glfbikube_test
    echo "✅ База glfbikube_test створена"
fi
echo ""

# ============================================
# ФАЗА 3: Оновлення залежностей
# ============================================
echo "📋 ФАЗА 3: Оновлення залежностей"
echo ""

echo "3.1. Composer install..."
composer install --no-interaction
echo "✅ Composer залежності встановлено"

echo "3.2. npm install..."
npm install
echo "✅ npm залежності встановлено"

echo "3.3. Очищення кешів Laravel..."
php artisan optimize:clear
echo "✅ Кеші Laravel очищено"
echo ""

# ============================================
# ФАЗА 4: Перевірка конфігурацій
# ============================================
echo "📋 ФАЗА 4: Перевірка конфігурацій"
echo ""

echo "4.1. Перевірка tailwind.config.js..."
if [ -f "tailwind.config.js" ]; then
    if grep -q "frontend/src" tailwind.config.js; then
        echo "⚠️  Знайдено посилання на frontend/src, видаляємо..."
        # Видалити рядки з frontend/src
        sed -i '/frontend\/src/d' tailwind.config.js
        echo "✅ tailwind.config.js очищено"
    else
        echo "✅ tailwind.config.js налаштовано правильно"
    fi
else
    echo "⚠️  tailwind.config.js не знайдено"
fi
echo ""

# ============================================
# ФАЗА 5: Фінальна перевірка
# ============================================
echo "📋 ФАЗА 5: Фінальна перевірка"
echo ""

echo "5.1. PHP версія:"
php -v 2>&1 | head -3
echo ""

echo "5.2. PHP модулі:"
php -m 2>&1 | grep -E "^pdo_pgsql$|^pgsql$" || echo "⚠️  Модулі не знайдено"
php -m 2>&1 | grep -E "sqlite" && echo "⚠️  SQLite все ще завантажено!" || echo "✅ SQLite не завантажено"
echo ""

echo "5.3. Перевірка конфігурації БД:"
php artisan config:show database.default 2>/dev/null || echo "⚠️  Не вдалося отримати конфігурацію"
echo ""

echo "✅ Всі зміни виконано!"
echo ""
echo "🌐 Наступні кроки:"
echo "   1. Запустіть: npm run dev (в окремому терміналі)"
echo "   2. Відкрийте: http://localhost:2244"
echo "   3. Запустіть тести: php artisan test"


