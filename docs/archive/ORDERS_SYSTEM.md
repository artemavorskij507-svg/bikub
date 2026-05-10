# 📦 Система заказов GLF BiKube

**Дата создания:** 27 октября 2025  
**Статус:** ✅ Улучшена и готова к использованию

---

## ✅ Что реализовано

### 🎯 Основной функционал
1. **Создание заказов** - с множественными товарами/услугами
2. **Расчет стоимости** - автоматически на основе pricing rules
3. **Привязка к геозонам** - определение зоны доставки
4. **Оценка времени** - расчет примерного времени доставки
5. **Управление статусами** - отслеживание заказа

---

## 🔧 Компоненты системы

### 1. OrderPricingService (новый)
**Файл:** `app/Services/OrderPricingService.php`

**Функционал:**
- ✅ Расчет стоимости заказа
- ✅ Применение pricing rules
- ✅ Учет геозон и расстояния
- ✅ Модификаторы цены (по расстоянию, по зоне)
- ✅ Расчет времени доставки
- ✅ Определение geo-зоны по координатам

**Методы:**
```php
calculateOrderPrice(array $orderData): array
getPricingRuleForService(int $serviceTypeId, ?array $location): ?PricingRule
applyGeoZoneModifier(float $basePrice, ?array $location): float
findGeoZoneForLocation(float $lat, float $lng): ?GeoZone
calculateEstimatedTime(?array $location): int
getAvailableTimeSlots(string $date): array
```

### 2. OrderController (улучшен)
**Файл:** `app/Http/Controllers/Api/OrderController.php`

**Новый функционал:**
- ✅ Поддержка множественных товаров (`items[]`)
- ✅ Автоматический расчет цены через OrderPricingService
- ✅ Определение geo-зоны при создании заказа
- ✅ Метод `updateStatus()` - обновление статуса заказа
- ✅ Автоматическая установка временных меток (started_at, completed_at)
- ✅ Метаданные о времени доставки и geo-зоне

### 3. Models

#### Order Model
**Файл:** `app/Models/Order.php`

**Связи:**
- `user()` - пользователь-заказчик
- `assignedUser()` - назначенный исполнитель
- `orderItems()` - товары в заказе

**Scopes:**
- `byStatus($status)`
- `byPriority($priority)`
- `assignedTo($userId)`

#### OrderItem Model
**Файл:** `app/Models/OrderItem.php`

**Связи:**
- `order()` - заказ
- `serviceType()` - тип услуги
- `pricingRule()` - примененное правило цены

**Методы:**
- `calculateTotalPrice()` - расчет итоговой цены

#### GeoZone Model
**Файл:** `app/Models/GeoZone.php`

**Методы:**
- `containsPoint($lat, $lng)` - проверка нахождения точки в зоне
- `distanceTo($lat, $lng)` - расстояние до точки в метрах

---

## 🔌 API Endpoints

### Создание заказа
```
POST /api/v1/orders
```

**Body:**
```json
{
  "user_id": 1,
  "priority": "normal",
  "status": "pending",
  "items": [
    {
      "service_type_id": 1,
      "quantity": 1,
      "name": "Доставка лекарств"
    },
    {
      "service_type_id": 5,
      "quantity": 2,
      "name": "Поручения в городе"
    }
  ],
  "location": {
    "lat": 68.4384,
    "lng": 17.4278,
    "address": "Kongens gate 1, Narvik"
  },
  "scheduled_at": "2025-10-28T10:00:00Z",
  "notes": "Оставить у двери"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20251027-ABC123",
    "user_id": 1,
    "status": "pending",
    "priority": "normal",
    "total_amount": "350.00",
    "currency": "NOK",
    "payment_status": "pending",
    "location": {
      "lat": 68.4384,
      "lng": 17.4278,
      "address": "Kongens gate 1, Narvik"
    },
    "metadata": {
      "estimated_time_minutes": 45,
      "geo_zone": "narvik-city-center"
    },
    "orderItems": [...]
  },
  "message": "Order created successfully"
}
```

### Получение заказа
```
GET /api/v1/orders/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20251027-ABC123",
    "user_id": 1,
    "status": "pending",
    "total_amount": "350.00",
    "orderItems": [
      {
        "id": 1,
        "service_type_id": 1,
        "name": "Доставка лекарств",
        "quantity": 1,
        "unit_price": "200.00",
        "total_price": "200.00"
      }
    ]
  }
}
```

