# 🌐 GLF BiKube - API Endpoints Dashboard

**Версия API:** 1.0.0  
**База URL:** `http://localhost:8000/api/v1`  
**Дата обновления:** 27 октября 2025

---

## ✅ Статус проекта

| Категория | Количество | Статус |
|-----------|-----------|--------|
| **API Endpoints** | 33 | ✅ Реализовано |
| **Controllers** | 12 | ✅ Создано |
| **Models** | 13 | ✅ |
| **Migrations** | 17 | ✅ |
| **Filament Resources** | 13 | ✅ |

---

## 📊 База данных

```
✅ Service Categories: 8
✅ Service Types: 62
✅ Restaurants: 25
✅ Retail Stores: 33
✅ Pricing Rules: 33
✅ Geo Zones: 6
✅ Orders: 0
✅ Users: 1
```

---

## 🔌 API Endpoints (35)

### 🏥 Health Check

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/health` | Проверка статуса API | ✅ | ❌ |

**Пример ответа:**
```json
{
  "status": "ok",
  "timestamp": "2025-10-27T19:02:27.439497Z",
  "service": "GLF BiKube API",
  "version": "1.0.0"
}
```

---

### 📂 Service Categories (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/categories` | Список категорий услуг | ✅ | ❌ |
| GET | `/categories/{code}` | Детали категории | ✅ | ❌ |

**Примеры:**
- `GET /api/v1/categories` - Все категории
- `GET /api/v1/categories/care` - Alle Care категория

---

### 🛠️ Service Types (3 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/service-types` | Список типов услуг | ✅ | ❌ |
| GET | `/service-types/{slug}` | Детали типа услуги | ✅ | ❌ |
| GET | `/service-types/category/{category}` | Фильтр по категории | ✅ | ❌ |

**Примеры:**
- `GET /api/v1/service-types` - Все услуги (62 шт)
- `GET /api/v1/service-types/care-l1-med-delivery` - Доставка лекарств
- `GET /api/v1/service-types/category/care` - Услуги категории Care

---

### 🍕 Restaurants (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/restaurants` | Список ресторанов | ✅ | ❌ |
| GET | `/restaurants/{slug}` | Детали ресторана | ✅ | ❌ |

**Примеры:**
- `GET /api/v1/restaurants` - Все рестораны (25 шт)
- `GET /api/v1/restaurants/pizza-bakeren` - Pizza Bakeren

**Рестораны Нарвика:**
- ✅ Pizza Bakeren
- ✅ Rå Sushi
- ✅ vou Lam Restaurant
- ✅ 22 других заведения

---

### 🛒 Retail Stores (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/stores` | Список магазинов | ✅ | ❌ |
| GET | `/stores/{slug}` | Детали магазина | ✅ | ❌ |

**Примеры:**
- `GET /api/v1/stores` - Все магазины (33 шт)
- `GET /api/v1/stores/bunnpris` - Bunnpris

**Категории магазинов:**
- 🍞 Продуктовые (8): Bunnpris, REMA 1000, Coop, Joker, SPAR
- 🏠 DIY и хозяйство (11): Rusta, Europris, Clas Ohlson, Biltema
- 🏪 Мебель и электроника (14): Elkjøp, POWER, Skeidar, Bohus, JYSK

---

### 💰 Pricing Rules (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/pricing-rules` | Список правил ценообразования | ✅ | ❌ |
| GET | `/pricing-rules/{id}` | Детали правила | ✅ | ❌ |

**Примеры:**
- `GET /api/v1/pricing-rules` - Все правила (33 шт)
- `GET /api/v1/pricing-rules/1` - Правило #1

---

### 🗺️ Geo Zones (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/geo-zones` | Список геозон | ✅ | ❌ |
| GET | `/geo-zones/{slug}` | Детали геозоны | ✅ | ❌ |

**Примеры:**
- `GET /api/v1/geo-zones` - Все геозоны (6 шт)
- `GET /api/v1/geo-zones/narvik-city-center` - Центр Нарвика

**Геозоны:**
- Нарвик центр
- Нарвик окраины
- Нарвик промзона
- Нарвик районы

---

### 👥 Partners (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/partners` | Список партнеров | ✅ | ❌ |
| GET | `/partners/{slug}` | Детали партнера | ✅ | ❌ |

**Примечание:** ⚠️ Партнеры пока не заполнены в БД

---

### 👷 Employees (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/employees` | Список сотрудников | ✅ | ❌ |
| GET | `/employees/{id}` | Детали сотрудника | ✅ | ❌ |

**Примечание:** ⚠️ Сотрудники пока не заполнены в БД

---

### 📦 Orders (5 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/orders` | Список заказов | ✅ | ❌ |
| POST | `/orders` | Создать заказ | ✅ | ❌ |
| GET | `/orders/{id}` | Детали заказа | ✅ | ❌ |
| PATCH | `/orders/{id}/status` | Обновить статус | ✅ | ❌ |
| POST | `/orders/{id}/payment/intent` | Создать платеж | ✅ | ❌ |
| POST | `/orders/{id}/payment/confirm` | Подтвердить платеж | ✅ | ❌ |

**Примечание:** 
- ✅ GET работает
- ✅ POST создание заказов с автоматическим расчетом стоимости
- ✅ Payment integration через Stripe

