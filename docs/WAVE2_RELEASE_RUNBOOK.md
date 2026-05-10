# WAVE 2 Release Runbook (Staging/Production)

## 1) Цель релизного окна

Запустить **только** Wave 2 migrations:

1. `2026_05_07_180000_create_order_events_table.php`
2. `2026_05_07_183000_create_worker_applications_table.php`
3. `2026_05_07_184000_create_contract_foundation_tables.php`

Явно **не запускать**:

- `2026_04_23_000001_add_delivery_release_guardrails_and_observability.php`

Причина: это отдельная pending migration c PostgreSQL trigger/function/view для `delivery_orders`, с более высоким операционным риском.

---

## 2) Pre-flight checks

```bash
cd /var/www/bikube

php -v
php artisan about
php artisan migrate:status
php artisan route:list
bash scripts/bikube_os_smoke.sh
```

Ожидание:

- `route:list` проходит без ошибок;
- smoke script проходит;
- целевые Wave 2 migrations в статусе `Pending`.

---

## 3) Backup plan

Создать каталог:

```bash
TS="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="/var/backups/bikube/wave2-${TS}"
mkdir -p "$BACKUP_DIR"
echo "$BACKUP_DIR"
```

Примечание: команды ниже используют `pg_dump` через env-переменные текущего runtime (`.env` не печатать и не изменять).

### 3.1 Backup обязательных таблиц

```bash
cd /var/www/bikube

PGHOST=127.0.0.1
PGPORT=5432
PGDATABASE=bikube

# migrations
pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -t migrations > "$BACKUP_DIR/migrations.sql"

# core business tables
pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -t orders > "$BACKUP_DIR/orders.sql"
pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -t users > "$BACKUP_DIR/users.sql"
pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -t partners > "$BACKUP_DIR/partners.sql"
pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -t delivery_orders > "$BACKUP_DIR/delivery_orders.sql"
pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -t partner_contracts > "$BACKUP_DIR/partner_contracts.sql"
```

### 3.2 Backup таблиц, если уже существуют

```bash
for t in order_events worker_applications contract_templates contracts contract_events; do
  if psql -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -Atqc "SELECT to_regclass('public.${t}') IS NOT NULL"; then
    if [ "$(psql -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -Atqc "SELECT to_regclass('public.${t}') IS NOT NULL")" = "t" ]; then
      pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" -t "$t" > "$BACKUP_DIR/${t}.sql"
    fi
  fi
done
```

### 3.3 Backup структуры целевых таблиц (schema-only)

```bash
pg_dump -h "$PGHOST" -p "$PGPORT" -d "$PGDATABASE" --schema-only > "$BACKUP_DIR/schema_before_wave2.sql"
```

---

## 4) Staging execution (path-based, по одной миграции)

```bash
cd /var/www/bikube

php artisan migrate --force --path=database/migrations/2026_05_07_180000_create_order_events_table.php
php artisan migrate:status
php artisan route:list
bash scripts/bikube_os_smoke.sh

php artisan migrate --force --path=database/migrations/2026_05_07_183000_create_worker_applications_table.php
php artisan migrate:status
php artisan route:list
bash scripts/bikube_os_smoke.sh

php artisan migrate --force --path=database/migrations/2026_05_07_184000_create_contract_foundation_tables.php
php artisan migrate:status
php artisan route:list
bash scripts/bikube_os_smoke.sh
```

Критично: **не** использовать `php artisan migrate --force` без `--path`, чтобы случайно не применить `2026_04_23_000001...`.

---

## 5) Production execution

Те же команды, но только если:

1. staging миграции прошли;
2. staging smoke/UAT прошли;
3. есть явное подтверждение owner.

Последовательность в production должна полностью повторять staging.

---

## 6) Post-migration verification

### 6.1 Проверка таблиц

```bash
cd /var/www/bikube
php artisan migrate:status

psql -h 127.0.0.1 -p 5432 -d bikube -Atqc "SELECT to_regclass('public.order_events');"
psql -h 127.0.0.1 -p 5432 -d bikube -Atqc "SELECT to_regclass('public.worker_applications');"
psql -h 127.0.0.1 -p 5432 -d bikube -Atqc "SELECT to_regclass('public.contract_templates');"
psql -h 127.0.0.1 -p 5432 -d bikube -Atqc "SELECT to_regclass('public.contracts');"
psql -h 127.0.0.1 -p 5432 -d bikube -Atqc "SELECT to_regclass('public.contract_events');"
```

### 6.2 Проверка ключевых URL

Ожидания:

