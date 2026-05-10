#!/bin/bash
# Виправлення залишкових проблем

set -e

echo "🔧 Виправлення залишкових проблем"
echo ""

# 1. Встановлення php83-xml (якщо потрібно)
echo "📋 1. Перевірка php83-xml..."
if ! pacman -Q php83-xml &>/dev/null; then
    echo "Встановлюємо php83-xml..."
    yay -S php83-xml --noconfirm --batchinstall --answerclean All --answerdiff None --removemake
    echo "✅ php83-xml встановлено"
else
    echo "✅ php83-xml вже встановлено"
fi
echo ""

# 2. Виправлення php.ini (видалити SQLite)
echo "📋 2. Виправлення php.ini..."
PHP_INI="/etc/php83/php.ini"
sudo sed -i 's/^extension=pdo_sqlite$/;extension=pdo_sqlite/' "$PHP_INI"
sudo sed -i 's/^extension=sqlite3$/;extension=sqlite3/' "$PHP_INI"
sudo sed -i 's/^;extension=pdo_pgsql$/extension=pdo_pgsql/' "$PHP_INI"
echo "✅ php.ini виправлено"
echo ""

# 3. Перезапуск Apache
echo "📋 3. Перезапуск Apache..."
sudo systemctl restart httpd
echo "✅ Apache перезапущено"
echo ""

# 4. Створення тестової БД
echo "📋 4. Створення тестової БД..."
if ! sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw glfbikube_test; then
    sudo -u postgres createdb -O dima glfbikube_test
    echo "✅ База glfbikube_test створена"
else
    echo "✅ База glfbikube_test вже існує"
fi
echo ""

# 5. Перевірка
echo "📋 5. Фінальна перевірка..."
php -v 2>&1 | head -3
echo ""
php -m 2>&1 | grep -E "^dom$|^xml$" && echo "✅ DOM/XML працюють" || echo "⚠️  DOM/XML не знайдено"
php -m 2>&1 | grep -E "sqlite" && echo "⚠️  SQLite все ще завантажено!" || echo "✅ SQLite не завантажено"
echo ""

echo "✅ Готово!"


