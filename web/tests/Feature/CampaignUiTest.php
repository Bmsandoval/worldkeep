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

    public function test_authenticated_user_sees_dashboard_from_go_engine(): void
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
                    'recent_events' => [],
                    'pending_update_count' => 2,
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
        $response->assertSee('2');
    }

    public function test_authenticated_user_sees_approval_queue(): void
    {
        $this->mock(WorldKeepClient::class, function ($mock) {
            $mock->shouldReceive('listPendingUpdates')
                ->once()
                ->andReturn([
                    [
                        'id' => 'update_test01',
                        'reason' => 'Add a dockside rumor.',
                        'created_at' => '2026-06-18T12:00:00Z',
                        'proposed_changes' => [['op' => 'add_fact']],
                        'warnings' => [],
                    ],
                ]);
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.campaign.approvals'));

        $response->assertOk();
        $response->assertSee('Canon approval queue');
        $response->assertSee('update_test01');
        $response->assertSee('Add a dockside rumor.');
    }

    public function test_commit_redirects_after_go_engine_accepts_update(): void
    {
        $this->mock(WorldKeepClient::class, function ($mock) {
            $mock->shouldReceive('commitUpdate')
                ->once()
                ->with('update_test01')
                ->andReturn([
                    'status' => 'committed',
                    'update_id' => 'update_test01',
                ]);
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('app.campaign.approvals.commit', 'update_test01'));

        $response->assertRedirect(route('app.campaign.approvals'));
        $response->assertSessionHas('status');
    }

    public function test_reject_redirects_after_go_engine_rejects_update(): void
    {
        $this->mock(WorldKeepClient::class, function ($mock) {
            $mock->shouldReceive('rejectUpdate')
                ->once()
                ->with('update_test01', 'Conflicts with session ruling.')
                ->andReturn([
                    'status' => 'rejected',
                    'update_id' => 'update_test01',
                ]);
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('app.campaign.approvals.reject', 'update_test01'), [
            'reason' => 'Conflicts with session ruling.',
        ]);

        $response->assertRedirect(route('app.campaign.approvals'));
    }
}
