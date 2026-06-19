<?php

namespace App\Services\WorldKeep\Data;

final readonly class SeatHandoffResult
{
    public function __construct(
        public CampaignSeat $seat,
        public Event $auditEvent,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'seat' => $this->seat->toArray(),
            'audit_event' => $this->auditEvent->toArray(),
        ];
    }
}
