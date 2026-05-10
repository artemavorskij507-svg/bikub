# ADMIN_MODULES_AUDIT

- STATUS: IN PROGRESS
- Last evidence refresh: 2026-04-22
- Evidence:
  - `audit/_admin_domain_modules_audit.json`
  - `audit/_admin_sidebar_links.json`
  - `audit/_admin_non_domain_functional_probe.json`
  - `audit/_ops_authenticated_api_matrix_server.json`
  - `audit/_ops_authenticated_api_matrix_cycle_20260422_after_cmd_fix.json`
  - `audit/_ops_admin_page_role_matrix_server.json`
  - `audit/_ops_admin_page_role_matrix_full_server.json`
  - `audit/_ops_admin_page_role_matrix_post_payment_confirm_20260422.json`
  - `audit/_ops_admin_page_role_matrix_cycle_20260422.json`
  - `audit/_ops_admin_page_role_matrix_cycle_20260422_after_perm_fix.json`
  - `audit/_ops_admin_page_role_matrix_cycle_20260422_final.json`
  - `audit/_laravel_tail_after_perm_and_matrix_fix_20260422.log`
  - `audit/_ops_smoke_runtime_cycle2_20260422.json`
  - `audit/_laravel_tail_cycle2_20260422.log`
  - `audit/_orders_create_direct_check.json`
  - `audit/_admin_500_regression_check_20260422.txt`
  - `audit/_admin_and_agent_chat_route_probe_20260422.txt`
  - `audit/_laravel_tail_after_https_hardening.txt`

## Domain module audit scope executed this cycle
- Operations
- Delivery
- Moving
- Handyman
- Roadside
- Eco
- Social Care

## Group Summary
| Group | Total modules checked | Bad | Status |
|---|---:|---:|---|
| operations | 8 | 0 | REVIEWED |
| delivery | 7 | 0 | REVIEWED |
| moving | 6 | 0 | REVIEWED |
| handyman | 7 | 0 | REVIEWED |
| roadside | 8 | 0 | REVIEWED |
| eco | 5 | 0 | REVIEWED |
| social_care | 7 | 0 | REVIEWED |

