#!/bin/bash
# Виправлення tokenizer та встановлення composer

set -e

echo "🔧 Виправлення tokenizer та встановлення composer"
echo ""

# 1. Видалити конфліктний файл та встановити tokenizer
echo "📋 1. Виправлення tokenizer..."
echo "Виконайте через su:"
echo "  su -"
echo "  rm -f /etc/php83/conf.d/20-tokenizer.ini"
echo "  pacman -U /home/dima/.cache/yay/php83/php83-tokenizer-8.3.21-1-x86_64.pkg.tar.zst"
echo "  systemctl restart httpd"
echo "  exit"
echo ""

# 2. Встановити composer
echo "📋 2. Встановлення composer..."
echo "Виконайте через su:"
echo "  su -"
echo "  pacman -S composer"
echo "  exit"
echo ""

# 3. Перевірка
echo "📋 3. Після встановлення перевірте:"
echo "  php -m | grep -E '^tokenizer$'"
echo "  composer --version"
echo ""

echo "✅ Інструкції готові!"

