<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_with_hashed_password_and_session(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Alex Diner',
            'email' => 'alex@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.email', 'alex@example.com');
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('password123', User::query()->first()->password));
    }

    public function test_login_authenticates_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'alex@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.email', 'alex@example.com');
        $this->assertAuthenticated();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'alex@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'alex@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_write_routes_reject_guests(): void
    {
        $this->postJson('/logout')->assertUnauthorized();
        $this->getJson('/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_me_and_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->actingAs($user)
            ->postJson('/logout')
            ->assertOk();

        $this->assertGuest();
    }
}
