<?php

namespace App\Services\WorldKeep\Data;

final readonly class SessionWorkspace
{
    /**
     * @param  list<Event>  $events
     * @param  list<SessionChange>  $modifiedEntities
     */
    public function __construct(
        public Session $session,
        public array $events,
        public array $modifiedEntities,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session' => $this->session->toArray(),
            'events' => array_map(static fn (Event $e) => $e->toArray(), $this->events),
            'modified_entities' => array_map(static fn (SessionChange $c) => $c->toArray(), $this->modifiedEntities),
        ];
    }
}
