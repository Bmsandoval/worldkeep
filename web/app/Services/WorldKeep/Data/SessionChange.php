<?php

namespace App\Services\WorldKeep\Data;

final readonly class SessionChange
{
    public function __construct(
        public int $id,
        public string $sessionId,
        public string $entityId,
        public string $changeOp,
        public string $createdAt,
    ) {}

    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            sessionId: (string) $row->session_id,
            entityId: (string) $row->entity_id,
            changeOp: (string) $row->change_op,
            createdAt: (string) $row->created_at,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'entity_id' => $this->entityId,
            'change_op' => $this->changeOp,
            'created_at' => $this->createdAt,
        ];
    }
}
