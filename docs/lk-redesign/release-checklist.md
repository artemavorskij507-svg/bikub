# /lk Redesign Release Checklist

Updated: 2026-04-03 (Europe/Kiev)
Role: QA Lead (API tester + Evidence collector + Reality checker + Accessibility)

## 1. Release Gate (Go/No-Go)
- [ ] UAT build deployed to staging and mapped to current release tag/commit.
- [ ] `/lk` opens for authenticated user without 5xx/JS critical errors.
- [ ] Core `/lk` navigation works: dashboard, profile, orders/history, notifications, billing (if enabled).
- [ ] Backward compatibility checked for deep links/bookmarks into old `/lk` routes.
- [ ] Error budget not exceeded during smoke window (no P0/P1 open defects).
- [ ] Rollback plan validated (previous stable artifact + DB compatibility note).

## 2. Smoke Tests (UI + API)

### 2.1 Auth & Session
- [ ] Login -> redirect to `/lk` works.
- [ ] Session persists after refresh/new tab.
- [ ] Logout invalidates session and blocks protected `/lk` routes.
- [ ] Expired session returns expected UX (login prompt, no broken state).

### 2.2 Core User Journeys
- [ ] Open `/lk` dashboard and verify data widgets render.
- [ ] Profile read/update flow works and persists after refresh.
- [ ] Orders list opens, details view works, statuses look consistent.
- [ ] Notifications list loads; mark-as-read (single/all) behaves correctly.
- [ ] Billing summary and transactions load (or feature-flag fallback shown).

### 2.3 API Contract Smoke (for /lk data)
- [ ] `GET /api/account/dashboard` returns 200 + expected JSON keys.
- [ ] `GET /api/account/orders` returns paginated/expected list structure.
- [ ] `GET /api/account/notifications/list` returns stable schema.
- [ ] `POST /api/account/notifications/mark-read` validates payload and updates state.
- [ ] `GET /api/account/billing/summary` returns consistent totals/types.
- [ ] Unauthorized requests to `/api/account/*` return 401/403 (no data leakage).

### 2.4 Reality Checks (Data Integrity)
- [ ] UI totals match API totals for dashboard/order counters.
- [ ] Status labels in UI match backend enum mapping.
- [ ] Currency/date/timezone formatting in UI matches locale and backend values.
- [ ] Empty states (no orders/no notifications/no billing) render without errors.
- [ ] Error responses (4xx/5xx) show user-safe messages and do not expose internals.

## 3. Evidence Collection (must attach to release ticket)
- [ ] Build metadata: commit SHA, environment, test window start/end.
- [ ] Screenshots/video of each core journey in section 2.2.
- [ ] API evidence: request/response samples for section 2.3 (sanitized).
- [ ] Console/network log snapshot for `/lk` landing and one full user flow.
- [ ] Defect log with severity, reproduction, owner, current status.
- [ ] Final QA sign-off note: risks accepted + explicit Go/No-Go decision.

## 4. WCAG Quick Audit (fast pass)
Target: WCAG 2.1 AA quick baseline

### 4.1 Keyboard & Focus
- [ ] Full `/lk` primary flow works keyboard-only (Tab/Shift+Tab/Enter/Escape).
- [ ] Visible focus indicator on all interactive controls.
- [ ] No keyboard trap in modals/dropdowns.

### 4.2 Semantics & Screen Reader Basics
- [ ] Page has one clear H1 and logical heading order.
- [ ] Form inputs have labels; errors are announced and associated.
- [ ] Buttons/links have meaningful accessible names.

### 4.3 Color/Contrast/State
- [ ] Text contrast passes AA for normal text and UI controls.
- [ ] Status is not conveyed by color only.
- [ ] Disabled/loading/error states are clearly distinguishable.

### 4.4 Responsive/Zoom
- [ ] `/lk` usable at 320px width.
- [ ] Content remains usable at 200% zoom.
- [ ] No critical clipping/overlap for key actions.

## 5. Suggested Smoke Commands
```bash
# PHP/Laravel smoke
php artisan test --filter=Account
php artisan test --filter=Notification
php artisan test --filter=Billing

# API quick checks (example)
curl -i http://127.0.0.1:2244/api/account/dashboard
curl -i http://127.0.0.1:2244/api/account/orders
curl -i http://127.0.0.1:2244/api/account/notifications/list

# Route sanity
php artisan route:list --path=account
php artisan route:list --path=lk
```

## 6. Exit Criteria
- [ ] All P0/P1 defects closed or explicitly waived by product/engineering lead.
- [ ] Smoke checklist fully green or documented exceptions approved.
- [ ] WCAG quick audit issues triaged with owner and due date.
- [ ] QA evidence package attached and reviewed.
- [ ] Final release recommendation recorded.
