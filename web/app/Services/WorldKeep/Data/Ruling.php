<?php

namespace App\Services\WorldKeep\Data;

final readonly class Ruling
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public string $question,
        public string $answer,
        public string $scope,
        public string $system,
        public string $createdAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            campaignId: (string) $row->campaign_id,
            question: (string) $row->question,
            answer: (string) $row->answer,
            scope: (string) $row->scope,
            system: (string) $row->system,
            createdAt: (string) $row->created_at,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaignId,
            'question' => $this->question,
            'answer' => $this->answer,
            'scope' => $this->scope,
            'system' => $this->system,
            'created_at' => $this->createdAt,
        ];
    }
}
