# ROUTES_AUDIT

- STATUS: IN PROGRESS
- Last evidence refresh: 2026-04-22
- Source evidence:
  - `audit/_routes_raw.txt`
  - `audit/ROUTES_INVENTORY.csv`
  - `audit/_critical_route_checks_node.json`
  - `audit/_critical_route_checks_https_node.json`
  - `audit/_ops_authenticated_api_check.json`
  - `audit/_ops_authenticated_api_check_json_accept.json`
  - `audit/_ops_authenticated_api_matrix_server.json`
  - `audit/_payment_smoke_public_order_replay_20260422.json`
  - `audit/_payment_smoke_public_order_replay_after_fix_20260422.json`
  - `audit/_payment_path_check_order16_20260422.json`
  - `audit/_payment_intent_idempotency_order18_20260422.json`
  - `audit/_payment_vipps_fallback_20260422.json`
  - `audit/_payment_confirm_contract_check_20260422.json`
  - `audit/_payment_confirm_status_check_20260422.json`
  - `audit/_payment_confirm_idempotency_conflict_20260422.json`
  - `audit/_ops_admin_page_role_matrix_server.json`
  - `audit/_ops_admin_page_role_matrix_post_payment_confirm_20260422.json`
  - `audit/_ops_admin_page_role_matrix_cycle_20260422.json`
  - `audit/_ops_admin_page_role_matrix_cycle_20260422_after_perm_fix.json`
  - `audit/_ops_admin_page_role_matrix_cycle_20260422_final.json`
  - `audit/_ops_authenticated_api_matrix_cycle_20260422.json`
  - `audit/_ops_authenticated_api_matrix_cycle_20260422_after_perm_fix.json`
  - `audit/_ops_authenticated_api_matrix_cycle_20260422_after_cmd_fix.json`
  - `audit/_ops_smoke_runtime_cycle_20260422.json`
  - `audit/_ops_smoke_runtime_cycle_20260422_after_perm_fix.json`
  - `audit/_ops_smoke_runtime_cycle_20260422_final.json`
  - `audit/_laravel_tail_after_perm_and_matrix_fix_20260422.log`
  - `audit/_tls_cert_chain_server_20260422_cycle2.txt`
  - `audit/_nginx_server_block_tls_20260422_cycle2.txt`
  - `audit/_dns_a_records_server_20260422_cycle2.txt`
  - `audit/_tls_strict_verify_client_20260422_cycle2.txt`
  - `audit/_payment_env_flags_20260422_cycle2.txt`
  - `audit/_vipps_init_probe_20260422_cycle2.json`
  - `audit/_ops_smoke_runtime_cycle2_20260422.json`
  - `audit/_laravel_tail_cycle2_20260422.log`
  - `audit/_nginx_bikube_after_certbot_20260422.txt`
  - `audit/_env_app_url_after_nipio_20260422.txt`
  - `audit/_https_headers_nipio_server_20260422.txt`
  - `audit/_https_headers_nipio_client_20260422.txt`
  - `audit/_https_strict_verify_nipio_client_20260422_postswitch.txt`
  - `audit/_https_strict_verify_ip_client_20260422_postswitch.txt`
  - `audit/_http_redirect_ip_to_nipio_20260422.txt`
  - `audit/_ops_authenticated_api_matrix_cycle3_20260422.json`
  - `audit/_ops_admin_page_role_matrix_cycle3_20260422.json`
  - `audit/_ops_smoke_runtime_cycle3_20260422.json`
  - `audit/_laravel_tail_cycle3_20260422.log`
  - `audit/_public_slots_no_date_before_fix_20260422_cycle4.txt`
  - `audit/_public_slots_with_date_20260422_cycle4.txt`
  - `audit/_vipps_init_runtime_cycle5_20260422.txt`
  - `audit/_vipps_shipping_runtime_cycle5_20260422.txt`
  - `audit/_vipps_consent_runtime_cycle5_20260422.txt`
  - `audit/_vipps_callback_runtime_cycle5_20260422.txt`
  - `audit/_php_l_vipps_controller_cycle5_20260422.txt`
  - `audit/_php_l_ops_smoke_dispatch_cycle6_20260422.txt`
  - `audit/_ops_smoke_vipps_readiness_refs_cycle6_20260422.txt`
  - `audit/_orders_create_direct_check.json`
  - `audit/_admin_500_regression_check_20260422.txt`
  - `audit/_admin_and_agent_chat_route_probe_20260422.txt`
  - `audit/_http_root_headers_20260422_after_https.txt`
  - `audit/_https_root_headers_20260422_after_https.txt`
  - `audit/_https_strict_verify_20260422.txt`
  - `audit/_tls_cert_server_20260422.txt`
  - `audit/_tls_dns_ip_lookup_20260422.txt`
  - `audit/_tls_dns_ptr_host_lookup_20260422.txt`
  - `audit/_tls_dns_glf_no_20260422.txt`
  - `audit/_tls_dns_bikube_no_20260422.txt`
  - `audit/_ops-smoke-runtime-report-server-https.json`
  - `audit/_ops-smoke-runtime-report-server-post-confirm-contract.json`
  - `audit/_critical_route_checks_cycle9_20260430.json`
  - `audit/_admin_module_probe_cycle9_20260430.json`

