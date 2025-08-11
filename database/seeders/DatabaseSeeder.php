<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
            AccessControlTestSeeder::class,
        ]);

        // Find or create test user with super-admin role
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // Assign super-admin role to test user
        if (!$testUser->hasRole('super-admin')) {
            $testUser->assignRole('super-admin');
        }

        // User::factory(10)->create();
    }
}
