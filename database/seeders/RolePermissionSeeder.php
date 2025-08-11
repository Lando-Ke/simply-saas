<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
            'impersonate users',
            
            // Project management
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'manage project teams',
            'view project analytics',
            
            // Task management
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',
            'complete tasks',
            
            // Subscription management
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',
            'manage billing',
            'view financial data',
            
            // Plan management
            'view plans',
            'create plans',
            'edit plans',
            'delete plans',
            
            // Analytics and reporting
            'view analytics',
            'view dashboard',
            'view content',
            'create content',
            'export reports',
            'view audit logs',
            
            // System administration
            'manage settings',
            'manage branding',
            'view logs',
            'manage backups',
            'system maintenance',
            
            // Tenant management (for multi-tenancy)
            'manage tenants',
            'view tenant data',
            'create tenants',
            'edit tenants',
            'delete tenants',
            'access admin panel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->syncPermissions(Permission::all());

        // Application admin role (separate from tenant admin)
        $appAdminRole = Role::firstOrCreate(['name' => 'app-admin']);
        $appAdminRole->syncPermissions([
            'manage tenants',
            'view tenant data', 
            'create tenants',
            'edit tenants',
            'delete tenants',
            'view users',
            'create users',
            'edit users',
            'assign roles',
            'view analytics',
            'view dashboard',
            'manage settings',
            'system maintenance',
            'access admin panel',
        ]);

        // Tenant admin role (manages only their tenant)
        $tenantAdminRole = Role::firstOrCreate(['name' => 'tenant-admin']);
        $tenantAdminRole->syncPermissions([
            'view users',
            'create users',
            'edit users',
            'assign roles',
            'manage billing',
            'view financial data',
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'view analytics',
            'view dashboard',
            'manage branding',
            'export reports',
            'access admin panel',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            'view users',
            'create users',
            'edit users',
            'assign roles',
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'manage project teams',
            'view project analytics',
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',
            'complete tasks',
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'manage billing',
            'view financial data',
            'view plans',
            'create plans',
            'edit plans',
            'view analytics',
            'view dashboard',
            'export reports',
            'manage settings',
            'manage branding',
            'view logs',
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->syncPermissions([
            'view users',
            'view projects',
            'create projects',
            'edit projects',
            'manage project teams',
            'view project analytics',
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            'complete tasks',
            'view subscriptions',
            'view analytics',
            'view dashboard',
            'export reports',
        ]);

        $teamLeadRole = Role::firstOrCreate(['name' => 'team-lead']);
        $teamLeadRole->syncPermissions([
            'view projects',
            'edit projects',
            'manage project teams',
            'view tasks',
            'create tasks',
            'edit tasks',
            'assign tasks',
            'complete tasks',
            'view analytics',
            'view dashboard',
        ]);

        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions([
            'view projects',
            'view tasks',
            'create tasks',
            'edit tasks',
            'complete tasks',
            'view dashboard',
            'view content',
            'create content',
        ]);

        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $clientRole->syncPermissions([
            'view projects',
            'view tasks',
            'view dashboard',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
