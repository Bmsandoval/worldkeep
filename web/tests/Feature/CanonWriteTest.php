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
