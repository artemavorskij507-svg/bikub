# System Patterns - GLF Bikube

## Архітектура системи
- **Backend**: Laravel 10.x (MVC + Service Layer)
- **Frontend Admin**: Filament v3 (PHP components)
- **Frontend Public**: Blade templates (майбутнє: Next.js 14)
- **Mobile**: PWA (Progressive Web App)
- **API**: RESTful з версіонуванням (/api/v1/*)

## Ключові технічні рішення

### Аутентифікація та авторизація
- **Sanctum**: Для API токенів
- **OAuth2/OIDC**: Для партнерської екосистеми
- **Filament Auth**: Для адмін-панелі
- **SSO**: BankID/ID-porten OIDC для гос-рівня

### База даних
- **SQLite**: Для розробки
- **PostgreSQL**: Для продакшн (готово)
- **Міграції**: 62+ таблиць
- **Моделі**: 57+ Eloquent моделей

### Черги та кешування
- **Redis**: Для черг (Laravel Horizon)
- **Redis**: Для кешування
- **Cache Strategy**: Multi-layer caching

### Платіжні системи
- **Stripe**: Основна платіжна система
- **Vipps**: Норвезька платіжна система
- **Payment Intents**: Асинхронна обробка платежів
- **Webhooks**: Обробка подій від платіжних систем

### Маршрутизація та геолокація
- **OSRM**: Для маршрутизації (планується)
- **Custom ETA**: ML-модель для прогнозування часу
- **Matrix API**: Для оптимізації маршрутів
- **Geofences**: Геозамки для автоматичних подій

## Патерни проектування

### Service Layer
- Сервіси в `app/Services/` для бізнес-логіки
- Контролери тільки для HTTP обробки
- Репозиторії для доступу до даних (де потрібно)

### Event-Driven Architecture
- Події в `app/Events/` (OrderCreated, TaskCompleted, etc.)
- Слухачі в `app/Listeners/`
- Асинхронна обробка через черги

### Multi-Tenant
- Організації як верхній рівень ієрархії
- Scoping через middleware та policies
- Ізоляція даних на рівні бази даних

### API Versioning
- Префікс `/api/v1/` для всіх API endpoints
- Контролери в `app/Http/Controllers/Api/`
- Валідація через Form Requests

## Компонентні зв'язки

### Замовлення (Order)
- Пов'язано з: User, ServiceType, Organization, Payment, Task, ScheduleSlot, Address
- Події: OrderCreated, OrderPaid, OrderCompleted, OrderCanceled
- Життєвий цикл: `pending` → `confirmed` (після оплати) → `assigned` → `in_progress` → `completed`
- **Критично**: Tasks створюються ТІЛЬКИ після оплати через OrderPaid event

### Задачі (Task)
- Пов'язано з: Order, Employee, Route
- Події: TaskCreated, TaskAssigned, TaskCompleted, TaskFailed
- Статуси: pending → assigned → in_progress → completed/failed

### Партнери (Partner)
- OAuth2 клієнти для інтеграції
- Webhooks для подій
- KYC документи та контракти
- Партнерський портал

## Критичні шляхи реалізації

### Створення замовлення
1. Клієнт обирає послугу → `/api/v1/public/orders` (POST) або через Filament
2. Система розраховує ціну (динамічне ціноутворення)
3. Створюється Order зі статусом `pending`
4. Створюється Payment Intent (Stripe/Vipps)
5. Клієнт оплачує замовлення

### Обробка платежу (критичний workflow)
1. Stripe/Vipps webhook → `/api/v1/stripe/webhook` або `/api/v1/payments/vipps/webhook`
2. Валідація підпису (HMAC-SHA256)
3. Оновлення статусу замовлення: `payment_status = 'paid'`, `status = 'confirmed'`
4. **OrderPaid event** → `GenerateTasksForOrderPaid` listener
5. Створюються Tasks для виконавців через `TaskGenerator::generateForOrder()`
6. TaskCreated event → `UpdateSlotUtilization` listener
7. Задачі призначаються виконавцям (автоматично або вручну)
8. TaskAssigned event → `SendTaskWebhook` listener
9. Виконавець виконує задачу
10. TaskCompleted event → `SendTaskWebhook` + `UpdateSlotUtilization` listeners
11. OrderCompleted event (коли всі задачі виконано)

### Маршрутизація
1. Створення маршруту → `/api/v1/routes` (POST)
2. Розрахунок матриці відстаней
3. Оптимізація порядку зупинок
4. Розрахунок ETA з ML-моделлю
5. Оновлення в реальному часі через телематику