## Cycle 9 regression check (2026-04-30)
- Canonical host probe: `https://136.119.84.22.nip.io`.
- Guest critical paths: `/`, `/login`, `/register`, `/category/delivery`, `/admin/login` all `200`; auth-protected paths redirect as expected.
- Authenticated (`keks@glf.no`) checks:
  - `/account` -> `200`
  - `/lk` -> `200`
  - `/admin/live-operations-map` -> `200`
  - `/executor` -> `500` (**new high-severity regression under user context**)
- Admin module probe (logged-in admin): 89 `/admin/*` pages returned HTTP `200` in crawl sample; no fresh Whoops stack traces in sampled pages.

## Transport update (2026-04-22)
- HTTP root now returns `301` to HTTPS.
- HTTPS endpoint is live and serves app responses.
- Runtime smoke remains green after HTTPS switch (`10 PASS / 1 WARN / 0 FAIL`).
- Note: current cert is temporary/untrusted for strict TLS clients; CA-trusted cert remains open.

## Transport update (2026-04-22, cycle3 trusted CA activation)
- Let's Encrypt certificate issued and deployed for `136.119.84.22.nip.io`.
- `APP_URL` switched to `https://136.119.84.22.nip.io`.
- Strict client verify on canonical URL succeeds (no `-k`).
- HTTP IP entrypoint still redirects to HTTPS; canonical host is now nip.io.
- Runtime smoke remains green (`10 PASS / 1 WARN / 0 FAIL`).

## Post-HTTPS critical route probe (35 routes)
- HTTPS probe evidence: `audit/_critical_route_checks_https_node.json`
- Summary:
  - `200`: 16
  - `302`: 19
  - `5xx`: 0
  - `ERR`: 0

## Route Files Inventory
| File | Coverage status |
|---|---|
| `routes/web.php` | REVIEWED (critical public paths validated live) |
| `routes/api.php` | REVIEWED (critical `/api/v1/*` paths validated live) |
| `routes/api_ops.php` | REVIEWED (guest + JSON-auth probes executed) |
| `routes/admin.php` | REVIEWED (admin route family sanity-checked) |
| `routes/auth.php` | REVIEWED (redirect/login behavior validated) |
| `routes/channels.php` | PENDING_DEEP |
| `routes/api-virtual-office.php` | PENDING_DEEP |
| `routes/web-virtual-office.php` | PENDING_DEEP |
| `routes/agency-agents.php` | PENDING_DEEP |
| `routes/agency_hub.php` | PENDING_DEEP |
| `routes/api_agency_agents.php` | PENDING_DEEP |
| `routes/logistics-api.php` | PENDING_DEEP |
| `routes/console.php` | NOT_APPLICABLE (console-only) |

