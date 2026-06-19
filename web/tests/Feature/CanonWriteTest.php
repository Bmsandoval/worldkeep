<?php

namespace Tests\Feature;

use App\Services\WorldKeep\Data\Campaign;
use App\Services\WorldKeep\Data\WorldChange;
use App\Services\WorldKeep\ReadScope;
use App\Services\WorldKeep\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonWriteTest extends TestCase
{
    use RefreshDatabase;

    private function freshStore(string $campaignId): Store
    {
        $store = new Store();
        $store->migrate();
        $store->createCampaign(new Campaign($campaignId, 'Test Realm', 'D&D 5e', ''));

        return $store;
    }

    /** @param array<string, mixed> $entity */
    private function createEntityChange(array $entity): WorldChange
    {
        return WorldChange::fromArray(['op' => 'create_entity', 'entity' => $entity]);
    }

    public function test_committing_multiple_entities_assigns_distinct_ids(): void
    {
        $store = $this->freshStore('campaign_test');

        $changes = [
            $this->createEntityChange(['type' => 'location', 'name' => 'Saltwrack', 'summary' => 'A port town.']),
            $this->createEntityChange(['type' => 'npc', 'name' => 'Mareth Coll', 'summary' => 'Dockmaster.']),
        ];

        $pending = $store->proposeWorldUpdate('campaign_test', $changes, 'bootstrap');
        $store->commitWorldUpdate($pending->id);

        $locations = $store->listEntitiesByType('campaign_test', 'location', ReadScope::Dm);
        $npcs = $store->listEntitiesByType('campaign_test', 'npc', ReadScope::Dm);

        // Regression: the npc must NOT clobber the location via a shared empty id.
        $this->assertCount(1, $locations, 'location should persist');
        $this->assertCount(1, $npcs, 'npc should persist alongside the location');

        $this->assertNotSame('', $locations[0]->id, 'committed entity must receive a generated id');
        $this->assertNotSame('', $npcs[0]->id, 'committed entity must receive a generated id');
        $this->assertNotSame($locations[0]->id, $npcs[0]->id, 'ids must be distinct');

        // The generated id is retrievable.
        $this->assertSame('Mareth Coll', $store->getEntity($npcs[0]->id)->name);
    }

    public function test_update_entity_flat_patch_merges_into_data(): void
    {
        $store = $this->freshStore('campaign_test');

        $seed = [$this->createEntityChange([
            'type' => 'faction',
            'name' => 'Crimson Guild',
            'summary' => 'Smugglers.',
            'data' => ['power' => 65, 'attitude_to_party' => -10, 'goals' => ['Control the docks']],
        ])];
        $pending = $store->proposeWorldUpdate('campaign_test', $seed, 'seed');
        $store->commitWorldUpdate($pending->id);

        $faction = $store->listEntitiesByType('campaign_test', 'faction', ReadScope::Dm)[0];

        // A flat patch (the natural shape an MCP client sends) must merge into data,
        // changing the named key while preserving untouched keys.
        $patch = [WorldChange::fromArray([
            'op' => 'update_entity',
            'entity_id' => $faction->id,
            'patch' => ['attitude_to_party' => -25, 'alert_state' => 'hunting'],
        ])];
        $pending = $store->proposeWorldUpdate('campaign_test', $patch, 'stance hardens');
        $store->commitWorldUpdate($pending->id);

        $data = json_decode($store->getEntity($faction->id)->data, true);

        $this->assertSame(-25, $data['attitude_to_party'], 'patched key must update');
        $this->assertSame('hunting', $data['alert_state'], 'new key must be added');
        $this->assertSame(65, $data['power'], 'untouched key must be preserved');
        $this->assertSame(['Control the docks'], $data['goals'], 'untouched key must be preserved');
    }

    public function test_listing_with_empty_type_returns_all_types(): void
    {
        $store = $this->freshStore('campaign_test');

        $changes = [
            $this->createEntityChange(['type' => 'location', 'name' => 'Saltwrack', 'summary' => 'A port town.']),
            $this->createEntityChange(['type' => 'npc', 'name' => 'Mareth Coll', 'summary' => 'Dockmaster.']),
            $this->createEntityChange(['type' => 'faction', 'name' => 'Tide Wardens', 'summary' => 'Harbor guild.']),
        ];
        $pending = $store->proposeWorldUpdate('campaign_test', $changes, 'bootstrap');
        $store->commitWorldUpdate($pending->id);

        // Empty type = "all types" (the World browser's default "All" tab).
        $all = $store->listEntitiesByType('campaign_test', '', ReadScope::Dm);

        $this->assertCount(3, $all, 'empty type must return every entity, not filter on type = ""');
        $names = array_map(static fn ($e) => $e->name, $all);
        $this->assertEqualsCanonicalizing(['Saltwrack', 'Mareth Coll', 'Tide Wardens'], $names);
    }

    public function test_duplicate_entity_name_is_flagged_as_conflict(): void
    {
        $store = $this->freshStore('campaign_test');

        $seed = [$this->createEntityChange(['type' => 'npc', 'name' => 'Mareth Coll', 'summary' => 'Dockmaster.'])];
        $pending = $store->proposeWorldUpdate('campaign_test', $seed, 'seed');
        $store->commitWorldUpdate($pending->id);

        $duplicate = [$this->createEntityChange(['type' => 'npc', 'name' => 'Mareth Coll', 'summary' => 'impostor'])];
        $warnings = $store->checkForConflicts('campaign_test', $duplicate);

        $this->assertCount(1, $warnings);
        $this->assertSame('duplicate_name', $warnings[0]->type);
    }
}
