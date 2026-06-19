<?php

namespace App\Services\WorldKeep;

use App\Services\WorldKeep\Data\ConflictWarning;
use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Data\PendingUpdate;
use App\Services\WorldKeep\Data\Session;
use App\Services\WorldKeep\Data\SessionWorkspace;
use App\Services\WorldKeep\Data\WorldChange;
use RuntimeException;
use Throwable;

class Engine
{
    public function __construct(
        public readonly Store $store,
        public readonly string $campaignId,
        public readonly string $role = 'owner',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function campaignDashboard(
        ?string $campaignId = null,
        int $eventLimit = 5,
        string $scope = 'party',
    ): array {
        $cid = $this->campaignId($campaignId);
        $limit = $eventLimit <= 0 ? 5 : $eventLimit;
        $readScope = $this->readScope($scope);

        try {
            $campaign = $this->store->getCampaign($cid);
        } catch (RuntimeException) {
            throw ApiError::notFound('campaign not found');
        }

        $openSession = null;
        try {
            $openSession = $this->store->getOpenSession($cid);
        } catch (RuntimeException) {
            // No open session is valid.
        }

        try {
            $plots = $this->store->listActivePlots($cid, $readScope);
            $events = $this->store->getRecentEvents($cid, $limit);
            $pending = $this->store->listPendingUpdates($cid);
            $secrets = $readScope->includesDmOnly()
                ? $this->store->listEntitiesByType($cid, 'secret', $readScope)
                : [];
        } catch (Throwable $e) {
            throw ApiError::internal($e);
        }

        $pendingEnriched = [];
        $continuityWarnings = [];

        foreach ($pending as $update) {
            $changes = $this->decodeChanges($update->proposedChanges);
            $warnings = $this->store->checkForConflicts($cid, $changes);
            $pendingEnriched[] = $this->pendingItem($update, $warnings);
            array_push($continuityWarnings, ...$warnings);
        }

        return [
            'campaign' => $campaign->toArray(),
            'open_session' => $openSession?->toArray(),
            'active_plots' => array_map(static fn (Entity $e) => $e->toArray(), $plots),
            'secrets' => array_map(static fn (Entity $e) => $e->toArray(), $secrets),
            'recent_events' => array_map(static fn ($e) => $e->toArray(), $events),
            'pending_updates' => $pendingEnriched,
            'continuity_warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $continuityWarnings),
            'pending_update_count' => count($pendingEnriched),
            'scope' => $readScope->value,
        ];
    }

    public function getEntity(string $entityId, string $scope = 'party'): Entity
    {
        if (trim($entityId) === '') {
            throw ApiError::badRequest('invalid_params', 'entity_id required');
        }

        $readScope = $this->readScope($scope);

        try {
            $entity = $this->store->getEntity($entityId);
        } catch (RuntimeException) {
            throw ApiError::notFound('entity not found');
        }

        if ($entity->type === 'secret' && ! $readScope->includesDmOnly()) {
            throw ApiError::notFound('entity not found');
        }

        return $entity;
    }

    /**
     * @return list<Entity>
     */
    public function listEntities(?string $campaignId, string $entityType, string $scope = 'party'): array
    {
        $cid = $this->campaignId($campaignId);
        $readScope = $this->readScope($scope);

        try {
            return $this->store->listEntitiesByType($cid, $entityType, $readScope);
        } catch (Throwable $e) {
            throw ApiError::internal($e);
        }
    }

    /**
     * @return list<Session>
     */
    public function listSessions(?string $campaignId, int $limit = 20): array
    {
        $cid = $this->campaignId($campaignId);

        try {
            return $this->store->listSessions($cid, $limit);
        } catch (Throwable $e) {
            throw ApiError::internal($e);
        }
    }

    public function getSessionWorkspace(string $sessionId): SessionWorkspace
    {
        if (trim($sessionId) === '') {
            throw ApiError::badRequest('invalid_params', 'session_id required');
        }

        try {
            return $this->store->getSessionWorkspace($sessionId);
        } catch (RuntimeException) {
            throw ApiError::notFound('session not found');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function searchWorld(
        ?string $campaignId,
        string $query,
        int $limit = 20,
        string $scope = 'party',
        bool $hybrid = false,
    ): array {
        if (trim($query) === '') {
            throw ApiError::badRequest('invalid_params', 'query required');
        }

        $cid = $this->campaignId($campaignId);
        $readScope = $this->readScope($scope);
        $limit = $limit <= 0 ? 20 : $limit;

        try {
            if ($hybrid) {
                [$entities, $facts] = $this->store->searchWorldHybrid($cid, $query, $limit, $readScope);
            } else {
                [$entities, $facts] = $this->store->searchWorld($cid, $query, $limit, $readScope);
            }
        } catch (Throwable $e) {
            throw ApiError::internal($e);
        }

        return [
            'entities' => array_map(static fn (Entity $e) => $e->toArray(), $entities),
            'facts' => array_map(static fn ($f) => $f->toArray(), $facts),
            'scope' => $readScope->value,
            'hybrid' => $hybrid,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPendingUpdates(?string $campaignId = null): array
    {
        $cid = $this->campaignId($campaignId);

        try {
            $list = $this->store->listPendingUpdates($cid);
        } catch (Throwable $e) {
            throw ApiError::internal($e);
        }

        $out = [];
        foreach ($list as $update) {
            $changes = $this->decodeChanges($update->proposedChanges);
            $warnings = $this->store->checkForConflicts($cid, $changes);
            $out[] = $this->pendingItem($update, $warnings);
        }

        return $out;
    }

    public function commitWorldUpdate(string $updateId, ?string $sessionId = null): PendingUpdate
    {
        if (trim($updateId) === '') {
            throw ApiError::badRequest('invalid_params', 'update_id required');
        }

        if ($this->role === 'player') {
            throw ApiError::forbidden('player role cannot commit canon updates');
        }

        try {
            $pending = $this->store->getPendingUpdate($updateId);
        } catch (RuntimeException) {
            throw ApiError::notFound('pending update not found');
        }

        $changes = $this->decodeChanges($pending->proposedChanges);

        try {
            $updated = $this->store->commitWorldUpdate($updateId);
        } catch (Throwable $e) {
            throw ApiError::internal($e);
        }

        $sid = trim((string) $sessionId);
        if ($sid === '') {
            try {
                $sid = $this->store->getOpenSession($pending->campaignId)->id;
            } catch (RuntimeException) {
                $sid = '';
            }
        }

        if ($sid !== '') {
            foreach ($changes as $change) {
                $entityId = $this->entityIdFromChange($change);
                if ($entityId !== '') {
                    $this->store->recordSessionChange($sid, $entityId, $change->op);
                }
            }
        }

        return $updated;
    }

    public function rejectWorldUpdate(string $updateId, string $reason = ''): void
    {
        if (trim($updateId) === '') {
            throw ApiError::badRequest('invalid_params', 'update_id required');
        }

        try {
            $this->store->rejectWorldUpdate($updateId, $reason);
        } catch (RuntimeException) {
            throw ApiError::notFound('pending update not found');
        }
    }

    /**
     * @param  list<WorldChange>  $changes
     * @return array<string, mixed>
     */
    public function proposeWorldUpdate(?string $campaignId, array $changes, string $reason = ''): array
    {
        if ($changes === []) {
            throw ApiError::badRequest('invalid_params', 'changes required');
        }

        $cid = $this->campaignId($campaignId);

        try {
            $pending = $this->store->proposeWorldUpdate($cid, $changes, $reason);
        } catch (Throwable $e) {
            throw ApiError::internal($e);
        }

        $warnings = $this->store->checkForConflicts($cid, $changes);

        return [
            'pending_update' => $pending->toArray(),
            'warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $warnings),
        ];
    }

    private function campaignId(?string $raw): string
    {
        $id = trim((string) $raw);

        return $id !== '' ? $id : $this->campaignId;
    }

    private function readScope(string $scope): ReadScope
    {
        $parsed = ReadScope::parse($scope);
        if ($parsed === ReadScope::Dm && $this->role === 'player') {
            throw ApiError::forbidden('player role cannot use dm scope');
        }

        return $parsed;
    }

    /** @return list<WorldChange> */
    private function decodeChanges(string $json): array
    {
        /** @var list<array<string, mixed>>|null $raw */
        $raw = json_decode($json, true);

        if (! is_array($raw)) {
            return [];
        }

        return array_map(static fn (array $row) => WorldChange::fromArray($row), $raw);
    }

    /**
     * @param  list<ConflictWarning>  $warnings
     * @return array<string, mixed>
     */
    private function pendingItem(PendingUpdate $update, array $warnings): array
    {
        return array_merge($update->toArray(), [
            'warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $warnings),
        ]);
    }

    private function entityIdFromChange(WorldChange $change): string
    {
        return match ($change->op) {
            'add_fact' => $change->fact?->entityId ?? '',
            'upsert_entity', 'create_entity', 'update_entity' => $change->entity?->id ?: $change->entityId,
            default => '',
        };
    }
}