## Critical Route-by-Route Check (35 routes)
| URL | HTTP | Redirect | Status |
|---|---:|---|---|
| `http://136.119.84.22/` | 200 | - | REVIEWED |
| `http://136.119.84.22/category/delivery` | 200 | - | REVIEWED |
| `http://136.119.84.22/category/moving` | 200 | - | REVIEWED |
| `http://136.119.84.22/category/handyman` | 200 | - | REVIEWED |
| `http://136.119.84.22/category/eco` | 200 | - | REVIEWED |
| `http://136.119.84.22/category/personal-task` | 200 | - | REVIEWED |
| `http://136.119.84.22/category/tow` | 200 | - | REVIEWED |
| `http://136.119.84.22/classifieds` | 200 | - | REVIEWED |
| `http://136.119.84.22/catalog` | 200 | - | REVIEWED |
| `http://136.119.84.22/stores` | 302 | `http://136.119.84.22/catalog` | REVIEWED |
| `http://136.119.84.22/restaurants` | 302 | `http://136.119.84.22/catalog` | REVIEWED |
| `http://136.119.84.22/account` | 302 | `http://136.119.84.22/login` | REVIEWED |
| `http://136.119.84.22/account/orders` | 302 | `http://136.119.84.22/login` | REVIEWED |
| `http://136.119.84.22/account/profile` | 302 | `http://136.119.84.22/login` | REVIEWED |
| `http://136.119.84.22/lk` | 302 | `http://136.119.84.22/login` | REVIEWED |
| `http://136.119.84.22/lk/orders` | 302 | `http://136.119.84.22/login` | REVIEWED |
| `http://136.119.84.22/lk/support` | 302 | `http://136.119.84.22/login` | REVIEWED |
| `http://136.119.84.22/admin` | 302 | `http://136.119.84.22/admin/login` | REVIEWED |
| `http://136.119.84.22/admin/login` | 200 | - | REVIEWED |
| `http://136.119.84.22/api/v1/health` | 200 | - | REVIEWED |
| `http://136.119.84.22/api/v1/categories` | 200 | - | REVIEWED |
| `http://136.119.84.22/api/v1/service-types` | 200 | - | REVIEWED |
| `http://136.119.84.22/api/v1/restaurants` | 200 | - | REVIEWED |
| `http://136.119.84.22/api/v1/stores` | 200 | - | REVIEWED |
| `http://136.119.84.22/api/v1/public/catalog` | 200 | - | REVIEWED |
| `http://136.119.84.22/api/v1/public/slots` | 302 | `http://136.119.84.22` | REVIEWED (behavior suspicious) |
| `http://136.119.84.22/api/ops/map/live` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/jobs` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/executors` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/exceptions` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/workbench/triage` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/workbench/saved-filters` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/workbench/replan-recommendations` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/workbench/routing-shadow-metrics` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |
| `http://136.119.84.22/api/ops/workbench/routing-provider-health` | 302 | `http://136.119.84.22/login` | REVIEWED (guest redirect) |

## Authenticated Ops API Matrix (Sanctum, read + write)
Dedicated command with token auth and fresh write probes is deployed:
- `app/Console/Commands/OpsAuthenticatedApiMatrixCommand.php`
- Evidence:
  - `audit/_ops_authenticated_api_matrix_server.json`
  - `audit/_ops_authenticated_api_matrix_cycle_20260422_after_cmd_fix.json`

### Matrix result summary
| Role/account | Read endpoints | Write probes | Result |
|---|---|---|---|
| `admin` (`keks@glf.no`) | `18x200`, `0x5xx` | `manual_dispatch=200`, `manual_reassign=200`, `ack=200`, `resolve=200` | REVIEWED |
| `ops_admin` (`oleksandr@glf.no`) | `18x200`, `0x5xx` | `manual_dispatch=200`, `manual_reassign=200`, `ack=200`, `resolve=200` | REVIEWED |
| `ops_manager` (`maria@glf.no`) | `16x200`, `2x403`, `0x5xx` | dispatch/reassign policy-limited, ack/resolve allowed | REVIEWED (policy-limited write) |
| `ops_rules_admin` (`eva.nystad@glf.no`) | `13x200`, `5x403`, `0x5xx` | write actions policy-limited | REVIEWED (read-only diagnostics role) |

