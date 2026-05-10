#!/bin/bash
# Фінальне виправлення php.ini

set -e

PHP_INI="/etc/php83/php.ini"

echo "🔧 Фінальне виправлення php.ini"
echo ""

# 1. Закоментувати SQLite (рядки 960, 971)
echo "📋 1. Закоментовування SQLite..."
sudo sed -i '960s/^extension=pdo_sqlite/;extension=pdo_sqlite/' "$PHP_INI"
sudo sed -i '971s/^extension=sqlite3/;extension=sqlite3/' "$PHP_INI"
echo "✅ SQLite закоментовано"
echo ""

# 2. Перевірити результат
echo "📋 2. Перевірка..."
grep -n "extension.*sqlite" "$PHP_INI" | head -5
echo ""

# 3. Перезапуск Apache
echo "📋 3. Перезапуск Apache..."
sudo systemctl restart httpd
echo "✅ Apache перезапущено"
echo ""

# 4. Фінальна перевірка
echo "📋 4. Фінальна перевірка..."
php -v 2>&1 | head -3
echo ""
php -m 2>&1 | grep -E "sqlite" && echo "⚠️  SQLite все ще завантажено!" || echo "✅ SQLite не завантажено"
echo ""

echo "✅ Готово!"

