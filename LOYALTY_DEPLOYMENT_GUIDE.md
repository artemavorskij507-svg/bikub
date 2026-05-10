# 🚀 ІНСТРУКЦІЇ ДЕПЛОЙМЕНТУ - СИСТЕМА БАЛІВ ЛОЯЛЬНОСТІ

## Розташування: Проект GLF BiKube
**Дата:** 14 грудня 2025  
**Версія:** 1.0.0  
**Статус:** ✅ Production Ready

---

## 📋 Перед Деплойментом

### 1. Перевірка Окремого Середовища

```bash
# На вашому серверу
php artisan migrate --env=production
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### 2. Перевірка Залежностей

Система використовує тільки вбудовані Laravel компоненти:
- ✅ Filament 3.x (вже встановлено)
- ✅ Livewire (вже встановлено)
- ✅ Laravel Sanctum (вже встановлено)

**Нових пакетів не потрібно!**

---

## 📦 Файли для Деплойменту

### Основні Файли

```bash
# Моделі
app/Models/LoyaltyBalance.php
app/Models/LoyaltyTransaction.php

# API Controller
app/Http/Controllers/Api/LoyaltyController.php

# Observer
app/Observers/OrderObserver.php

# Filament Resources
app/Filament/Resources/LoyaltyBalanceResource.php
app/Filament/Resources/LoyaltyTransactionResource.php
app/Filament/Resources/LoyaltyBalanceResource/Pages/ManageLoyaltyPoints.php
app/Filament/Resources/LoyaltyBalanceResource/Pages/ListLoyaltyBalances.php
app/Filament/Resources/LoyaltyBalanceResource/Pages/EditLoyaltyBalance.php
app/Filament/Resources/LoyaltyBalanceResource/Pages/CreateLoyaltyBalance.php
app/Filament/Resources/LoyaltyBalanceResource/RelationManagers/TransactionsRelationManager.php
app/Filament/Resources/LoyaltyTransactionResource/Pages/ListLoyaltyTransactions.php
app/Filament/Resources/LoyaltyTransactionResource/Pages/EditLoyaltyTransaction.php
app/Filament/Resources/LoyaltyTransactionResource/Pages/CreateLoyaltyTransaction.php

# Livewire
app/Livewire/UserLoyaltyBalance.php
resources/views/livewire/user-loyalty-balance.blade.php

# Widget
app/Filament/Widgets/LoyaltyStatsOverview.php

# Console Command
app/Console/Commands/DistributeLoyaltyPoints.php

# Migrations (обидві виконаються автоматично)
database/migrations/2025_12_14_*_create_loyalty_balances_table.php
database/migrations/2025_12_14_*_create_loyalty_transactions_table.php
```

### Модифіковані Файли

```bash
# AppServiceProvider - додано Observer registration
app/Providers/AppServiceProvider.php

# Dashboard - додано Widget
app/Filament/Pages/Dashboard.php

# API Routes - додано 3 endpoints
routes/api.php

# User Model - додано convenience methods
app/Models/User.php
```

---

## 🔧 Процес Деплойменту

### Крок 1: Завантажити Файли

```bash
git pull origin main  # або вручну завантажити файли
```

### Крок 2: Виконати Міграції

```bash
php artisan migrate --force

# Або окремо:
php artisan migrate:refresh --seed  # якщо потрібен reset
```

### Крок 3: Очистити Кеши

```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Крок 4: Перевірити Системні Команди

```bash
# Переконайтеся що команда доступна
php artisan list | grep loyalty

# Має вивести:
# loyalty:distribute              Розповсюджити бали лояльності користувачам
```

### Крок 5: Перевірити API

```bash
# Протестуйте один раз на dev
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://your-domain.com/api/loyalty/balance
```

### Крок 6: Перевірити Filament

```bash
# Заходьте в адмін панель
https://your-domain.com/admin

# Повинні бути видні:
# - Навігаційна група "Лояльність"
# - Два нові ресурси
# - Widget на Dashboard
```

---

## ✅ Перевірки Після Деплойменту

### 1. Таблиці Створені

```bash
php artisan tinker

# Перевірити що таблиці існують
>>> Schema::hasTable('loyalty_balances')  # true
>>> Schema::hasTable('loyalty_transactions')  # true
>>> exit()
```

### 2. Observer Працює

