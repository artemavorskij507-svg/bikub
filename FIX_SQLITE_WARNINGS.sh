#!/bin/bash
# Виправлення попереджень про SQLite та DOM

set -e

echo "🔧 Виправлення попереджень про SQLite та DOM"
echo ""

# 1. Видалити SQLite з conf.d (якщо є)
echo "📋 1. Видалення SQLite з conf.d..."
if ls /etc/php83/conf.d/*sqlite*.ini 2>/dev/null; then
    sudo rm -f /etc/php83/conf.d/*sqlite*.ini
    echo "✅ SQLite файли видалено з conf.d"
else
    echo "✅ SQLite файлів немає в conf.d"
fi
echo ""

# 2. Перевірити чи є DOM в conf.d
echo "📋 2. Перевірка DOM/XML..."
if ! php -m 2>&1 | grep -q "^dom$"; then
    echo "⚠️  DOM не завантажено, перевіряємо конфігурацію..."
    if [ -f "/etc/php83/conf.d/15-xml.ini" ]; then
        echo "✅ XML конфігурація знайдена"
        cat /etc/php83/conf.d/15-xml.ini
    else
        echo "⚠️  XML конфігурація не знайдена"
    fi
else
    echo "✅ DOM вже завантажено"
fi
echo ""

# 3. Перезапуск Apache
echo "📋 3. Перезапуск Apache..."
sudo systemctl restart httpd
echo "✅ Apache перезапущено"
echo ""

# 4. Перевірка
echo "📋 4. Фінальна перевірка..."
php -v 2>&1 | head -3
echo ""
php -m 2>&1 | grep -E "sqlite" && echo "⚠️  SQLite все ще завантажено!" || echo "✅ SQLite не завантажено"
php -m 2>&1 | grep -E "^dom$|^xml$" && echo "✅ DOM/XML працюють" || echo "⚠️  DOM/XML не знайдено"
echo ""

echo "✅ Готово!"

