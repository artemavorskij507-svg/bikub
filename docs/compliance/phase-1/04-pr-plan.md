# Phase 1 Week 1 — First PR Plan (P0 Start)

## PR-1
- **Ticket:** `BIK-GDPR-W1-001`
- **Branch:** `feature/BIK-GDPR-W1-001`
- **Scope:** Compliance baseline package and ownership matrix
- **Files:**
  - `docs/compliance/phase-1/01-compliance-baseline-matrix.md`
  - `docs/compliance/phase-1/02-executable-backlog.md`
- **Tests:**
  - `TestComplianceMatrixColumns`
  - `TestBacklogHasUniqueTicketIds`
- **Reviewers (min 2):** `engineering-technical-writer`, `support-legal-compliance-checker`
- **Merge criteria:**
  - All controls have legal links
  - All P0 controls mapped to backlog tickets
  - Reviewer sign-off from legal+engineering

## PR-2
- **Ticket:** `BIK-GDPR-W1-004` + `BIK-GDPR-W1-005`
- **Branch:** `feature/BIK-GDPR-W1-004`
- **Scope:** Art.30 registry schema + annotation parser + generated artifact
- **Files:**
  - `src/gdpr/data-registry.schema.json`
  - `src/gdpr/registry-annotations.example.ts`
  - `src/gdpr/generate-registry.ts`
  - `src/gdpr/README.md`
- **Tests:**
  - `TestRegistrySchemaRequiredFields`
  - `TestExtractAnnotationsFromSource`
  - `IntegrationTestRegistryGeneratedInCI`
- **Reviewers (min 2):** `engineering-backend-architect`, `engineering-devops-automator`
- **Merge criteria:**
  - Schema validation green
  - Parser output deterministic
  - CI artifact generated and attached

## PR-3
- **Ticket:** `BIK-GDPR-W1-007` + `BIK-GDPR-W1-008` + `BIK-GDPR-W1-009`
- **Branch:** `feature/BIK-GDPR-W1-007`
- **Scope:** DSAR API contract, auth profile, export format contract
- **Files:**
  - `src/gdpr/dsar.openapi.yaml`
  - `docs/compliance/phase-1/03-gdpr-foundation-deliverables.md` (DSAR sections)
- **Tests:**
  - `TestDsarOpenApiSpecValid`
  - `TestDsarStateEnumCoverage`
  - `IntegrationTestDsarAuthRejectionCases`
- **Reviewers (min 2):** `engineering-backend-architect`, `engineering-security-engineer`
- **Merge criteria:**
  - OpenAPI linter passes
  - State machine covers all DSAR types
  - Auth claims profile documented and approved

## PR-4
- **Ticket:** `BIK-GDPR-W1-010` + `BIK-GDPR-W1-011`
- **Branch:** `feature/BIK-GDPR-W1-010`
- **Scope:** Consent Journal model and integration map
- **Files:**
  - `src/gdpr/consent-journal.model.sql`
  - `docs/compliance/phase-1/03-gdpr-foundation-deliverables.md` (Consent sections)
- **Tests:**
  - `TestConsentEventSchema`
  - `TestAppendOnlyConstraint`
  - `TestAnalyticsExcludedWithoutConsent`
- **Reviewers (min 2):** `engineering-security-engineer`, `engineering-frontend-developer`
- **Merge criteria:**
  - Append-only constraints documented
  - Integration points complete (frontend/API/analytics)
  - Withdrawal flow accepted by legal owner

## PR-5
- **Ticket:** `BIK-GDPR-W1-006`
- **Branch:** `feature/BIK-GDPR-W1-006`
- **Scope:** Compliance gates in GitHub Actions (lint/a11y/security)
- **Files:**
  - `.github/workflows/pr-compliance-lint.yml`
  - `.github/workflows/pr-accessibility-audit.yml`
  - `.github/workflows/pr-security-scan.yml`
- **Tests:**
  - `IntegrationTestWorkflowLintJob`
  - `IntegrationTestWorkflowA11yJob`
  - `IntegrationTestWorkflowSecurityJob`
- **Reviewers (min 2):** `engineering-devops-automator`, `engineering-code-reviewer`
- **Merge criteria:**
  - All three workflows trigger on PR
  - Failing gate blocks merge
  - Required checks configured in repository branch protection

---

## Draft GitHub Actions Compliance Gates

> Workflows are intentionally draft-level and safe by default; they can be hardened in Week 2.

- `lint` gate: markdown lint, YAML lint, OpenAPI lint, TypeScript typecheck for `src/gdpr`.
- `accessibility` gate: static accessibility check and optional pa11y URL scan.
- `security` gate: composer audit, npm audit, secret scan, SAST placeholder.
