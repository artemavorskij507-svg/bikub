# CODEBASE_REVIEW

- STATUS: IN PROGRESS
- Baseline inventory: `audit/_file_inventory.txt` (2266 files)
- Registry: `audit/CODEBASE_REVIEW_REGISTRY.csv`

## Status legend
- `REVIEWED`
- `CHANGED`
- `PENDING_REVIEW`
- `BLOCKED`
- `NOT_APPLICABLE`

## Top-priority files transitioned this cycle
| Path | Purpose | Final status in this cycle | Evidence |
|---|---|---|---|
| `routes/web.php` | public/client routes | REVIEWED | `audit/_critical_route_checks_node.json` |
| `routes/api.php` | public API routes | REVIEWED | `audit/_critical_route_checks_node.json` |
| `routes/api_ops.php` | ops API routes | REVIEWED (auth-path blocked) | `audit/_ops_authenticated_api_check_json_accept.json` |
| `app/Http/Controllers/PublicStorefrontController.php` | `/api/v1/public/*` catalog/slots | CHANGED (deployed + verified) | `php -l`, `audit/_critical_route_checks_node.json` |
| `app/Filament/Resources/OrderResource.php` | legacy orders admin resource | CHANGED (deployed + verified) | `php -l`, `audit/_admin_domain_modules_audit.json`, `audit/_orders_create_direct_check.json` |
| `app/Filament/Resources/OrderResource/Pages/ListOrders.php` | orders index actions | CHANGED (deployed + verified) | `php -l`, `audit/_admin_domain_modules_audit.json`, `audit/_orders_create_direct_check.json` |
| `app/Services/StripePaymentService.php` | Stripe intent/create/confirm/refund service | CHANGED (deployed + verified) | `php -l`, `audit/_payment_path_check_order16_20260422.json`, `audit/_payment_confirm_status_check_20260422.json` |
| `app/Http/Controllers/Api/OrderController.php` | payment intent/confirm API | CHANGED (deployed + verified) | `php -l`, `audit/_payment_intent_idempotency_order18_20260422.json`, `audit/_payment_confirm_contract_check_20260422.json`, `audit/_payment_confirm_idempotency_conflict_20260422.json` |
| `app/Http/Controllers/PublicStorefrontController.php` | public checkout + payment bootstrap | CHANGED (deployed + verified) | `php -l`, `audit/_payment_smoke_public_order_replay_20260422.json`, `audit/_payment_smoke_public_order_replay_after_fix_20260422.json` |
| `app/Domain/Ops/Queries/LiveJobDrawerQuery.php` | workbench drawer read model | REVIEWED | functional pages 200 in domain audit |
| `app/Domain/Ops/Queries/CandidateCompareQuery.php` | candidate compare read model | REVIEWED | module probe reached endpoints/pages |
| `app/Http/Controllers/Api/Ops/JobDrawerController.php` | drawer API controller | REVIEWED | auth probe + route mapping |
| `app/Console/Commands/OpsAuthenticatedApiMatrixCommand.php` | Sanctum role matrix read/write probe | CHANGED (deployed + server verified) | `audit/_ops_authenticated_api_matrix_server.json`, `audit/_ops_authenticated_api_matrix_cycle_20260422_after_cmd_fix.json` |
| `app/Http/Middleware/TrustHosts.php` | trusted host whitelist for production requests | CHANGED (deployed + server verified) | `audit/_trust_hosts_after_nipio_20260422.txt`, `audit/_ops_authenticated_api_matrix_cycle3_20260422.json` |
| `app/Console/Commands/OpsAdminPageRoleMatrixCommand.php` | admin UI role matrix (core workbench pages) | CHANGED (deployed + server verified) | `audit/_ops_admin_page_role_matrix_server.json` |
| `app/Filament/Pages/AiAgentTeamChat.php` | legacy/alias admin AI chat route page | CHANGED (deployed + 500 fixed) | server route probe + log recheck |
| `app/Console/Commands/OpsSmokeDispatchCommand.php` | runtime smoke command | CHANGED (HTTPS-safe local probe logic) | `audit/_ops-smoke-runtime-report-server-https.json` |
| `app/Http/Controllers/PublicStorefrontController.php` | public slots API contract | CHANGED (local cycle: explicit JSON 422 validation for `/api/v1/public/slots`) | `audit/_php_l_public_storefront_slots_fix_20260422_cycle4.txt`, `audit/_public_slots_no_date_before_fix_20260422_cycle4.txt` |
| `tests/Feature/Api/PublicSlotsContractTest.php` | API contract guard for public slots validation | CHANGED (added) | test file created; runtime execution blocked locally by PHP 8.2 vs required 8.4 |
| `app/Http/Controllers/VippsController.php` | Vipps payment init/callback/capture/refund contracts | CHANGED (fixed callback topology, config guard semantics, JSON validation contracts, auxiliary handlers) | `audit/_php_l_vipps_controller_cycle5_20260422.txt`, `audit/_vipps_init_runtime_cycle5_20260422.txt` |
| `routes/api.php` | public payment API routes | CHANGED (added `/payments/vipps/consent-removal` and `/payments/vipps/shipping-details`) | `audit/_php_l_routes_api_cycle5_20260422.txt` |
| `routes/web.php` | public web routes | CHANGED (added `/order/{orderId}/vipps/fallback`) | `audit/_php_l_routes_web_cycle5_20260422.txt` |
| `tests/Feature/Payments/VippsConfigGuardTest.php` | Vipps misconfig and callback contract tests | CHANGED (added) | `audit/_php_l_vipps_config_guard_test_cycle5_20260422.txt` |
| `app/Console/Commands/OpsSmokeDispatchCommand.php` | runtime smoke orchestration | CHANGED (added warn-only `runtime_vipps_readiness` check with missing-config detail + init probe) | `audit/_php_l_ops_smoke_dispatch_cycle6_20260422.txt`, `audit/_ops_smoke_vipps_readiness_refs_cycle6_20260422.txt` |
| `app/Console/Commands/OpsPaymentReadinessCommand.php` | dedicated payment provider readiness gate (Vipps/Stripe) | CHANGED (added) | `audit/_php_l_ops_payment_readiness_cycle7_20260422.txt`, `audit/_payment_readiness_refs_cycle7_20260422.txt` |
| `config/services.php` | payment provider configuration mapping | CHANGED (added canonical `services.vipps` + `services.stripe` env bindings) | `audit/_php_l_config_services_cycle8_20260422.txt`, `audit/_payment_readiness_refs_cycle8_20260422.txt` |
| `app/Console/Kernel.php` | scheduler | CHANGED (hourly `ops:payment-readiness` run with JSON output) | `audit/_php_l_console_kernel_cycle8_20260422.txt`, `audit/_payment_readiness_refs_cycle8_20260422.txt` |
| `tests/Feature/Payments/OpsPaymentReadinessCommandTest.php` | command acceptance test | CHANGED (added) | `audit/_php_l_ops_payment_readiness_test_cycle8_20260422.txt`, `audit/_payment_readiness_refs_cycle8_20260422.txt` |
| `app/Http/Controllers/Executor/ExecutorDashboardController.php` | executor cabinet dashboard + assignment actions | CHANGED (local fix added null-guard to prevent `/executor` 500 for non-executor accounts; deploy pending) | `audit/_critical_route_checks_cycle9_20260430.json`, `php -l` |
| `app/Models/User.php` | auth identity model + 2FA contract | CHANGED (added `hasTwoFactorEnabled()` + 2FA cast to align auth flow) | code review + `php -l` |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | login session flow + security notifications | CHANGED (normalized security login notification payload text, syntax validated) | `php -l` |

