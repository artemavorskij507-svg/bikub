# 🚀 Модуль доставки GLF Deliveri

## 📋 Опис

Модуль доставки GLF Deliveri - це повнофункціональна система управління доставкою, інтегрована в існуючу екосистему GLF BiKube. Модуль підтримує три типи доставки: продукти (Grocery), крупногабарит (Bulky) та готова їжа (Food).

## 🏗️ Архітектура

### Монолітна архітектура
- **Без мікросервісів** - все в одному Laravel додатку
- **Polymorphic relationships** - для різних типів доставки
- **Domain-Driven Design** - чітке розділення відповідальності

### Інтеграція з існуючою системою
- Використовує існуючі **GeoZone** (6 геозон Нарвіка)
- Інтегрується з **PricingRule** для розрахунку тарифів
- Використовує **Order** модель як базову
- Підтримує **Partner** та **Store** моделі

## 📦 Структура модуля

### Моделі

1. **DeliveryOrder** (`app/Models/Delivery/DeliveryOrder.php`)
   - Основна модель для доставки
   - Polymorphic relationship з GroceryOrder, BulkyOrder, FoodOrder
   - Автоматичний розрахунок ETA
   - Відстеження статусу та локації кур'єра

2. **GroceryOrder** (`app/Models/Delivery/GroceryOrder.php`)
   - Замовлення продуктів
   - Підтримка замін (substitution policy)
   - Зв'язок з магазинами

3. **BulkyOrder** (`app/Models/Delivery/BulkyOrder.php`)
   - Крупногабаритні замовлення
   - Розміри, вага, додаткові послуги
   - Розрахунок об'єму та ціни

4. **FoodOrder** (`app/Models/Delivery/FoodOrder.php`)
   - Замовлення готової їжі
   - Зв'язок з ресторанами
   - Температурні вимоги та алергени

5. **GroceryItem** (`app/Models/Delivery/GroceryItem.php`)
   - Елементи замовлення продуктів
   - Підтримка пропозицій замін

### Сервіси

1. **GeofenceService** (`app/Services/Delivery/GeofenceService.php`)
   - Розрахунок ETA на основі геозон
   - Використання Haversine formula для відстані
   - Кешування результатів

2. **TariffCalculator** (`app/Services/Delivery/TariffCalculator.php`)
   - Розрахунок тарифів для різних типів доставки
   - Урахування погодних умов
   - Інтеграція з PricingRule

3. **OrderFactory** (`app/Services/Delivery/OrderFactory.php`)
   - Створення замовлень через polymorphic
   - Автоматичний розрахунок ETA та тарифів

### API Endpoints

```
POST   /api/v1/delivery/quick-order      - Швидке створення замовлення
GET    /api/v1/delivery/orders/{id}/tracking - Отримати інформацію про відстеження
PATCH  /api/v1/delivery/orders/{id}/status   - Оновити статус доставки
```

### Jobs

1. **ProcessOrder** (`app/Jobs/Delivery/ProcessOrder.php`)
   - Обробка нового замовлення
   - Оновлення статусів
   - Broadcasting подій

2. **SubstitutionJob** (`app/Jobs/Delivery/SubstitutionJob.php`)
   - Пошук альтернатив для продуктів
   - AI-пропозиції замін
   - Обробка через чергу `ai-processing`

### Events

1. **OrderCreated** (`app/Events/Delivery/OrderCreated.php`)
   - Broadcasting при створенні замовлення
   - Канал: `order.{order_id}`

2. **OrderUpdated** (`app/Events/Delivery/OrderUpdated.php`)
   - Broadcasting при оновленні замовлення
   - Real-time оновлення ETA та локації кур'єра

## 🎨 Filament Адмін-панель

### DeliveryOrderResource

**Розташування:** `app/Filament/Resources/DeliveryOrderResource.php`

**Особливості:**
- Динамічні поля залежно від типу доставки
- Reactive форми з Alpine.js
- Фільтри за типом, статусом, терміновістю
- Інтеграція з існуючими моделями

**Поля форми:**
- Базові: order_id, type, tracking_status, courier_id
- Адреси: pickup_address, delivery_address
- ETA: автоматичний розрахунок
- Динамічні поля для кожного типу

## 🗺️ Real-time Tracking

### Компонент: `delivery-tracking.blade.php`

**Функціональність:**
- Mapbox GL JS для відображення карти
- Real-time оновлення через Laravel Echo
- Відстеження локації кур'єра
- Автоматичне оновлення кожні 10 секунд
- Кнопки для зв'язку з кур'єром

**Використання:**
```blade
<x-delivery-tracking :order-id="$order->id" :delivery-order-id="$deliveryOrder->id" />
```

## ⚙️ Конфігурація

### config/delivery.php

```php
'grocery' => [
    'base_time' => 15,      // хвилини
    'time_per_km' => 2,     // хвилини на км
    'delivery_fee' => 50,   // NOK
],

'bulky' => [
    'base_time' => 30,
    'time_per_km' => 3,
    'base_rate' => 200,
    'rate_per_m3' => 50,
    'service_prices' => [...],
],

'food' => [
    'base_time' => 20,
    'time_per_km' => 2.5,
    'delivery_fee' => 40,
],
```

