<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WorldKeep\ApiError;
use App\Services\WorldKeep\Data\WorldChange;
use App\Services\WorldKeep\Engine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    public function __construct(protected readonly Engine $engine) {}

    protected function ok(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function run(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (ApiError $e) {
            return response()->json([
                'error' => $e->code,
                'message' => $e->getMessage(),
            ], $e->httpStatus);
        }
    }

    /** @return list<WorldChange> */
    protected function decodeChanges(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            return [];
        }

        if (! is_array($decoded) || $decoded === []) {
            return [];
        }

        return array_map(static fn (array $row) => WorldChange::fromArray($row), $decoded);
    }

    protected function queryInt(Request $request, string $key, int $default): int
    {
        $raw = trim((string) $request->query($key, ''));
        if ($raw === '' || ! ctype_digit($raw)) {
            return $default;
        }

        return (int) $raw;
    }

    protected function queryBool(Request $request, string $key): bool
    {
        $raw = strtolower(trim((string) $request->query($key, '')));

        return in_array($raw, ['1', 'true', 'yes'], true);
    }
}
