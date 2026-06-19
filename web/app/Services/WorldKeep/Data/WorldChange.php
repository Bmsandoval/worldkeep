<?php

namespace App\Services\WorldKeep\Data;

final readonly class WorldChange
{
    public function __construct(
        public string $op,
        public ?Entity $entity = null,
        public string $entityId = '',
        public string $patch = '',
        public ?Fact $fact = null,
        public ?Event $event = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $entity = null;
        if (isset($data['entity']) && is_array($data['entity'])) {
            $e = $data['entity'];
            $entity = new Entity(
                id: (string) ($e['id'] ?? ''),
                campaignId: (string) ($e['campaign_id'] ?? ''),
                type: (string) ($e['type'] ?? ''),
                name: (string) ($e['name'] ?? ''),
                summary: (string) ($e['summary'] ?? ''),
                data: is_string($e['data'] ?? null) ? $e['data'] : json_encode($e['data'] ?? [], JSON_THROW_ON_ERROR),
                createdAt: (string) ($e['created_at'] ?? ''),
                updatedAt: (string) ($e['updated_at'] ?? ''),
            );
        }

        $fact = null;
        if (isset($data['fact']) && is_array($data['fact'])) {
            $f = $data['fact'];
            $fact = new Fact(
                id: (string) ($f['id'] ?? ''),
                campaignId: (string) ($f['campaign_id'] ?? ''),
                entityId: isset($f['entity_id']) ? (string) $f['entity_id'] : null,
                text: (string) ($f['text'] ?? ''),
                visibility: (string) ($f['visibility'] ?? 'party_known'),
                confidence: (string) ($f['confidence'] ?? 'high'),
                sourceType: isset($f['source_type']) ? (string) $f['source_type'] : null,
                sourceId: isset($f['source_id']) ? (string) $f['source_id'] : null,
                createdAt: (string) ($f['created_at'] ?? ''),
            );
        }

        $event = null;
        if (isset($data['event']) && is_array($data['event'])) {
            $ev = $data['event'];
            $event = new Event(
                id: (string) ($ev['id'] ?? ''),
                campaignId: (string) ($ev['campaign_id'] ?? ''),
                sessionId: isset($ev['session_id']) ? (string) $ev['session_id'] : null,
                title: (string) ($ev['title'] ?? ''),
                summary: (string) ($ev['summary'] ?? ''),
                entityIds: is_string($ev['entity_ids'] ?? null) ? $ev['entity_ids'] : json_encode($ev['entity_ids'] ?? [], JSON_THROW_ON_ERROR),
                createdAt: (string) ($ev['created_at'] ?? ''),
            );
        }

        $patch = '';
        if (isset($data['patch'])) {
            $patch = is_string($data['patch']) ? $data['patch'] : json_encode($data['patch'], JSON_THROW_ON_ERROR);
        }

        return new self(
            op: (string) ($data['op'] ?? ''),
            entity: $entity,
            entityId: (string) ($data['entity_id'] ?? ''),
            patch: $patch,
            fact: $fact,
            event: $event,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'op' => $this->op,
            'entity' => $this->entity?->toArray(),
            'entity_id' => $this->entityId !== '' ? $this->entityId : null,
            'patch' => $this->patch !== '' ? $this->patch : null,
            'fact' => $this->fact?->toArray(),
            'event' => $this->event?->toArray(),
        ], static fn ($v) => $v !== null);
    }
}
