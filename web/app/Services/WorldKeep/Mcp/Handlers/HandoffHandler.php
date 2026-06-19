<?php

namespace App\Services\WorldKeep\Mcp\Handlers;

use App\Services\WorldKeep\Mcp\Protocol;
use App\Services\WorldKeep\Mcp\Server;
use RuntimeException;

final class HandoffHandler
{
    use HandlerSupport;

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function handoffSeat(Server $server, array $args): array
    {
        if ($rerr = $this->requireSeatAdmin($server)) {
            return [[], $rerr];
        }

        $seatId = $this->argString($args, 'seat_id');
        $controller = $this->argString($args, 'controller');
        if ($seatId === '' || $controller === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'seat_id and controller required')];
        }

        try {
            $seat = $server->store->getSeat($seatId);
        } catch (RuntimeException) {
            return [Protocol::toolResultError('seat not found'), null];
        }

        $sessionId = $this->argString($args, 'session_id');
        $sessionRef = $sessionId !== '' ? $sessionId : $this->openSessionId($server, $seat->campaignId);
        $userId = $this->argString($args, 'controller_user_id');

        try {
            $result = $server->store->handoffSeat(
                $seatId,
                $controller,
                $userId !== '' ? $userId : null,
                $this->argString($args, 'reason'),
                $sessionRef,
            );
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($result->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function releaseSeatToAI(Server $server, array $args): array
    {
        if ($rerr = $this->requireSeatAdmin($server)) {
            return [[], $rerr];
        }

        $seatId = $this->argString($args, 'seat_id');
        if ($seatId === '') {
            return [[], Protocol::rpcError(Protocol::CODE_INVALID_PARAMS, 'seat_id required')];
        }

        try {
            $seat = $server->store->getSeat($seatId);
        } catch (RuntimeException) {
            return [Protocol::toolResultError('seat not found'), null];
        }

        $sessionId = $this->argString($args, 'session_id');
        $sessionRef = $sessionId !== '' ? $sessionId : $this->openSessionId($server, $seat->campaignId);

        try {
            $result = $server->store->releaseSeatToAI($seatId, $this->argString($args, 'reason'), $sessionRef);
        } catch (RuntimeException $e) {
            return [Protocol::toolResultError($e->getMessage()), null];
        }

        return [Protocol::toolResultText($result->toArray()), null];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    public function getSessionFloor(Server $server, array $args): array
    {
        $id = $this->argString($args, 'session_id');
        if ($id === '') {
            $id = $server->activeSessionId;
        }
        if ($id === '') {
            try {
                $id = $server->store->getOpenSession($server->campaignId)->id;
            } catch (RuntimeException) {
                return [Protocol::toolResultError('no open session'), null];
            }
        }

        try {
            $floor = $server->store->getSessionFloor($id);
        } catch (RuntimeException) {
            return [Protocol::toolResultError('session floor not found'), null];
        }

        return [Protocol::toolResultText($floor->toArray()), null];
    }

    private function openSessionId(Server $server, string $campaignId): ?string
    {
        if (trim($server->activeSessionId) !== '') {
            return $server->activeSessionId;
        }

        try {
            return $server->store->getOpenSession($campaignId)->id;
        } catch (RuntimeException) {
            return null;
        }
    }
}