## Module-by-Module Functional Status
| Group | URL | List status | Create/status | Module status |
|---|---|---:|---|---|
| operations | `http://136.119.84.22/admin/operations-core` | 200 | n/a | REVIEWED |
| operations | `http://136.119.84.22/admin/service-jobs` | 200 | n/a | REVIEWED |
| operations | `http://136.119.84.22/admin/live-operations-map` | 200 | n/a | REVIEWED |
| operations | `http://136.119.84.22/admin/operation-exceptions` | 200 | n/a | REVIEWED |
| operations | `http://136.119.84.22/admin/executor-shifts` | 200 | 200 | REVIEWED |
| operations | `http://136.119.84.22/admin/executor-breaks` | 200 | 200 | REVIEWED |
| operations | `http://136.119.84.22/admin/dispatch-rule-sets` | 200 | 200 | REVIEWED |
| operations | `http://136.119.84.22/admin/dispatch-rule-preview` | 200 | n/a | REVIEWED |
| delivery | `http://136.119.84.22/admin/errand-tasks` | 200 | 200 | REVIEWED |
| delivery | `http://136.119.84.22/admin/restaurants` | 200 | 200 | REVIEWED |
| delivery | `http://136.119.84.22/admin/retail-stores` | 200 | 200 | REVIEWED |
| delivery | `http://136.119.84.22/admin/delivery/delivery-orders` | 200 | 200 | REVIEWED |
| delivery | `http://136.119.84.22/admin/orders` | 200 | n/a | REVIEWED |
| delivery | `http://136.119.84.22/admin/errand-order-details` | 200 | n/a | REVIEWED |
| delivery | `http://136.119.84.22/admin/delivery-zones` | 200 | 200 | REVIEWED |
| moving | `http://136.119.84.22/admin/moving/executor-profiles` | 200 | 200 | REVIEWED |
| moving | `http://136.119.84.22/admin/moving/moving-items` | 200 | 200 | REVIEWED |
| moving | `http://136.119.84.22/admin/moving/moving-orders` | 200 | 200 | REVIEWED |
| moving | `http://136.119.84.22/admin/moving/teams` | 200 | 200 | REVIEWED |
| moving | `http://136.119.84.22/admin/moving/moving-order-photos` | 200 | 200 | REVIEWED |
| moving | `http://136.119.84.22/admin/moving/moving-order-tasks` | 200 | 200 | REVIEWED |
| handyman | `http://136.119.84.22/admin/claims` | 200 | 200 | REVIEWED |
| handyman | `http://136.119.84.22/admin/handyman-assignments` | 200 | 200 | REVIEWED |
| handyman | `http://136.119.84.22/admin/handyman-materials-entries` | 200 | 200 | REVIEWED |
| handyman | `http://136.119.84.22/admin/repair-projects` | 200 | 200 | REVIEWED |
| handyman | `http://136.119.84.22/admin/repair-stages` | 200 | 200 | REVIEWED |
| handyman | `http://136.119.84.22/admin/repair-team-members` | 200 | 200 | REVIEWED |
| handyman | `http://136.119.84.22/admin/work-warranties` | 200 | 200 | REVIEWED |
| roadside | `http://136.119.84.22/admin/roadside-dashboard` | 200 | n/a | REVIEWED |
| roadside | `http://136.119.84.22/admin/roadside-dispatch-board` | 200 | n/a | REVIEWED |
| roadside | `http://136.119.84.22/admin/roadside-presets` | 200 | 200 | REVIEWED |
| roadside | `http://136.119.84.22/admin/road-helper-profiles` | 200 | 200 | REVIEWED |
| roadside | `http://136.119.84.22/admin/vehicle-inspection-requests` | 200 | 200 | REVIEWED |
| roadside | `http://136.119.84.22/admin/roadside-partners` | 200 | 200 | REVIEWED |
| roadside | `http://136.119.84.22/admin/vehicle-inspection-presets` | 200 | 200 | REVIEWED |
| roadside | `http://136.119.84.22/admin/roadside-emergencies` | 200 | 200 | REVIEWED |
| eco | `http://136.119.84.22/admin/disposal-items` | 200 | 200 | REVIEWED |
| eco | `http://136.119.84.22/admin/disposal-partners` | 200 | 200 | REVIEWED |
| eco | `http://136.119.84.22/admin/eco-certificates` | 200 | n/a | REVIEWED |
| eco | `http://136.119.84.22/admin/eco-teams` | 200 | 200 | REVIEWED |
| eco | `http://136.119.84.22/admin/eco-disposal-dashboard` | 200 | n/a | REVIEWED |
| social_care | `http://136.119.84.22/admin/analitika-social-care` | 200 | n/a | REVIEWED |
| social_care | `http://136.119.84.22/admin/community-points-balances` | 200 | n/a | REVIEWED |
| social_care | `http://136.119.84.22/admin/care-plans` | 200 | 200 | REVIEWED |
| social_care | `http://136.119.84.22/admin/social-helper-profiles` | 200 | 200 | REVIEWED |
| social_care | `http://136.119.84.22/admin/pultkoordinatora-social-care` | 200 | n/a | REVIEWED |
| social_care | `http://136.119.84.22/admin/social-care-orders` | 200 | n/a | REVIEWED |
| social_care | `http://136.119.84.22/admin/care-services` | 200 | 200 | REVIEWED |

## Open module defects
- No active 500 in audited 48-module domain set after current deployment cycle.
- `/admin/command-center-a-i-agent-team-chat` 500 was fixed in current cycle by correcting page binding.
- 2026-04-22 stabilization defect fixed: `storage/framework/views` permission drift caused mass 500 during authenticated module probes; ownership/permission repair removed all 5xx in final matrix.

## Non-domain admin functional probe (this cycle)
- Scope: 24 modules outside the 7 domain groups (core admin/system/security/content/support).
- Evidence: `audit/_admin_non_domain_functional_probe.json`
- Result:
  - List pages: `24/24` returned `200`
  - Create pages: `20/24` returned `200`, `4/24` returned expected `404` (no create screen by design)
  - `500`: `0`

