<?php

namespace App\Services\WorldKeep\Data;

final readonly class SessionFloor
{
    /**
     * @param  list<string>  $partyBeatQueue
     */
    public function __construct(
        public string $sessionId,
        public ?string $floorSeatId,
        public array $partyBeatQueue,
        public bool $awaitingPlayerCheckpoint,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'floor_seat_id' => $this->floorSeatId,
            'party_beat_queue' => $this->partyBeatQueue,
            'awaiting_player_checkpoint' => $this->awaitingPlayerCheckpoint,
        ];
    }
}
