Security & Access Sprint — Summary

New artifacts (Safe to migrate and reversible):

- New database tables (migrations):
  - `user_two_factor_settings` — stores TOTP secret (encrypted), recovery codes, enabled/confirmed timestamps
  - `audit_logs` — centralized audit trail
  - `admin_ip_rules` — allow/deny IP/CIDR rules for admin area
  - `api_keys` — SHA-256 hashed API keys with scopes and lifecycle fields
  - `oauth_providers` — prepared table to store OAuth provider stubs (not activated)

- Middleware:
  - `RequestIdMiddleware` — generates/propagates `X-Request-Id` for tracing
  - `EnsureAdminIpAllowed` — global check that blocks access to `/admin` and `/filament` per allow/deny rules (logs denials)
  - `EnsureTwoFactorConfirmed` — route middleware alias `2fa.confirmed` (enforces 2FA for privileged roles)

- Models:
  - `UserTwoFactorSetting`, `AuditLog`, `AdminIpRule`, `ApiKey`, `OAuthProvider`

- Services:
  - `AuditLogger` — centralized service to write audit entries
  - `ApiKeyService` — generate/show key once, revoke, validate

- Observers / Listeners:
  - `ModelObserver` — generic observer to log create/update/delete for selected models
  - `AuthEventSubscriber` — logs login/logout/failed attempts

- Filament placeholders (pages/views):
  - `Security\TwoFactorSetup` -> `filament.security.two-factor-setup` (placeholder view)
  - `Security\AuditLogs` -> `filament.security.audit-logs`
  - `Security\AdminIpRules` -> `filament.security.admin-ip-rules`
  - `Security\ApiKeys` -> `filament.security.api-keys`

Quick commands

1) Run migrations:
```
php artisan migrate
```

2) Clear caches (after deploy/config change):
```
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

How to enable 2FA for an admin (manual flow):
 - Create a `UserTwoFactorSetting` row for the admin user with generated encrypted `secret` and `recovery_codes` (tools/Filament will be added soon).
 - Set `enabled_at` and `confirmed_at` timestamps.
 - After that the user will pass `EnsureTwoFactorConfirmed` middleware.

How to add an IP rule:
 - Insert into `admin_ip_rules` with `type`='allow' and `ip_range`='1.2.3.4' (or CIDR `1.2.3.0/24`).

How to generate API key (programmatically):
```
app(\App\Services\ApiKeyService::class)->generate('User', 1, 'internal-service', ['partners:read'], 365, true);
```
The returned `api_key` is the only time the plaintext is shown.

Notes & GDPR
 - Audit logs include `ip_address` and `user_agent` — these are personal data and marked with `@gdpr-critical` in code comments.
 - Audit logs are read-only by UI design (placeholders added). Retention default is 5 years (config not yet added).

Next steps (recommended):
 - Implement Filament forms for Two-Factor (QR generator using `pragmarx/google2fa-laravel`), recovery code regeneration and enable/disable.
 - Implement Filament CRUD for `admin_ip_rules` and `api_keys` to manage them from admin UI.
 - Add background job or command to purge audit logs older than retention period.
