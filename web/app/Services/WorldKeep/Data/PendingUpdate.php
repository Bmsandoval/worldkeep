<?php

namespace App\Services\WorldKeep\Data;

final readonly class PendingUpdate
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public string $proposedChanges,
        public string $reason,
        public string $status,
        public string $createdAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            campaignId: (string) $row->campaign_id,
            proposedChanges: (string) $row->proposed_changes,
            reason: (string) $row->reason,
            status: (string) $row->status,
            createdAt: (string) $row->created_at,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'proposed_changes' => $this->proposedChanges,
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
