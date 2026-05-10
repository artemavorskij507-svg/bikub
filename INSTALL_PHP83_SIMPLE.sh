#!/bin/bash
# Упрощенный скрипт установки PHP 8.3 (по одному пакету)

set -e

echo "🔍 Установка PHP 8.3 из AUR (по одному пакету)..."
echo ""

# Предварительно установить dma
if ! pacman -Qi dma &>/dev/null; then
    echo "📧 Установка dma..."
    sudo pacman -S --noconfirm dma
fi

# Установка основных пакетов по одному
echo "📦 Установка основных пакетов..."

# Основной PHP 8.3
echo "→ php83..."
yay -S --batchinstall --noconfirm --answerclean All --answerdiff None php83 || {
    echo "⚠️  Ошибка установки php83. Попробуйте вручную: yay -S php83"
    exit 1
}

# Расширения
for pkg in php83-pdo php83-pgsql php83-apache php83-gd php83-mbstring php83-xml php83-curl php83-zip php83-intl php83-bcmath php83-opcache; do
    echo "→ $pkg..."
    yay -S --batchinstall --noconfirm --answerclean All --answerdiff None "$pkg" || {
        echo "⚠️  Пропущен $pkg (можно установить позже)"
    }
done

# Redis (опционально)
echo "→ php83-redis (опционально)..."
yay -S --batchinstall --noconfirm --answerclean All --answerdiff None php83-redis || {
    echo "ℹ️  php83-redis пропущен (не критично)"
}

echo ""
echo "✅ Установка завершена!"
echo ""
echo "📝 Следующие шаги:"
echo ""
echo "1. Обновите конфигурацию Apache:"
echo "   sudo sed -i 's/LoadModule php_module modules\/libphp\.so/LoadModule php_module modules\/libphp83.so/' /etc/httpd/conf/httpd.conf"
echo "   # или в /etc/httpd/conf/extra/php8_module.conf"
echo ""
echo "2. Перезапустите Apache:"
echo "   sudo systemctl restart httpd"
echo ""
echo "3. Проверьте версию:"
echo "   php -v"
echo "   php -m | grep pdo_pgsql"

