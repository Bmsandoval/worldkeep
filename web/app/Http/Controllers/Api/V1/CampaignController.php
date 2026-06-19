<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends ApiController
{
    public function dashboard(Request $request, string $campaignId): JsonResponse
    {
        return $this->run(fn () => $this->ok(
            $this->engine->campaignDashboard(
                $campaignId,
                $this->queryInt($request, 'event_limit', 5),
                (string) $request->query('scope', 'party'),
            )
        ));
    }

    public function search(Request $request, string $campaignId): JsonResponse
    {
        return $this->run(fn () => $this->ok(
            $this->engine->searchWorld(
                $campaignId,
                (string) $request->query('q', ''),
                $this->queryInt($request, 'limit', 20),
                (string) $request->query('scope', 'party'),
                $this->queryBool($request, 'hybrid'),
            )
        ));
    }

    public function entities(Request $request, string $campaignId): JsonResponse
    {
        return $this->run(function () use ($request, $campaignId) {
            $entities = $this->engine->listEntities(
                $campaignId,
                (string) $request->query('type', ''),
                (string) $request->query('scope', 'party'),
            );

            return $this->ok([
                'entities' => array_map(static fn ($e) => self::normalizeEntity($e->toArray()), $entities),
            ]);
        });
    }

    public function sessions(Request $request, string $campaignId): JsonResponse
    {
        return $this->run(function () use ($request, $campaignId) {
            $sessions = $this->engine->listSessions($campaignId, $this->queryInt($request, 'limit', 20));

            return $this->ok([
                'sessions' => array_map(static fn ($s) => $s->toArray(), $sessions),
            ]);
        });
    }

    public function listPendingUpdates(string $campaignId): JsonResponse
    {
        return $this->run(fn () => $this->ok([
            'pending_updates' => $this->engine->listPendingUpdates($campaignId),
        ]));
    }

    public function propose(Request $request, string $campaignId): JsonResponse
    {
        return $this->run(function () use ($request, $campaignId) {
            $changes = $this->decodeChanges($request->input('changes'));
            if ($changes === []) {
                throw \App\Services\WorldKeep\ApiError::badRequest('invalid_params', 'changes must be a non-empty JSON array');
            }

            return $this->ok(
                $this->engine->proposeWorldUpdate($campaignId, $changes, (string) $request->input('reason', '')),
                201,
            );
        });
    }

    /** @param  array<string, mixed>  $entity */
    private static function normalizeEntity(array $entity): array
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
