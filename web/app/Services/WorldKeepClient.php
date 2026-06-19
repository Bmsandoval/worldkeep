<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WorldKeepClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $campaignId,
        private readonly ?string $apiToken = null,
        private readonly int $timeout = 15,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('worldkeep.internal_url'),
            config('worldkeep.campaign_id'),
            config('worldkeep.api_token'),
            config('worldkeep.timeout_seconds'),
        );
    }

    public function dashboard(int $eventLimit = 5, string $scope = 'party'): array
    {
        return $this->get("/api/v1/campaigns/{$this->campaignId}/dashboard", [
            'event_limit' => $eventLimit,
            'scope' => $scope,
        ]);
    }

    public function listPendingUpdates(): array
    {
        $data = $this->get("/api/v1/campaigns/{$this->campaignId}/pending-updates");

        return $data['pending_updates'] ?? [];
    }

    public function commitUpdate(string $updateId, ?string $sessionId = null): array
    {
        $body = $sessionId ? ['session_id' => $sessionId] : [];

        return $this->post("/api/v1/pending-updates/{$updateId}/commit", $body);
    }

    public function rejectUpdate(string $updateId, string $reason = ''): array
    {
        return $this->post("/api/v1/pending-updates/{$updateId}/reject", [
            'reason' => $reason,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listEntities(?string $type = null, string $scope = 'party'): array
    {
        $query = ['scope' => $scope];
        if ($type !== null && $type !== '') {
            $query['type'] = $type;
        }
        $data = $this->get("/api/v1/campaigns/{$this->campaignId}/entities", $query);

        return $data['entities'] ?? [];
    }

    /** @return array<string, mixed> */
    public function getEntity(string $entityId, string $scope = 'party'): array
    {
        return $this->get("/api/v1/entities/{$entityId}", ['scope' => $scope]);
    }

    /** @return array<string, mixed> */
    public function searchWorld(string $query, int $limit = 20, string $scope = 'party', bool $hybrid = false): array
    {
        return $this->get("/api/v1/campaigns/{$this->campaignId}/search", [
            'q' => $query,
            'limit' => $limit,
            'scope' => $scope,
            'hybrid' => $hybrid ? 'true' : 'false',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listSessions(int $limit = 20): array
    {
        $data = $this->get("/api/v1/campaigns/{$this->campaignId}/sessions", [
            'limit' => $limit,
        ]);

        return $data['sessions'] ?? [];
    }

    /** @return array<string, mixed> */
    public function getSession(string $sessionId): array
    {
        return $this->get("/api/v1/sessions/{$sessionId}");
    }

    private function get(string $path, array $query = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->headers())
                ->get($this->baseUrl.$path, $query);
            $response->throw();

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            throw new RuntimeException('WorldKeep engine is not reachable at '.$this->baseUrl, 0, $e);
        } catch (RequestException $e) {
            $message = $e->response?->json('message') ?? $e->getMessage();
            throw new RuntimeException('WorldKeep API error: '.$message, 0, $e);
        }
    }

    private function post(string $path, array $body = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->headers())
                ->post($this->baseUrl.$path, $body);
            $response->throw();

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            throw new RuntimeException('WorldKeep engine is not reachable at '.$this->baseUrl, 0, $e);
        } catch (RequestException $e) {
            $message = $e->response?->json('message') ?? $e->getMessage();
            throw new RuntimeException('WorldKeep API error: '.$message, 0, $e);
        }
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        if ($this->apiToken === null || $this->apiToken === '') {
            return ['Accept' => 'application/json'];
        }

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiToken,
        ];
    }
}
