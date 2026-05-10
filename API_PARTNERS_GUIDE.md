# 🔌 API Документація для Партнерів (Tenants)

## 📝 Базова інформація

Кожен партнер має унікальний API ключ для доступу до своїх даних через REST API.

### Базовий URL
```
https://partners.glfbikube.local/api
```

### Аутентифікація
```
Header: X-API-Key: your-api-key-here
```

## 🔑 Отримання API ключа

### 1. Через Filament панель
- Перейти до меню "Партнери"
- Вибрати партнера
- Скопіювати API ключ

### 2. Через консоль
```bash
php artisan tinker
>>> $partner = App\Models\Partner::find(1);
>>> echo $partner->settings->api_key;
```

## 📚 Доступні эндпойнти

### 1. Отримати інформацію про поточного партнера
```bash
GET /api/partner
```

**Заголовки**:
```
X-API-Key: your-api-key
```

**Відповідь (200 OK)**:
```json
{
  "id": 1,
  "name": "Narvik Bilberging AS",
  "domain": "narvikbilberging-1.partners.glfbikube.local",
  "subscription_tier": "basic",
  "is_active": true,
  "created_at": "2025-11-20T20:49:00Z"
}
```

### 2. Отримати налаштування партнера
```bash
GET /api/partner/settings
```

**Заголовки**:
```
X-API-Key: your-api-key
```

**Відповідь (200 OK)**:
```json
{
  "id": 1,
  "partner_id": 1,
  "notification_email": "support@narvik.no",
  "sms_notifications_enabled": true,
  "email_notifications_enabled": true,
  "auto_assign_orders": true,
  "max_concurrent_orders": 10,
  "order_timeout_minutes": 30,
  "timezone": "Europe/Kyiv",
  "language": "uk",
  "features_enabled": ["geo_zones", "auto_dispatch"],
  "api_key": "sha256-hash..."
}
```

### 3. Оновити налаштування партнера
```bash
PUT /api/partner/settings
```

**Заголовки**:
```
X-API-Key: your-api-key
Content-Type: application/json
```

**Тіло запиту**:
```json
{
  "notification_email": "new-email@narvik.no",
  "max_concurrent_orders": 15,
  "timezone": "Europe/Oslo"
}
```

**Відповідь (200 OK)**:
```json
{
  "message": "Settings updated successfully",
  "settings": { ... }
}
```

### 4. Отримати всі замовлення партнера
```bash
GET /api/partner/orders
```

**Параметри запиту**:
- `page` (int) - номер сторінки, за замовчуванням 1
- `per_page` (int) - замовлень на сторінку, за замовчуванням 15
- `status` (string) - фільтр по статусу (pending, accepted, completed, cancelled)
- `sort` (string) - сортування (created_at, updated_at, status)

**Приклад**:
```bash
GET /api/partner/orders?page=1&per_page=10&status=pending&sort=-created_at
```

**Відповідь (200 OK)**:
```json
{
  "data": [
    {
      "id": 1,
      "partner_id": 1,
      "status": "pending",
      "pickup_location": "Oslo, Norway",
      "delivery_location": "Bergen, Norway",
      "created_at": "2025-12-14T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 145,
    "total_pages": 15
  }
}
```

### 5. Отримати дані про доставку зон
```bash
GET /api/partner/delivery-zones
```

**Відповідь (200 OK)**:
```json
{
  "data": [
    {
      "id": 1,
      "partner_id": 1,
      "name": "Oslo Center",
      "coordinates": {
        "lat": 59.9139,
        "lng": 10.7522
      },
      "radius_km": 10,
      "is_active": true
    }
  ]
}
```

### 6. Отримати статистику партнера
```bash
GET /api/partner/stats
```

**Параметри**:
- `period` (string) - період (today, week, month, all)

**Приклад**:
```bash
GET /api/partner/stats?period=month
```

**Відповідь (200 OK)**:
```json
{
  "total_orders": 450,
  "completed_orders": 420,
  "pending_orders": 15,
  "cancelled_orders": 15,
  "average_rating": 4.7,
  "total_earnings": 125500,
  "period": "month"
}
```

## 🔄 Вебхуки (Webhooks)

Партнер може налаштувати вебхук для отримання подій в реальному часі.

### Налаштування вебхука
```bash
PUT /api/partner/settings
```

**Тіло**:
```json
{
  "webhook_url": "https://your-domain.com/webhooks/glfbikube"
}
```

### События, що надсилаються

1. **order.created** - Нове замовлення
```json
{
  "event": "order.created",
  "data": {
    "id": 1,
    "status": "pending",
    "pickup_location": "...",
    "timestamp": "2025-12-14T10:00:00Z"
  }
}
```

2. **order.status_changed** - Статус замовлення змінився
```json
{
  "event": "order.status_changed",
  "data": {
    "id": 1,
    "old_status": "pending",
    "new_status": "accepted",
    "timestamp": "2025-12-14T10:15:00Z"
  }
}
```

3. **order.completed** - Замовлення завершено
```json
{
  "event": "order.completed",
  "data": {
    "id": 1,
    "total_amount": 500,
    "rating": 5,
    "timestamp": "2025-12-14T11:00:00Z"
  }
}
```

## ⚠️ Коди помилок

| Код | Опис |
|-----|------|
| 200 | OK - Успішний запит |
| 201 | Created - Ресурс створений |
| 400 | Bad Request - Невірні параметри |
| 401 | Unauthorized - Невірний або відсутній API ключ |
| 403 | Forbidden - Доступ заборонений |
| 404 | Not Found - Ресурс не знайдений |
| 429 | Too Many Requests - Перевищено ліміт запитів |
| 500 | Internal Server Error - Помилка сервера |

## 📊 Ліміти запитів (Rate Limiting)

- **Вільні партнери (basic)**: 100 запитів/хвилину
- **Професійні партнери (professional)**: 500 запитів/хвилину
- **Корпоративні партнери (enterprise)**: 2000 запитів/хвилину

**Заголовки у відповіді**:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1702550400
```

## 🔐 Безпека

1. **Завжди використовуйте HTTPS**
2. **Тримайте API ключ в секреті**
3. **Не передавайте API ключ в URL**
4. **Зберігайте API ключ в безпечному місці (environment variables)**

### Приклад у Python
```python
import requests

API_KEY = "your-api-key-here"
headers = {"X-API-Key": API_KEY}
response = requests.get("https://partners.glfbikube.local/api/partner", headers=headers)
print(response.json())
```

### Приклад у JavaScript
```javascript
const apiKey = "your-api-key-here";
const response = await fetch("https://partners.glfbikube.local/api/partner", {
  headers: {
    "X-API-Key": apiKey
  }
});
const data = await response.json();
console.log(data);
```

### Приклад у cURL
```bash
curl -H "X-API-Key: your-api-key-here" \
  https://partners.glfbikube.local/api/partner
```

## 🆘 Отримання допомоги

Для питань щодо API:
- Посилання на документацію: [MULTITENANCY_GUIDE.md](./MULTITENANCY_GUIDE.md)
- Email: support@glfbikube.com
- Status Page: https://status.glfbikube.com
