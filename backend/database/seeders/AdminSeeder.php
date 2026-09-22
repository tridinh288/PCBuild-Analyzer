<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The single admin account (D-023), from ADMIN_EMAIL / ADMIN_PASSWORD.
 * Skipped with a warning when they are not set, so no default password ever exists.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set: admin account not seeded.');

            return;
        }

        User::updateOrCreate(['email' => $email], ['name' => 'Admin', 'password' => $password]);
    }
}
