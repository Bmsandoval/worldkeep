<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use App\Services\WorldKeep\ReadScope;

trait HandlerSupport
{
    protected function campaignIdArg(Server $server, string $raw): string
    {
        $id = trim($raw);

        return $id !== '' ? $id : $server->campaignId;
    }

    /**
     * @return array{0: ReadScope, 1: array<string, mixed>|null}
     */
    protected function readScopeArg(Server $server, string $scope): array
    {
        $parsed = ReadScope::parse($scope);
        if ($parsed === ReadScope::Dm && $server->role === 'player') {
            return [$parsed, Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'player role cannot use dm scope')];
        }

        return [$parsed, null];
    }

    protected function requireSeatAdmin(Server $server): ?array
    {
        if ($server->role === 'player') {
            return Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'player role cannot manage seats');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $args
     */
    protected function argString(array $args, string $key, string $default = ''): string
    {
        return trim((string) ($args[$key] ?? $default));
    }

    /**
     * @param  array<string, mixed>  $args
     */
    protected function argInt(array $args, string $key, int $default = 0): int
    {
        if (! array_key_exists($key, $args)) {
            return $default;
        }

        return (int) $args[$key];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    protected function argBool(array $args, string $key, bool $default = false): bool
    {
        if (! array_key_exists($key, $args)) {
            return $default;
        }

        return (bool) $args[$key];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return mixed
     */
    protected function argRaw(array $args, string $key): mixed
    {
        return $args[$key] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function decodeChangesJson(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
