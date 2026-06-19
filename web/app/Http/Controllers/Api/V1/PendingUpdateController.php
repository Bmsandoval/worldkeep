<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingUpdateController extends ApiController
{
    public function commit(Request $request, string $updateId): JsonResponse
    {
        return $this->run(function () use ($request, $updateId) {
            $updated = $this->engine->commitWorldUpdate(
                $updateId,
                $request->input('session_id'),
            );

            return $this->ok($updated->toArray());
        });
    }

    public function reject(Request $request, string $updateId): JsonResponse
    {
        return $this->run(function () use ($request, $updateId) {
            $this->engine->rejectWorldUpdate($updateId, (string) $request->input('reason', ''));

            return $this->ok([
                'status' => 'rejected',
                'update_id' => $updateId,
            ]);
        });
    }
}
