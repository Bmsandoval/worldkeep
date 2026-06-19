<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityController extends ApiController
{
    public function show(Request $request, string $entityId): JsonResponse
    {
        return $this->run(function () use ($request, $entityId) {
            $entity = $this->engine->getEntity($entityId, (string) $request->query('scope', 'party'));
            $row = $entity->toArray();
            if (is_string($row['data'] ?? null)) {
                $decoded = json_decode($row['data'], true);
                if (is_array($decoded)) {
                    $row['data'] = $decoded;
                }
            }

            return $this->ok($row);
        });
    }
}
