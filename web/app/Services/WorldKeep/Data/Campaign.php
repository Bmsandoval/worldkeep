<?php

namespace App\Services\WorldKeep\Data;

final readonly class Campaign
{
    public function __construct(
        public string $id,
        public string $name,
        public string $system,
        public string $createdAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            name: (string) $row->name,
            system: (string) $row->system,
            createdAt: (string) $row->created_at,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'system' => $this->system,
            'created_at' => $this->createdAt,
        ];
    }
}