## Applied fix in this cycle (deployed)
- `app/Filament/Resources/OrderResource.php`
  - `canCreate()` forced `false`.
  - create page route removed from `getPages()`.
  - safe `resolveRecordRouteBinding()` for non-numeric keys (`create` -> 404, no bigint SQL error).
  - removed unsupported `Select::colors()` / `Select::icons()` from form select.
- `app/Filament/Resources/OrderResource/Pages/ListOrders.php`
  - `CreateAction` removed to prevent broken navigation.

## Remaining to close module DoD
1. Expand role-based UAT from current 7 core workbench pages to full admin module list.
2. Write actions audit (bulk/transition actions, not only list/create-open).
3. Validate form submit lifecycle on representative modules per domain.
4. Validate payment settings -> runtime payment APIs alignment (Stripe/Vipps) under production config.

## Payment module/runtime cross-check (this cycle)
- Payment API critical paths were tested against server runtime:
  - `POST /api/v1/public/orders` idempotency replay fixed.
  - `POST /api/v1/orders/{id}/payment/intent` 500 fixed + idempotent replay added.
  - `POST /api/v1/orders/{id}/payment/confirm` contract stabilized (explicit 422/409 behavior, no 500).
  - `POST /api/v1/payments/vipps/init` still requires external credentials configuration.

## Role matrix evidence (API layer)
- `admin` and `ops_admin` completed `manual_dispatch` + `manual_reassign` + exception `ack/resolve` with 200 under fresh versions.
- `ops_manager` denied dispatch/reassign (403) but allowed exception ack/resolve (200).
- `ops_rules_admin` read diagnostics endpoints 200; write actions 403.
- Source: `audit/_ops_authenticated_api_matrix_server.json`.

## Role matrix evidence (UI core pages)
- Command: `php artisan ops:admin-page-role-matrix --host=136.119.84.22`
- Source: `audit/_ops_admin_page_role_matrix_server.json`
- Result:
  - `admin`: all 7 pages -> 200
  - `ops_admin`: all 7 pages -> 200
  - `ops_manager`: all 7 pages -> 200
  - `ops_rules_admin`: all 7 pages -> 200

## Full admin sidebar route probe (89 pages)
- Source list: `audit/_admin_sidebar_links.json`
- Evidence report: `audit/_ops_admin_page_role_matrix_full_server.json`

### Aggregate by role
| Role | Total pages | 200 | 403 | 302 | 500 |
|---|---:|---:|---:|---:|---:|
| admin | 89 | 87 | 0 | 2 | 0 |
| ops_admin | 89 | 80 | 7 | 2 | 0 |
| ops_manager | 89 | 80 | 7 | 2 | 0 |
| ops_rules_admin | 89 | 80 | 7 | 2 | 0 |

### Latest rerun after payment-confirm contract changes
- Evidence: `audit/_ops_admin_page_role_matrix_post_payment_confirm_20260422.json`
- Summary:
  - total users in matrix: `5`
  - `fail=0` (no 5xx)
  - expected `warn` due role-based `403` and alias redirects `302`.

### Latest rerun after permission + matrix-command fixes
- Evidence:
  - `audit/_ops_admin_page_role_matrix_cycle_20260422_final.json`
  - `audit/_ops_authenticated_api_matrix_cycle_20260422_after_cmd_fix.json`
- Summary:
  - admin matrix: `fail=0`, role-expected warnings only.
  - authenticated API matrix: `ok=2`, `warn=2`, `fail=0`, `missing_user=1`.

### Continuation rerun (2026-04-22 cycle2)
- Runtime smoke kept green after P0 gate checks:
  - `10 PASS / 1 WARN / 0 FAIL`
  - no new `production.ERROR` in latest tail.

### Non-200 paths observed
- Redirect aliases (`302`) for all roles:
  - `/admin/admin-i-p-rules` -> `/admin/admin-ip-rules`
  - `/admin/a-p-i-keys-management` -> `/admin/api-keys`
- Role-limited (`403`) for non-admin roles:
  - `/admin/tasks`
  - `/admin/work-specifications`
  - `/admin/analytics-orders`
  - `/admin/delivery/delivery-orders`
  - `/admin/orders`
  - `/admin/claims`
  - `/admin/social-care-orders`

No 500 detected in the 89-page matrix run.
