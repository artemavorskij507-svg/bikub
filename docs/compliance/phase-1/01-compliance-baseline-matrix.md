# Phase 1 Week 1 — Compliance Baseline Matrix (As-Is vs Target)

Project: **Bikube (GLF Narvik)**  
Iteration: **Phase 1 / Week 1 — GDPR Foundation**  
Method: **agency-agents role ownership + evidence-based gap scoring**

Priority legend:
- `P0` = blocks go-live / launch-gate blocker
- `P1` = critical before release
- `P2` = planned hardening improvement

## GDPR (Personopplysningsloven + GDPR)

| Control | As-Is | Target | Gap | Priority | Owner | Normative Act |
|---|---|---|---|---|---|---|
| Art. 30 register of processing activities | Partial tables (`gdpr_requests`, `privacy_impact_assessments`) exist, no living registry document or CI-updated inventory | Central Article 30 register auto-generated from code annotations and reviewed in PR | No authoritative registry source of truth, no CI gate | P0 | `engineering-backend-architect` + `engineering-technical-writer` | GDPR Art. 30: https://eur-lex.europa.eu/eli/reg/2016/679/oj ; Personopplysningsloven: https://lovdata.no/dokument/NL/lov/2018-06-15-38 |
| Art. 35 DPIA for high-risk processing | No maintained DPIA pack in repo for current Narvik scope | DPIA template + completed assessments for top 3 high-risk flows | Missing mandatory assessment evidence package | P0 | `engineering-security-engineer` | GDPR Art. 35: https://eur-lex.europa.eu/eli/reg/2016/679/oj ; Datatilsynet DPIA guidance: https://www.datatilsynet.no/regelverk-og-verktoy/veiledere/vurdering-av-personvernkonsekvenser-dpia/ |
| Data subject rights workflow (access/erase/rectify/portability) | `/api/v1/gdpr/*` endpoints exist, but no SLA-driven DSAR state machine + formal API contract + PDF export proof | Versioned DSAR API (`/api/v1/dsar*`), SLA 30 days, auditable state transitions, JSON+PDF export | Existing implementation is not contractual, incomplete governance evidence | P0 | `engineering-backend-architect` + `engineering-code-reviewer` | GDPR Arts. 12, 15-22: https://eur-lex.europa.eu/eli/reg/2016/679/oj |
| Consent capture and withdrawal traceability | Consent data appears in `users.consents` and `user_consents`, no append-only journal policy | Encrypted append-only consent journal with withdrawal flow and analytics exclusion hooks | Audit-trace and revocation integrity are insufficiently formalized | P0 | `engineering-security-engineer` + `engineering-frontend-developer` | GDPR Arts. 6, 7: https://eur-lex.europa.eu/eli/reg/2016/679/oj |
| Breach notification readiness (72h) | No explicit breach runbook wired to legal timer and regulator notification templates | Incident playbook with 72h timer, severity matrix, legal notification checklist | Operational + legal response cadence not documented/tested | P1 | `engineering-incident-response-commander` + `engineering-sre` | GDPR Art. 33: https://eur-lex.europa.eu/eli/reg/2016/679/oj |
| Data retention schedule enforcement | Retention hints in service code, no policy-as-code checks | Retention matrix + automated archival/deletion checks in CI/cron | Policy exists informally, enforcement not measurable | P1 | `engineering-database-optimizer` + `engineering-devops-automator` | GDPR Art. 5(1)(e): https://eur-lex.europa.eu/eli/reg/2016/679/oj |

## WCAG 2.2 AA / Universell utforming

| Control | As-Is | Target | Gap | Priority | Owner | Normative Act |
|---|---|---|---|---|---|---|
| Accessibility baseline audit | No formal WCAG 2.2 AA baseline report in repository | Full baseline report per critical journeys (login, order, checkout, support, admin) | No measurable baseline for compliance program | P0 | `testing-accessibility-auditor` + `design-ui-designer` | WCAG 2.2: https://www.w3.org/TR/WCAG22/ |
| Keyboard-only navigation | Mixed Blade templates, no explicit keyboard QA protocol | All critical flows pass keyboard navigation acceptance tests | Missing systematic keyboard test coverage | P1 | `engineering-frontend-developer` | WCAG 2.2.1 / 2.4.x: https://www.w3.org/TR/WCAG22/ |
| ARIA semantics / SR compatibility | ARIA usage is inconsistent, no component-level a11y contract | ARIA map + reusable accessible components + SR test script | No enforceable accessibility component standard | P1 | `engineering-frontend-developer` + `engineering-code-reviewer` | WCAG 4.1.2: https://www.w3.org/TR/WCAG22/ |
| Color contrast (4.5:1) | Visual styles exist, no contrast regression automation | Automated contrast checks in CI and design tokens with contrast constraints | Visual regression and contrast gate absent | P1 | `design-ui-designer` + `engineering-devops-automator` | WCAG 1.4.3: https://www.w3.org/TR/WCAG22/ |
| Accessibility declaration process | No formal declaration workflow in docs | Accessibility statement template + quarterly review cycle | Governance process missing | P2 | `engineering-technical-writer` | UU IKT regulation context: https://lovdata.no/dokument/SF/forskrift/2013-06-21-732 |

