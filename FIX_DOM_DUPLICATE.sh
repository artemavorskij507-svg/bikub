#!/bin/bash
set -e

echo "🔧 Виправлення дублікату DOM модуля"
echo ""

echo "📋 Крок 1: Видалення дублікату 16-dom.ini..."
sudo rm -f /etc/php83/conf.d/16-dom.ini
echo "✅ 16-dom.ini видалено (залишено 20-dom.ini)"
echo ""

echo "📋 Крок 2: Перезапуск Apache..."
sudo systemctl restart httpd
echo "✅ Apache перезапущено"
echo ""

echo "📋 Крок 3: Перевірка DOM модуля..."
php -v 2>&1 | head -1
php -m 2>&1 | grep -E "^dom$" || echo "⚠️  DOM не завантажено"
echo ""

echo "✅ Готово! DOM тепер завантажується один раз."

