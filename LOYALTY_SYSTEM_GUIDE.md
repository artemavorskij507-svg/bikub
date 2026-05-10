# Система Балів Лояльності

## Огляд

Комплексна система управління балами лояльності для премійних користувачів та повернення коштів.

### Можливості

- **Автоматичне накопичення** - 1 бал за 1 ₴ при завершенні замовлення
- **Витрачання балів** - Обміну балів на знижку або товари
- **Фіксування балів** - Адміністратор може вручну додавати/видаляти бали
- **Історія операцій** - Повна аудит всіх транзакцій
- **API інтеграція** - REST API для фронтенду та мобільних додатків

## Моделі

### LoyaltyBalance
```
- id: ID
- user_id: FK (unique) - Посилання на користувача (один на одного)
- points: int - Поточні накопичені бали
- lifetime_points: int - Всього накопичено в історії (включаючи витрачені)
- created_at, updated_at: Timestamps
```

**Методи:**
- `addPoints(int $amount, string $description, string $sourceType, int $sourceId)` - Додати бали
- `redeemPoints(int $amount, string $description): bool` - Витратити бали
- `hasEnoughPoints(int $amount): bool` - Перевірити достатність
- `getPointsValue(int $points, float $pointValue = 0.01): float` - Конвертувати в гроші

### LoyaltyTransaction
```
- id: ID
- user_id: FK - Користувач
- type: enum (earn|redeem|manual_add|manual_remove|expire|admin_adjustment) - Тип операції
- points_amount: int - Кількість балів (від'ємне для видалення)
- description: text - Причина
- source_type: string - Модель, що спровокувала операцію (Order, Refund, etc)
- source_id: int - ID моделі-джерела
- created_at, updated_at: Timestamps
```

**Методи:**
- `getTypeLabel(): string` - Людиночитаемий тип ("Накопичено", "Витрачено", etc)
- `getTypeColor(): string` - Колір для UI (success, warning, info, danger, etc)
- `getTypeIcon(): string` - Heroicon для UI

## Observer: OrderObserver

При зміні статусу замовлення на `completed`:
- Розраховує бали: `floor($order->total_amount)`
- Створює LoyaltyTransaction типу `earn`
- Оновлює поля points та lifetime_points

## Filament Ресурси

### LoyaltyBalanceResource
**Навігація:** Лояльність → Баланси балів

**Таблиця:**
- user.email - Користувач (searchable, sortable)
- user.name - Ім'я користувача
- points - Поточні бали (formatStateUsing)
- lifetime_points - Всього накопичено
- updated_at - Дата оновлення

**Форма:**
- user_id - Вибір користувача (readonly на редагуванні)
- points - Readonly
- lifetime_points - Readonly

**Relation Manager:**
- TransactionsRelationManager - Показ всіх транзакцій користувача

**Сторінка Управління Балами:** `/manage-points`
- Вибір користувача
- Вибір дії (додати/видалити)
- Введення кількості балів
- Причина операції

### LoyaltyTransactionResource
**Навігація:** Лояльність → Історія транзакцій (sort: 2)

**Таблиця:**
- user.email - Користувач
- type - Тип (BadgeColumn з кольором та іконкою)
- points_amount - Бали (зі знаком + для додавання)
- description - Опис (limit 50)
- source_type - Джерело (class_basename)
- created_at - Дата

**Форма (readonly):**
- user_id
- type
- points_amount
- description
- source_type
- created_at

## Livewire Компонент

### UserLoyaltyBalance

**Властивості:**
- `$full` - Показати карточку (true) чи бейдж (false, за замовчуванням)
- `$recentTransactions` - К-во показаних операцій (5 за замовчуванням)

**Режими:**

1. **Бейдж** (compact view):
   - Іконка зі зіркою + кількість балів
   - Градієнт фіолетовий-блакитний
   - Цілком в одному рядку

2. **Карточка** (full view):
   - Заголовок "Ваші бали лояльності"
   - Великий дисплей поточних балів
   - Конвертація в гроші (≈ X.XX ₴)
   - Сітка: Всього накопичено + Останнє оновлення
   - Список останніх операцій
   - Hover еффекти

**Використання:**
```blade
<!-- Compact badge -->
<livewire:user-loyalty-balance />

<!-- Full card -->
<livewire:user-loyalty-balance :full="true" />

<!-- З кастомною кількістю операцій -->
<livewire:user-loyalty-balance :full="true" :recentTransactions="10" />
```

## API Endpoints

### GET /api/loyalty/balance
Отримати баланс користувача
```json
{
  "data": {
    "current_points": 250,
    "lifetime_points": 500,
    "points_value": 2.50,
    "updated_at": "2025-12-14T10:30:00Z"
  }
}
```

### GET /api/loyalty/transactions
Отримати історію транзакцій (paginated)
```json
{
  "data": [
    {
      "id": 1,
      "type": "earn",
      "type_label": "Накопичено балів",
      "type_color": "success",
      "type_icon": "heroicon-o-arrow-trending-up",
      "points_amount": 100,
      "description": "Бали за замовлення #ORD-001",
      "source_type": "Order",
      "created_at": "2025-12-14T09:00:00Z"
    }
  ],
  "pagination": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8,
    "from": 1,
    "to": 20
  }
}
```

### POST /api/loyalty/redeem
Витратити бали
**Body:**
```json
{
  "points": 50,
  "reason": "Купівля знижки"
}
```

**Відповідь:**
```json
{
  "success": true,
  "message": "Бали успішно витрачені",
  "remaining_points": 200,
  "points_value": 2.00
}
```

## Аутентифікація

Всі API endpoints захищені `auth:sanctum` middleware та rate limitingом `throttle:api_critical` (60 запитів/хв).

## Примітки

- Поточні бали не можуть бути від'ємними (max функція)
- lifetime_points завжди зростає (для аудиту)
- Системі дозволено додавання балів за:
  - Завершення замовлення (Order observer)
  - Адміністративні дії (Filament)
  - API виклики
- Transaction типи:
  - `earn` - Автоматичне накопичення
  - `redeem` - Витрачання користувачем
  - `manual_add` - Адміністратор добавляє
  - `manual_remove` - Адміністратор видаляє
  - `expire` - Закінчення строку дії
  - `admin_adjustment` - Коригування

## Майбутні Покращення

- [ ] Закінчення строку дії балів (TTL)
- [ ] Комплекси лояльності (VIP рівні)
- [ ] Реферальні бали
- [ ] Спеціальні промо-коди для подарунків балів
- [ ] SMS/Email повідомлення про операції
- [ ] Дашборд аналітики для адміністратора
- [ ] Інтеграція з Telegram ботом
