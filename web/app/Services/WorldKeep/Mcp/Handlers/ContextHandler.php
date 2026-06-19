<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use App\Services\WorldKeep\ReadScope;
use RuntimeException;

final class ContextHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function compileSceneContext(Server $server, array $args): array
    {
        $prompt = $this->argString($args, 'prompt');
        if ($prompt === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'prompt required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $limit = $this->argInt($args, 'limit');
        if ($limit <= 0) {
            $limit = 10;
        }

        [$sc, $rerr] = $this->readScopeArg($server, $this->argString($args, 'scope'));
        if ($rerr !== null) {
            return [[], $rerr];
        }

        $hybrid = $this->argBool($args, 'hybrid');

        try {
            if ($hybrid) {
                [$entities, $facts] = $server->store->searchWorldHybrid($cid, $prompt, $limit, $sc);
            } else {
                [$entities, $facts] = $server->store->searchWorld($cid, $prompt, $limit, $sc);
            }
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        $tokens = $this->tokenizePrompt($prompt);
        foreach (['npc', 'location', 'faction'] as $entityType) {
            try {
                $all = $server->store->listEntitiesByType($cid, $entityType, $sc);
            } catch (RuntimeException) {
                continue;
            }

            foreach ($all as $entity) {
                foreach ($entities as $existing) {
                    if ($existing->id === $entity->id) {
                        continue 2;
                    }
                }

                $name = strtolower($entity->name);
                foreach ($tokens as $tok) {
                    if (str_contains($name, $tok)) {
                        $entities[] = $entity;
                        break;
                    }
                }
            }
        }

        try {
            $plots = $server->store->listActivePlots($cid, $sc);
            $entities = $this->appendNpcsAtLocations($server, $cid, $entities, $sc);
            $events = $server->store->getRecentEvents($cid, $limit);
            $rulings = $server->store->searchRulings($cid, $prompt, 5);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText([
            'prompt' => $prompt,
            'scope' => $sc->value,
            'actors' => array_map(static fn (Entity $e) => $e->toArray(), $this->filterEntitiesByTypes($entities, 'npc', 'faction')),
            'locations' => array_map(static fn (Entity $e) => $e->toArray(), $this->filterEntitiesByTypes($entities, 'location')),
            'facts' => array_map(static fn ($f) => $f->toArray(), $facts),
            'plots' => array_map(static fn (Entity $e) => $e->toArray(), $plots),
            'events' => array_map(static fn ($e) => $e->toArray(), $events),
            'rulings' => array_map(static fn ($r) => $r->toArray(), $rulings),
        ]), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getRecentEvents(Server $server, array $args): array
    {
        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));

        try {
            $events = $server->store->getRecentEvents($cid, $this->argInt($args, 'limit'));
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText(array_map(static fn ($e) => $e->toArray(), $events)), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getActivePlots(Server $server, array $args): array
    {
        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        [$sc, $rerr] = $this->readScopeArg($server, $this->argString($args, 'scope'));
        if ($rerr !== null) {
            return [[], $rerr];
        }

        try {
            $plots = $server->store->listActivePlots($cid, $sc);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText(array_map(static fn (Entity $e) => $e->toArray(), $plots)), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function searchRulings(Server $server, array $args): array
    {
        $query = $this->argString($args, 'query');
        if ($query === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'query required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));

        try {
            $rulings = $server->store->searchRulings($cid, $query, $this->argInt($args, 'limit'));
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText(array_map(static fn ($r) => $r->toArray(), $rulings)), null];
    }

    /** @return list<string> */
    private function tokenizePrompt(string $prompt): array
    {
        $words = preg_split('/\s+/', strtolower($prompt)) ?: [];
        $tokens = [];

        foreach ($words as $word) {
            $word = trim($word, ".,!?\"'");
            if (strlen($word) >= 3) {
                $tokens[] = $word;
            }
        }

        return $tokens;
    }

    /**
     * @param  list<Entity>  $entities
     * @return list<Entity>
     */
    private function filterEntitiesByTypes(array $entities, string ...$types): array
    {
        $allowed = array_fill_keys($types, true);
        $out = [];

        foreach ($entities as $entity) {
            if (isset($allowed[$entity->type])) {
                $out[] = $entity;
            }
        }

        return $out;
    }

    /**
     * @param  list<Entity>  $entities
     * @return list<Entity>
     */
    private function appendNpcsAtLocations(Server $server, string $campaignId, array $entities, ReadScope $scope): array
    {
        $locationIds = [];
        foreach ($entities as $entity) {
            if ($entity->type === 'location') {
                $locationIds[$entity->id] = true;
            }
        }

        if ($locationIds === []) {
            return $entities;
        }

        try {
            $npcs = $server->store->listEntitiesByType($campaignId, 'npc', $scope);
        } catch (RuntimeException) {
            return $entities;
        }

        $seen = [];
        foreach ($entities as $entity) {
            $seen[$entity->id] = true;
        }

        foreach ($npcs as $npc) {
            if (isset($seen[$npc->id])) {
                continue;
            }

            $data = json_decode($npc->data, true);
            if (! is_array($data)) {
                continue;
            }

            $locId = (string) ($data['location_id'] ?? '');
            if ($locId !== '' && isset($locationIds[$locId])) {
                $entities[] = $npc;
                $seen[$npc->id] = true;
            }
        }

        return $entities;
    }
}