### Notes
- No `401/419/5xx` in matrix run.
- `keks@gfl.no` is missing user (typo variant), excluded from role closure.
- 2026-04-22 rerun note: initial run produced false `FAIL` due command transport defaults (`https://127.0.0.1` TLS mismatch); fixed in command and rerun now `fail=0`.

## Server-side authenticated secured path check
Runtime smoke includes authenticated secured endpoint checks and currently returns 200 for critical Ops APIs.

- Evidence: `audit/_ops-smoke-runtime-report-server.json` (`runtime_authenticated_secured_paths`)
- Key statuses:
  - `/api/ops/map/live` -> 200
  - `/api/ops/jobs/{job}/drawer` -> 200
  - `/api/ops/jobs/{job}/candidate-compare` -> 200
  - `/api/ops/workbench/triage` -> 200
  - `/api/ops/workbench/saved-filters` (GET/POST) -> 200

## Open Route Defects
1. `BUG-PUBLIC-API-003`: `/api/v1/public/slots` missing-date path currently returns `302`; code fix prepared to enforce JSON `422`, pending server deployment verification.
2. `BUG-PAY-004`: `/api/v1/payments/vipps/init` unavailable until Vipps credentials are configured.
3. Vipps auxiliary endpoints (`/payments/vipps/shipping-details`, `/payments/vipps/consent-removal`) and fallback route are fixed in code but currently 404 on production until deploy.
4. Runtime observability now includes `runtime_vipps_readiness` warn-only check (missing config keys + init probe), so P0 payment gate degradation is explicitly visible in smoke reports.
5. Dedicated acceptance command `ops:payment-readiness` added for deterministic payment gate verification (config + probes + JSON report).
6. `services.vipps` / `services.stripe` config mappings are now explicit in `config/services.php`, removing hidden config drift for payment readiness checks.
3. `BUG-SEC-003`: direct-IP HTTPS remains non-canonical (expected strict-verify failure); use trusted hostname entrypoint.

## P0 gate re-check (2026-04-22 cycle2)
- TLS trusted-chain gate:
  - cert subject/issuer is self-signed (`CN=instance-20260405-001420`), SAN internal-only.
  - strict client verify still fails (`SEC_E_UNTRUSTED_ROOT`).
  - domain A-records do not point to current server IP (`glf.no`/`bikube.no` mismatch).
- Vipps gate:
  - `/api/v1/payments/vipps/init` still returns `{"success":false,"message":"Vipps is not configured"}`.

## Payment Critical Path (P0 #3) - this cycle
| Route | Check | Result | Status |
|---|---|---|---|
| `POST /api/v1/public/orders` | replay with same `X-Idempotency-Key` before fix | created two orders (`order_count_for_email=2`) | DEFECT FOUND (`BUG-PAY-001`) |
| `POST /api/v1/public/orders` | replay with same `X-Idempotency-Key` after fix | same `order_id` returned, `order_count_for_email=1` | REVIEWED + FIXED |
| `POST /api/v1/orders/{id}/payment/intent` | direct call before fix | 500 HTML server error | DEFECT FOUND (`BUG-PAY-002`) |
| `POST /api/v1/orders/{id}/payment/intent` | after `StripePaymentService` fix | 200 JSON with intent payload | REVIEWED + FIXED |
| `POST /api/v1/orders/{id}/payment/intent` | replay with same `X-Idempotency-Key` after idempotency patch | same `payment_intent_id` returned (`same_intent=true`) | REVIEWED + FIXED |
| `POST /api/v1/orders/{id}/payment/confirm` | no extra confirm params | `422` with actionable `requires_action` payload (no 500) | REVIEWED + FIXED |
| `POST /api/v1/orders/{id}/payment/confirm` | replay with same `X-Idempotency-Key` | same response body on repeat call | REVIEWED + FIXED |
| `POST /api/v1/orders/{id}/payment/confirm` | same key + different payload | second call returns `409 Idempotency key ... different payload` | REVIEWED + FIXED |
| `POST /api/v1/payments/vipps/init` | init on current server config | graceful JSON error `Vipps is not configured` (no 500 HTML) | REVIEWED (external config gap) |
| `POST /api/v1/public/orders` with `payment_provider=vipps` | fallback path | order created successfully with `payment_status=ready` and vipps placeholder payment URL | REVIEWED |

