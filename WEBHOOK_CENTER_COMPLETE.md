# Webhook Center & Signature Validation - Итоговый отчет

**Дата:** 15 декабря 2025 г.  
**Статус:** ✓ ГОТОВО К ПРОДАКШЕНУ

---

## 1. Реализованные компоненты

### 1.1 Централизованный Webhook Controller
**Файл:** `app/Http/Controllers/WebhookController.php`

Принимает POST запросы на `/api/webhooks/{provider}` с функциональностью:
- Парсирование webhook payload (JSON)
- Извлечение метаданных (event_type, external_id, request_id)
- **Проверка подписи перед сохранением** (критическая функция безопасности)
- Логирование failed webhooks (статус 401) с сообщением об ошибке
- Диспетчеризация ProcessWebhook job для валидных вебхуков
- Аудит логирование событий `webhook_received` и `webhook_signature_invalid`

**Возвращаемые коды:**
- `202 Accepted` - webhook принят и поставлен в очередь
- `401 Unauthorized` - проверка подписи не пройдена (сохраняется как failed)

### 1.2 WebhookSignatureValidator сервис
**Файл:** `app/Services/WebhookSignatureValidator.php`

Реализует проверку подписей для провайдеров:

#### Stripe (Stripe-Signature header)
- Формат: `t=timestamp,v1=signature`
- Алгоритм: `HMAC-SHA256(timestamp.payload, secret)`
- Проверка timestamp: ±300 секунд (5 минут)
- Защита от timing атак: `hash_equals()` для constant-time сравнения

#### n8n (X-N8N-Signature и X-N8N-Timestamp headers)
- Формат: X-N8N-Signature (хеш), X-N8N-Timestamp (timestamp)
- Алгоритм: `HMAC-SHA256(timestamp.payload, secret)`
- Проверка timestamp: ±300 секунд
- Защита от timing атак: `hash_equals()`

#### Fallback
- Провайдеры без сигнатуры (internal, sms): возвращает `true`

**Конфигурация:** `config/webhooks.php`
```php
'providers' => [
    'stripe' => ['webhook_secret' => env('STRIPE_WEBHOOK_SECRET')],
    'n8n' => ['webhook_secret' => env('N8N_WEBHOOK_SECRET')],
]
```

### 1.3 WebhookLog модель и таблица
**Файл:** `app/Models/WebhookLog.php`

Структура таблицы:
```
id                  bigint PRIMARY KEY AUTO_INCREMENT
provider            varchar (stripe, n8n, sms, internal)
event_type          varchar (charge.succeeded, workflow.executed, etc)
external_id         varchar (evt_xxx, exec_xxx)
status              varchar (received, processed, failed)
http_status         integer (response code from processing)
payload             json (full webhook data)
error_message       text (error details if failed)
request_id          varchar (unique request tracking ID)
received_at         timestamp (when webhook was accepted)
processed_at        timestamp (when job completed processing)
attempt             integer (retry count)
order_id            bigint (link to orders table)
payment_id          bigint (link to payments table)
created_at          timestamp
updated_at          timestamp
```

### 1.4 ProcessWebhook Job
**Файл:** `app/Jobs/ProcessWebhook.php`

Асинхронная обработка webhook:
- Загружает WebhookLog из DB
- Для `provider='n8n'`: перенаправляет payload на N8N_WEBHOOK_URL
- Обновляет статус: `received` → `processed` или `failed`
- Логирует события аудита
- При ошибке: выбрасывает исключение для retry механизма
- Queue: `webhooks` (специальная очередь для вебхуков)

### 1.5 WebhookLogResource (Filament UI)
**Файл:** `app/Filament/Resources/WebhookLogResource.php`

Интерфейс управления вебхуками в админ-панели:

**Список (ListWebhookLogs page):**
- Таблица с колонками: created_at, provider, event_type, status (color badge), external_id, request_id
- Фильтры: provider (dropdown), status (dropdown), date range (from/to)
- Статус badge colors: 
  - 🟢 green = processed
  - 🟠 orange = received
  - 🔴 red = failed

**Просмотр (ViewWebhookLog):**
- Read-only форма со всеми полями
- Payload выводится как formatted JSON
- Error message (если есть)

**Действия (Actions):**
- **Retry** (видна только для failed webhooks):
  - Сбрасывает статус на `received`
  - Инкрементирует `attempt`
  - Диспетчерит новый ProcessWebhook job
  - Логирует `webhook_retry` аудит event

