#!/bin/bash
# Полная миграция с PHP 8.4 на PHP 8.3

set -e

echo "🚀 Начало миграции с PHP 8.4.14 на PHP 8.3"
echo ""

# Шаг 1: Удаление PHP 8.4
echo "📋 Шаг 1: Удаление PHP 8.4.14..."
sudo pacman -Rns php php-apache php-pgsql php-sqlite --noconfirm

echo ""
echo "✅ PHP 8.4.14 удален!"
echo ""

# Шаг 2: Установка dma (smtp-forwarder) для избежания интерактивных вопросов
if ! pacman -Qi dma &>/dev/null; then
    echo "📧 Установка dma (smtp-forwarder)..."
    sudo pacman -S --noconfirm dma
fi

# Шаг 3: Установка PHP 8.3 из AUR
echo "📦 Шаг 2: Установка PHP 8.3 и расширений из AUR..."
echo "Это может занять некоторое время, так как пакеты будут скомпилированы из исходников."
echo ""

PACKAGES=(
    "php83"
    "php83-pdo"
    "php83-pgsql"
    "php83-apache"
    "php83-gd"
    "php83-mbstring"
    "php83-xml"
    "php83-curl"
    "php83-zip"
    "php83-intl"
    "php83-bcmath"
    "php83-opcache"
)

# Установка через yay с автоматическим выбором опций
yay -S --batchinstall --noconfirm --answerclean All --answerdiff None --removemake "${PACKAGES[@]}"

echo ""
echo "✅ PHP 8.3 установлен!"
echo ""

# Шаг 4: Настройка Apache
echo "📋 Шаг 3: Настройка Apache для PHP 8.3..."

# Проверяем конфигурацию Apache
APACHE_CONF="/etc/httpd/conf/httpd.conf"
PHP_MODULE_CONF="/etc/httpd/conf/extra/php_module.conf"

if [ -f "$PHP_MODULE_CONF" ]; then
    echo "Обновление $PHP_MODULE_CONF..."
    sudo sed -i 's/LoadModule php_module modules\/libphp\.so/LoadModule php_module modules\/libphp83.so/' "$PHP_MODULE_CONF" || true
    sudo sed -i 's/LoadModule php_module modules\/libphp8\.4\.so/LoadModule php_module modules\/libphp83.so/' "$PHP_MODULE_CONF" || true
fi

# Проверяем основной конфиг
if grep -q "LoadModule php_module" "$APACHE_CONF" 2>/dev/null; then
    echo "Обновление $APACHE_CONF..."
    sudo sed -i 's/LoadModule php_module modules\/libphp\.so/LoadModule php_module modules\/libphp83.so/' "$APACHE_CONF" || true
    sudo sed -i 's/LoadModule php_module modules\/libphp8\.4\.so/LoadModule php_module modules\/libphp83.so/' "$APACHE_CONF" || true
fi

# Шаг 5: Настройка php.ini
echo "📋 Шаг 4: Настройка php.ini..."

PHP_INI="/etc/php83/php.ini"
if [ ! -f "$PHP_INI" ]; then
    PHP_INI="/etc/php/php.ini"
fi

if [ -f "$PHP_INI" ]; then
    # Включаем pdo_pgsql
    sudo sed -i 's/^;extension=pdo_pgsql/extension=pdo_pgsql/' "$PHP_INI" || true
    sudo sed -i 's/^;extension=pgsql/extension=pgsql/' "$PHP_INI" || true
    
    # Отключаем sqlite
    sudo sed -i 's/^extension=pdo_sqlite/;extension=pdo_sqlite/' "$PHP_INI" || true
    sudo sed -i 's/^extension=sqlite3/;extension=sqlite3/' "$PHP_INI" || true
    
    echo "✅ php.ini обновлен"
else
    echo "⚠️  php.ini не найден по стандартному пути"
fi

# Шаг 6: Перезапуск Apache
echo "📋 Шаг 5: Перезапуск Apache..."
sudo systemctl restart httpd

echo ""
echo "✅ Миграция завершена!"
echo ""
echo "📝 Финальная проверка:"
echo "   php -v"
echo "   php -m | grep pdo_pgsql"
echo ""
echo "🌐 Проверьте веб-версию: http://localhost/info.php"

