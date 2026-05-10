# Wave 2 Staging Migration Checklist

## Scope
Run only:
- `2026_05_07_180000_create_order_events_table.php`
- `2026_05_07_183000_create_worker_applications_table.php`
- `2026_05_07_184000_create_contract_foundation_tables.php`

Do **not** run:
- `2026_04_23_000001_add_delivery_release_guardrails_and_observability.php`

## 1) Pre-flight
```bash
cd /var/www/bikube
php -v
php artisan about
php artisan migrate:status
php artisan route:list
bash scripts/bikube_os_smoke.sh
```

## 2) Backup
```bash
TS=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR=/var/backups/bikube/wave2-$TS
mkdir -p "$BACKUP_DIR"

pg_dump -Fc -t migrations bikube > "$BACKUP_DIR/migrations.dump"
pg_dump -Fc -t orders bikube > "$BACKUP_DIR/orders.dump"
pg_dump -Fc -t users bikube > "$BACKUP_DIR/users.dump"
pg_dump -Fc -t partners bikube > "$BACKUP_DIR/partners.dump"
pg_dump -Fc -t delivery_orders bikube > "$BACKUP_DIR/delivery_orders.dump"
pg_dump -Fc -t partner_contracts bikube > "$BACKUP_DIR/partner_contracts.dump"

pg_dump -Fc -t order_events bikube > "$BACKUP_DIR/order_events.dump" 2>/dev/null || true
pg_dump -Fc -t worker_applications bikube > "$BACKUP_DIR/worker_applications.dump" 2>/dev/null || true
pg_dump -Fc -t contract_templates bikube > "$BACKUP_DIR/contract_templates.dump" 2>/dev/null || true
pg_dump -Fc -t contracts bikube > "$BACKUP_DIR/contracts.dump" 2>/dev/null || true
pg_dump -Fc -t contract_events bikube > "$BACKUP_DIR/contract_events.dump" 2>/dev/null || true
```

## 3) Staging migration (path-based only)
```bash
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

## 4) Rollback (if needed)
```bash
# Test rollback on staging first.
php artisan migrate:rollback --path=database/migrations/2026_05_07_184000_create_contract_foundation_tables.php --step=1
php artisan migrate:rollback --path=database/migrations/2026_05_07_183000_create_worker_applications_table.php --step=1
php artisan migrate:rollback --path=database/migrations/2026_05_07_180000_create_order_events_table.php --step=1
```

If path rollback is inconsistent by batch, stop and restore from backup.

## 5) Smoke URLs
```bash
curl -k -I https://136.119.84.22.nip.io/category/delivery
curl -k -I https://136.119.84.22.nip.io/checkout/delivery.groceries
curl -k -I https://136.119.84.22.nip.io/admin/dispatch-center
curl -k -I https://136.119.84.22.nip.io/admin/order-scenarios
curl -k -I https://136.119.84.22.nip.io/admin/landing-pages
curl -k -I https://136.119.84.22.nip.io/admin/contracts-registry
curl -k -I https://136.119.84.22.nip.io/partner
curl -k -I https://136.119.84.22.nip.io/account/classifieds
```

## Forbidden
```bash
php artisan migrate --force
```