## Previously changed critical files (carried forward)
- `app/Policies/ServiceJobPolicy.php` (CHANGED)
- `app/Policies/ExecutorPolicy.php` (CHANGED)
- `app/Policies/OperationExceptionPolicy.php` (CHANGED)
- `app/Policies/SavedOpsFilterPolicy.php` (CHANGED)
- `app/Providers/AuthServiceProvider.php` (CHANGED)
- `app/Domain/Ops/Actions/ResolveOrganizationScopeAction.php` (CHANGED)
- `app/Http/Controllers/Api/Ops/SavedOpsFilterController.php` (CHANGED)
- `app/Http/Controllers/Api/Ops/ServiceJobController.php` (CHANGED)
- `app/Http/Controllers/Api/Ops/ExecutorController.php` (CHANGED)
- `app/Http/Controllers/Api/Ops/ExceptionController.php` (CHANGED)
- `app/Http/Controllers/Api/Concerns/ReturnsNotImplementedJson.php` (CHANGED)
- multiple stub API controllers migrated to safe 501 fallback (CHANGED)
- `app/Models/ScheduleSlot.php` (CHANGED)
- `app/Domain/Operations/Actions/NormalizeOrderToServiceJobAction.php` (CHANGED)
- SLA/listener bridge files (CHANGED)

## Payment path files reviewed this cycle
- `app/Http/Controllers/PublicStorefrontController.php`: REVIEWED+CHANGED (idempotency for public order creation).
- `app/Http/Controllers/Api/OrderController.php`: REVIEWED+CHANGED (idempotency for payment intent + confirm contract stabilization).
- `app/Services/StripePaymentService.php`: REVIEWED+CHANGED (fixed Stripe `Customer` return type namespace bug + confirm options support).
- `app/Http/Controllers/VippsController.php`: REVIEWED (behavior verified; external config required for live init/capture/refund).

## Review queue status
- Total files in registry: 2266
- Files in final status (`REVIEWED`/`CHANGED`/`BLOCKED`/`NOT_APPLICABLE`): partial
- Remaining `PENDING_REVIEW`: majority of low-priority/supporting files

## Blockers
1. No `.git` metadata in current workspace for commit-based diff evidence.
2. Local artisan runtime unavailable on workstation (`PHP 8.2` vs project `>=8.4`), verification runs server-side.
