#!/bin/bash
# Встановлення phar, openssl та composer

set -e

echo "🔧 Встановлення phar, openssl та composer"
echo ""

# 1. Перевірити phar та openssl
echo "📋 1. Перевірка phar та openssl..."
if ! php -m 2>&1 | grep -q "^phar$"; then
    echo "⚠️  phar не завантажено"
    # Перевірити, чи є пакети
    if pacman -Q php83-phar &>/dev/null; then
        echo "✅ php83-phar встановлено, перевіряємо конфігурацію..."
    else
        echo "⚠️  php83-phar не встановлено, потрібно встановити"
        echo "Виконайте: yay -S php83-phar --noconfirm"
    fi
else
    echo "✅ phar вже завантажено"
fi

if ! php -m 2>&1 | grep -q "^openssl$"; then
    echo "⚠️  openssl не завантажено"
    if pacman -Q php83-openssl &>/dev/null; then
        echo "✅ php83-openssl встановлено, перевіряємо конфігурацію..."
    else
        echo "⚠️  php83-openssl не встановлено, потрібно встановити"
        echo "Виконайте: yay -S php83-openssl --noconfirm"
    fi
else
    echo "✅ openssl вже завантажено"
fi
echo ""

# 2. Встановити composer
echo "📋 2. Встановлення composer..."
echo "Після встановлення phar та openssl виконайте:"
echo "  curl -sS https://getcomposer.org/installer | php"
echo "  sudo mv composer.phar /usr/local/bin/composer"
echo ""

echo "✅ Інструкції готові!"

