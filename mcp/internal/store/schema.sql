PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version INTEGER PRIMARY KEY,
    applied_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS campaigns (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    system TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    title TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'open',
    started_at TEXT NOT NULL DEFAULT (datetime('now')),
    ended_at TEXT
);

CREATE TABLE IF NOT EXISTS entities (
    id TEXT PRIMARY KEY,
    campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    name TEXT NOT NULL,
    summary TEXT NOT NULL DEFAULT '',
    data TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_entities_campaign ON entities(campaign_id);
CREATE INDEX IF NOT EXISTS idx_entities_type ON entities(campaign_id, type);
CREATE INDEX IF NOT EXISTS idx_entities_name ON entities(campaign_id, name);

CREATE TABLE IF NOT EXISTS facts (
    id TEXT PRIMARY KEY,
    campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    entity_id TEXT REFERENCES entities(id) ON DELETE SET NULL,
    text TEXT NOT NULL,
    visibility TEXT NOT NULL DEFAULT 'party_known',
    confidence TEXT NOT NULL DEFAULT 'high',
    source_type TEXT,
    source_id TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_facts_campaign ON facts(campaign_id);
CREATE INDEX IF NOT EXISTS idx_facts_entity ON facts(entity_id);

CREATE TABLE IF NOT EXISTS events (
    id TEXT PRIMARY KEY,
    campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    session_id TEXT REFERENCES sessions(id) ON DELETE SET NULL,
    title TEXT NOT NULL,
    summary TEXT NOT NULL,
    entity_ids TEXT NOT NULL DEFAULT '[]',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_events_campaign ON events(campaign_id, created_at DESC);

CREATE TABLE IF NOT EXISTS rulings (
    id TEXT PRIMARY KEY,
    campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    scope TEXT NOT NULL DEFAULT 'campaign',
    system TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_rulings_campaign ON rulings(campaign_id);

CREATE TABLE IF NOT EXISTS pending_updates (
    id TEXT PRIMARY KEY,
    campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    proposed_changes TEXT NOT NULL,
    reason TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_pending_updates_campaign ON pending_updates(campaign_id, status);
