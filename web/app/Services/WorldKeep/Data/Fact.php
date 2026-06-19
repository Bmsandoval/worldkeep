<?php

namespace App\Services\WorldKeep\Data;

final readonly class Fact
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public ?string $entityId,
        public string $text,
        public string $visibility,
        public string $confidence,
        public ?string $sourceType,
        public ?string $sourceId,
        public string $createdAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            campaignId: (string) $row->campaign_id,
            entityId: $row->entity_id !== null ? (string) $row->entity_id : null,
            text: (string) $row->text,
            visibility: (string) $row->visibility,
            confidence: (string) $row->confidence,
            sourceType: $row->source_type !== null ? (string) $row->source_type : null,
            sourceId: $row->source_id !== null ? (string) $row->source_id : null,
            createdAt: (string) $row->created_at,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'entity_id' => $this->entityId,
            'text' => $this->text,
            'visibility' => $this->visibility,
            'confidence' => $this->confidence,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'created_at' => $this->createdAt,
        ];
    }
}
