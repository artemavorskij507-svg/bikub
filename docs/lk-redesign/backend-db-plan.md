# LK Backend + DB Optimization Plan (No-Code Change)

## Scope
- `app/Http/Controllers/Lk/*`
- Goal: safe production optimizations without behavioral changes.
- Focus: eager loading, cache strategy, DB indexes, guards against missing schema.

## Priority Summary
1. Reduce repeated aggregate queries in dashboard/wallet/assistant.
2. Eliminate N+1 in wallet/support/debug related data rendering.
3. Standardize cache keys + invalidation points.
4. Add targeted composite indexes for LK read paths.
5. Minimize repeated `Schema::hasTable/hasColumn` checks per request.

## Concrete Optimizations by File

### 1) `app/Http/Controllers/Lk/DashboardController.php`
- Eager-loading:
  - Keep existing `with(...)`, but narrow payload with column-scoped eager loading where possible (`order:id,order_number,user_id,address_id`, `user:id,name`, `address:id,address_line_1,city`).
- Query consolidation:
  - `todayEarnings` + `todayCompleted` already combined (good).
  - Roadside metrics currently run as separate queries; consider one prefiltered base query and clone for count/completed.
- Cache:
  - Current key `dashboard_data_user_{id}` (TTL 5s) is too generic.
  - Recommend key version: `lk:dashboard:v2:user:{id}:roles:{roles_hash}`.
  - Keep short TTL 5–15s for polling endpoint.
- Guards:
  - `workerStatus` may be `null` when `worker_statuses` table is absent but API branch reads `$workerStatus->is_online`; add safe fallback in plan (`false`).
- Indexes (high impact):
  - `orders (assigned_to, status, completed_at)`
  - `orders (assigned_to, status, created_at)`
  - `delivery_orders (courier_id, tracking_status)`
  - `handyman_assignments (executor_profile_id, status, created_at)`
  - `tasks (order_id, type, status)`

### 2) `app/Http/Controllers/Lk/OrderController.php`
- Eager-loading:
  - Existing dynamic relations are correct; add select-lists for heavy relations to reduce transfer.
- Query optimization:
  - `activeCount/completedCount/upcomingCount/roadsideCount` are separate queries.
  - Safe option: keep current behavior but cache counts for short TTL (10–30s) per user/filter.
- Guards:
  - `Schema::hasTable(...)` called repeatedly in helper methods; memoize table availability once per request (local array).
- Indexes:
  - `orders (assigned_to, status, scheduled_at)`
  - `orders (assigned_to, created_at)`
  - `order_items (order_id, service_type_id)`
  - `service_types (code)` and `service_types (category)`
  - `roadside_emergencies (order_id, status)`
  - `vehicle_inspection_requests (order_id, status)`

### 3) `app/Http/Controllers/Lk/WalletController.php`
- Biggest hot path.
- N+1 fix:
  - `recentOrders->map(...)` runs `Task::where(order_id=...)` per order. Replace with pre-aggregated payouts by `order_id` in one query and map in memory.
- Aggregation reuse:
  - `totalEarned` logic duplicated in `index()` and `requestPayout()`; centralize in service/query method and cache short-term.
- JSON filtering:
  - `JSON_EXTRACT(tasks.meta, '$.executor_user_id')` is expensive and DB-specific.
  - Prefer generated column/indexed column for `executor_user_id` if MySQL, or fallback relational link.
- Cache keys:
  - `lk:wallet:v1:user:{id}:summary`
  - `lk:wallet:v1:user:{id}:recent_orders`
  - Invalidate on payout create/status update, task completion, order completion.
- Indexes:
  - `tasks (order_id, status, type, payout_amount)`
  - `orders (id, assigned_to, status, completed_at)`
  - `payouts (user_id, status, created_at)`
  - Optional functional/generated index for `tasks.meta.executor_user_id`.

### 4) `app/Http/Controllers/Lk/ScheduleController.php`
- Query shape:
  - Three similar `whereHas('employees')` queries; acceptable, but can share base query and clone.
- Eager-loading:
  - `with(['zone','serviceType','employees'])` is valid; for list pages usually avoid loading full `employees` collection if only current employee is needed.
- Indexes:
  - `schedule_slots (start_at)`
  - `schedule_slots (end_at)`
  - Pivot `schedule_slot_employees (employee_id, schedule_slot_id)` and reverse `(schedule_slot_id, employee_id)`.

### 5) `app/Http/Controllers/Lk/ExecutorJobsController.php`
- Eager-loading:
  - Good baseline `with(['order','order.handymanDetails','repairProject'])`; add select-lists.
- Code-path duplication:
  - Auto-provision of executor profile duplicated in `index()` and `authorizeAssignment()`; move to shared helper/service.
- Indexes:
  - `handyman_assignments (executor_profile_id, status, created_at)`
  - `executor_profiles (user_id)` unique.

### 6) `app/Http/Controllers/Lk/RoadsideJobController.php`
- Query strategy:
  - Base query with `whereHas(order/helper)` + `with(...)` is okay.
  - If data volume grows, switch active/completed to pagination (active can stay small limit).
- Indexes:
  - `roadside_emergencies (status, created_at)`
  - `roadside_emergencies (road_helper_id, status)`
  - `orders (assigned_to)`
  - `road_helper_profiles (user_id)`

