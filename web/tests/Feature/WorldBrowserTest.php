<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorldBrowserTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_browse_world(): void
    {
        $this->get(route('app.campaign.world.index'))->assertRedirect(route('app.login'));
    }

    public function test_authenticated_user_sees_entity_list(): void
    {
        Http::fake([
            '127.0.0.1:8788/api/v1/campaigns/campaign_001/entities*' => Http::response([
                'entities' => [
                    ['id' => 'npc_finn', 'type' => 'npc', 'name' => 'Finn', 'summary' => 'Dock spy.'],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.campaign.world.index'));

        $response->assertOk();
        $response->assertSee('World browser');
        $response->assertSee('Finn');
    }

    public function test_authenticated_user_sees_entity_detail(): void
    {
        Http::fake([
            '127.0.0.1:8788/api/v1/entities/npc_finn*' => Http::response([
                'id' => 'npc_finn',
                'type' => 'npc',
                'name' => 'Finn',
                'summary' => 'Dock spy.',
                'data' => ['role' => 'informant'],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.campaign.world.show', 'npc_finn'));

        $response->assertOk();
        $response->assertSee('Finn');
        $response->assertSee('informant');
    }

    public function test_search_shows_matching_entities(): void
    {
        Http::fake([
            '127.0.0.1:8788/api/v1/campaigns/campaign_001/search*' => Http::response([
                'entities' => [['id' => 'npc_finn', 'type' => 'npc', 'name' => 'Finn', 'summary' => 'Spy.']],
                'facts' => [],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.campaign.world.index', ['q' => 'Finn']));

        $response->assertOk();
        $response->assertSee('Matching entities');
        $response->assertSee('Finn');
    }
}

class SessionTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_sessions(): void
    {
        $this->get(route('app.campaign.sessions.index'))->assertRedirect(route('app.login'));
    }

    public function test_authenticated_user_sees_session_list(): void
    {
        Http::fake([
            '127.0.0.1:8788/api/v1/campaigns/campaign_001/sessions*' => Http::response([
                'sessions' => [
                    [
                        'id' => 'session_abc',
                        'title' => 'Dockside intrigue',
                        'status' => 'closed',
                        'started_at' => '2026-06-01',
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.campaign.sessions.index'));

        $response->assertOk();
        $response->assertSee('Play sessions');
        $response->assertSee('Dockside intrigue');
    }

    public function test_authenticated_user_sees_session_timeline(): void
    {
        Http::fake([
            '127.0.0.1:8788/api/v1/sessions/session_abc' => Http::response([
                'session' => [
                    'id' => 'session_abc',
                    'title' => 'Dockside intrigue',
                    'status' => 'closed',
                    'started_at' => '2026-06-01',
                    'summary' => 'The party met Finn.',
                ],
                'events' => [
                    ['id' => 'ev_1', 'title' => 'Harbor meeting', 'summary' => 'Finn shared a rumor.', 'created_at' => '2026-06-01'],
                ],
                'modified_entities' => [
                    ['entity_id' => 'npc_finn', 'change_op' => 'add_fact', 'created_at' => '2026-06-01'],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.campaign.sessions.show', 'session_abc'));

        $response->assertOk();
        $response->assertSee('Dockside intrigue');
        $response->assertSee('Harbor meeting');
        $response->assertSee('npc_finn');
    }
}
