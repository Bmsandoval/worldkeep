<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Data\ConflictWarning;
use App\Services\WorldKeep\Data\WorldChange;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use RuntimeException;

final class SessionHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function startSession(Server $server, array $args): array
    {
        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));

        try {
            $sess = $server->store->startSession($cid, $this->argString($args, 'title'));
            $notes = $this->argString($args, 'notes');
            if ($notes !== '') {
                $sess = $server->store->updateSessionNotes($sess->id, $notes);
            }
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        $server->activeSessionId = $sess->id;

        return [Protocol::toolResultText($sess->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function endSession(Server $server, array $args): array
    {
        $id = $this->argString($args, 'session_id');
        if ($id === '') {
            $id = $server->activeSessionId;
        }
        if ($id === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'session_id required')];
        }

        $notes = $this->argString($args, 'notes');
        if ($notes !== '') {
            try {
                $server->store->updateSessionNotes($id, $notes);
            } catch (RuntimeException $e) {
                return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
            }
        }

        try {
            $sess = $server->store->endSession($id, $this->argString($args, 'summary'));
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        if ($server->activeSessionId === $id) {
            $server->activeSessionId = '';
        }

        $out = ['session' => $sess->toArray()];
        $summary = $this->argString($args, 'summary');
        if ($summary !== '') {
            $out['session_summary'] = $summary;
        }

        $changesRaw = $this->argRaw($args, 'changes');
        if ($changesRaw !== null && $changesRaw !== '' && $changesRaw !== []) {
            $decoded = $this->decodeChangesJson($changesRaw);
            if ($decoded === []) {
                return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'changes must be a JSON array')];
            }

            $changes = array_map(static fn (array $row) => WorldChange::fromArray($row), $decoded);
            $cid = $this->campaignIdArg($server, '');
            $reason = $this->argString($args, 'reason');
            if ($reason === '') {
                $reason = $summary !== '' ? $summary : 'End-of-session canon updates';
            }

            try {
                $pending = $server->store->proposeWorldUpdate($cid, $changes, $reason);
                $warnings = $server->store->checkForConflicts($cid, $changes);
            } catch (RuntimeException $e) {
                return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
            }

            $out['pending_update'] = $pending->toArray();
            $out['warnings'] = array_map(static fn (ConflictWarning $w) => $w->toArray(), $warnings);
            $out['canon_review_queue'] = $decoded;
        }

        return [Protocol::toolResultText($out), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getSession(Server $server, array $args): array
    {
        $sessionId = $this->argString($args, 'session_id');
        if ($sessionId === '') {
            $sessionId = $server->activeSessionId;
        }
        if ($sessionId === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'session_id required')];
        }

        try {
            $workspace = $server->store->getSessionWorkspace($sessionId);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError('session not found: '.$e->getMessage()), null];
        }

        $pending = $server->store->listPendingUpdates($workspace->session->campaignId);

        return [Protocol::toolResultText([
            'workspace' => $workspace->toArray(),
            'pending_updates' => array_map(static fn ($u) => $u->toArray(), $pending),
        ]), null];
    }
}
