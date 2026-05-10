# Tech Context - GLF Bikube

## Технології

### Backend
- **PHP**: 8.1+
- **Laravel**: 10.10+
- **Composer**: Залежності управління

### Frontend
- **Filament**: 2.17+ (адмін-панель)
- **Blade**: Шаблони для публічної вітрини
- **Tailwind CSS**: Стилізація
- **Vite**: Збірка фронтенду
- **Next.js 14**: Планується для публічної вітрини

### База даних
- **SQLite**: Розробка (`database/database.sqlite`)
- **PostgreSQL**: Продакшн (готово до використання)
- **Doctrine DBAL**: 3.10+ (для міграцій)

### Черги та кеш
- **Redis**: 3.2+ (через Predis)
- **Laravel Horizon**: 5.38+ (monitoring черг)

### Аутентифікація
- **Laravel Sanctum**: 3.3+ (API токени)
- **OAuth2/OIDC**: Власна реалізація

### Платіжні системи
- **Stripe PHP SDK**: 18.0+
- **Vipps**: Власна інтеграція

### Моніторинг та логування
- **Sentry**: 4.18+ (error tracking)
- **Laravel Logging**: Вбудоване логування

### Тестування
- **PHPUnit**: 10.1+
- **Mockery**: 1.4.4+
- **Laravel Pint**: 1.0+ (code style)

### Інші бібліотеки
- **Guzzle HTTP**: 7.2+ (HTTP клієнт)
- **Faker**: 1.9.1+ (тестові дані)

## Налаштування розробки

### Структура проекту
```
/home/dima/Local server/
├── app/                    # Основна логіка
├── config/                 # Конфігурація
├── database/               # Міграції, seeders
├── routes/                 # Маршрути
├── resources/              # Views, CSS, JS
├── public/                 # Публічні файли
├── tests/                  # Тести
├── vendor/                 # Залежності
└── memory-bank/           # Банк пам'яті (новий)
```

### Веб-сервер
- **Apache**: Конфігурація в `apache-glfbikube.conf`
- **Порт**: 2244
- **DocumentRoot**: `/home/dima/Local server/public` ✅ ВИПРАВЛЕНО
- **Статус**: Конфігурація готова, потребує запуску сервісу

### Команди розробки
```bash
# Запуск сервера розробки
php artisan serve --host=0.0.0.0 --port=2222

# Міграції
php artisan migrate

# Seeders
php artisan db:seed

# Очищення кешу
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Оптимізація
php artisan optimize
```

### Налаштування Apache ✅ НАЛАШТОВАНО ТА ЗАПУЩЕНО
- **Конфігурація**: `apache-glfbikube.conf` → `/etc/httpd/conf/extra/glfbikube.conf`
- **Порт**: 2244
- **DocumentRoot**: `/srv/glfbikube/public` ✅ (переміщено через проблеми з правами доступу)
- **Проект**: `/srv/glfbikube` (копія проекту для Apache)
- **MPM**: prefork (для сумісності з PHP модулем)
- **Тестовий скрипт**: `public/test-apache.php` (для діагностики)
- **Сервіс**: httpd (systemd)
- **Статус**: ✅ Запущено та працює
- **PHP модуль**: libphp.so (завантажено)
- **PHP extensions**: pdo_sqlite, sqlite3 (налаштовано)
- **URL**: http://localhost:2244/ ✅ Працює

## Git та Version Control

### Репозиторій
- **Версія**: 0.1 (тег v0.1)
- **Гілка**: main
- **Remote**: none (віддалений origin видалено)
- **Статус**: ✅ Локальна розробка (без GitHub)
- **Банк пам'яті**: ✅ Включено

### Структура коміту v0.1
- Всі файли проекту
- Банк пам'яті (memory-bank/)
- Конфігурація Apache
- README.md
- .gitignore
- Документація

## Технічні обмеження

### PHP
- Мінімальна версія: 8.1
- Рекомендовані extensions: pdo, pdo_sqlite, redis, curl, mbstring

### База даних
- SQLite для розробки (легкість налаштування)
- PostgreSQL для продакшн (масштабованість)

### Пам'ять
- Рекомендований ліміт: 256MB+
- Для ML-операцій: 512MB+

## Залежності та інтеграції

### Зовнішні сервіси
- **Stripe**: Платежі
- **Vipps**: Норвезькі платежі
- **OSRM**: Маршрутизація (планується)
- **FCM**: Push-нотифікації
- **Sentry**: Error tracking

### Внутрішні сервіси
- **Redis**: Кеш та черги (опціонально; у dev можна file/database)
- **Horizon Dashboard**: `/horizon` (monitoring)
- **Filament Admin**: `/admin` (адмін-панель)
- **Scheduler**: `slots:release-expired` кожні 15 хв (очищення hold’ів)

## Патерни використання інструментів

### Git
- Основна гілка: `main`
- Feature branches для нових функцій
- Структура комітів: Conventional Commits

### Composer
- Автозавантаження: PSR-4
- Dev dependencies: Тільки для розробки

### Artisan
- Команди в `app/Console/Commands/`
- Scheduled tasks в `app/Console/Kernel.php`

## Конфігурація

### Environment
- Файл: `.env`
- Ключові змінні:
  - `APP_ENV=local`
  - `DB_CONNECTION=sqlite`
  - `REDIS_HOST=127.0.0.1`
  - `STRIPE_KEY`, `STRIPE_SECRET`
  - `VIPPS_CLIENT_ID`, `VIPPS_SECRET`

### Feature Flags
- Схема: таблиці `feature_flags`, `feature_flag_scopes`
- Сервіс: `App\Services\FeatureFlags\FeatureFlagger` + helper-и `ff()`, `ff_rules()`
- Кеш: in-memory (Cache::remember) із TTL 60s, кнопка очистки в ресурсі
- Admin: `FeatureFlagResource` з Scopes RM

