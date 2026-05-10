#!/bin/bash
# Виправлення конфлікту при встановленні php83-cli

set -e

echo "🔧 Виправлення конфлікту php83-cli"
echo ""

# Крок 1: Видалити старий wrapper
echo "📋 Крок 1: Видалення старого wrapper-скрипта..."
sudo rm -f /usr/bin/php83
echo "✅ Старий wrapper видалено"
echo ""

# Крок 2: Встановити пакет
echo "📋 Крок 2: Встановлення php83-cli..."
if [ -f "/home/dima/.cache/yay/php83/php83-cli-8.3.21-1-x86_64.pkg.tar.zst" ]; then
    sudo pacman -U /home/dima/.cache/yay/php83/php83-cli-8.3.21-1-x86_64.pkg.tar.zst --noconfirm
    echo "✅ php83-cli встановлено"
else
    echo "⚠️  Пакет не знайдено, спробуйте встановити через yay:"
    echo "   yay -S php83-cli --noconfirm"
    exit 1
fi
echo ""

# Крок 3: Створити symlink
echo "📋 Крок 3: Створення symlink для команди php..."
sudo ln -sf /usr/bin/php83 /usr/bin/php
echo "✅ Symlink створено: /usr/bin/php -> /usr/bin/php83"
echo ""

# Крок 4: Перевірка
echo "📋 Крок 4: Перевірка встановлення..."
php -v
echo ""
php -m | grep -E "(pdo_pgsql|pdo_sqlite)" && echo "✅ Розширення працюють" || echo "⚠️  Розширення не знайдено"

echo ""
echo "✅ Готово!"


