<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitAndCorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_endpoints_are_rate_limited(): void
    {
        config(['api.rate_limits.analysis' => 2]);

        $this->postJson('/api/builder/analyze', ['selected' => []])->assertOk();
        $this->postJson('/api/builder/analyze', ['selected' => []])->assertOk();

        $this->postJson('/api/builder/analyze', ['selected' => []])
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('message', 'Bạn gửi quá nhiều yêu cầu, vui lòng thử lại sau.');
    }

    public function test_public_reads_have_their_own_limit(): void
    {
        config(['api.rate_limits.analysis' => 1, 'api.rate_limits.public' => 5]);

        $this->postJson('/api/builder/analyze', ['selected' => []])->assertOk();
        $this->postJson('/api/builder/analyze', ['selected' => []])->assertStatus(429);

        $this->getJson('/api/categories')->assertOk();
    }

    public function test_rate_limit_is_per_client_behind_the_proxy(): void
    {
        config(['api.rate_limits.analysis' => 1]);
        $proxy = ['REMOTE_ADDR' => '10.0.0.1'];

        $this->withServerVariables($proxy)->withHeader('X-Forwarded-For', '203.0.113.7')
            ->postJson('/api/builder/analyze', ['selected' => []])->assertOk();

        // Same proxy, different client: its own limit.
        $this->withHeader('X-Forwarded-For', '198.51.100.9')
            ->postJson('/api/builder/analyze', ['selected' => []])->assertOk();

        // A spoofed leftmost value does not help: the proxy-appended (rightmost) IP counts.
        $this->withHeader('X-Forwarded-For', '1.2.3.4, 203.0.113.7')
            ->postJson('/api/builder/analyze', ['selected' => []])->assertStatus(429);

        // Extra internal (private) hops are skipped too.
        $this->withHeader('X-Forwarded-For', '203.0.113.7, 10.20.30.40')
            ->postJson('/api/builder/analyze', ['selected' => []])->assertStatus(429);
    }

    public function test_cors_allows_only_the_frontend_origin(): void
    {
        $frontend = config('cors.allowed_origins')[0];

        $this->withHeaders(['Origin' => $frontend])->getJson('/api/categories')
            ->assertHeader('Access-Control-Allow-Origin', $frontend);

        // With a single allowed origin the header always names the frontend; the browser then
        // blocks any other origin because it does not match.
        $other = $this->withHeaders(['Origin' => 'https://evil.example'])->getJson('/api/categories');
        $this->assertNotSame('https://evil.example', $other->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $other->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_cors_preflight_for_builder_post(): void
    {
        $frontend = config('cors.allowed_origins')[0];

        $this->call('OPTIONS', '/api/builder/analyze', [], [], [], [
            'HTTP_ORIGIN' => $frontend,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', $frontend);
    }
}
