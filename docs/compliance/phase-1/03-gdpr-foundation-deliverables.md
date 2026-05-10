# Phase 1 Week 1 — GDPR Foundation Deliverables

## 3a) DPIA (Data Protection Impact Assessment)

### DPIA Template (Art. 35 GDPR + Datatilsynet-aligned)

```markdown
# DPIA-<ID>

## 1. Processing Overview
- Processing activity:
- Controller:
- Processor(s):
- Data subjects:
- Data categories:
- Sensitive / special categories:
- Scale and context:
- Legal basis (GDPR Art. 6/9):
- Purpose(s):

## 2. Necessity and Proportionality
- Why this processing is necessary:
- Less intrusive alternatives assessed:
- Data minimization controls:
- Access control and segregation:
- Transparency and notice:

## 3. Risk Assessment
For each risk:
- Risk ID:
- Threat scenario:
- Likelihood (1-5):
- Impact (1-5):
- Inherent risk score (LxI):
- Existing controls:
- Residual risk score:
- Risk owner:

## 4. Measures and Safeguards
- Technical safeguards:
- Organizational safeguards:
- Detection and response safeguards:
- Remaining gaps and deadlines:

## 5. Decision
- Is prior consultation with authority required?
- Go / No-Go / Conditional Go:
- Approval signatures:
```

### DPIA-01 (Highest Risk): eID Authentication and National Identity Data

- **Processing activity:** eID login and account linking (`BankID/MinID/Buypass`)  
- **Scope:** Citizens of Narvik using service login and account-security flows  
- **Data:** National identity identifiers (`eid_national_id`), provider metadata, session events, IP/user-agent  
- **Legal basis:** GDPR Art. 6(1)(b), 6(1)(c); strong identity assurance for public service access

**Top risks:**
1. Identity theft via compromised callback/token flow (Inherent 20 -> Residual 8)
2. Excess retention of identity proof artifacts (15 -> 6)
3. Unauthorized linking of external identity to wrong account (16 -> 8)

**Required safeguards (Week 1):**
- strict callback state/nonce validation,
- signed audit events for link/unlink,
- retention timer for identity evidence,
- privileged action alerts to security queue.

### DPIA-02 (Highest Risk): Location + Dispatch + Mobility History

- **Processing activity:** order dispatch, route tracking, ETA updates, partner assignment  
- **Scope:** user and executor geolocation streams in Narvik operational zones  
- **Data:** location points, route and activity timestamps, assignment metadata, device/session signals  
- **Legal basis:** GDPR Art. 6(1)(b), 6(1)(e) where municipal/public interest use is applicable

**Top risks:**
1. Re-identification from movement patterns (20 -> 10)
2. Excessive historical retention of traces (16 -> 8)
3. Internal misuse of route intelligence (15 -> 9)

**Required safeguards (Week 1):**
- retention partitioning (hot/warm/archive),
- role-based access to historical traces,
- pseudonymized analytics views,
- data export redaction policy for third-party recipients.

### DPIA-03 (Highest Risk): Social Care / Vulnerable Citizen Processing

- **Processing activity:** social-care emergency event and care plan workflow  
- **Scope:** vulnerable users and trusted contacts within Narvik care flows  
- **Data:** health-adjacent context, emergency triggers, contact graphs, care preferences  
- **Legal basis:** GDPR Art. 6(1)(d)/(e), potentially Art. 9 with explicit safeguards

**Top risks:**
1. Harm from unauthorized disclosure of vulnerable status (25 -> 10)
2. Incorrect emergency escalation due to stale profile data (16 -> 8)
3. Over-broad access by non-care staff (20 -> 8)

**Required safeguards (Week 1):**
- care-role access boundaries + just-in-time access,
- event-level encryption and access auditability,
- data quality checkpoints before escalations,
- mandatory incident path for care-data access anomalies.

---

## 3b) Data Registry (Register over behandlingsaktiviteter, Art. 30)

### Record Schema

Each processing activity record MUST include:
- `activity_id`
- `system_component`
- `business_owner`
- `data_controller`
- `purposes[]`
- `legal_basis[]`
- `data_subject_categories[]`
- `personal_data_categories[]`
- `special_category_data`
- `recipients[]`
- `third_country_transfer`
- `retention_policy`
- `security_measures[]`
- `last_reviewed_at`
- `source_refs[]` (files and symbols)

