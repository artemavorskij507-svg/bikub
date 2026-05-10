# 🏢 Система Multitenancy на базі Spatie Laravel Multitenancy

## 📋 Огляд

GLF BiKube тепер підтримує многоклієнтну архітектуру (multitenancy) за допомогою пакету **spatie/laravel-multitenancy**. Кожен партнер (логістична компанія) виступає окремим тенантом з ізоляцією даних на рівні запитів.

## 🎯 Основні компоненти

### 1. Модель Partner (Tenant)
- **Файл**: `app/Models/Partner.php`
- **Батьківський клас**: `Spatie\Multitenancy\Models\Tenant`
- **Основні поля**:
  - `name` - Назва партнера
  - `domain` - Унікальний домен (наприклад: partner1.glfbikube.local)
  - `database` - Ім'я бази даних (для майбутної ізоляції на рівні БД)
  - `subscription_tier` - Рівень підписки (basic, professional, enterprise)
  - `is_active` - Статус активності

### 2. Модель PartnerSettings
- **Файл**: `app/Models/PartnerSettings.php`
- **Призначення**: Зберігання специфічних налаштувань для кожного партнера
- **Основні поля**:
  - Налаштування сповіщень
  - Налаштування замовлень (автопризначення, таймаут)
  - Налаштування рейтингу та комісій
  - Робочі години
  - API налаштування

### 3. TenantFinder (DomainTenantFinder)
- **Файл**: `app/Tenancy/DomainTenantFinder.php`
- **Функція**: Визначає активного тенанта на основі домену запиту
- **Процес**:
  1. Отримує домен з HTTP запиту
  2. Шукає партнера за доменом у БД
  3. Повертає активного партнера

### 4. Middleware (IdentifyTenant)
- **Файл**: `app/Http/Middleware/IdentifyTenant.php`
- **Функція**: Активує тенанта для кожного запиту
- **Місцезнаходження в Kernel.php**: Global middleware stack

## 🔧 Консольні команди

### Створити нового тенанта
```bash
php artisan tenant:create "Назва партнера" "domain.glfbikube.local" --tier=professional
```

### Переглянути список тенантів
```bash
php artisan tenant:list
```

### Налаштувати домени для всіх партнерів
```bash
php artisan tenant:setup-domains --base-domain="partners.glfbikube.local"
```

## 🗂️ Структура базованої даних

### Таблиця partners
```sql
CREATE TABLE partners (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255),
  domain VARCHAR(255) UNIQUE,
  database VARCHAR(255) UNIQUE,
  subscription_tier VARCHAR(255) DEFAULT 'basic',
  subscription_ends_at TIMESTAMP,
  is_active BOOLEAN DEFAULT true,
  -- інші поля
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Таблиця partner_settings
```sql
CREATE TABLE partner_settings (
  id BIGINT PRIMARY KEY,
  partner_id BIGINT FOREIGN KEY,
  notification_email VARCHAR(255),
  sms_notifications_enabled BOOLEAN,
  email_notifications_enabled BOOLEAN,
  auto_assign_orders BOOLEAN,
  max_concurrent_orders INT,
  order_timeout_minutes INT,
  estimated_delivery_accuracy_km DECIMAL,
  cancellation_allowed_minutes INT,
  rating_minimum_threshold DECIMAL,
  emergency_surcharge_percent DECIMAL,
  operating_hours_start TIME,
  operating_hours_end TIME,
  timezone VARCHAR(255),
  language VARCHAR(255),
  api_key VARCHAR(255) UNIQUE,
  webhook_url VARCHAR(255),
  features_enabled JSON,
  metadata JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

## 🔒 Ізоляція даних

### Як це працює?

1. **На рівні запиту**:
   - Запит приходить з домену `partner1.glfbikube.local`
   - Middleware `IdentifyTenant` визначає партнера
   - Multitenancy запускається, всі запити до БД відфільтровані для цього партнера

2. **На рівні моделей**:
   - Моделі, які потребують ізоляції, мають бути додані до конфігу
   - Автоматична фільтрація в query builder

3. **На рівні кешу**:
   - Кеш префіксується ID тенанта
   - Запобігає витоку даних між партнерами

## ⚙️ Налаштування (config/multitenancy.php)

```php
return [
    'tenant_finder' => \App\Tenancy\DomainTenantFinder::class,
    'tenant_model' => \App\Models\Partner::class,
    'tenant_artisan_search_fields' => ['id'],
    'switch_tenant_tasks' => [
        // \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
        // \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
    ],
];
```

## 🚀 Використання в коді

### Отримати поточного тенанта
```php
use Spatie\Multitenancy\Facades\Multitenancy;

$partner = Multitenancy::tenant();
echo $partner->name; // Назва партнера
```

### Мандатно встановити тенанта
```php
Multitenancy::makeCurrent($partner);
```

### Отримати налаштування партнера
```php
$partner = Multitenancy::tenant();
$settings = $partner->settings()->first();
echo $settings->api_key;
```

### Запити з фільтром тенанта
```php
$orders = $partner->orders; // Автоматично фільтрується по тенанту
$zones = $partner->deliveryZones;
```

## 📊 Поточні партнери

| ID | Назва | Домен | Статус |
|----|-------|-------|--------|
| 1 | Narvik Bilberging AS | narvikbilberging-1.partners.glfbikube.local | ✅ |
| 2 | Frydenlund Bilservice | frydenlundbilservice-2.partners.glfbikube.local | ✅ |
| 3 | Ofoten Road Rescue | ofotenroadrescue-3.partners.glfbikube.local | ✅ |
| 4 | Demo Logistics | demologistics-4.partners.glfbikube.local | ✅ |
| 5 | Nordic Tow Service AS | nordictowservice-5.partners.glfbikube.local | ✅ |
| 6 | Arctic Roadside Assistance Ltd | arcticroadsideassist-6.partners.glfbikube.local | ✅ |
| 7 | Fjord Towing AS | fjordtowing-7.partners.glfbikube.local | ✅ |
| 8 | Oslo Mobile Repair AB | oslomobilerepair-8.partners.glfbikube.local | ✅ |
| 9 | Bergen Auto Service AS | bergenautoservice-9.partners.glfbikube.local | ✅ |

## 🔄 Майбутні покращення

1. **Ізоляція на рівні БД**:
   - Кожен тенант матиме окремої базі даних
   - Максимальна безпека та продуктивність

2. **Sync команди для тенантів**:
   - `php artisan migrate --all-tenants`
   - `php artisan seed --all-tenants`

3. **Backups**:
   - Автоматичні резервні копії на тенанта
   - Механізм відновлення

4. **Моніторинг**:
   - Логування операцій на тенанта
   - Статистика використання

## 📞 Контакти

Для питань або проблем з multitenancy - звертайтеся до команди розробки.
