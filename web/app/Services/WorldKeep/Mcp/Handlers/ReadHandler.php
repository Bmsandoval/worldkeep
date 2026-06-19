<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use RuntimeException;

final class ReadHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getCampaignOverview(Server $server, array $args): array
    {
        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        [$sc, $rerr] = $this->readScopeArg($server, $this->argString($args, 'scope'));
        if ($rerr !== null) {
            return [[], $rerr];
        }

        try {
            $campaign = $server->store->getCampaign($cid);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError('campaign not found: '.$e->getMessage()), null];
        }

        try {
            $plots = $server->store->listActivePlots($cid, $sc);
            $npcs = $server->store->listEntitiesByType($cid, 'npc', $sc);
            $factions = $server->store->listEntitiesByType($cid, 'faction', $sc);
            $locations = $server->store->listEntitiesByType($cid, 'location', $sc);
            $secrets = $server->store->listEntitiesByType($cid, 'secret', $sc);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText([
            'campaign' => $campaign->toArray(),
            'plots' => array_map(static fn (Entity $e) => $e->toArray(), $plots),
            'npcs' => array_map(static fn (Entity $e) => $e->toArray(), $npcs),
            'factions' => array_map(static fn (Entity $e) => $e->toArray(), $factions),
            'locations' => array_map(static fn (Entity $e) => $e->toArray(), $locations),
            'secrets' => array_map(static fn (Entity $e) => $e->toArray(), $secrets),
            'scope' => $sc->value,
        ]), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getEntity(Server $server, array $args): array
    {
        $entityId = $this->argString($args, 'entity_id');
        if ($entityId === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'entity_id required')];
        }

        [$sc, $rerr] = $this->readScopeArg($server, $this->argString($args, 'scope'));
        if ($rerr !== null) {
            return [[], $rerr];
        }

        try {
            $entity = $server->store->getEntity($entityId);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError('entity not found: '.$e->getMessage()), null];
        }

        if ($entity->type === 'secret' && ! $sc->includesDmOnly()) {
            return [Protocol::toolResultError('entity not found'), null];
        }

        return [Protocol::toolResultText($entity->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function searchWorld(Server $server, array $args): array
    {
        $query = $this->argString($args, 'query');
        if ($query === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'query required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        [$sc, $rerr] = $this->readScopeArg($server, $this->argString($args, 'scope'));
        if ($rerr !== null) {
            return [[], $rerr];
        }

        $limit = $this->argInt($args, 'limit');
        $hybrid = $this->argBool($args, 'hybrid');

        try {
            if ($hybrid) {
                [$entities, $facts] = $server->store->searchWorldHybrid($cid, $query, $limit, $sc);
            } else {
                [$entities, $facts] = $server->store->searchWorld($cid, $query, $limit, $sc);
            }
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText([
            'entities' => array_map(static fn (Entity $e) => $e->toArray(), $entities),
            'facts' => array_map(static fn ($f) => $f->toArray(), $facts),
            'scope' => $sc->value,
            'hybrid' => $hybrid,
        ]), null];
    }
}