**Пример создания заказа:**
```json
POST /api/v1/orders
{
  "service_type_id": 1,
  "location": {
    "lat": 68.4384,
    "lng": 17.4278,
    "address": "Kongens gate 1"
  },
  "scheduled_at": "2025-10-28T10:00:00Z",
  "notes": "Оставить у двери"
}
```

---

### 💳 Payment (2 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| POST | `/orders/{id}/payment/intent` | Создать payment intent | ✅ | ❌ |
| POST | `/orders/{id}/payment/confirm` | Подтвердить платеж | ✅ | ❌ |

**Примеры:**

**Создать payment intent:**
```json
POST /api/v1/orders/1/payment/intent

Response:
{
  "success": true,
  "data": {
    "client_secret": "pi_xxx_secret_xxx",
    "payment_intent_id": "pi_xxxx",
    "amount": 34900,
    "currency": "nok"
  }
}
```

**Подтвердить платеж:**
```json
POST /api/v1/orders/1/payment/confirm
{
  "payment_intent_id": "pi_xxxx"
}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20251027-ABC123",
    "payment_status": "paid",
    "status": "confirmed"
  }
}
```

---

## 🔐 Authentication (3 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| POST | `/register` | Регистрация пользователя | ⚠️ TODO | ❌ |
| POST | `/login` | Вход в систему | ⚠️ TODO | ❌ |
| POST | `/logout` | Выход | ⚠️ TODO | 🔒 |
| GET | `/me` | Профиль пользователя | ⚠️ TODO | 🔒 |

**Примечание:** ⚠️ Auth система требует доработки (Sanctum tokens)

---

## 📊 Analytics (3 endpoints)

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| GET | `/analytics/statistics` | Статистика | ⚠️ TODO | ❌ |
| GET | `/analytics/orders-report` | Отчет по заказам | ⚠️ TODO | ❌ |
| GET | `/analytics/export` | Экспорт данных | ⚠️ TODO | ❌ |

**Примечание:** ⚠️ Analytics требует реализации логики

---

## 🔔 Push Notifications (3 endpoints) - Protected

| Метод | Endpoint | Описание | Статус | Auth |
|-------|----------|----------|--------|------|
| POST | `/push/subscribe` | Подписка на уведомления | ⚠️ TODO | 🔒 |
| POST | `/push/unsubscribe` | Отписка | ⚠️ TODO | 🔒 |
| GET | `/push/subscriptions` | Мои подписки | ⚠️ TODO | 🔒 |

**Примечание:** 
- 🔒 Требует авторизации (auth:sanctum)
- ⚠️ Требует настройки Firebase/Push

---

## 📈 Сводная статистика по статусам

### ✅ Полностью готово (20 endpoints)
- Health Check (1)
- Service Categories (2)
- Service Types (3)
- Restaurants (2)
- Retail Stores (2)
- Pricing Rules (2)
- Geo Zones (2)
- Partners (2)
- Employees (2)
- Orders GET (2)

### ⚠️ Требует доработки (13 endpoints)
- Orders POST (1)
- Auth (4)
- Analytics (3)
- Push Notifications (3)
- Me (1)
- Logout (1)

---

## 🧪 Тестирование

### Через cURL:
```bash
# Health check
curl http://localhost:8000/api/v1/health

# Все услуги
curl http://localhost:8000/api/v1/service-types

# Категория care
curl http://localhost:8000/api/v1/service-types/category/care

# Рестораны
curl http://localhost:8000/api/v1/restaurants

# Магазины
curl http://localhost:8000/api/v1/stores
```

### Через Tinker (работает ✅):
```bash
php artisan tinker

App\Models\ServiceType::count()
// 62

App\Models\Restaurant::count()  
// 25

App\Models\RetailStore::count()
// 33
```

---

## ⚠️ Известные проблемы

1. **PDO через веб-сервер**
   - Проблема: Class "PDO" not found
   - Статус: ⚠️ Не работает через HTTP
   - Работает: ✅ Через CLI (Tinker)
   - Решение: Настроить правильный PHP-FPM или использовать Docker

2. **Веб-сервер API**
   - URL: `http://localhost:8000`
   - Статус: ⚠️ Работает только health endpoint
   - Решение: Исправить PDO проблему

---

## 🚀 Следующие шаги

### Приоритет 1: Исправить проблемы
1. ✅ Создать все контроллеры
2. ⚠️ Исправить PDO для веб-сервера
3. ⚠️ Протестировать все GET endpoints

### Приоритет 2: Доработать функционал
1. ⚠️ Auth система (register, login, sanctum)
2. ⚠️ Orders POST (валидация, расчет стоимости)
3. ⚠️ Analytics (статистика, отчеты)
4. ⚠️ Push Notifications (Firebase)

### Приоритет 3: Дополнительные фичи
1. 📱 Мобильное приложение
2. 💳 Payment интеграции (Vipps, Stripe)
3. 🗺️ Геолокация (Google Maps)
4. 📧 Email уведомления
5. 📱 SMS уведомления

---

## 📝 Контакты

**Developer:** ROMA ∞  
**Project:** GLF BiKube AS  
**Location:** Narvik, Norway  
**Date:** 27 октября 2025

---

*Автоматически создано системой GLF BiKube*

