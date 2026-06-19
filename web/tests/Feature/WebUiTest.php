<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_home_renders_landing_for_guest(): void
    {
        $response = $this->get('/app');

        $response->assertOk();
        $response->assertSee('WorldKeep');
        $response->assertSee('Keep the world');
        $response->assertSee('Get started');
    }

    public function test_app_home_renders_quick_cards_for_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get('/app')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Approvals')
            ->assertSee('Dashboard');
    }

    public function test_login_page_shows_cognito_setup_hint_when_not_configured(): void
    {
        config([
            'cognito.user_pool_id' => null,
            'cognito.app_client_id' => null,
            'cognito.domain' => null,
        ]);

        $this->get('/app/login')
            ->assertOk()
            ->assertSee('Cognito is not configured');
    }

    public function test_root_redirects_to_app(): void
    {
        $this->get('/')->assertRedirect('/app');
    }
}
