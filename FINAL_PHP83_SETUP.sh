#!/bin/bash
# Фінальна настройка PHP 8.3

set -e

PHP_INI="/etc/php83/php.ini"

echo "🚀 Фінальна настройка PHP 8.3"
echo ""

# Крок 1: Виправити дублікати в php.ini
echo "📋 Крок 1: Виправлення дублікатів в php.ini..."
sudo sed -i 's/^extension=pdo_pgsql$/;extension=pdo_pgsql/' "$PHP_INI"
sudo sed -i 's/^extension=pgsql$/;extension=pgsql/' "$PHP_INI"
echo "✅ Дублікати видалено (залишено тільки в conf.d)"
echo ""

# Крок 2: Включити pdo_sqlite і sqlite3 для тестів
echo "📋 Крок 2: Включення pdo_sqlite і sqlite3 для тестів..."
sudo sed -i 's/^;extension=pdo_sqlite$/extension=pdo_sqlite/' "$PHP_INI"
sudo sed -i 's/^;extension=sqlite3$/extension=sqlite3/' "$PHP_INI"
echo "✅ SQLite включено для тестів"
echo ""

# Крок 3: Перевірити, що немає дублікатів sqlite в conf.d
echo "📋 Крок 3: Перевірка дублікатів sqlite..."
if [ -f "/etc/php83/conf.d/pdo_sqlite.ini" ]; then
    echo "⚠️  Знайдено pdo_sqlite.ini в conf.d, видаляємо..."
    sudo rm -f /etc/php83/conf.d/pdo_sqlite.ini
fi
if [ -f "/etc/php83/conf.d/sqlite3.ini" ]; then
    echo "⚠️  Знайдено sqlite3.ini в conf.d, видаляємо..."
    sudo rm -f /etc/php83/conf.d/sqlite3.ini
fi
echo "✅ Дублікати sqlite перевірено"
echo ""

# Крок 4: Налаштування Apache
echo "📋 Крок 4: Налаштування Apache для PHP 8.3..."

# Перевірити конфигурацію Apache
APACHE_CONF="/etc/httpd/conf/httpd.conf"
PHP_MODULE_CONF="/etc/httpd/conf/extra/php83-module.conf"

if [ -f "$PHP_MODULE_CONF" ]; then
    echo "✅ Конфігурація PHP для Apache знайдена: $PHP_MODULE_CONF"
    # Перевірити, чи включена в httpd.conf
    if ! grep -q "Include conf/extra/php83-module.conf" "$APACHE_CONF" 2>/dev/null; then
        echo "⚠️  Include не знайдено, додаємо вручну..."
        echo "# PHP 8.3" | sudo tee -a "$APACHE_CONF" > /dev/null
        echo "Include conf/extra/php83-module.conf" | sudo tee -a "$APACHE_CONF" > /dev/null
    fi
else
    echo "⚠️  Конфігурація не знайдена, перевірте вручну"
fi

# Перевірити LoadModule
if grep -q "LoadModule.*php.*module.*libphp83.so" "$APACHE_CONF" 2>/dev/null || grep -q "LoadModule.*php.*module.*libphp83.so" "$PHP_MODULE_CONF" 2>/dev/null; then
    echo "✅ LoadModule для libphp83.so налаштовано"
else
    echo "⚠️  LoadModule не знайдено, потрібно налаштувати вручну"
fi

echo "✅ Apache налаштовано"
echo ""

# Крок 5: Перезапуск Apache
echo "📋 Крок 5: Перезапуск Apache..."
sudo systemctl restart httpd
echo "✅ Apache перезапущено"
echo ""

# Крок 6: Фінальна перевірка
echo "📋 Крок 6: Фінальна перевірка..."
echo ""
echo "PHP версія:"
php -v 2>&1 | head -3
echo ""
echo "Модулі:"
php -m 2>&1 | grep -E "^pdo$|^pdo_pgsql$|^pgsql$|^pdo_sqlite$|^sqlite3$" || echo "⚠️  Деякі модулі не знайдені"
echo ""
echo "Конфігурація:"
php --ini 2>&1 | head -3
echo ""

echo "✅ Настройка завершена!"
echo ""
echo "🌐 Перевірте веб-версію: http://localhost/info.php"