### .env

```env
MAPBOX_TOKEN=your_mapbox_token_here
```

## 🚀 Встановлення

1. **Запустити міграції:**
```bash
php artisan migrate
```

2. **Додати Mapbox token:**
```env
MAPBOX_TOKEN=your_token
```

3. **Налаштувати Redis для Laravel Echo:**
```env
BROADCAST_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

4. **Очистити кеші:**
```bash
php artisan optimize:clear
```

## 📊 Приклад використання

### Створення замовлення через API

```bash
POST /api/v1/delivery/quick-order
Authorization: Bearer {token}

{
    "type": "grocery",
    "address": "Narvik, Norway",
    "items": [
        {
            "id": 1,
            "quantity": 2,
            "unit_price": 50.00
        }
    ],
    "substitution_policy": "ai",
    "is_urgent": false
}
```

### Відстеження замовлення

```blade
<x-delivery-tracking 
    :order-id="$order->id" 
    :delivery-order-id="$deliveryOrder->id" 
/>
```

## 🔧 Налаштування черг

### config/queue.php

```php
'delivery' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'delivery',
    'retry_after' => 90,
],

'ai-processing' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => 'ai-processing',
    'retry_after' => 300,
],
```

## 📈 Моніторинг

### Ключові метрики:
- Кількість замовлень за день
- Точність ETA
- Навантаження кур'єрів
- Статуси доставки

### Filament Widgets:
Можна створити віджети для відображення метрик на dashboard.

## 🔒 Безпека

- **Rate Limiting:** 60 запитів на хвилину для API
- **Authentication:** Всі endpoints вимагають `auth:sanctum`
- **GDPR:** Автоматичне видалення даних через 30 днів
- **Encryption:** Геолокації шифруються в БД

## 🎯 Наступні кроки

1. ✅ Моделі та міграції - **Готово**
2. ✅ Сервіси та API - **Готово**
3. ✅ Filament адмін-панель - **Готово**
4. ✅ Real-time tracking - **Готово**
5. ✅ Тестування та оптимізація - **Готово**
6. ✅ Реальні seeders (RealWorldCatalogSeeder, PricingRuleSeeder) - **Готово**
7. ✅ Главная страница с живыми данными - **Готово**
8. ✅ ЛК клиента с цепочкой заказов - **Готово**
9. ✅ Хабы категорий как полноценные страницы - **Готово**
10. ⏳ Інтеграція з мобільним додатком
11. ⏳ OCR для чеків (Google Cloud Vision)
12. ⏳ IoT-датчики (MQTT bridge)

## 📝 Примітки

- Модуль повністю інтегрований з існуючою системою
- Використовує існуючі геозони та тарифи
- Підтримує всі типи доставки через polymorphic
- Real-time tracking через Laravel Echo + Redis
- Готовий до використання після міграцій

## 🗄️ Реальні дані (Seeders)

### RealWorldCatalogSeeder
- Заполняет `service_categories` (7 категорий)
- Заполняет `geo_zones` (6 зон Нарвика)
- Заполняет `retail_stores` (10 магазинов)
- Заполняет `restaurants` (17 ресторанов)

### PricingRuleSeeder
- Создает правила ценообразования для `grocery`, `bulky`, `food`
- Учитывает геозоны и коэффициенты (urgency, night)
- Интегрирован с `TariffCalculator`

### BikubeDemoOrdersSeeder
- Создает тестовые заказы всех типов:
  - Delivery (Grocery, Food, Bulky)
  - Handyman
  - Eco
  - Social Care
  - Errand

## 🏠 Главная страница

Главная страница (`/`) показывает живые данные:
- Категории услуг из БД
- Популярные магазины
- Популярные рестораны
- Популярные услуги мастера
- Эко-услуги
- Активные доставки
- Индивидуальные поручения

Все данные кешируются (TTL 1 час) для производительности.

## 👤 ЛК клиента

### Маршруты
- `/account` - Dashboard
- `/account/orders` - Мои заказы
- `/account/orders/{id}` - Детали заказа
- `/account/deliveries` - Мои доставки
- `/account/deliveries/{id}` - Детали доставки с трекингом

### Цепочка заказов
1. Клиент создаёт заказ → сохраняется в `orders` с `user_id`
2. Создаётся модульный объект → `DeliveryOrder`, `HandymanOrderDetails`, и т.д.
3. Заказ отображается в ЛК → все модули подключены
4. Детали заказа доступны → показывает все модульные данные
5. Трекинг работает → для delivery заказов отображается компонент трекинга

## 📂 Хабы категорий

Все категории имеют полноценные страницы:
- `/category/delivery` - хаб доставки
- `/category/moving` - хаб переезда
- `/category/handyman` - хаб мастера
- `/category/eco` - хаб эко-услуг
- `/category/social-help` - хаб социальной помощи
- `/category/personal-task` - хаб поручений
- `/category/tow` - хаб эвакуатора

Каждая страница показывает реальные данные из БД и имеет рабочие ссылки.