## NSM (Grunnprinsipper / Sikkerhetsnivåer-aligned controls)

| Control | As-Is | Target | Gap | Priority | Owner | Normative Act |
|---|---|---|---|---|---|---|
| Asset and data classification | Domain entities exist, no NSM-aligned classification matrix | Data/system classification (KONFIDENSIALITET / INTEGRITET / TILGJENGELIGHET) per subsystem | Missing classification baseline for controls selection | P0 | `engineering-security-engineer` | NSM guidance: https://nsm.no/regelverk-og-hjelp/rad-og-anbefalinger/grunnprinsipper-for-ikt-sikkerhet/ |
| Security monitoring and anomaly detection | Logs exist, no explicit SIEM onboarding plan for critical events | Event catalog + SIEM integration + alert severity playbooks | Detection capability not standardized | P1 | `engineering-sre` + `engineering-threat-detection-engineer` | NSM principles (Detect/Handle): https://nsm.no/regelverk-og-hjelp/rad-og-anbefalinger/grunnprinsipper-for-ikt-sikkerhet/ |
| Incident management and drills | Incident response role exists in plans, no drill evidence | Incident runbooks + tabletop exercise evidence + postmortem template | Readiness exists conceptually, not operationally verified | P1 | `engineering-incident-response-commander` | NSM incident handling principles: https://nsm.no/regelverk-og-hjelp/rad-og-anbefalinger/grunnprinsipper-for-ikt-sikkerhet/ |
| Cryptography baseline (TLS, encryption at rest) | Mixed config, no single cryptography baseline doc | Enforced TLS 1.3 where possible + AES-256 at rest + key rotation SOP | Policy and verification controls not centralized | P1 | `engineering-security-engineer` + `engineering-devops-automator` | NSM crypto recommendations: https://nsm.no/regelverk-og-hjelp/rad-og-anbefalinger/grunnprinsipper-for-ikt-sikkerhet/ |

## Digdir (fellesløsninger + interoperability)

| Control | As-Is | Target | Gap | Priority | Owner | Normative Act |
|---|---|---|---|---|---|---|
| ID-porten integration readiness | eID/OIDC support is present, but no Digdir conformance test matrix in repo | Documented ID-porten integration contract, test vectors, error handling matrix | Missing conformance evidence package | P0 | `engineering-backend-architect` | Digdir docs (ID-porten): https://docs.digdir.no/docs/idporten/ |
| Maskinporten service-to-service auth | OAuth/OIDC components exist, no dedicated Maskinporten flow artifacts | Maskinporten client credentials flow profile + key rotation + scope registry | No formal S2S compliance profile | P1 | `engineering-backend-architect` + `engineering-security-engineer` | Digdir docs (Maskinporten): https://docs.digdir.no/docs/Maskinporten/maskinporten_overordnet |
| OpenAPI / API discoverability | APIs exist, no single compliance-grade OpenAPI bundle for regulatory interfaces | Versioned OpenAPI specs for DSAR/consent/compliance endpoints published in CI artifact | Interoperability contract incomplete | P1 | `engineering-technical-writer` + `engineering-backend-architect` | Digitaliseringsrundskrivet: https://www.regjeringen.no/no/dokumenter/digitaliseringsrundskrivet/id2895185/ |

## Målloven / Språklova language obligations (Bokmål/Nynorsk)

| Control | As-Is | Target | Gap | Priority | Owner | Normative Act |
|---|---|---|---|---|---|---|
| Dual written Norwegian support in public UX | Current locale mix is `en`, `ru`, `no`; no explicit `nb`/`nn` split | Full Bokmål (`nb`) + Nynorsk (`nn`) UI coverage with switcher and fallback policy | Required language split missing | P0 | `engineering-frontend-developer` + `engineering-technical-writer` | Målloven §5 reference: https://lovdata.no/dokument/NL/lov/1980-04-11-5 ; Språklova: https://lovdata.no/dokument/NL/lov/2021-05-21-42 |
| Content governance for legal/compliance texts | No translation QA process for policy/legal pages | Terminology glossary + translation QA checklist + review ownership | No controlled translation quality process | P1 | `engineering-technical-writer` | Språklova obligations: https://lovdata.no/dokument/NL/lov/2021-05-21-42 |
| North Sami and English support roadmap | English partially present; North Sami coverage not defined | Prioritized language rollout backlog with domain-specific glossary | Missing explicit implementation roadmap and acceptance criteria | P2 | `design-ui-designer` + `engineering-frontend-developer` | Language policy context: https://lovdata.no/dokument/NL/lov/2021-05-21-42 |

---

## Baseline Notes (evidence anchors)

- Existing GDPR routes found under `routes/api.php` (`/gdpr/request`, `/gdpr/requests`, etc.), but no DSAR-specific OpenAPI contract and SLA state model.
- Existing eID/OIDC components found in `app/Http/Controllers/Auth/EidLoginController.php` and `config/eid.php`.
- Existing locale handling found in `app/Http/Controllers/LanguageController.php` with limited locale set (no explicit `nb`/`nn` split).
- Accessibility automation artifacts are not present as CI gates yet.