### 7) `app/Http/Controllers/Lk/RoadsideJobActionController.php`
- Transactionality: good.
- Guard optimization:
  - Authorization checks use `$job->order` and `$job->helper`; ensure eager load once before checks in high-load paths.
- Cache invalidation:
  - Existing forget keys are hardcoded and narrow. Align to namespaced keys:
    - `lk:dashboard:*:user:{id}`
    - `lk:roadside:*:user:{id}`

### 8) `app/Http/Controllers/Lk/OrderActionController.php`
- Eager-loading:
  - Response loads deep graph after mutation; use lightweight response DTO for action endpoint to reduce query count.
- Cache invalidation:
  - Extend invalidation beyond `active_orders_count` to dashboard/wallet counters when action changes completion/cancel state.
- Indexes:
  - `orders (assigned_to, status)`
  - `orders (status, completed_at)`

### 9) `app/Http/Controllers/Lk/SupportController.php`
- Eager-loading:
  - `show()` uses `messages.user` (good).
  - For `index()`, if UI shows author/meta snippets, preload minimal relations once.
- Indexes:
  - `support_tickets (user_id, status, created_at)`
  - `support_ticket_messages (support_ticket_id, created_at)`

### 10) `app/Http/Controllers/Lk/NotificationController.php`
- Guards: already defensive with `Schema::hasTable('notifications')`.
- Optimization:
  - For badge counters and lists, use dedicated cached counter key:
    - `lk:notifications:v1:user:{id}:unread_count`
  - Invalidate on mark-read/mark-all/delete.
- Indexes:
  - In `notifications`: `(notifiable_type, notifiable_id, read_at, created_at)`.

### 11) `app/Http/Controllers/Lk/AssistantController.php`
- Hotspot:
  - `generateSmartReply()` runs multiple count/sum queries per message.
- Safe optimization:
  - Cache short-lived assistant stats per user (10–30s):
    - `lk:assistant:v1:user:{id}:orders_stats`
    - `lk:assistant:v1:user:{id}:earnings_stats`
- Data correctness:
  - Verify model namespace mismatch (`DeliveryOrder` import) and status fields used in stats query.
- Indexes:
  - Reuse `orders (assigned_to, status, created_at, completed_at)` and delivery-order status index.

### 12) `app/Http/Controllers/Lk/ProfileController.php`
- Guard/cost:
  - Repeated `Schema::hasColumn()` checks in transaction are expensive.
  - Memoize column existence once per request, then reuse booleans.

### 13) `app/Http/Controllers/Lk/DebugController.php`
- Non-critical but can amplify load.
- Add optional feature flag + disable in prod or cache blocks for 10–30s.
- If kept enabled, preload only needed columns.

### 14) `app/Http/Controllers/Lk/WorkerStatusController.php`
- Performance:
  - Logging includes `$user->roles->pluck(...)`; ensure `roles` preloaded or replace with lightweight role names fetch only when debug level.
- Cache invalidation:
  - Invalidate dashboard cache key after status change:
    - `lk:dashboard:v2:user:{id}:*`
- Indexes:
  - `worker_statuses (user_id)` unique.

### 15) `app/Http/Controllers/Lk/SettingsController.php` and `SpecController.php`
- No DB-heavy optimizations required.

## Global Cache-Key Convention
- Use namespaced keys:
  - `lk:{domain}:v{n}:user:{id}:{context}`
- Recommended domains:
  - `dashboard`, `wallet`, `assistant`, `notifications`, `roadside`
- Invalidation events:
  - order status changed, roadside action handled, worker status toggled, payout requested/processed, notifications changed.

## Missing-Table / Missing-Column Guard Policy
- Keep guards for modular schema (`Schema::hasTable/hasColumn`).
- Avoid repetitive guard calls inside the same request:
  - Introduce request-local memoized schema map (array/static in controller or helper service).
- For optional modules, return stable empty collections/zero counters instead of null where API expects scalar values.

## Suggested Migration Backlog (Indexes Only)
- `orders`: `(assigned_to,status,created_at)`, `(assigned_to,status,completed_at)`, `(assigned_to,status,scheduled_at)`
- `tasks`: `(order_id,status,type,payout_amount)` + optional generated/functional index for `executor_user_id` from JSON
- `payouts`: `(user_id,status,created_at)`
- `delivery_orders`: `(courier_id,tracking_status)`
- `handyman_assignments`: `(executor_profile_id,status,created_at)`
- `roadside_emergencies`: `(status,created_at)`, `(road_helper_id,status)`, `(order_id,status)`
- `support_tickets`: `(user_id,status,created_at)`
- `support_ticket_messages`: `(support_ticket_id,created_at)`
- `schedule_slot_employees`: `(employee_id,schedule_slot_id)` and `(schedule_slot_id,employee_id)`
- `notifications`: `(notifiable_type,notifiable_id,read_at,created_at)`
- `worker_statuses`: unique `(user_id)`

## Rollout Plan (Low-Risk)
1. Add indexes (online/lock-minimizing migrations where supported).
2. Add cache keys + invalidation, keep old keys temporarily.
3. Remove N+1 in wallet recent orders.
4. Memoize schema checks.
5. Add query monitoring baseline (Laravel Telescope/slow query log) and compare p95 before/after.

## Expected Wins
- Lower DB round-trips on LK dashboard/wallet pages.
- Reduced CPU from JSON filters and repeated aggregates.
- More predictable polling load due to stable cache/invalidation.
- Better resilience in partially-migrated environments.
