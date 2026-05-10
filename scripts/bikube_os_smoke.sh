#!/usr/bin/env bash
set -euo pipefail

php artisan route:list >/tmp/bikube_route_list.txt
php -v >/tmp/bikube_php_version.txt
php artisan about >/tmp/bikube_about.txt
grep -q "checkout/{scenario}" /tmp/bikube_route_list.txt
grep -q "api/v1/order-scenarios" /tmp/bikube_route_list.txt
grep -q "orders/{order}/track" /tmp/bikube_route_list.txt
grep -q "api/v1/payments/{order}/manual-reserve" /tmp/bikube_route_list.txt
grep -q "partner/orders/{order}/status" /tmp/bikube_route_list.txt
grep -q "become-worker" /tmp/bikube_route_list.txt
grep -q "admin/dispatch-center" /tmp/bikube_route_list.txt

php -l app/Services/Orders/OrderScenarioRegistry.php
php -l app/Services/Orders/UnifiedOrderEngine.php
php -l app/Services/Orders/OrderLifecycleService.php
php -l app/Services/Payments/PaymentEngine.php
php -l app/Filament/Pages/DispatchCenter.php
php -l app/Filament/Pages/OrderScenarios.php
php -l app/Filament/Pages/LandingPages.php
php -l app/Filament/Pages/ContractsRegistry.php
php -l app/Filament/Widgets/OpsOverviewStats.php
php -l app/Http/Controllers/Lk/OrderActionController.php
php -l app/Http/Controllers/PartnerPortalWebController.php

echo "Bikube OS smoke checks passed."
