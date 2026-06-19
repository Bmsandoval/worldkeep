<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Data\ConflictWarning;
use App\Services\WorldKeep\Data\WorldChange;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use RuntimeException;

final class WriteHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function proposeWorldUpdate(Server $server, array $args): array
    {
        $changesRaw = $this->argRaw($args, 'changes');
        if ($changesRaw === null || $changesRaw === '' || $changesRaw === []) {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'changes required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $decoded = $this->decodeChangesJson($changesRaw);
        if ($decoded === []) {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'changes must be a JSON array')];
        }

        $changes = array_map(static fn (array $row) => WorldChange::fromArray($row), $decoded);

        try {
            $pending = $server->store->proposeWorldUpdate($cid, $changes, $this->argString($args, 'reason'));
            $warnings = $server->store->checkForConflicts($cid, $changes);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText([
            'pending_update' => $pending->toArray(),
            'warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $warnings),
        ]), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function listPendingUpdates(Server $server, array $args): array
    {
        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));

        try {
            $list = $server->store->listPendingUpdates($cid);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        $enriched = [];
        foreach ($list as $update) {
            $changes = array_map(
                static fn (array $row) => WorldChange::fromArray($row),
                $this->decodeChangesJson($update->proposedChanges),
            );
            $warnings = $server->store->checkForConflicts($cid, $changes);
            $enriched[] = array_merge($update->toArray(), [
                'warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $warnings),
            ]);
        }

        return [Protocol::toolResultText($enriched), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function commitWorldUpdate(Server $server, array $args): array
    {
        $updateId = $this->argString($args, 'update_id');
        if ($updateId === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'update_id required')];
        }

        if ($server->role === 'player') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'player role cannot commit canon updates')];
        }

        try {
            $pending = $server->store->getPendingUpdate($updateId);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError('commit failed: '.$e->getMessage()), null];
        }

        $changes = array_map(
            static fn (array $row) => WorldChange::fromArray($row),
            $this->decodeChangesJson($pending->proposedChanges),
        );

        try {
            $updated = $server->store->commitWorldUpdate($updateId);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError('commit failed: '.$e->getMessage()), null];
        }

        if ($server->activeSessionId !== '') {
            foreach ($changes as $change) {
                $entityId = $this->entityIdFromChange($change);
                if ($entityId !== '') {
                    $server->store->recordSessionChange($server->activeSessionId, $entityId, $change->op);
                }
            }
        }

        return [Protocol::toolResultText($updated->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function rejectWorldUpdate(Server $server, array $args): array
    {
        $updateId = $this->argString($args, 'update_id');
        if ($updateId === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'update_id required')];
        }

        try {
            $server->store->rejectWorldUpdate($updateId, $this->argString($args, 'reason'));
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError('reject failed: '.$e->getMessage()), null];
        }

        return [Protocol::toolResultText(['status' => 'rejected', 'update_id' => $updateId]), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function checkForConflicts(Server $server, array $args): array
    {
        $changesRaw = $this->argRaw($args, 'changes');
        if ($changesRaw === null || $changesRaw === '' || $changesRaw === []) {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'changes required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $decoded = $this->decodeChangesJson($changesRaw);
        if ($decoded === []) {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'changes must be a JSON array')];
        }

        $changes = array_map(static fn (array $row) => WorldChange::fromArray($row), $decoded);

        try {
            $warnings = $server->store->checkForConflicts($cid, $changes);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText([
            'warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $warnings),
        ]), null];
    }

    private function entityIdFromChange(WorldChange $change): string
    {
        return match ($change->op) {
            'add_fact' => $change->fact?->entityId ?? '',
            'upsert_entity', 'create_entity', 'update_entity' => $change->entity?->id ?: $change->entityId,
            default => '',
        };
    }
}