### Auto-generation from code annotations

Use TSDoc/JSDoc tags in service/controller/model files:

```ts
/**
 * @processingActivity AUTH-EID-LINK
 * @purpose Strong identity verification for account access
 * @legalBasis GDPR-6(1)(b),GDPR-6(1)(c)
 * @dataSubjects citizens,service-users
 * @dataCategories eid_national_id,session_metadata,ip,user_agent
 * @retention P1Y
 * @security encryption-at-rest,audit-log,rbac
 */
```

Generation flow:
1. Parse annotations from selected code roots.
2. Validate against registry schema.
3. Output `art30.registry.json`.
4. Publish as CI artifact and commit diff for regulated changes.

### CI Integration

- Trigger on PR changes to `app/**`, `routes/**`, `src/gdpr/**`.
- Run parser + schema validation.
- Fail PR if:
  - required fields missing,
  - annotation changed but registry artifact not updated,
  - legal basis tags invalid.

---

## 3c) DSAR API

### Endpoint contract

- `POST /api/v1/dsar`
  - Creates DSAR request.
  - Body: type (`access|erasure|rectification|portability|objection`), subject info, preferred export format.

- `GET /api/v1/dsar/:id`
  - Returns DSAR status, SLA deadline, audit trail summary.

### Workflow states

`submitted -> identity_verification -> triage -> processing -> qa_review -> completed`  
Failure path: `rejected` / `requires_more_info` / `escalated_legal`

### SLA + policy

- Standard resolution target: **30 calendar days**.
- Extension policy: +60 days only with documented reason and subject notification.
- Breach to SLA triggers compliance alert to DPO queue.

### Auth and trust

- Citizen DSAR: ID-porten identity assurance profile.
- System-to-system DSAR operations (bulk/legal hold): Maskinporten client credentials with scoped tokens.
- Required claims (minimum): `sub`, `acr`, `loa`, `scope`, `exp`, `aud`, `jti`.

### Audit requirements

Each transition logs:
- actor (`subject|agent|system`),
- old/new state,
- timestamp,
- justification,
- evidence refs.

### Export formats

- **JSON:** machine-consumable canonical export.
- **PDF:** human-readable report with glossary and processing context.

---

## 3d) Consent Journal (Samtykkejournal)

### Data model (append-only event log)

`consent_events`
- `event_id` (UUID)
- `subject_id`
- `consent_type` (`privacy|analytics|marketing|third_party_sharing|care_data`)
- `status` (`granted|withdrawn|updated|expired`)
- `legal_basis`
- `policy_version`
- `channel` (`web|mobile|api|support`)
- `evidence_ref` (banner version, form hash, API payload hash)
- `event_hash` (current event hash)
- `prev_event_hash` (chain integrity)
- `created_at`

### Storage and integrity

- Encryption at rest (AES-256 equivalent policy).
- Append-only write policy (no UPDATE/DELETE to event rows).
- Hash-chain validation job daily.

### Integration points

- Frontend consent banner (first-party + granular toggles).
- API middleware (`analytics` and optional processors blocked without valid consent).
- Data pipeline/analytics exclusion (respect withdrawn consent immediately).

### Withdrawal flow

1. User submits withdrawal.
2. New append-only event with `status=withdrawn`.
3. Effective immediately for non-essential processing.
4. Downstream processors notified.
5. Confirmation exposed in account privacy page and DSAR audit trail.

### Retention policy

- Consent evidence kept for legal defense window (configurable by legal policy).
- Hash-chain integrity metadata retained as long as legal basis requires.
- Retention matrix cross-linked with Art. 30 register.

---

## Deliverable Completion Checklist (Week 1)

- [ ] DPIA template committed and approved.
- [ ] 3 completed high-risk DPIAs approved by security owner.
- [ ] Art. 30 registry schema + generator + CI gate operational.
- [ ] DSAR API v1 contract reviewed by backend + legal.
- [ ] Consent Journal model and integration map approved.
