<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_succeeds(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Owner Name', 'email' => 'owner@example.com',
            'password' => 'Secure123', 'password_confirmation' => 'Secure123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'owner@example.com')
            ->assertJsonStructure(['data' => ['token']]);
        $this->assertTrue(Hash::check('Secure123', User::firstOrFail()->password));
    }

    public function test_registration_validation_fails(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => '', 'email' => 'invalid', 'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_succeeds(): void
    {
        User::factory()->create(['email' => 'owner@example.com', 'password' => 'Secure123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@example.com', 'password' => 'Secure123',
        ])->assertOk()->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@example.com', 'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_be_retrieved(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.user.id', $user->id);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_protected_endpoint_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/restaurant')->assertUnauthorized();
    }
}
