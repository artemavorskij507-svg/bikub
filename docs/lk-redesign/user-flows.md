# Worker LK User Flows

## Scope
- Role: Worker (courier/executor/roadside helper) in LK.
- Source basis: UX audit of `/resources/views/lk`.
- Flows covered:
1. Start shift
2. Accept order
3. Finish order
4. Request payout
5. Support ticket

## Flow Map Format
- Entry points: where user starts the flow.
- Main path: ideal scenario from start to success.
- Alternate paths: key branches and exceptions.
- UX pain points: friction detected in current templates.
- UX controls: what to enforce in redesign.

---

## 1. Start Shift

### Entry points
- `Dashboard` (`lk.dashboard`) via CTA "Выйти на линию".
- `Schedule` (`lk.schedule`) via availability toggles.

### Main path
1. User opens dashboard.
2. Sees current status (`OFFLINE`) and shift context.
3. Clicks "Выйти на линию".
4. System sends status update and returns success feedback.
5. Dashboard reflects `ONLINE` state and active search for orders.

### Alternate paths
- A1: User opens `Schedule` first and sets availability (today/tomorrow), then goes back to dashboard to go online.
- A2: API/network error during status toggle -> state reverts to previous.
- A3: User is online but has no active order -> empty state with search indicator.

### UX pain points
- Dashboard is visually overloaded (high motion, many competing blocks), primary action is not isolated.
- "Availability" and "Online" are split across two screens (mental model mismatch).
- Error feedback is generic; recovery steps are unclear.

### UX controls
- One dominant "Shift status" module at top with one primary action.
- Unified semantics: `Availability` vs `Online` with plain labels and dependency explanation.
- Deterministic feedback states: pending/success/error + retry inline.
- Keep contextual metrics secondary during shift-start decision.

---

## 2. Accept Order

### Entry points
- `Orders index` (`lk.orders.index`) from active tab.
- `Roadside jobs index` (`lk.roadside-jobs.index`) for emergency jobs.
- `Executor jobs` (`lk.executor.jobs.index`) for assignment proposals.

### Main path (generic order)
1. User opens orders list.
2. Filters active items.
3. Opens order details.
4. Reviews key info (address, customer, type, payout, constraints).
5. Clicks accept action.
6. Order status changes to assigned/in progress.

### Alternate paths
- A1: Reject/decline assignment (executor/roadside cases).
- A2: Simultaneous reassignment/race -> action fails.
- A3: Missing critical data (address/phone) -> user cannot safely commit.

### UX pain points
- Action hierarchy is weak: multiple strong buttons can appear with limited guidance.
- Missing explicit "decision summary" block before accept.
- Status vocabulary differs across modules (orders/roadside/executor), causing confusion.

### UX controls
- Standardized decision panel: `What`, `Where`, `When`, `Payout`, `Risk`.
- Single primary action per state; secondary actions visually downgraded.
- Shared status model and labels across modules.
- Pre-accept validation gate for required operational data.

---

## 3. Finish Order

### Entry points
- `Order details` (`lk.orders.show`)
- `Roadside job details` (`lk.roadside-jobs.show`)
- `Executor job details` (`lk.executor.jobs.show`)

### Main path
1. User transitions through active work states (on route -> arrived -> started).
2. Completes required fields/checks (if roadside: work summary, service outcome).
3. Confirms finish action.
4. System stores completion and updates timeline/payment states.
5. User returns to list or next task.

### Alternate paths
- A1: Cancel order with reason.
- A2: Finish blocked due to missing mandatory fields.
- A3: Network failure after click -> uncertain completion state.

### UX pain points
- Multi-step progression lacks a single explicit stepper and "next action" logic.
- Modals + alerts create fragmented flow and risk duplicate submissions.
- Sticky action panels can compete with content and reduce context on mobile.

### UX controls
- Explicit stepper with current step, prerequisites, and completion criteria.
- One submission channel with clear loading/locked states.
- Confirmation pattern with idempotent finish action.
- Post-finish success screen with next best action (`Back to queue`, `Open payouts`, `View history`).

---

## 4. Request Payout

### Entry points
- `Wallet` (`lk.wallet`) via "Запросить выплату".

### Main path
1. User opens wallet.
2. Checks available balance and payout history.
3. Inputs amount, selects method, optional note.
4. Submits payout request.
5. Sees confirmation and updated state/history.

### Alternate paths
- A1: Amount invalid (`<=0` or `>available`).
- A2: API error/network error on submit.
- A3: User needs clarification about payout method differences.

### UX pain points
- Dense finance screen with many cards before primary payout action.
- Method/fee/timing expectations are not explicit at point of choice.
- Success/error feedback relies partly on generic alerts and reload.

### UX controls
- Priority to payout form when balance > 0 (top task-first layout).
- Method helper text: processing time, limits, fees, required details.
- Inline validation with immediate amount guidance.
- Success state without full-page reload; clear status progression in history.

---

## 5. Support Ticket

### Entry points
- `Support` (`lk.support`) create ticket form.
- `Ticket details` (`lk.support.tickets.show`) for conversation.

### Main path
1. User opens support page.
2. Creates ticket with subject, priority, category, message.
3. Submits request.
4. Opens ticket thread.
5. Exchanges messages until resolved.

### Alternate paths
- A1: User starts from FAQ and resolves issue without ticket.
- A2: Ticket closed -> reply disabled.
- A3: Urgent issue -> phone fallback.

### UX pain points
- Create form and ticket lists compete for attention on one page.
- Ticket thread uses fixed-height chat container; weaker readability on mobile.
- Response SLA is shown but expectation management is minimal.

### UX controls
- Separate "Create ticket" and "My tickets" as tabbed/step structure.
- Mobile-first thread layout with adaptive height and stable composer.
- Explicit ticket state model (`Open`, `In progress`, `Waiting for user`, `Resolved`).
- At submit: show expected first-response window and escalation path.

---

## Cross-Flow Friction (Global)
- Over-animated UI increases cognitive load in operational tasks.
- Inconsistent action language and status labels across worker subdomains.
- Nested scroll containers and sticky blocks can degrade mobile ergonomics.
- Relative time-only labels reduce auditability for work history.

## Redesign Principles for All Flows
1. One primary action per state.
2. Clear step/state model with shared terminology.
3. Task-first information hierarchy; decorative UI is secondary.
4. Deterministic feedback and recoverable errors.
5. Mobile-first ergonomics for field operations.
