<?php

namespace App\Services\WorldKeep\Data;

final readonly class Event
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public ?string $sessionId,
        public string $title,
        public string $summary,
        public string $entityIds,
        public string $createdAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            campaignId: (string) $row->campaign_id,
            sessionId: $row->session_id !== null ? (string) $row->session_id : null,
            title: (string) $row->title,
            summary: (string) $row->summary,
            entityIds: (string) $row->entity_ids,
            createdAt: (string) $row->created_at,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'session_id' => $this->sessionId,
            'title' => $this->title,
            'summary' => $this->summary,
            'entity_ids' => $this->entityIds,
            'created_at' => $this->createdAt,
        ];
    }
}
