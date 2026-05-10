#!/bin/bash
# Скрипт для установки PHP 8.3 из AUR на Arch Linux / CachyOS

set -e

echo "🔍 Проверка доступности пакетов в AUR..."

# Предварительно установить smtp-forwarder (dma) чтобы избежать интерактивных вопросов
if ! pacman -Qi dma &>/dev/null; then
    echo "📧 Установка dma (smtp-forwarder)..."
    sudo pacman -S --noconfirm dma
fi

# Список необходимых пакетов PHP 8.3
PACKAGES=(
    "php83"
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
    "php83-redis"  # если используется Redis
)

echo "📦 Установка PHP 8.3 и расширений из AUR..."
echo "Это может занять некоторое время, так как пакеты будут скомпилированы из исходников."
echo ""

# Установка через yay с автоматическим выбором опций
# --batchinstall - автоматически устанавливать без вопросов
# --noconfirm - не спрашивать подтверждение
# --answerclean All - очистить все существующие файлы сборки
# --answerdiff None - не показывать diff
# --removemake - удалить make зависимости после установки
yay -S --batchinstall --noconfirm --answerclean All --answerdiff None --removemake "${PACKAGES[@]}"

echo ""
echo "✅ PHP 8.3 установлен!"
echo ""
echo "📝 Следующие шаги:"
echo ""
echo "1. Обновите конфигурацию Apache для использования PHP 8.3:"
echo "   sudo nano /etc/httpd/conf/httpd.conf"
echo "   или"
echo "   sudo nano /etc/httpd/conf/extra/php8_module.conf"
echo ""
echo "   Найдите строку:"
echo "   LoadModule php_module modules/libphp.so"
echo ""
echo "   Замените на:"
echo "   LoadModule php_module modules/libphp83.so"
echo ""
echo "2. Перезапустите Apache:"
echo "   sudo systemctl restart httpd"
echo ""
echo "3. Проверьте версию PHP:"
echo "   php -v"
echo ""
echo "4. Проверьте расширения:"
echo "   php -m | grep pdo_pgsql"
echo "   php -m | grep -i sqlite  # Не должно быть вывода"
echo ""
echo "5. Обновите зависимости Composer:"
echo "   cd '/home/dima/Стільниця/glfbikube (1-я копия)'"
echo "   composer update --no-interaction"
echo "   php artisan optimize:clear"


