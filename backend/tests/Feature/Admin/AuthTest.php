<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@example.test', 'password' => 'correct-password']);
    }

    public function test_login_returns_a_bearer_token_with_expiry(): void
    {
        $this->admin();

        $response = $this->postJson('/api/admin/login', ['email' => 'admin@example.test', 'password' => 'correct-password'])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'admin@example.test');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertNotNull(PersonalAccessToken::first()->expires_at);
    }

    public function test_wrong_credentials_get_the_same_message(): void
    {
        $this->admin();

        $wrongPassword = $this->postJson('/api/admin/login', ['email' => 'admin@example.test', 'password' => 'nope'])
            ->assertUnprocessable()->json('errors.email.0');
        $unknownEmail = $this->postJson('/api/admin/login', ['email' => 'who@example.test', 'password' => 'nope'])
            ->assertUnprocessable()->json('errors.email.0');

        $this->assertSame('Email hoặc mật khẩu không đúng.', $wrongPassword);
        $this->assertSame($wrongPassword, $unknownEmail);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/admin/me')->assertUnauthorized();

        $token = $this->admin()->createToken('admin')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/me')->assertOk()->assertJsonPath('data.email', 'admin@example.test');
    }

    public function test_logout_revokes_the_token(): void
    {
        $token = $this->admin()->createToken('admin')->plainTextToken;

        $this->withToken($token)->postJson('/api/admin/logout')->assertNoContent();
        $this->assertSame(0, PersonalAccessToken::count());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = $this->admin()->createToken('admin', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        config(['api.rate_limits.login' => 2]);
        $this->admin();

        $this->postJson('/api/admin/login', ['email' => 'admin@example.test', 'password' => 'x'])->assertUnprocessable();
        $this->postJson('/api/admin/login', ['email' => 'admin@example.test', 'password' => 'x'])->assertUnprocessable();
        $this->postJson('/api/admin/login', ['email' => 'admin@example.test', 'password' => 'x'])->assertStatus(429);
    }

    public function test_there_is_no_registration_endpoint(): void
    {
        $this->postJson('/api/admin/register', ['email' => 'x@example.test', 'password' => 'secret'])->assertNotFound();
    }
}
