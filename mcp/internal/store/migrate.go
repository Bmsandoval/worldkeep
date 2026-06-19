package store

import (
	"context"
	"fmt"
)

const currentSchemaVersion = 3

func (s *Store) Migrate(ctx context.Context) error {
	if _, err := s.db.ExecContext(ctx, schemaSQL); err != nil {
		return fmt.Errorf("apply schema: %w", err)
	}

	var version int
	err := s.db.QueryRowContext(ctx, `SELECT COALESCE(MAX(version), 0) FROM schema_migrations`).Scan(&version)
	if err != nil {
		return fmt.Errorf("read schema version: %w", err)
	}
	if version >= currentSchemaVersion {
		return nil
	}

	for v := version + 1; v <= currentSchemaVersion; v++ {
		switch v {
		case 1:
			// Baseline schema from schema.sql (legacy installs already at v1).
		case 2:
			if err := s.migrateToV2(ctx); err != nil {
				return fmt.Errorf("migrate v2: %w", err)
			}
		case 3:
			if err := s.migrateToV3(ctx); err != nil {
				return fmt.Errorf("migrate v3: %w", err)
			}
		default:
			return fmt.Errorf("unknown schema version %d", v)
		}
		if _, err := s.db.ExecContext(ctx, `INSERT INTO schema_migrations (version) VALUES (?)`, v); err != nil {
			return fmt.Errorf("record schema version %d: %w", v, err)
		}
	}
	return nil
}

func (s *Store) migrateToV2(ctx context.Context) error {
	if err := s.addColumnIfMissing(ctx, "sessions", "notes", "TEXT NOT NULL DEFAULT ''"); err != nil {
		return err
	}
	if err := s.addColumnIfMissing(ctx, "sessions", "summary", "TEXT NOT NULL DEFAULT ''"); err != nil {
		return err
	}
	_, err := s.db.ExecContext(ctx, `
CREATE TABLE IF NOT EXISTS session_changes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    entity_id TEXT NOT NULL,
    change_op TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_session_changes_session ON session_changes(session_id);
CREATE TABLE IF NOT EXISTS campaign_roles (
    campaign_id TEXT PRIMARY KEY REFERENCES campaigns(id) ON DELETE CASCADE,
    role TEXT NOT NULL DEFAULT 'owner'
);`)
	return err
}

func (s *Store) migrateToV3(ctx context.Context) error {
	_, err := s.db.ExecContext(ctx, `
CREATE TABLE IF NOT EXISTS campaign_seats (
    id TEXT PRIMARY KEY,
    campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    seat_type TEXT NOT NULL,
    controller TEXT NOT NULL DEFAULT 'ai',
    controller_user_id TEXT,
    actor_id TEXT REFERENCES entities(id) ON DELETE SET NULL,
    display_name TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_campaign_seats_campaign ON campaign_seats(campaign_id);`)
	return err
}

func (s *Store) addColumnIfMissing(ctx context.Context, table, column, def string) error {
	rows, err := s.db.QueryContext(ctx, fmt.Sprintf("PRAGMA table_info(%s)", table))
	if err != nil {
		return err
	}
	defer rows.Close()
	for rows.Next() {
		var cid int
		var name, ctype string
		var notnull int
		var dflt, pk interface{}
		if err := rows.Scan(&cid, &name, &ctype, &notnull, &dflt, &pk); err != nil {
			return err
		}
		if name == column {
			return nil
		}
	}
	_, err = s.db.ExecContext(ctx, fmt.Sprintf("ALTER TABLE %s ADD COLUMN %s %s", table, column, def))
	return err
}
