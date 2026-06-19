<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class SessionController extends ApiController
{
    public function show(string $sessionId): JsonResponse
    {
        return $this->run(fn () => $this->ok(
            $this->engine->getSessionWorkspace($sessionId)->toArray()
        ));
    }
}
