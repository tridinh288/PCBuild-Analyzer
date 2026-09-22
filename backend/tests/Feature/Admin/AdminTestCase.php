<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Admin feature tests: seeded demo data and an authenticated admin.
 */
abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function actingAsAdmin(): static
    {
        Sanctum::actingAs(User::factory()->create());

        return $this;
    }
}