- `/category/delivery` -> `200`
- `/checkout/delivery.groceries` -> `200`
- `/admin/dispatch-center` -> `302 /admin/login` (без сессии) или `200` (под admin)
- `/admin/order-scenarios` -> `302 /admin/login` или `200`
- `/admin/landing-pages` -> `302 /admin/login` или `200`
- `/admin/contracts-registry` -> `302 /admin/login` или `200`
- `/partner` -> `302 /login` или `200` (под partner)
- `/account/classifieds` -> `302 /login` или `200` (под customer)
- `/orders/{id}/track` и `/account/orders/{id}/track` -> по текущему ACL (обычно redirect/login без auth)

Пример проверки:

```bash
/usr/bin/curl -k -s -o /dev/null -w "%{http_code}\n" https://136.119.84.22.nip.io/category/delivery
/usr/bin/curl -k -s -o /dev/null -w "%{http_code}\n" https://136.119.84.22.nip.io/checkout/delivery.groceries
/usr/bin/curl -k -s -o /dev/null -w "%{http_code}\n" https://136.119.84.22.nip.io/admin/dispatch-center
```

---

## 7) Rollback plan

Откат в обратном порядке:

```bash
cd /var/www/bikube

php artisan migrate:rollback --step=1 --path=database/migrations/2026_05_07_184000_create_contract_foundation_tables.php
php artisan migrate:rollback --step=1 --path=database/migrations/2026_05_07_183000_create_worker_applications_table.php
php artisan migrate:rollback --step=1 --path=database/migrations/2026_05_07_180000_create_order_events_table.php
```

Важное предупреждение:

- `migrate:rollback --path` в Laravel может вести себя неочевидно при batch-смешении;
- rollback обязательно прогнать и проверить на staging;
- если поведение rollback сомнительно, использовать manual SQL rollback только после backup и подтверждения owner/DBA.

---

## 8) Decision points

### T-30

- backup создан и проверен;
- `route:list` OK;
- smoke OK;
- активных деплоев/DDL задач нет.

### T-10

- staging migration OK;
- staging smoke OK;
- owner approval получен.

### T+10

- prod migration OK;
- smoke OK;
- admin login OK;
- `GET /api/v1/order-scenarios` OK.

---

## 9) Known risks

1. Случайный запуск pending migration `2026_04_23_000001...`.
2. Потенциальное дублирование контрактного домена (`contracts` vs `partner_contracts`).
3. Ручной UAT по ролям обязателен.
4. Реальные интеграции всё ещё на mock/foundation уровне (payments/e-sign/identity/SMS/maps/accounting).

---

## 10) `/become-worker` issue (read-only analysis)

Проверки:

```bash
php artisan route:list | grep become-worker
grep -R "become-worker" -n routes app resources
grep -R "WorkerOnboardingController" -n routes app
```

Факты:

- В `routes/web.php` есть:
  - `GET /become-worker -> WorkerOnboardingController@create`
  - `POST /become-worker -> WorkerOnboardingController@store`
- На route нет `auth` middleware.
- В `WorkerOnboardingController@create` прямой `return view('public.become-worker')`.
- Сейчас `HEAD /become-worker` возвращает `200` (публично), не `302`.

Вывод:

- Причина прежнего `302` не подтверждается текущей конфигурацией; вероятно, был проверен другой endpoint или временное состояние сессии/редиректа.
- По задаче это должен быть public onboarding entrypoint — текущее `GET` уже public и корректно.

Рекомендация без изменения кода:

1. `GET /become-worker` оставить публичным.
2. `POST /become-worker` оставить публичным, но обязательно держать:
   - strict validation (уже есть),
   - rate-limit middleware (рекомендуется добавить в следующей итерации),
   - anti-spam/honeypot/captcha (рекомендуется для production hardening).

---

## 11) Финальный вывод для релизного окна

### Можно ли идти в staging?

Да, можно. Runbook готов для staged path-based применения только Wave 2 migrations.

### Какие команды выполнять первыми

1. Pre-flight checks (раздел 2)
2. Backup (раздел 3)
3. Staging path-based migrations (раздел 4)

### Какие команды запрещены

- `php artisan migrate --force` без `--path`
- любые destructive команды без окна и подтверждения
- запуск `2026_04_23_000001_add_delivery_release_guardrails_and_observability.php` в этом окне

### Что подтвердить вручную

1. owner approval на production окно;
2. staging UAT по ролям (owner/admin/dispatcher/worker/partner/customer/guest);
3. post-migration route/smoke и вход в admin;
4. корректность `order-scenarios` API и ключевых public flows.

