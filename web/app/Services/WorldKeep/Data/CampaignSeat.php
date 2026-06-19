<?php

namespace App\Services\WorldKeep\Data;

final readonly class CampaignSeat
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public string $seatType,
        public string $controller,
        public ?string $controllerUserId,
        public ?string $actorId,
        public string $displayName,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            campaignId: (string) $row->campaign_id,
            seatType: (string) $row->seat_type,
            controller: (string) $row->controller,
            controllerUserId: $row->controller_user_id !== null ? (string) $row->controller_user_id : null,
            actorId: $row->actor_id !== null ? (string) $row->actor_id : null,
            displayName: (string) $row->display_name,
            status: (string) $row->status,
            createdAt: (string) $row->created_at,
            updatedAt: (string) $row->updated_at,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'seat_type' => $this->seatType,
            'controller' => $this->controller,
            'controller_user_id' => $this->controllerUserId,
            'actor_id' => $this->actorId,
            'display_name' => $this->displayName,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
