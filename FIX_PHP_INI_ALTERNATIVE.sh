#!/bin/bash
# Альтернативний спосіб виправлення php.ini (через su або безпосереднє редагування)

PHP_INI="/etc/php83/php.ini"

echo "🔧 Альтернативне виправлення php.ini"
echo ""

# Перевірити поточний стан
echo "Поточний стан (рядки 960, 971):"
sed -n '960p;971p' "$PHP_INI"
echo ""

# Спробувати через sudo
echo "Спробуємо через sudo..."
if sudo -n true 2>/dev/null; then
    echo "✅ Sudo працює без пароля"
    sudo sed -i '960s/^extension=pdo_sqlite/;extension=pdo_sqlite/' "$PHP_INI"
    sudo sed -i '971s/^extension=sqlite3/;extension=sqlite3/' "$PHP_INI"
    sudo systemctl restart httpd
    echo "✅ Виправлено через sudo"
else
    echo "⚠️  Sudo потребує пароль"
    echo ""
    echo "Варіанти:"
    echo "1. Використайте 'su -' (потрібен root пароль)"
    echo "2. Або виконайте команди вручну з sudo:"
    echo "   sudo sed -i '960s/^extension=pdo_sqlite/;extension=pdo_sqlite/' /etc/php83/php.ini"
    echo "   sudo sed -i '971s/^extension=sqlite3/;extension=sqlite3/' /etc/php83/php.ini"
    echo "   sudo systemctl restart httpd"
    echo ""
    echo "3. Або відредагуйте файл вручну:"
    echo "   sudo nano /etc/php83/php.ini"
    echo "   Знайдіть рядки 960 і 971, додайте ; на початку"
fi

echo ""
echo "Перевірка результату:"
sed -n '960p;971p' "$PHP_INI"
echo ""