### Обновление статуса заказа
```
PATCH /api/v1/orders/{id}/status
```

**Body:**
```json
{
  "status": "in_progress"
}
```

**Доступные статусы:**
- `pending` - Ожидает
- `confirmed` - Подтвержден
- `in_progress` - В работе
- `completed` - Завершен
- `cancelled` - Отменен

### Список заказов
```
GET /api/v1/orders?status=completed&user_id=1
```

**Query параметры:**
- `status` - фильтр по статусу
- `user_id` - фильтр по пользователю

---

## 💰 Система ценообразования

### Как работает расчет цены

1. **Базовая цена** - из PricingRule для типа услуги
2. **Модификатор по геозоне** - доплата за удаленность
3. **Модификатор по расстоянию** - цена за км
4. **Итоговая цена** = Базовая цена + модификаторы

### Примеры

**Заказ в центре Нарвика:**
- Базовая цена: 200 NOK
- Geo zone: city-center (0% модификатор)
- Итого: 200 NOK

**Заказ в пригороде:**
- Базовая цена: 200 NOK
- Geo zone: suburbs (+15% модификатор)
- Дистанция: 5 км (20 NOK за км)
- Итого: 200 + 30 + 100 = 330 NOK

---

## 🗺️ Геозоны

Текущие геозоны в базе (6):
1. **Нарвик центр** (city-center)
2. **Нарвик окраины** (suburbs)
3. **Промзона Нарвика**
4. **Районы Нарвика**
5. **Трасса Е6**
6. **Нарвик аэропорт**

### Проверка координат

Система автоматически определяет в какой геозоне находится адрес доставки используя формулу Haversine для расчета расстояния.

---

## ⏱️ Расчет времени доставки

### Логика

```php
базовое время (15 мин) 
+ расстояние (>5 км = +15 мин)
+ приоритет (high = +10 мин, urgent = +20 мин)
= итоговое время
```

### Примеры

- **Близко, нормальный приоритет:** 15-30 мин
- **Близко, высокий приоритет:** 25-40 мин
- **Далеко, нормальный приоритет:** 30-45 мин
- **Далеко, срочно:** 50-60 мин

---

## 📊 Статистика заказов

### Улучшения в OrderController

```php
index() // Список заказов
store() // Создание с автоматическим расчетом цены
show() // Детали заказа
updateStatus() // Обновление статуса
```

### Новые возможности

1. ✅ **Множественные товары** в одном заказе
2. ✅ **Автоматический расчет** стоимости
3. ✅ **Определение geo-зоны** по координатам
4. ✅ **Оценка времени** доставки
5. ✅ **Метаданные** о заказе в JSON
6. ✅ **Timestamps** для статусов

---

## 🧪 Тестирование

### Через Tinker:

```bash
php artisan tinker

# Создать тестовый заказ
$order = App\Models\Order::create([
    'order_number' => 'ORD-TEST-001',
    'user_id' => 1,
    'status' => 'pending',
    'total_amount' => 250.00,
    'currency' => 'NOK'
]);

# Проверить расчет цены
$service = new App\Services\OrderPricingService();
$pricing = $service->calculateOrderPrice([
    'items' => [
        ['service_type_id' => 1, 'quantity' => 1]
    ],
    'location' => ['lat' => 68.4384, 'lng' => 17.4278]
]);
```

---

## 📝 Следующие шаги

### Приоритет 1: Интеграции
1. ⚠️ Payment gateway (Vipps, Stripe)
2. ⚠️ Email/SMS уведомления
3. ⚠️ Push notifications
4. ⚠️ Tracking статуса в real-time

### Приоритет 2: Дополнительные фичи
1. 📱 Mobile app для исполнителей
2. 🗺️ Карта маршрутов
3. 📊 Analytics и reporting
4. 💬 Чат с заказчиком
5. ⭐ Rating система

---

## ✅ Чек-лист готовности

- [x] Создание заказов
- [x] Расчет стоимости
- [x] Определение геозон
- [x] Оценка времени
- [x] Управление статусами
- [x] Метод updateStatus
- [x] Метаданные о заказе
- [ ] Payment integration
- [ ] Notifications
- [ ] Tracking
- [ ] Reports

---

*Система заказов готова к использованию! 🚀*

