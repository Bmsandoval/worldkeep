<?php

namespace App\Services;

use App\Services\WorldKeep\ApiError;
use App\Services\WorldKeep\Data\Campaign;
use App\Services\WorldKeep\Engine;
use App\Support\UserUiPreferences;
use RuntimeException;

class WorldKeepClient
{
    public function __construct(
        private readonly Engine $engine,
        private readonly UserUiPreferences $preferences,
    ) {}

    public static function fromConfig(): self
    {
        return app(self::class);
    }

    public function activeCampaignId(): string
    {
        return $this->preferences->activeCampaignId();
    }

    public function activeCampaign(): Campaign
    {
        return $this->preferences->activeCampaign();
    }

    public function dashboard(int $eventLimit = 5, ?string $scope = null): array
    {
        return $this->engine->campaignDashboard(
            $this->activeCampaignId(),
            $eventLimit,
            $this->readScope($scope),
        );
    }

    public function listPendingUpdates(): array
    {
        return $this->engine->listPendingUpdates($this->activeCampaignId());
    }

    public function commitUpdate(string $updateId, ?string $sessionId = null): array
    {
        $updated = $this->run(fn () => $this->engine->commitWorldUpdate($updateId, $sessionId));

        return $updated->toArray();
    }

    public function rejectUpdate(string $updateId, string $reason = ''): array
    {
        $this->run(fn () => $this->engine->rejectWorldUpdate($updateId, $reason));

        return ['status' => 'rejected', 'update_id' => $updateId];
    }

    /** @return array<int, array<string, mixed>> */
    public function listEntities(?string $type = null, ?string $scope = null): array
    {
        $entities = $this->run(fn () => $this->engine->listEntities(
            $this->activeCampaignId(),
            $type ?? '',
            $this->readScope($scope),
        ));

        return array_map(fn ($entity) => $this->normalizeEntity($entity->toArray()), $entities);
    }

    /** @return array<string, mixed> */
    public function getEntity(string $entityId, ?string $scope = null): array
    {
        $entity = $this->run(fn () => $this->engine->getEntity($entityId, $this->readScope($scope)));

        return $this->normalizeEntity($entity->toArray());
    }

    /** @return array<string, mixed> */
    public function searchWorld(string $query, int $limit = 20, ?string $scope = null, bool $hybrid = false): array
    {
        $result = $this->run(fn () => $this->engine->searchWorld(
            $this->activeCampaignId(),
            $query,
            $limit,
            $this->readScope($scope),
            $hybrid,
        ));

        $result['entities'] = array_map(
            fn (array $row) => $this->normalizeEntity($row),
            $result['entities'] ?? [],
        );

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function listSessions(int $limit = 20): array
    {
        $sessions = $this->run(fn () => $this->engine->listSessions($this->activeCampaignId(), $limit));

        return array_map(static fn ($session) => $session->toArray(), $sessions);
    }

    /** @return array<string, mixed> */
    public function getSession(string $sessionId): array
    {
        return $this->run(fn () => $this->engine->getSessionWorkspace($sessionId)->toArray());
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    private function run(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (ApiError $e) {
            throw new RuntimeException('WorldKeep API error: '.$e->getMessage(), 0, $e);
        }
    }

    private function readScope(?string $scope): string
    {
        return $scope ?? $this->preferences->readScope();
    }

    /** @param  array<string, mixed>  $entity */
    private function normalizeEntity(array $entity): array
    {
        if (isset($entity['data']) && is_string($entity['data'])) {
            $decoded = json_decode($entity['data'], true);
            if (is_array($decoded)) {
                $entity['data'] = $decoded;
            }
        }

        return $entity;
    }
}