## Required Follow-up to close file
1. Deploy and verify implemented contract fix for `/api/v1/public/slots` (missing `date` must return JSON `422`, no redirect).
2. Expand from API matrix to full UI role UAT across admin/workbench pages.
3. Re-baseline full critical route table under HTTPS URLs after transport switch.
4. Replace temporary TLS certificate with trusted CA chain and re-probe strict TLS (currently blocked by IP-only endpoint).

## Targeted authenticated regression checks
| URL | Expected | Actual | Result |
|---|---|---:|---|
| `http://136.119.84.22/admin/orders/create` | no 500 (route disabled or denied) | 404 | REVIEWED (fixed regression) |
| `https://136.119.84.22/admin/command-center-a-i-agent-team-chat` | auth redirect or page render; no 500 | 302 -> `/admin/login` (guest) / 200 (authenticated) | REVIEWED (fixed regression) |

## Admin UI role matrix (core workbench pages)
- Evidence: `audit/_ops_admin_page_role_matrix_server.json`
- Roles checked: `admin`, `ops_admin`, `ops_manager`, `ops_rules_admin` (+ missing alias `keks@gfl.no`)
- Pages:
  - `/admin/live-operations-map`
  - `/admin/service-jobs`
  - `/admin/operation-exceptions`
  - `/admin/executor-shifts`
  - `/admin/executor-breaks`
  - `/admin/dispatch-rule-sets`
  - `/admin/dispatch-rule-preview`
- Result for existing target accounts: all `200`, no 5xx.

## Admin sidebar route-by-route matrix (expanded)
- Evidence: `audit/_ops_admin_page_role_matrix_full_server.json`
- Scope: 89 `/admin/*` routes from sidebar inventory.
- Result summary:
  - admin: `87x200`, `2x302`, `0x500`
  - ops_admin: `80x200`, `7x403`, `2x302`, `0x500`
  - ops_manager: `80x200`, `7x403`, `2x302`, `0x500`
  - ops_rules_admin: `80x200`, `7x403`, `2x302`, `0x500`
- Notable role-limited pages (403 for non-admin in this run):
  - `/admin/tasks`
  - `/admin/work-specifications`
  - `/admin/analytics-orders`
  - `/admin/delivery/delivery-orders`
  - `/admin/orders`
  - `/admin/claims`
  - `/admin/social-care-orders`

### Post-payment-confirm rerun
- Evidence: `audit/_ops_admin_page_role_matrix_post_payment_confirm_20260422.json`
- Result: `fail=0` (no 5xx), role-limited pages remain expected `403` for non-admin roles.

### Stabilization rerun (2026-04-22)
- First pass (`audit/_ops_admin_page_role_matrix_cycle_20260422.json`) showed widespread `500` due server file-permission drift in `storage/framework/views`.
- After permission repair + cache clear:
  - `audit/_ops_admin_page_role_matrix_cycle_20260422_after_perm_fix.json`
  - `audit/_ops_admin_page_role_matrix_cycle_20260422_final.json`
- Final result:
  - `fail=0`, `warn=4`, `missing_user=1`
  - admin `200=87`, non-admin roles `200=80`, role-expected `403=7`, aliases `302=2`.


