<?php

namespace Tests\Feature\Database;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_checks_pass_on_seeded_mysql(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('app:verify-database')
            ->expectsOutputToContain('All checks passed.')
            ->assertSuccessful();
    }

    public function test_fails_without_data(): void
    {
        $this->artisan('app:verify-database')->assertFailed();
    }
}
