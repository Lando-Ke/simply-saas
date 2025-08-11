<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Stancl\Tenancy\Database\Models\Domain;

class TenantUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some test tenants
        $tenants = [
            [
                'id' => 'acme-corp',
                'data' => [
                    'name' => 'Acme Corporation',
                    'email' => 'admin@acme.com',
                    'status' => 'active',
                    'subscription_plan' => 'premium',
                    'trial_ends_at' => now()->addDays(30),
                    'settings' => [
                        'branding' => [
                            'primary_color' => '#FF6B35',
                            'secondary_color' => '#2E4057',
                        ]
                    ]
                ]
            ],
            [
                'id' => 'tech-startup',
                'data' => [
                    'name' => 'Tech Startup Inc',
                    'email' => 'hello@techstartup.com',
                    'status' => 'trial',
                    'subscription_plan' => 'basic',
                    'trial_ends_at' => now()->addDays(14),
                    'settings' => [
                        'branding' => [
                            'primary_color' => '#4ECDC4',
                            'secondary_color' => '#45B7D1',
                        ]
                    ]
                ]
            ],
            [
                'id' => 'global-solutions',
                'data' => [
                    'name' => 'Global Solutions Ltd',
                    'email' => 'contact@globalsolutions.com',
                    'status' => 'active',
                    'subscription_plan' => 'enterprise',
                    'subscription_ends_at' => now()->addYear(),
                    'settings' => [
                        'branding' => [
                            'primary_color' => '#8B5CF6',
                            'secondary_color' => '#EC4899',
                        ]
                    ]
                ]
            ]
        ];

        foreach ($tenants as $tenantData) {
            $tenant = Tenant::firstOrCreate(
                ['id' => $tenantData['id']],
                ['data' => $tenantData['data']]
            );

            // Create domains for each tenant
            Domain::firstOrCreate([
                'domain' => $tenantData['id'] . '.localhost',
                'tenant_id' => $tenant->id,
            ]);
        }

        // Get all users and assign them to tenants
        $users = User::all();
        $tenantIds = collect($tenants)->pluck('id');

        foreach ($users as $user) {
            // Super admins get access to all tenants
            if ($user->hasRole('super-admin')) {
                foreach ($tenantIds as $tenantId) {
                    $user->tenants()->syncWithoutDetaching([
                        $tenantId => [
                            'role' => 'owner',
                            'joined_at' => now(),
                        ]
                    ]);
                }
            }
            // Admins get access to 2 random tenants
            elseif ($user->hasRole('admin')) {
                $randomTenants = $tenantIds->random(2);
                foreach ($randomTenants as $tenantId) {
                    $user->tenants()->syncWithoutDetaching([
                        $tenantId => [
                            'role' => 'admin',
                            'joined_at' => now(),
                        ]
                    ]);
                }
            }
            // Other users get access to 1 random tenant
            else {
                $randomTenant = $tenantIds->random(1)->first();
                $role = match ($user->getPrimaryRole()) {
                    'manager' => 'admin',
                    'team-lead' => 'member',
                    'user' => 'member',
                    'client' => 'viewer',
                    default => 'member',
                };

                $user->tenants()->syncWithoutDetaching([
                    $randomTenant => [
                        'role' => $role,
                        'joined_at' => now(),
                    ]
                ]);
            }
        }

        $this->command->info('Tenant-user relationships created successfully!');
        $this->command->info('Available tenants:');
        foreach ($tenants as $tenant) {
            $this->command->info("- {$tenant['data']['name']}: http://{$tenant['id']}.localhost");
        }
    }
}