```bash
# Змініть статус замовлення на completed
# (в базі або через API)

# Перевірити що створилася транзакція:
php artisan tinker
>>> \App\Models\LoyaltyTransaction::latest()->first()
# має повернути записаний об'єкт
```

### 3. API Доступні

```bash
# Збережіть токен користувача
export TOKEN="YOUR_BEARER_TOKEN"

# Протестуйте API
curl -H "Authorization: Bearer $TOKEN" \
     https://your-domain.com/api/loyalty/balance

# Повинна повернути JSON:
# { "data": { "current_points": 0, ... } }
```

### 4. Filament Відображається

- [ ] Зайти в админ-панель
- [ ] Переглянути "Лояльність" → "Баланси балів"
- [ ] Переглянути "Лояльність" → "Історія операцій"
- [ ] Побачити widget на Dashboard
- [ ] Клацнути "Керування балами"

---

## 🔒 Настройки Безпеки

### 1. Rate Limiting

Вже налаштовано в `RouteServiceProvider`:
```php
// 60 запитів за хвилину на користувача
'api_critical' => '60,1'
```

### 2. CORS (якщо потрібно)

Якщо вам потрібен доступ з інших домен:

```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['https://your-app.com'],
```

### 3. Permission Checks

Observer автоматично проводить перевірку:
```php
if (!$order->user_id) return;  // захист від null user
```

---

## 📊 Моніторинг

### 1. Логування

Всі операції логуються в базу:
```bash
# Переглянути останні операції
php artisan tinker
>>> \App\Models\LoyaltyTransaction::latest()->limit(10)->get()
```

### 2. Dashboard Widget

На Dashboard адміністратор бачить:
- Кількість користувачів з балами
- Всього балів в системі
- Активні користувачі
- Всього операцій

### 3. Health Check

Додавте до вашого health check сценарію:
```bash
curl https://your-domain.com/api/v1/health
# Має повернути: {"status": "ok"}
```

---

## 🚨 Troubleshooting

### Проблема: 404 на API

**Рішення:**
```bash
php artisan route:cache
php artisan route:clear
```

### Проблема: Filament ресурси не видні

**Рішення:**
```bash
php artisan cache:clear
php artisan config:cache
# Перезавантажте адмін-панель (F5)
```

### Проблема: Observer не спрацьовує

**Рішення:**
1. Перевірте що AppServiceProvider завантажується
2. Перевірте що OrderObserver зареєстрований:
```bash
php artisan tinker
>>> \App\Models\Order::getObservers()
# має містити OrderObserver
```

### Проблема: API повертає 401

**Рішення:**
- Перевірте що токен коректний
- Перевірте що користувач автентифікований
- Перевірте що middleware `auth:sanctum` активний

---

## 📝 Послідовність Кроків Для Quick Start

```bash
# 1. Завантажити файли
git pull

# 2. Виконати міграції
php artisan migrate

# 3. Очистити кеши
php artisan cache:clear && php artisan route:cache

# 4. Перевірити команду
php artisan list | grep loyalty

# 5. Перевірити маршрути
php artisan route:list | grep api/loyalty

# 6. Перевірити Observer
php artisan tinker
> \App\Models\Order::getObservers()

# 7. Готово! ✅
```

---

## 🎓 Примітки для Команди

### Для Frontend Розробників

API endpoints розташовані за `/api/loyalty/`:
- `GET /api/loyalty/balance` - отримати баланс користувача
- `GET /api/loyalty/transactions` - отримати історію
- `POST /api/loyalty/redeem` - витратити бали

Livewire компонент: `<livewire:user-loyalty-balance />`

### Для Backend/DevOps

Система повністю інтегрована в Laravel:
- Observer автоматично спрацює на Order.updated
- Console command: `php artisan loyalty:distribute`
- Таблиці індексовані для швидкості

### Для Адміністратора

Всі функції доступні через Filament Admin:
- `/admin/loyalty-balances` - управління балансами
- `/admin/loyalty-balances/manage-points` - ручне управління
- `/admin/loyalty-transactions` - переглядання історії

---

## 📞 Контакти і Підтримка

Якщо виникнуть проблеми:
1. Перевірте цей документ (розділ Troubleshooting)
2. Перевірте документацію в LOYALTY_SYSTEM_GUIDE.md
3. Перевірте логи Laravel (storage/logs/)

---

**Статус:** ✅ READY FOR PRODUCTION  
**Дата:** 14 грудня 2025  
**Версія:** 1.0.0
