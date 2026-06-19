<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WorldKeep\Data\Campaign;
use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_shows_campaign_selector_for_authenticated_user(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.campaign.dashboard'))
            ->assertOk()
            ->assertSee('id="mobile-campaign"', false)
            ->assertSee('Shadows of Blackport');
    }

    public function test_switching_campaign_scopes_world_browser_entities(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $store = app(Store::class);
        $store->createCampaign(new Campaign(
            id: 'campaign_002',
            name: 'Salt and Stone',
            system: 'D&D 3.5e',
            createdAt: '',
        ));
        $store->upsertEntity(new Entity(
            id: 'npc_other',
            campaignId: 'campaign_002',
            type: 'npc',
            name: 'Only In Campaign Two',
            summary: 'Unique to the second campaign.',
            data: '{}',
            createdAt: '',
            updatedAt: '',
        ));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.campaign.world.index'))
            ->assertOk()
            ->assertSee('Finn')
            ->assertDontSee('Only In Campaign Two');

        $this->actingAs($user)
            ->post(route('app.campaign.switch'), ['campaign_id' => 'campaign_002'])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('app.campaign.world.index'))
            ->assertOk()
            ->assertSee('Only In Campaign Two')
            ->assertDontSee('Finn');
    }

    public function test_entity_from_other_campaign_returns_not_found(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $store = app(Store::class);
        $store->createCampaign(new Campaign(
            id: 'campaign_002',
            name: 'Salt and Stone',
            system: 'D&D 3.5e',
            createdAt: '',
        ));
        $store->upsertEntity(new Entity(
            id: 'npc_other',
            campaignId: 'campaign_002',
            type: 'npc',
            name: 'Only In Campaign Two',
            summary: 'Unique to the second campaign.',
            data: '{}',
            createdAt: '',
            updatedAt: '',
        ));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.campaign.world.show', ['entityId' => 'npc_other']))
            ->assertNotFound();
    }

    public function test_spoilers_toggle_appears_in_sidebar_when_advanced_enabled(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.campaign.dashboard'))
            ->assertOk()
            ->assertDontSee('id="sidebar_show_spoilers"', false);

        $this->actingAs($user)
            ->put(route('app.settings.update'), ['advanced_options' => '1']);

        $this->actingAs($user)
            ->get(route('app.campaign.dashboard'))
            ->assertOk()
            ->assertSee('id="sidebar_show_spoilers"', false);
    }

    public function test_sidebar_spoilers_toggle_reveals_secret_entities(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('app.settings.update'), ['advanced_options' => '1']);

        $this->actingAs($user)
            ->post(route('app.preferences.spoilers'), ['show_spoilers' => '1'])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('app.campaign.world.index'))
            ->assertOk()
            ->assertSee('Spoilers on')
            ->assertSee('Prince still alive');
    }
}
