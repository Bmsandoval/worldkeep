<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use App\Services\WorldKeep\Open5e\Client as Open5eClient;
use RuntimeException;

final class Open5eHandler
{
    use HandlerSupport;

    private function client(Server $server): Open5eClient
    {
        return $server->open5e ?? new Open5eClient;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function searchRulesReference(Server $server, array $args): array
    {
        $query = $this->argString($args, 'query');
        if ($query === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'query required')];
        }

        try {
            $res = $this->client($server)->searchRulesReference($query, $this->argInt($args, 'limit'));
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($res), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getRulesSection(Server $server, array $args): array
    {
        $key = $this->argString($args, 'key');
        if ($key === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'key required (Open5e rule key from search_rules_reference)')];
        }

        try {
            $rule = $this->client($server)->getRulesSection($key);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($rule), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function searchSpells(Server $server, array $args): array
    {
        $query = $this->argString($args, 'query');
        if ($query === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'query required')];
        }

        try {
            $res = $this->client($server)->searchSpells($query, $this->argInt($args, 'limit'));
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($res), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getSpell(Server $server, array $args): array
    {
        try {
            $spell = $this->client($server)->resolveSpell(
                $this->argString($args, 'key'),
                $this->argString($args, 'name'),
            );
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($spell), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function searchCreatures(Server $server, array $args): array
    {
        $query = $this->argString($args, 'query');
        if ($query === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'query required')];
        }

        try {
            $res = $this->client($server)->searchCreatures($query, $this->argInt($args, 'limit'));
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($res), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getCreature(Server $server, array $args): array
    {
        try {
            $creature = $this->client($server)->resolveCreature(
                $this->argString($args, 'key'),
                $this->argString($args, 'name'),
            );
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($creature), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getCondition(Server $server, array $args): array
    {
        $name = $this->argString($args, 'name');
        $key = $this->argString($args, 'key');
        if ($name === '' && $key === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'name or key required')];
        }

        try {
            $cond = $this->client($server)->getCondition($name, $key);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($cond), null];
    }
}
