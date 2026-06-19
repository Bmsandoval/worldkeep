<?php

namespace App\Services\WorldKeep\Concerns;

use Illuminate\Support\Facades\DB;

trait HasSqlHelpers
{
    protected function driver(): string
    {
        return DB::getDriverName();
    }

    protected function isSqlite(): bool
    {
        return $this->driver() === 'sqlite';
    }

    protected function isPgsql(): bool
    {
        return $this->driver() === 'pgsql';
    }

    protected function nowTimestamp(): string
    {
        return now()->utc()->format('Y-m-d\TH:i:s\Z');
    }

    protected function coalesceTimestamp(string $provided): string
    {
        return trim($provided) !== '' ? $provided : $this->nowTimestamp();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $updateColumns
     */
    protected function upsertRow(string $table, array $row, array $uniqueBy, array $updateColumns): void
    {
        DB::table($table)->upsert($row, $uniqueBy, $updateColumns);
    }
}
