# Cursor's Memory Bank — GLF BiKube Edition

I am Cursor working on GLF BiKube (city multiservice platform, Narvik). My context resets between sessions, so my ONLY source of truth is the Memory Bank in /memory-bank/*.md. 

MANDATORY RULES
1) At the start of EVERY task: read ALL memory-bank core files.
2) Apply the canonical blocks: OVERVIEW / SYSTEM / CATALOG / FLOWS / INFRA / PROGRESS / BACKLOG.
3) Perform code edits only as precise operations (search_replace or write). Never overwrite whole files unless explicitly requested.
4) Language: RU/UK (match user input). Style: expert developer, best-effort, no extra questions.
5) Version notes use delta format: vX.Y.Z — short, essential, long-lived facts only. No secrets, keys, tokens, or one-off CLI logs in memory.
6) Confirm context at the beginning (“following custom instructions; memory read OK”) and summarize planned steps (Plan Mode) прежде чем действовать (Act Mode).

MEMORY BANK STRUCTURE
- /memory-bank/projectbrief.md       — foundation: goals, scope, audience
- /memory-bank/productContext.md     — why it exists; problems; UX goals; how it works
- /memory-bank/systemPatterns.md     — architecture, patterns, critical flows, data isolation
- /memory-bank/techContext.md        — stack, infra, constraints, tools
- /memory-bank/activeContext.md      — current focus, recent changes, next steps, decisions
- /memory-bank/progress.md           — done / todo / issues / milestones
- optional: /memory-bank/*.md        — integrations, APIs, testing, deploy runbooks

CORE FLOWS (must be reflected in SYSTEM/FLOWS)
- Order flow: choose service → dynamic price → Payment Intent → pay → OrderCreated → slot → Task.
- Payment flow: Stripe/Vipps webhook → HMAC verify → OrderPaid → TaskCreated.
- Routing flow: Matrix API → optimization → ML-ETA → telematics updates.

TECH BASELINES (long-lived essentials)
- Backend Laravel 10 (MVC + Service Layer + Events); Filament v3 admin; Blade public (Next.js 14 planned).
- DB: SQLite(dev) / PostgreSQL(prod). Queues/cache: Redis + Horizon.
- Auth: Sanctum (planned), OAuth2/OIDC for partners; Filament Auth; SSO BankID/ID-porten planned.
- Payments: Stripe (demo), add Vipps + Apple/Google Pay via Stripe. Webhooks signed HMAC-SHA256.
- Infra: local Apache httpd:2244; staging/prod & CI/CD — pending (Docker + GitHub Actions plan).
- Feature Flags present (TTL≈60s, admin UI).

WORK MODES
Plan Mode:
- Read all memory-bank files → verify gaps → propose minimal viable plan and risks → wait for confirmation only when destructive actions are required; otherwise proceed best-effort.

Act Mode:
- Update activeContext.md & progress.md with concise delta (vX.Y.Z Δ) → execute task (precise edits) → document changes (diff summary) → if patterns emerged, update systemPatterns.md.

UPDATE TRIGGERS
- “initialize memory bank” — creates/repairs structure if missing.
- “follow your custom instructions” — force re-read of memory bank.
- “update memory bank” — review ALL files; write delta in activeContext.md + progress.md; keep only durable facts.

PROHIBITIONS
- No secrets, tokens, credentials, or transient logs in memory-bank.
- No library micro-versions, long CLI dumps, or machine-specific paths unless they’re canonical ops.

COMMIT POLICY
- Conventional Commits, short scope, example: 
  feat(catalog): add express slots pricing
  fix(infra): apache DocumentRoot to /srv/glfbikube/public
  docs(memory): v0.1.7 Δ — add Cline Memory Bank rules

SUCCESS CRITERIA
- On session start: “Memory loaded ✓; files checked; proceeding.”
- Outputs reflect current BANK blocks; no duplicate info; deltas concise and auditable.
