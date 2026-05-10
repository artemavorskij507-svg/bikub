# ⚡ QUICK START - СИСТЕМА БАЛІВ ЛОЯЛЬНОСТІ

**Версія:** 1.0.0  
**Статус:** ✅ Production Ready  
**Дата:** 14 грудня 2025

---

## 🚀 За 5 Хвилин

### 1. Завантажити Файли
```bash
git pull origin main
# або скопіювати файли вручну
```

### 2. Виконати Міграції
```bash
php artisan migrate
```

### 3. Перевірити
```bash
php artisan route:list | grep api/loyalty
php artisan route:list | grep admin/loyalty
```

**Done!** ✅

---

## 🎯 Що Де Знаходиться?

### API (для frontend)
```
GET  /api/loyalty/balance        # Отримати бали користувача
GET  /api/loyalty/transactions   # Отримати історію операцій  
POST /api/loyalty/redeem         # Витратити бали
```

### Admin Panel (для адміністраторів)
```
/admin/loyalty-balances          # Управління балансами
/admin/loyalty-balances/manage-points  # Ручне управління
/admin/loyalty-transactions      # Переглядання історії
```

### Frontend (для користувачів)
```blade
<livewire:user-loyalty-balance />        # Компактна вкладка
<livewire:user-loyalty-balance :full="true" />  # Повна карточка
```

---

## 💻 Приклади Використання

### JavaScript (API)
```javascript
// Отримати баланс користувача
const response = await fetch('/api/loyalty/balance', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const data = await response.json();
console.log(`У вас ${data.data.current_points} балів`);

// Витратити бали
const redeem = await fetch('/api/loyalty/redeem', {
  method: 'POST',
  headers: { 
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ points: 50, reason: 'Знижка' })
});
```

### PHP (Console)
```bash
# Розповсюджити бали всім користувачам
php artisan loyalty:distribute --points=10 --reason="Новорічний подарунок"

# Конкретному користувачу
php artisan loyalty:distribute --points=50 --user=john@example.com
```

---

## 📊 Структура Даних

### Таблиця: loyalty_balances
```sql
id              INT
user_id         INT (unique FK)
points          INT
lifetime_points INT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Таблиця: loyalty_transactions
```sql
id              INT
user_id         INT FK
type            ENUM('earn','redeem','manual_add','manual_remove','expire','admin_adjustment')
points_amount   INT (може бути негативне)
description     TEXT
source_type     VARCHAR
source_id       INT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

## 🔒 Безпека

Всі API endpoints захищені:
- ✅ `auth:sanctum` - Вимагає логіну
- ✅ `throttle:api_critical` - 60 запитів/хв
- ✅ Валідація вхідних даних
- ✅ Аудит всіх операцій

---

## 🆘 Швидкі Виправлення

### Проблема: 404 на API
```bash
php artisan route:cache
php artisan route:clear
```

### Проблема: Filament не показує ресурси
```bash
php artisan cache:clear
php artisan config:cache
# Перезавантажте браузер (F5)
```

### Проблема: Observer не працює
```bash
php artisan tinker
>>> \App\Models\Order::getObservers()
# має показати OrderObserver
>>> exit()
```

---

## 📚 Документація

Прочитайте детальну документацію:

1. **LOYALTY_SYSTEM_GUIDE.md** - Техдокументація
2. **LOYALTY_DEPLOYMENT_GUIDE.md** - Deployment інструкції
3. **LOYALTY_SYSTEM_SUMMARY.md** - Виконавчий звіт

---

## ✅ Перевірка Після Deploy

```bash
# 1. Перевірити таблиці
php artisan tinker
> Schema::hasTable('loyalty_balances')   # true
> Schema::hasTable('loyalty_transactions')  # true

# 2. Перевірити маршрути
php artisan route:list | grep loyalty

# 3. Перевірити команду
php artisan list | grep loyalty

# 4. Готово! ✅
```

---

## 🎓 Корисні Команди

```bash
# Перевірити здоров'я системи
php artisan health:check

# Очистити всі кеші
php artisan cache:clear && php artisan route:cache

# Перевірити помилки
php artisan tinker --execute="echo 'OK';"

# Запустити всі тести (якщо є)
php artisan test

# Дивитися логи
tail -f storage/logs/laravel.log
```

---

## 🚀 Статус

```
✅모델 готові
✅ API готові
✅ Admin готовий
✅ Frontend готовий
✅ Documentation готова
✅ PRODUCTION READY
```

**Все готово до деплойменту!** 🎉

---

**Потребуется помощь?** Прочитайте LOYALTY_DEPLOYMENT_GUIDE.md или LOYALTY_SYSTEM_GUIDE.md
