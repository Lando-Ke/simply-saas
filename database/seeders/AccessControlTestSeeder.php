<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AccessControlTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating access control test data...');

        // Create plans first
        $plans = $this->createPlans();
        
        // Create test users with different roles
        $users = $this->createTestUsers();
        
        // Create test tenants
        $tenants = $this->createTestTenants();
        
        // Assign users to tenants
        $this->assignUsersToTenants($users, $tenants);
        
        // Create subscriptions
        $this->createSubscriptions($users, $plans);
        
        // Set up branding for tenants
        $this->setupTenantBranding($tenants);

        $this->command->info('Access control test data created successfully!');
        $this->command->info('');
        $this->command->info('Test Users Created:');
        $this->command->info('===================');
        foreach ($users as $role => $user) {
            $this->command->info("{$role}: {$user->email} (password: password)");
        }
        $this->command->info('');
        $this->command->info('Test Tenants Created:');
        $this->command->info('====================');
        foreach ($tenants as $tenant) {
            $name = $tenant->data['name'] ?? 'Unknown Tenant';
            $this->command->info("- {$name} ({$tenant->id})");
        }
    }

    private function createPlans(): array
    {
        $plans = [
            [
                'name' => 'Free',
                'description' => 'Basic plan for getting started',
                'price' => 0.00,
                'billing_cycle' => 'monthly',
                'features' => ['Basic features', '1 user', 'Email support'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'description' => 'Perfect for small teams',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'features' => ['All Free features', 'Up to 5 users', 'Priority support', 'Basic analytics'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium',
                'description' => 'Advanced features for growing businesses',
                'price' => 79.99,
                'billing_cycle' => 'monthly',
                'features' => ['All Basic features', 'Up to 25 users', 'Advanced analytics', 'API access'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Full-featured solution for large organizations',
                'price' => 199.99,
                'billing_cycle' => 'monthly',
                'features' => ['All Premium features', 'Unlimited users', 'Custom integrations', 'Dedicated support'],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        $planModels = [];
        foreach ($plans as $planData) {
            $planModels[] = Plan::firstOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }

        return $planModels;
    }

    private function createTestUsers(): array
    {
        $users = [];

        // Super Admin
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@example.com'
        ], [
            'name' => 'Super Administrator',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'hourly_rate' => 150.00,
        ]);
        $superAdmin->assignRole('super-admin');
        $users['super-admin'] = $superAdmin;

        // Application Admin
        $appAdmin = User::firstOrCreate([
            'email' => 'appadmin@example.com'
        ], [
            'name' => 'Application Administrator',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'hourly_rate' => 100.00,
        ]);
        $appAdmin->assignRole('app-admin');
        $users['app-admin'] = $appAdmin;

        // Tenant Admins
        $tenantAdmin1 = User::firstOrCreate([
            'email' => 'tenantadmin1@acmecorp.com'
        ], [
            'name' => 'John Tenant Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'hourly_rate' => 75.00,
        ]);
        $tenantAdmin1->assignRole('tenant-admin');
        $users['tenant-admin-1'] = $tenantAdmin1;

        $tenantAdmin2 = User::firstOrCreate([
            'email' => 'tenantadmin2@techstart.com'
        ], [
            'name' => 'Jane Tenant Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'hourly_rate' => 80.00,
        ]);
        $tenantAdmin2->assignRole('tenant-admin');
        $users['tenant-admin-2'] = $tenantAdmin2;

        // Regular Admin (legacy role)
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Regular Administrator',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'hourly_rate' => 65.00,
        ]);
        $admin->assignRole('admin');
        $users['admin'] = $admin;

        // Manager
        $manager = User::firstOrCreate([
            'email' => 'manager@acmecorp.com'
        ], [
            'name' => 'Project Manager',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'hourly_rate' => 55.00,
        ]);
        $manager->assignRole('manager');
        $users['manager'] = $manager;

        // Regular User
        $user = User::firstOrCreate([
            'email' => 'user@techstart.com'
        ], [
            'name' => 'Regular User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'hourly_rate' => 40.00,
        ]);
        $user->assignRole('user');
        $users['user'] = $user;

        return $users;
    }

    private function createTestTenants(): array
    {
        $tenants = [];

        // Tenant 1: Acme Corporation
        $acmeCorp = Tenant::firstOrCreate([
            'id' => 'acme-corp'
        ], [
            'data' => [
                'name' => 'Acme Corporation',
                'email' => 'admin@acmecorp.com',
                'status' => 'active',
                'subscription_plan' => 'premium',
                'subscription_ends_at' => now()->addYear(),
                'trial_ends_at' => null,
                'settings' => [
                    'branding' => [
                        'logo' => null,
                        'primary_color' => '#1f2937',
                        'secondary_color' => '#6b7280',
                        'tagline' => 'Innovation through Excellence',
                        'website' => 'https://acmecorp.com',
                    ]
                ]
            ]
        ]);
        $tenants[] = $acmeCorp;

        // Tenant 2: TechStart Inc
        $techStart = Tenant::firstOrCreate([
            'id' => 'techstart-inc'
        ], [
            'data' => [
                'name' => 'TechStart Inc',
                'email' => 'hello@techstart.com',
                'status' => 'active',
                'subscription_plan' => 'basic',
                'subscription_ends_at' => now()->addMonths(3),
                'trial_ends_at' => null,
                'settings' => [
                    'branding' => [
                        'logo' => null,
                        'primary_color' => '#3b82f6',
                        'secondary_color' => '#8b5cf6',
                        'tagline' => 'Start something amazing',
                        'website' => 'https://techstart.com',
                    ]
                ]
            ]
        ]);
        $tenants[] = $techStart;

        // Tenant 3: Creative Solutions (trial)
        $creativeSolutions = Tenant::firstOrCreate([
            'id' => 'creative-solutions'
        ], [
            'data' => [
                'name' => 'Creative Solutions',
                'email' => 'info@creativesolutions.com',
                'status' => 'active',
                'subscription_plan' => 'free',
                'subscription_ends_at' => null,
                'trial_ends_at' => now()->addDays(14),
                'settings' => [
                    'branding' => [
                        'logo' => null,
                        'primary_color' => '#ec4899',
                        'secondary_color' => '#f97316',
                        'tagline' => 'Creative minds, powerful solutions',
                        'website' => null,
                    ]
                ]
            ]
        ]);
        $tenants[] = $creativeSolutions;

        return $tenants;
    }

    private function assignUsersToTenants(array $users, array $tenants): void
    {
        // Assign tenant admin 1 to Acme Corp
        $tenants[0]->users()->attach($users['tenant-admin-1']->id, [
            'role' => 'admin',
            'joined_at' => now()->subMonths(6)
        ]);

        // Assign manager to Acme Corp
        $tenants[0]->users()->attach($users['manager']->id, [
            'role' => 'manager',
            'joined_at' => now()->subMonths(4)
        ]);

        // Assign tenant admin 2 to TechStart Inc
        $tenants[1]->users()->attach($users['tenant-admin-2']->id, [
            'role' => 'admin',
            'joined_at' => now()->subMonths(3)
        ]);

        // Assign regular user to TechStart Inc
        $tenants[1]->users()->attach($users['user']->id, [
            'role' => 'user',
            'joined_at' => now()->subMonths(2)
        ]);

        // Assign admin to Creative Solutions
        $tenants[2]->users()->attach($users['admin']->id, [
            'role' => 'admin',
            'joined_at' => now()->subWeeks(2)
        ]);
    }

    private function createSubscriptions(array $users, array $plans): void
    {
        $subscriptions = [
            [
                'user_id' => $users['tenant-admin-1']->id,
                'plan_id' => $plans[2]->id, // Premium
                'status' => 'active',
                'amount' => 79.99,
                'currency' => 'USD',
                'starts_at' => now()->subMonths(6),
                'ends_at' => now()->addMonths(6),
            ],
            [
                'user_id' => $users['tenant-admin-2']->id,
                'plan_id' => $plans[1]->id, // Basic
                'status' => 'active',
                'amount' => 29.99,
                'currency' => 'USD',
                'starts_at' => now()->subMonths(3),
                'ends_at' => now()->addMonths(9),
            ],
            [
                'user_id' => $users['manager']->id,
                'plan_id' => $plans[1]->id, // Basic
                'status' => 'active',
                'amount' => 29.99,
                'currency' => 'USD',
                'starts_at' => now()->subMonths(4),
                'ends_at' => now()->addMonths(8),
            ],
            [
                'user_id' => $users['user']->id,
                'plan_id' => $plans[0]->id, // Free
                'status' => 'active',
                'amount' => 0.00,
                'currency' => 'USD',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->addYear(),
            ],
            [
                'user_id' => $users['admin']->id,
                'plan_id' => $plans[3]->id, // Enterprise
                'status' => 'trial',
                'amount' => 199.99,
                'currency' => 'USD',
                'starts_at' => now()->subWeeks(2),
                'ends_at' => now()->addWeeks(2),
            ],
        ];

        foreach ($subscriptions as $subscriptionData) {
            Subscription::firstOrCreate([
                'user_id' => $subscriptionData['user_id'],
                'plan_id' => $subscriptionData['plan_id'],
                'status' => $subscriptionData['status'],
            ], $subscriptionData);
        }

        // Create some historical subscriptions
        Subscription::firstOrCreate([
            'user_id' => $users['admin']->id,
            'plan_id' => $plans[0]->id,
            'status' => 'canceled',
        ], [
            'user_id' => $users['admin']->id,
            'plan_id' => $plans[0]->id,
            'status' => 'canceled',
            'amount' => 0.00,
            'currency' => 'USD',
            'starts_at' => now()->subMonths(8),
            'ends_at' => now()->subMonths(3),
            'canceled_at' => now()->subMonths(3),
        ]);
    }

    private function setupTenantBranding(array $tenants): void
    {
        // Branding is already set up in the tenant creation above
        $this->command->info('Tenant branding configured for all test tenants.');
    }
}
