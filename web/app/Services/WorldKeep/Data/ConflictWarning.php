<?php

namespace App\Services\WorldKeep\Data;

final readonly class ConflictWarning
{
    public function __construct(
        public string $type,
        public string $message,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
        ];
    }
}
