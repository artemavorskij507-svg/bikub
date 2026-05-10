# src/gdpr — Phase 1 Week 1 Artifacts

This directory contains executable GDPR Foundation artifacts for Bikube:

- `data-registry.schema.json` — Art.30 register schema
- `registry-annotations.example.ts` — JSDoc/TSDoc annotation conventions
- `generate-registry.ts` — draft parser/generator entrypoint
- `dsar.openapi.yaml` — DSAR API v1 contract draft
- `consent-journal.model.sql` — append-only consent journal model draft

## Annotation convention

Use tags in controllers/services/models:

- `@processingActivity <ID>`
- `@purpose <text>`
- `@legalBasis <comma separated GDPR refs>`
- `@dataSubjects <comma separated categories>`
- `@dataCategories <comma separated categories>`
- `@retention <ISO-like policy e.g. P1Y>`
- `@security <comma separated controls>`

## CI usage (draft)

1. Run parser on changed files.
2. Validate generated registry against schema.
3. Fail PR if registry update required but missing.
