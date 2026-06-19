<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Data\ConflictWarning;
use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Data\WorldChange;
use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use App\Services\WorldKeep\ReadScope;
use RuntimeException;

final class DashboardHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getCampaignDashboard(Server $server, array $args): array
    {
        [$dash, $rerr] = $this->buildCampaignDashboard($server, $args);
        if ($rerr !== null) {
            if (str_starts_with($rerr['message'] ?? '', 'campaign not found')) {
                return [Protocol::toolResultError($rerr['message']), null];
            }

            return [[], $rerr];
        }

        return [Protocol::toolResultText($dash), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function prepareSessionBrief(Server $server, array $args): array
    {
        [$dash, $rerr] = $this->buildCampaignDashboard($server, $args);
        if ($rerr !== null) {
            if (str_starts_with($rerr['message'] ?? '', 'campaign not found')) {
                return [Protocol::toolResultError($rerr['message']), null];
            }

            return [[], $rerr];
        }

        return [Protocol::toolResultText([
            'campaign' => $dash['campaign'],
            'open_session' => $dash['open_session'],
            'active_plots' => $dash['active_plots'],
            'recent_events' => $dash['recent_events'],
            'pending_update_count' => $dash['pending_update_count'],
            'continuity_warnings' => $dash['continuity_warnings'],
            'scope' => $dash['scope'],
        ]), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    private function buildCampaignDashboard(Server $server, array $args): array
    {
        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));
        $limit = $this->argInt($args, 'event_limit');
        if ($limit <= 0) {
            $limit = 5;
        }

        [$sc, $rerr] = $this->readScopeArg($server, $this->argString($args, 'scope'));
        if ($rerr !== null) {
            return [[], $rerr];
        }

        try {
            $campaign = $server->store->getCampaign($cid);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, 'campaign not found: '.$e->getMessage())];
        }

        $openSession = null;
        try {
            $openSession = $server->store->getOpenSession($cid);
        } catch (RuntimeException) {
            // No open session is valid.
        }

        try {
            $plots = $server->store->listActivePlots($cid, $sc);
            $events = $server->store->getRecentEvents($cid, $limit);
            $pending = $server->store->listPendingUpdates($cid);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        $pendingEnriched = [];
        $continuityWarnings = [];

        foreach ($pending as $update) {
            $changes = array_map(
                static fn (array $row) => WorldChange::fromArray($row),
                $this->decodeChangesJson($update->proposedChanges),
            );
            $warnings = $server->store->checkForConflicts($cid, $changes);
            $pendingEnriched[] = array_merge($update->toArray(), [
                'warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $warnings),
            ]);
            array_push($continuityWarnings, ...$warnings);
        }

        return [[
            'campaign' => $campaign->toArray(),
            'open_session' => $openSession?->toArray(),
            'active_plots' => array_map(static fn (Entity $e) => $e->toArray(), $plots),
            'recent_events' => array_map(static fn ($e) => $e->toArray(), $events),
            'pending_updates' => $pendingEnriched,
            'continuity_warnings' => array_map(static fn (ConflictWarning $w) => $w->toArray(), $continuityWarnings),
            'pending_update_count' => count($pendingEnriched),
            'scope' => $sc->value,
        ], null];
    }
}
