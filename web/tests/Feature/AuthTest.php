<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Cognito\CognitoHosted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_urls_when_cognito_configured(): void
    {
        config([
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
            'cognito.domain' => 'auth.example.com',
            'cognito.redirect_uri' => 'http://127.0.0.1:8000/app/auth/redirect',
        ]);

        $response = $this->getJson('/api/auth/authorize');

        $response->assertOk();
        $response->assertJsonStructure(['login_url', 'signup_url', 'logout_url', 'redirect_uri']);
        $this->assertStringContainsString('/login?', $response->json('login_url'));
    }

    public function test_authorize_returns_service_unavailable_when_not_configured(): void
    {
        config([
            'cognito.user_pool_id' => null,
            'cognito.app_client_id' => null,
            'cognito.domain' => null,
        ]);

        $this->getJson('/api/auth/authorize')->assertStatus(503);
    }

    public function test_cli_config_returns_public_oauth_settings(): void
    {
        config([
            'app.url' => 'http://127.0.0.1:8000',
            'cognito.region' => 'us-east-1',
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
            'cognito.domain' => 'hub-local',
            'cognito.cli_redirect_uri' => 'http://127.0.0.1:53682/callback',
        ]);

        $this->getJson('/api/auth/cli-config')
            ->assertOk()
            ->assertJsonPath('clientId', 'test-client')
            ->assertJsonPath('domain', 'hub-local')
            ->assertJsonPath('apiBaseURL', 'http://127.0.0.1:8000')
            ->assertJsonPath('redirectURI', 'http://127.0.0.1:53682/callback')
            ->assertJsonMissingPath('clientSecret');
    }

    public function test_cli_token_rejects_missing_code(): void
    {
        config([
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
            'cognito.domain' => 'auth.example.com',
            'cognito.cli_redirect_uri' => 'http://127.0.0.1:53682/callback',
            'cognito.allowed_redirect_uris' => ['http://127.0.0.1:53682/callback'],
        ]);

        $this->getJson('/api/auth/cli/token')->assertStatus(400);
    }

    public function test_token_requires_code(): void
    {
        config([
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
            'cognito.domain' => 'auth.example.com',
            'cognito.redirect_uri' => 'http://127.0.0.1:8000/app/auth/redirect',
        ]);

        $this->getJson('/api/auth/token')->assertStatus(400);
    }

    public function test_me_rejects_guests(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_me_and_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertGuest();
    }

    public function test_token_rejects_disallowed_redirect_uri(): void
    {
        config([
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
            'cognito.domain' => 'auth.example.com',
            'cognito.redirect_uri' => 'http://127.0.0.1:8000/app/auth/redirect',
            'cognito.allowed_redirect_uris' => ['http://127.0.0.1:8000/app/auth/redirect'],
        ]);

        $this->getJson('/api/auth/token?code=abc&redirect_uri=evil://callback')
            ->assertStatus(401)
            ->assertJsonPath('error', 'redirect_uri is not allowed');
    }

    public function test_login_route_redirects_to_cognito_when_configured(): void
    {
        config([
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
            'cognito.domain' => 'auth.example.com',
            'cognito.redirect_uri' => 'http://127.0.0.1:8000/app/auth/redirect',
        ]);

        $expected = app(CognitoHosted::class)->loginUrl();

        $this->get('/app/login')->assertRedirect($expected);
    }

    public function test_register_route_redirects_to_cognito_signup_when_configured(): void
    {
        config([
            'cognito.user_pool_id' => 'us-east-1_TestPool',
            'cognito.app_client_id' => 'test-client',
            'cognito.domain' => 'auth.example.com',
            'cognito.redirect_uri' => 'http://127.0.0.1:8000/app/auth/redirect',
        ]);

        $expected = app(CognitoHosted::class)->signupUrl();

        $this->get('/app/register')->assertRedirect($expected);
    }
}
