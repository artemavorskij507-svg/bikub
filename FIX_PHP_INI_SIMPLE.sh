#!/bin/bash
# Простий скрипт для виправлення php.ini

PHP_INI="/etc/php83/php.ini"

echo "🔧 Виправлення php.ini"
echo ""

# Перевірити поточний стан
echo "Поточний стан (рядки 960, 971):"
sed -n '960p;971p' "$PHP_INI"
echo ""

# Закоментувати SQLite
echo "Закоментовуємо SQLite..."
sudo sed -i '960s/^extension=pdo_sqlite/;extension=pdo_sqlite/' "$PHP_INI"
sudo sed -i '971s/^extension=sqlite3/;extension=sqlite3/' "$PHP_INI"

# Перевірити результат
echo "Результат:"
sed -n '960p;971p' "$PHP_INI"
echo ""

# Перезапустити Apache
echo "Перезапускаємо Apache..."
sudo systemctl restart httpd
echo "✅ Готово!"

