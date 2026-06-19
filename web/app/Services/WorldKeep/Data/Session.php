<?php

namespace App\Services\WorldKeep\Data;

final readonly class Session
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public string $title,
        public string $status,
        public string $notes,
        public string $summary,
        public string $startedAt,
        public ?string $endedAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            campaignId: (string) $row->campaign_id,
            title: (string) $row->title,
            status: (string) $row->status,
            notes: (string) ($row->notes ?? ''),
            summary: (string) ($row->summary ?? ''),
            startedAt: (string) $row->started_at,
            endedAt: $row->ended_at !== null ? (string) $row->ended_at : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'title' => $this->title,
            'status' => $this->status,
            'notes' => $this->notes,
            'summary' => $this->summary,
            'started_at' => $this->startedAt,
            'ended_at' => $this->endedAt,
        ];
    }
}
