<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use RuntimeException;

final class SeatsHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function listCampaignSeats(Server $server, array $args): array
    {
        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));

        try {
            $seats = $server->store->listCampaignSeats($cid);
        } catch (RuntimeException $e) {
            return [[], Protocol::rpcError(Protocol::CODE_INTERNAL_ERROR, $e->getMessage())];
        }

        return [Protocol::toolResultText([
            'campaign_id' => $cid,
            'seats' => array_map(static fn ($s) => $s->toArray(), $seats),
        ]), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getSeat(Server $server, array $args): array
    {
        $seatId = $this->argString($args, 'seat_id');
        if ($seatId === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'seat_id required')];
        }

        try {
            $seat = $server->store->getSeat($seatId);
        } catch (RuntimeException) {
            return [Protocol::toolResultError('seat not found'), null];
        }

        return [Protocol::toolResultText($seat->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function createPlayerSeat(Server $server, array $args): array
    {
        if ($rerr = $this->requireSeatAdmin($server)) {
            return [[], $rerr];
        }

        $actorId = $this->argString($args, 'actor_id');
        if ($actorId === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'actor_id required')];
        }

        $cid = $this->campaignIdArg($server, $this->argString($args, 'campaign_id'));

        try {
            $seat = $server->store->createPlayerSeat($cid, $actorId, $this->argString($args, 'display_name'));
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($seat->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function assignSeatController(Server $server, array $args): array
    {
        if ($rerr = $this->requireSeatAdmin($server)) {
            return [[], $rerr];
        }

        $seatId = $this->argString($args, 'seat_id');
        $controller = $this->argString($args, 'controller');
        if ($seatId === '' || $controller === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'seat_id and controller required')];
        }

        $userId = $this->argString($args, 'controller_user_id');

        try {
            $seat = $server->store->assignSeatController($seatId, $controller, $userId !== '' ? $userId : null);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($seat->toArray()), null];
    }
}
