#!/bin/bash
set -e

echo "🔄 Перезапуск Dev серверів GLF Bikube..."
echo ""

echo "📋 Крок 1: Зупинка поточних серверів..."
pkill -f 'php artisan serve' || echo "  ℹ️  Laravel сервер не запущено"
pkill -f 'npm run dev' || echo "  ℹ️  Vite сервер не запущено"
sleep 2
echo "✅ Сервери зупинено"
echo ""

echo "📋 Крок 2: Очищення кешів Laravel..."
php artisan optimize:clear > /dev/null 2>&1
echo "✅ Кеші очищено"
echo ""

echo "📋 Крок 3: Запуск Laravel Dev Server..."
nohup php artisan serve --host=0.0.0.0 --port=2244 > /tmp/laravel-serve.log 2>&1 &
sleep 3
if curl -s http://localhost:2244/api/v1/health > /dev/null 2>&1; then
    echo "✅ Laravel запущено на http://localhost:2244"
else
    echo "⚠️  Laravel запущено, но не отвечает (перевір логи: tail -f /tmp/laravel-serve.log)"
fi
echo ""

echo "📋 Крок 4: Запуск Vite Dev Server..."
cd "/home/dima/Стільниця/glfbikube (1-я копия)"
nohup npm run dev > /tmp/vite.log 2>&1 &
sleep 3
echo "✅ Vite запущено (перевір логи: tail -f /tmp/vite.log)"
echo ""

echo "🎉 Dev сервери перезапущено!"
echo ""
echo "🌐 Доступні URL:"
echo "  📱 Публічний сайт: http://localhost:2244"
echo "  🔐 Адмін панель: http://localhost:2244/admin"
echo "  ⚡ Vite Dev Server: http://localhost:5174 (або інший порт)"
echo ""
echo "📋 Логи:"
echo "  - Laravel: tail -f /tmp/laravel.log"
echo "  - Vite: tail -f /tmp/vite.log"

