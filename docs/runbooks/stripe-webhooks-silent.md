# Runbook: Stripe вебхуки молчат

## Симптомы
- Платежи обрабатываются через Stripe, но события не поступают
- Заказы остаются в статусе `pending` после успешной оплаты
- Нет записей в логах о получении webhook событий

## Диагностика

### 1. Проверить конфигурацию Stripe
```bash
cd /var/www/glfbikube
php artisan tinker --execute="
    echo 'Stripe Key: ' . (config('services.stripe.key') ? 'Set' : 'Missing') . "\n";
    echo 'Stripe Secret: ' . (config('services.stripe.secret') ? 'Set' : 'Missing') . "\n";
    echo 'Stripe Webhook Secret: ' . (config('services.stripe.webhook_secret') ? 'Set' : 'Missing') . "\n";
"
```

### 2. Проверить логи webhook контроллера
```bash
tail -n 100 /var/www/glfbikube/storage/logs/laravel.log | grep -i "stripe\|webhook"
```

### 3. Проверить доступность webhook endpoint
```bash
# Проверить, что маршрут существует
php artisan route:list | grep stripe

# Проверить доступность извне (должен возвращать 200 для POST с правильной подписью)
curl -X POST http://localhost:2244/api/v1/stripe/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

### 4. Проверить настройки в Stripe Dashboard
- Открыть Stripe Dashboard → Developers → Webhooks
- Проверить активные endpoints
- Проверить последние события и их статус (success/failed)

## Решение

### Вариант 1: Переотправка событий из Stripe
1. Открыть Stripe Dashboard → Developers → Events
2. Найти нужные события (например, `payment_intent.succeeded`)
3. Нажать "Send test webhook" для проверки
4. Если тестовый webhook работает, использовать "Replay event" для пропущенных событий

### Вариант 2: Проверка и обновление webhook secret
```bash
# Получить webhook secret из Stripe Dashboard
# Добавить в .env:
# STRIPE_WEBHOOK_SECRET=whsec_...

# Очистить конфигурацию
php artisan config:clear
php artisan config:cache
```

### Вариант 3: Проверка CSRF защиты
```bash
# Убедиться, что webhook маршрут исключен из CSRF
grep -A 10 "stripe/webhook" app/Http/Middleware/VerifyCsrfToken.php

# Должно быть:
# protected $except = [
#     'api/v1/stripe/webhook',
# ];
```

### Вариант 4: Локальное тестирование webhook
```bash
# Использовать Stripe CLI для локального тестирования
stripe listen --forward-to http://localhost:2244/api/v1/stripe/webhook

# В другом терминале отправить тестовое событие
stripe trigger payment_intent.succeeded
```

### Вариант 5: Проверка обработчика webhook
```bash
# Проверить, что контроллер существует и корректен
cat app/Http/Controllers/StripeWebhookController.php

# Проверить логи при обработке
tail -f /var/www/glfbikube/storage/logs/laravel.log
```

## Восстановление

1. **Обновить webhook secret в .env:**
   ```bash
   # Получить из Stripe Dashboard → Webhooks → Click endpoint → Signing secret
   nano .env
   # Добавить: STRIPE_WEBHOOK_SECRET=whsec_...
   ```

2. **Перезапустить приложение:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   php artisan queue:restart  # если используется очередь
   ```

3. **Отправить тестовый webhook:**
   - В Stripe Dashboard → Webhooks → Send test webhook
   - Выбрать событие: `payment_intent.succeeded`
   - Проверить логи на наличие записей

4. **Проверить обработку:**
   ```bash
   php artisan tinker --execute="
       \$order = \App\Models\Order::where('payment_status', 'pending')
           ->where('status', '!=', 'cancelled')
           ->first();
       if (\$order) {
           echo 'Order: ' . \$order->order_number . "\n";
           echo 'Payment Status: ' . \$order->payment_status . "\n";
       }
   "
   ```

## Проверка статуса платежей

```bash
# Найти заказы, которые оплачены в Stripe, но не обновлены локально
php artisan tinker --execute="
    \$pending = \App\Models\Order::where('payment_status', 'pending')
        ->where('total_amount', '>', 0)
        ->where('created_at', '>', now()->subDays(7))
        ->get(['id', 'order_number', 'total_amount', 'created_at']);
    echo 'Pending payments (last 7 days): ' . \$pending->count() . "\n";
"
```

## Мониторинг

- Настроить алерт на Stripe Dashboard для failed webhooks
- Регулярно проверять логи на ошибки обработки
- Мониторить количество pending платежей

## Профилактика

- Использовать Stripe CLI для локального тестирования
- Регулярно проверять webhook endpoints в Stripe Dashboard
- Включить логирование всех webhook событий
- Настроить retry механизм для failed webhooks