### 1.6 Аудит логирование
**Eventos:**
- `webhook_received` - webhook принят с валидной подписью
- `webhook_signature_invalid` - ошибка проверки подписи (401 response)
- `webhook_processed` - успешная обработка в job
- `webhook_failed` - ошибка при обработке
- `webhook_retry` - ручной retry через UI

**Логирование метаданных:**
- actor_user_id (если retry через UI)
- ip_address (originating request IP)
- request_id (для корреляции)
- provider, event_type, external_id

---

## 2. Интеграция в маршруты

**Файл:** `routes/api.php`

```php
// Central webhook endpoint (public, no auth required)
Route::post('/api/webhooks/{provider}', [WebhookController::class, 'receive']);
```

Доступно для:
- POST /api/webhooks/stripe
- POST /api/webhooks/n8n
- POST /api/webhooks/sms
- POST /api/webhooks/internal
- Итд...

---

## 3. Инфраструктура

### .env переменные
```env
STRIPE_WEBHOOK_SECRET=whsec_test_stripe_12345
N8N_WEBHOOK_SECRET=n8n_test_secret_67890
QUEUE_CONNECTION=database  # или redis
```

### Queue обработка
- Очередь `webhooks` для асинхронной обработки
- Job вернется на retry при ошибке
- Хранение метаданных в `WebhookLog`

### Безопасность
✓ HMAC-SHA256 подписи (Stripe + n8n)  
✓ Проверка timestamp ±300s (защита от replay атак)  
✓ Constant-time сравнение (защита от timing атак)  
✓ Invalid webhooks логируются и отклоняются (401)  
✓ Аудит всех операций с webhooks  
✓ Public endpoint, но защищен подписью  

---

## 4. Тестирование

### Пройденные тесты

✓ **HMAC Validation Test** (`scripts/test_hmac_signatures.php`)
- Stripe HMAC-SHA256: ✓ Valid
- n8n HMAC-SHA256: ✓ Valid
- Invalid signatures rejected: ✓
- Timestamp validation (±300s): ✓
- Old timestamps (>300s) rejected: ✓

✓ **E2E Webhook Flow Test** (`scripts/test_webhook_flow_e2e.php`)
- Valid Stripe webhook: ✓ Saved as `received`
- Invalid n8n webhook: ✓ Saved as `failed` with 401
- Expired timestamp: ✓ Rejected as invalid
- Database operations: ✓ Working
- Status transitions: ✓ Working

### Скрипты тестирования
```bash
php scripts/test_hmac_signatures.php        # HMAC validation
php scripts/test_webhook_flow_e2e.php       # Full workflow
php scripts/verify_webhook_validator.php    # Service verification
php scripts/setup_webhook_table.php         # Table setup
php scripts/inspect_webhook_table.php       # Table structure
```

---

## 5. Production Checklist

- ✓ WebhookController прошел syntax check
- ✓ WebhookSignatureValidator прошел syntax check
- ✓ ProcessWebhook Job прошел syntax check
- ✓ WebhookLogResource прошел syntax check
- ✓ HMAC validation logic verified
- ✓ E2E flow tested and working
- ✓ Database table created and indexed
- ✓ Migration files prepared
- ✓ Audit logging configured
- ✓ Config structure verified
- ✓ Routes configured

---

## 6. Дальнейшие улучшения (опционально)

### Phase 2: Correlation Heuristics
- Автоматическое извлечение `order_id` и `payment_id` из payload
- Связь вебхуков с заказами/платежами

### Phase 3: UI Enhancement
- Ссылки на связанные Order/Payment в Filament
- Ссылка на related AuditLog записи
- Timeline просмотр

### Phase 4: Advanced Features
- Rate limiting per provider
- Encryption of sensitive payloads
- Webhook signature key rotation
- Slack/email alerts на failed webhooks

---

## 7. Развертывание

### Инструкции
1. Убедиться что env переменные установлены:
   ```bash
   STRIPE_WEBHOOK_SECRET=your_secret
   N8N_WEBHOOK_SECRET=your_secret
   ```

2. Запустить setup скрипт:
   ```bash
   php scripts/setup_webhook_table.php
   ```

3. Настроить queue worker:
   ```bash
   php artisan queue:work --queue=webhooks
   ```

4. Webhook готов к приему запросов на:
   - POST /api/webhooks/stripe
   - POST /api/webhooks/n8n
   - Итд...

---

**Webhook Center успешно реализован и готов к боевому использованию! 🚀**
