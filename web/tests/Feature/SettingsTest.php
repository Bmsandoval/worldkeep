<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_settings(): void
    {
        $this->get(route('app.settings'))->assertRedirect(route('app.login'));
    }

    public function test_settings_page_only_shows_advanced_options_toggle(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.settings'));

        $response->assertOk();
        $response->assertSee('Enable advanced options');
        $response->assertDontSee('Show spoilers');
    }

    public function test_enabling_advanced_options_reveals_sidebar_spoilers_toggle(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('app.settings.update'), ['advanced_options' => '1'])
            ->assertRedirect(route('app.settings'));

        $this->actingAs($user)
            ->get(route('app.campaign.dashboard'))
            ->assertOk()
            ->assertSee('id="sidebar_show_spoilers"', false);
    }

    public function test_show_spoilers_reveals_secret_entities_in_world_browser(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('app.campaign.world.index'))
            ->assertOk()
            ->assertDontSee('Prince still alive');

        $this->actingAs($user)
            ->put(route('app.settings.update'), ['advanced_options' => '1']);

        $this->actingAs($user)
            ->post(route('app.preferences.spoilers'), ['show_spoilers' => '1']);

        $this->actingAs($user)
            ->get(route('app.campaign.world.index'))
            ->assertOk()
            ->assertSee('Spoilers on')
            ->assertSee('Prince still alive')
            ->assertSee('Secrets');
    }

    public function test_disabling_advanced_options_clears_spoilers_and_hides_sidebar_toggle(): void
    {
        $this->artisan('worldkeep:seed', ['campaign_id' => 'campaign_001']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('app.settings.update'), ['advanced_options' => '1']);

        $this->actingAs($user)
            ->post(route('app.preferences.spoilers'), ['show_spoilers' => '1']);

        $this->actingAs($user)
            ->put(route('app.settings.update'), [])
            ->assertRedirect(route('app.settings'));

        $this->actingAs($user)
            ->get(route('app.campaign.dashboard'))
            ->assertOk()
            ->assertDontSee('id="sidebar_show_spoilers"', false);
    }
}
