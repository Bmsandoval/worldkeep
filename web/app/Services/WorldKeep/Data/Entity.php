<?php

namespace App\Services\WorldKeep\Data;

final readonly class Entity
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public string $type,
        public string $name,
        public string $summary,
        public string $data,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            campaignId: (string) $row->campaign_id,
            type: (string) $row->type,
            name: (string) $row->name,
            summary: (string) $row->summary,
            data: (string) $row->data,
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
            'type' => $this->type,
            'name' => $this->name,
            'summary' => $this->summary,
            'data' => $this->data,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
