<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Data\WorldChange;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use App\Services\WorldKeep\Services\ImportMarkdown;
use Illuminate\Support\Str;
use RuntimeException;

final class MvpHandler
{
    use HandlerSupport;

    public function __construct(
        private readonly WriteHandler $writeHandler,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function createSecret(Server $server, array $args): array
    {
        if ($server->role === 'player') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'player role cannot create secrets')];
        }

        $title = $this->argString($args, 'title');
        if ($title === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'title required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $text = $this->argString($args, 'text');
        $summary = $text !== '' ? $text : $title;
        $reason = $this->argString($args, 'reason');
        if ($reason === '') {
            $reason = 'Create DM secret: '.$title;
        }

        $changes = [[
            'op' => 'create_entity',
            'entity' => [
                'id' => 'secret_'.substr(Str::uuid()->toString(), 0, 8),
                'campaign_id' => $cid,
                'type' => 'secret',
                'name' => $title,
                'summary' => $summary,
                'data' => ['visibility' => 'dm_only'],
            ],
        ]];

        return $this->writeHandler->proposeWorldUpdate($server, [
            'campaign_id' => $cid,
            'changes' => $changes,
            'reason' => $reason,
        ]);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function importCampaignMarkdown(Server $server, array $args): array
    {
        if ($server->role === 'player') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'player role cannot import campaigns')];
        }

        $markdown = $this->argString($args, 'markdown');
        if ($markdown === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'markdown required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $entities = ImportMarkdown::parse($markdown);
        if ($entities === []) {
            return [Protocol::toolResultError('no entities parsed from markdown'), null];
        }

        $changes = [];
        foreach ($entities as $entity) {
            $entity = new Entity(
                id: $entity->id !== '' ? $entity->id : $entity->type.'_'.substr(Str::uuid()->toString(), 0, 8),
                campaignId: $cid,
                type: $entity->type,
                name: $entity->name,
                summary: $entity->summary,
                data: $entity->data,
                createdAt: '',
                updatedAt: '',
            );
            $changes[] = ['op' => 'create_entity', 'entity' => $entity->toArray()];
        }

        $reason = $this->argString($args, 'reason');
        if ($reason === '') {
            $reason = sprintf('Import %d entities from markdown', count($changes));
        }

        return $this->writeHandler->proposeWorldUpdate($server, [
            'campaign_id' => $cid,
            'changes' => $changes,
            'reason' => $reason,
        ]);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function setCampaignRole(Server $server, array $args): array
    {
        $role = $this->argString($args, 'role');
        if ($role === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'role required (owner, dm, player)')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));

        try {
            $server->store->setCampaignRole($cid, $role);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText(['campaign_id' => $cid, 'role' => $role]), null];
    }
}
