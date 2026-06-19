<?php

namespace App\Services\WorldKeep\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;

trait MigratesSchema
{
    private const CURRENT_SCHEMA_VERSION = 4;

    /** Campaign play sessions — not Laravel's HTTP session store. */
    protected function sessionsTable(): string
    {
        return 'wk_sessions';
    }

    public function migrate(): void
    {
        foreach ($this->baselineSchemaStatements() as $sql) {
            DB::statement($sql);
        }

        $version = (int) DB::table('schema_migrations')->max('version');

        if ($version >= self::CURRENT_SCHEMA_VERSION) {
            return;
        }

        for ($v = $version + 1; $v <= self::CURRENT_SCHEMA_VERSION; $v++) {
            match ($v) {
                1 => null,
                2 => $this->migrateToV2(),
                3 => $this->migrateToV3(),
                4 => $this->migrateToV4(),
                default => throw new RuntimeException("unknown schema version {$v}"),
            };

            DB::table('schema_migrations')->insert(['version' => $v]);
        }
    }

    /** @return list<string> */
    private function baselineSchemaStatements(): array
    {
        $sessions = $this->sessionsTable();
        $nowDefault = $this->isSqlite()
            ? "datetime('now')"
            : 'CURRENT_TIMESTAMP';

        if ($this->isSqlite()) {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        return [
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                version INTEGER PRIMARY KEY,
                applied_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )",
            "CREATE TABLE IF NOT EXISTS campaigns (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                system TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )",
            "CREATE TABLE IF NOT EXISTS {$sessions} (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
                title TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'open',
                started_at TEXT NOT NULL DEFAULT ({$nowDefault}),
                ended_at TEXT
            )",
            "CREATE TABLE IF NOT EXISTS entities (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
                type TEXT NOT NULL,
                name TEXT NOT NULL,
                summary TEXT NOT NULL DEFAULT '',
                data TEXT NOT NULL DEFAULT '{}',
                created_at TEXT NOT NULL DEFAULT ({$nowDefault}),
                updated_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )",
            'CREATE INDEX IF NOT EXISTS idx_entities_campaign ON entities(campaign_id)',
            'CREATE INDEX IF NOT EXISTS idx_entities_type ON entities(campaign_id, type)',
            'CREATE INDEX IF NOT EXISTS idx_entities_name ON entities(campaign_id, name)',
            "CREATE TABLE IF NOT EXISTS facts (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
                entity_id TEXT REFERENCES entities(id) ON DELETE SET NULL,
                text TEXT NOT NULL,
                visibility TEXT NOT NULL DEFAULT 'party_known',
                confidence TEXT NOT NULL DEFAULT 'high',
                source_type TEXT,
                source_id TEXT,
                created_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )",
            'CREATE INDEX IF NOT EXISTS idx_facts_campaign ON facts(campaign_id)',
            'CREATE INDEX IF NOT EXISTS idx_facts_entity ON facts(entity_id)',
            "CREATE TABLE IF NOT EXISTS events (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
                session_id TEXT REFERENCES {$sessions}(id) ON DELETE SET NULL,
                title TEXT NOT NULL,
                summary TEXT NOT NULL,
                entity_ids TEXT NOT NULL DEFAULT '[]',
                created_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )",
            'CREATE INDEX IF NOT EXISTS idx_events_campaign ON events(campaign_id, created_at DESC)',
            "CREATE TABLE IF NOT EXISTS rulings (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
                question TEXT NOT NULL,
                answer TEXT NOT NULL,
                scope TEXT NOT NULL DEFAULT 'campaign',
                system TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )",
            'CREATE INDEX IF NOT EXISTS idx_rulings_campaign ON rulings(campaign_id)',
            "CREATE TABLE IF NOT EXISTS pending_updates (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
                proposed_changes TEXT NOT NULL,
                reason TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'pending',
                created_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )",
            'CREATE INDEX IF NOT EXISTS idx_pending_updates_campaign ON pending_updates(campaign_id, status)',
        ];
    }

    private function migrateToV2(): void
    {
        $sessions = $this->sessionsTable();

        $this->addColumnIfMissing($sessions, 'notes', "TEXT NOT NULL DEFAULT ''");
        $this->addColumnIfMissing($sessions, 'summary', "TEXT NOT NULL DEFAULT ''");

        $changesNow = $this->isSqlite() ? "datetime('now')" : 'CURRENT_TIMESTAMP';
        $changesId = $this->isSqlite()
            ? 'id INTEGER PRIMARY KEY AUTOINCREMENT'
            : 'id BIGSERIAL PRIMARY KEY';

        DB::statement("
            CREATE TABLE IF NOT EXISTS session_changes (
                {$changesId},
                session_id TEXT NOT NULL REFERENCES {$sessions}(id) ON DELETE CASCADE,
                entity_id TEXT NOT NULL,
                change_op TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT ({$changesNow})
            )");

        DB::statement('CREATE INDEX IF NOT EXISTS idx_session_changes_session ON session_changes(session_id)');
        DB::statement("
            CREATE TABLE IF NOT EXISTS campaign_roles (
                campaign_id TEXT PRIMARY KEY REFERENCES campaigns(id) ON DELETE CASCADE,
                role TEXT NOT NULL DEFAULT 'owner'
            )");
    }

    private function migrateToV3(): void
    {
        $nowDefault = $this->isSqlite() ? "datetime('now')" : 'CURRENT_TIMESTAMP';

        DB::statement("
            CREATE TABLE IF NOT EXISTS campaign_seats (
                id TEXT PRIMARY KEY,
                campaign_id TEXT NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
                seat_type TEXT NOT NULL,
                controller TEXT NOT NULL DEFAULT 'ai',
                controller_user_id TEXT,
                actor_id TEXT REFERENCES entities(id) ON DELETE SET NULL,
                display_name TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT NOT NULL DEFAULT ({$nowDefault}),
                updated_at TEXT NOT NULL DEFAULT ({$nowDefault})
            )");

        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaign_seats_campaign ON campaign_seats(campaign_id)');
    }

    private function migrateToV4(): void
    {
        $sessions = $this->sessionsTable();

        DB::statement("
            CREATE TABLE IF NOT EXISTS session_floor (
                session_id TEXT PRIMARY KEY REFERENCES {$sessions}(id) ON DELETE CASCADE,
                floor_seat_id TEXT,
                party_beat_queue TEXT NOT NULL DEFAULT '[]',
                awaiting_player_checkpoint INTEGER NOT NULL DEFAULT 0
            )");
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if ($this->columnExists($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function columnExists(string $table, string $column): bool
    {
        if ($this->isSqlite()) {
            $rows = DB::select("PRAGMA table_info({$table})");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $column) {
                    return true;
                }
            }

            return false;
        }

        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ? LIMIT 1',
            [$table, $column]
        );

        return $row !== null;
    }
}
