-- Draft schema: append-only consent journal (Samtykkejournal)
-- Week 1 foundation: encrypted fields + hash chain + immutable writes

CREATE TABLE IF NOT EXISTS consent_events (
  event_id TEXT PRIMARY KEY,
  subject_id BIGINT NOT NULL,
  consent_type TEXT NOT NULL,
  status TEXT NOT NULL CHECK(status IN ('granted','withdrawn','updated','expired')),
  legal_basis TEXT NOT NULL,
  policy_version TEXT NOT NULL,
  channel TEXT NOT NULL,
  evidence_ref TEXT,
  event_hash TEXT NOT NULL,
  prev_event_hash TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_consent_events_subject_type ON consent_events(subject_id, consent_type, created_at);
CREATE INDEX IF NOT EXISTS idx_consent_events_status ON consent_events(status, created_at);

-- SQLite-compatible immutable behavior via trigger (no update/delete)
CREATE TRIGGER IF NOT EXISTS trg_consent_events_no_update
BEFORE UPDATE ON consent_events
BEGIN
  SELECT RAISE(ABORT, 'consent_events is append-only');
END;

CREATE TRIGGER IF NOT EXISTS trg_consent_events_no_delete
BEFORE DELETE ON consent_events
BEGIN
  SELECT RAISE(ABORT, 'consent_events is append-only');
END;
