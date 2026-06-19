<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_home_renders_for_guest(): void
    {
        $response = $this->get('/app');

        $response->assertOk();
        $response->assertSee('WorldKeep');
    }

    public function test_web_login_redirects_to_app_home(): void
    {
        User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/app/login', [
            'email' => 'alex@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('app.home'));
        $this->assertAuthenticated();
    }

    public function test_root_redirects_to_app(): void
    {
        $this->get('/')->assertRedirect('/app');
    }
}
