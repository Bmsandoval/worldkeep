<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WorldKeepClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_campaign_dashboard(): void
    {
        $this->get(route('app.campaign.dashboard'))->assertRedirect(route('app.login'));
    }

    public function test_authenticated_user_sees_dashboard(): void
    {
        $this->mock(WorldKeepClient::class, function ($mock) {
            $mock->shouldReceive('dashboard')
                ->once()
                ->andReturn([
                    'campaign' => ['name' => 'Shadows of Blackport'],
                    'open_session' => null,
                    'active_plots' => [
                        ['id' => 'plot_missing_prince', 'name' => 'The Missing Prince', 'summary' => 'Find the heir.'],
                    ],
                    'recent_events' => [
                        ['summary' => 'Party arrived at the docks.', 'created_at' => '2026-06-18'],
                    ],
                    'continuity_warnings' => [],
                    'scope' => 'party',
                ]);
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.campaign.dashboard'));

        $response->assertOk();
        $response->assertSee('Campaign dashboard');
        $response->assertSee('Shadows of Blackport');
        $response->assertSee('The Missing Prince');
        $response->assertSee('World browser');
        $response->assertDontSee('Approvals');
        $response->assertDontSee('Pending approvals');
    }

    public function test_legacy_approvals_url_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app/campaign/approvals')
            ->assertRedirect('/app/campaign/dashboard');
    }
}
