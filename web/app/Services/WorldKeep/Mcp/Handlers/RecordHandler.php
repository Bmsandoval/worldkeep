<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Data\Event;
use App\Services\WorldKeep\Data\Ruling;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use Illuminate\Support\Str;
use RuntimeException;

final class RecordHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function recordEvent(Server $server, array $args): array
    {
        $title = $this->argString($args, 'title');
        $summary = $this->argString($args, 'summary');
        if ($title === '' || $summary === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'title and summary required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $sid = $this->argString($args, 'session_id');
        if ($sid === '') {
            $sid = $server->activeSessionId;
        }

        $entityIdsRaw = $this->argRaw($args, 'entity_ids');
        if ($entityIdsRaw === null) {
            $entityIds = '[]';
        } elseif (is_string($entityIdsRaw)) {
            $entityIds = $entityIdsRaw;
        } else {
            $entityIds = json_encode($entityIdsRaw) ?: '[]';
        }

        $uuid = Str::uuid()->toString();
        $ev = new Event(
            id: 'event_'.substr($uuid, 0, 8),
            campaignId: $cid,
            sessionId: $sid !== '' ? $sid : null,
            title: $title,
            summary: $summary,
            entityIds: $entityIds,
            createdAt: '',
        );

        try {
            $server->store->addEvent($ev);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText($ev->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function recordRuling(Server $server, array $args): array
    {
        $question = $this->argString($args, 'question');
        $answer = $this->argString($args, 'answer');
        if ($question === '' || $answer === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'question and answer required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $scope = $this->argString($args, 'scope', 'campaign');

        $r = new Ruling(
            id: 'ruling_'.substr(Str::uuid()->toString(), 0, 8),
            campaignId: $cid,
            question: $question,
            answer: $answer,
            scope: $scope,
            system: '',
            createdAt: '',
        );

        try {
            $server->store->addRuling($r);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, 'record ruling: '.$e->getMessage())];
        }

        return [Protocol::toolResultText($r->toArray()), null];
    }
}
