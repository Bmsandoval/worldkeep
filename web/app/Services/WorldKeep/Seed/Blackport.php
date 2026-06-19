<?php

namespace App\Services\WorldKeep\Seed;

use App\Services\WorldKeep\Data\Campaign;
use App\Services\WorldKeep\Data\CampaignSeat;
use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Data\Fact;
use App\Services\WorldKeep\Data\Ruling;
use App\Services\WorldKeep\Store;
use RuntimeException;

class Blackport
{
    public const DEMO_CAMPAIGN_ID = 'campaign_001';

    public function __construct(
        private readonly Store $store,
    ) {}

    public function seed(string $campaignId = self::DEMO_CAMPAIGN_ID): void
    {
        try {
            $this->store->getCampaign($campaignId);

            return;
        } catch (RuntimeException) {
            // Campaign missing — seed below.
        }

        $this->store->createCampaign(new Campaign(
            id: $campaignId,
            name: 'Shadows of Blackport',
            system: 'D&D 3.5e',
            createdAt: '',
        ));

        foreach ($this->entities($campaignId) as $entity) {
            $this->store->upsertEntity($entity);
        }

        $finnId = 'npc_finn';
        $this->store->addFact(new Fact(
            id: 'fact_finn_fears_guild',
            campaignId: $campaignId,
            entityId: $finnId,
            text: 'Finn fears the Crimson Guild.',
            visibility: 'party_known',
            confidence: 'high',
            sourceType: null,
            sourceId: null,
            createdAt: '',
        ));
        $this->store->addFact(new Fact(
            id: 'fact_finn_eye',
            campaignId: $campaignId,
            entityId: $finnId,
            text: 'Finn lost his left eye in a dockside knife fight.',
            visibility: 'party_known',
            confidence: 'high',
            sourceType: null,
            sourceId: null,
            createdAt: '',
        ));

        $this->store->addRuling(new Ruling(
            id: 'ruling_001',
            campaignId: $campaignId,
            question: 'Does flanking grant +3 instead of +2?',
            answer: 'Yes. In this campaign, flanking grants +3.',
            scope: 'campaign',
            system: 'D&D 3.5e',
            createdAt: '',
        ));

        $this->store->upsertEntity(new Entity(
            id: 'secret_prince_alive',
            campaignId: $campaignId,
            type: 'secret',
            name: 'Prince still alive',
            summary: 'The missing prince is alive, hidden in a Crimson Guild warehouse.',
            data: json_encode(['visibility' => 'dm_only'], JSON_THROW_ON_ERROR),
            createdAt: '',
            updatedAt: '',
        ));

        $this->store->addFact(new Fact(
            id: 'fact_prince_alive_dm',
            campaignId: $campaignId,
            entityId: null,
            text: 'The prince is alive and imprisoned beneath the docks.',
            visibility: 'dm_only',
            confidence: 'high',
            sourceType: null,
            sourceId: null,
            createdAt: '',
        ));

        $this->ensureDemoSeats($campaignId);
    }

    /** @return list<Entity> */
    private function entities(string $campaignId): array
    {
        return [
            $this->entity($campaignId, 'location_blackport', 'location', 'Blackport', 'A fog-heavy port city ruled by Duke Harland.', [
                'type' => 'city',
                'status' => 'tense',
                'tags' => ['port', 'political', 'urban'],
            ]),
            $this->entity($campaignId, 'npc_finn', 'npc', 'Finn', 'One-eyed bartender at the Salt Lantern.', [
                'location_id' => 'location_blackport',
                'status' => 'alive',
                'attitude_to_party' => 10,
                'goals' => ['Keep his tavern safe', 'Avoid angering the Crimson Guild'],
                'beliefs' => [['text' => 'The Crimson Guild controls the docks.', 'confidence' => 'high']],
                'tags' => ['bartender', 'informant'],
            ]),
            $this->entity($campaignId, 'faction_crimson_guild', 'faction', 'Crimson Guild', 'A smuggling syndicate operating through Blackport.', [
                'power' => 65,
                'attitude_to_party' => -10,
                'goals' => ['Control dockside trade', 'Keep nobles dependent on smuggled goods'],
            ]),
            $this->entity($campaignId, 'plot_missing_prince', 'plot', 'The Missing Prince', 'The prince disappeared after investigating Crimson Guild activity.', [
                'status' => 'active',
                'urgency' => 'medium',
                'involved_entities' => ['faction_crimson_guild', 'location_blackport'],
            ]),
        ];
    }

    /** @param array<string, mixed> $data */
    private function entity(string $campaignId, string $id, string $type, string $name, string $summary, array $data): Entity
    {
        return new Entity(
            id: $id,
            campaignId: $campaignId,
            type: $type,
            name: $name,
            summary: $summary,
            data: json_encode($data, JSON_THROW_ON_ERROR),
            createdAt: '',
            updatedAt: '',
        );
    }

    private function ensureDemoSeats(string $campaignId): void
    {
        foreach ([
            ['companion_kael', 'Kael', 'Party scout — quick wit, quicker blades.', ['role' => 'companion', 'class' => 'rogue', 'tags' => ['party', 'companion']]],
            ['companion_lia', 'Lia', 'Scholarly cleric keeping the party grounded.', ['role' => 'companion', 'class' => 'cleric', 'tags' => ['party', 'companion']]],
        ] as [$id, $name, $summary, $data]) {
            $this->store->upsertEntity($this->entity($campaignId, $id, 'npc', $name, $summary, $data));
        }

        if ($this->store->countCampaignSeats($campaignId) > 0) {
            return;
        }

        $this->store->createSeat(new CampaignSeat(
            id: 'seat_dm',
            campaignId: $campaignId,
            seatType: 'dm',
            controller: 'ai',
            controllerUserId: null,
            actorId: null,
            displayName: 'AI Dungeon Master',
            status: 'active',
            createdAt: '',
            updatedAt: '',
        ));

        $this->store->createSeat(new CampaignSeat(
            id: 'seat_player_1',
            campaignId: $campaignId,
            seatType: 'player',
            controller: 'ai',
            controllerUserId: null,
            actorId: 'companion_kael',
            displayName: 'Kael',
            status: 'active',
            createdAt: '',
            updatedAt: '',
        ));

        $this->store->createSeat(new CampaignSeat(
            id: 'seat_player_2',
            campaignId: $campaignId,
            seatType: 'player',
            controller: 'ai',
            controllerUserId: null,
            actorId: 'companion_lia',
            displayName: 'Lia',
            status: 'active',
            createdAt: '',
            updatedAt: '',
        ));
    }
}
