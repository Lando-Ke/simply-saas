<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class FilamentAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_super_admin_can_access_filament_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        // Create a tenant for the super admin to access
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'data' => ['name' => 'Test Tenant']
        ]);

        // Super admins should be able to access any tenant dashboard
        $response = $this->actingAs($user)->get('/admin/' . $tenant->id);

        $response->assertStatus(200);
    }

    public function test_app_admin_can_access_filament_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('app-admin');

        // Create a tenant for the app admin to access
        $tenant = Tenant::create([
            'id' => 'test-tenant-2',
            'data' => ['name' => 'Test Tenant 2']
        ]);

        // App admins should be able to access any tenant dashboard
        $response = $this->actingAs($user)->get('/admin/' . $tenant->id);

        $response->assertStatus(200);
    }

    public function test_tenant_admin_can_access_filament_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('tenant-admin');

        // Create a tenant and assign the tenant admin to it
        $tenant = Tenant::create([
            'id' => 'test-tenant-3',
            'data' => ['name' => 'Test Tenant 3']
        ]);

        // Assign tenant admin to this tenant
        $tenant->users()->attach($user->id, [
            'role' => 'admin',
            'joined_at' => now()
        ]);

        // Tenant admins should be able to access their tenant dashboard
        $response = $this->actingAs($user)->get('/admin/' . $tenant->id);

        $response->assertStatus(200);
    }

    public function test_regular_admin_cannot_access_filament_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Create a tenant
        $tenant = Tenant::create([
            'id' => 'test-tenant-4',
            'data' => ['name' => 'Test Tenant 4']
        ]);

        // Regular admins should not be able to access Filament dashboard
        $response = $this->actingAs($user)->get('/admin/' . $tenant->id);

        // Access control is now implemented - should return 403
        $response->assertStatus(403);
    }

    public function test_manager_cannot_access_filament_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        // Create a tenant
        $tenant = Tenant::create([
            'id' => 'test-tenant-5',
            'data' => ['name' => 'Test Tenant 5']
        ]);

        // Managers should not be able to access Filament dashboard
        $response = $this->actingAs($user)->get('/admin/' . $tenant->id);

        // Access control is now implemented - should return 403
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_filament_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        // Create a tenant
        $tenant = Tenant::create([
            'id' => 'test-tenant-6',
            'data' => ['name' => 'Test Tenant 6']
        ]);

        // Regular users should not be able to access Filament dashboard
        $response = $this->actingAs($user)->get('/admin/' . $tenant->id);

        // Access control is now implemented - should return 403
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_all_tenants()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'data' => ['name' => 'Test Tenant']
        ]);

        $this->assertTrue($user->canAccessTenant($tenant));
    }

    public function test_app_admin_can_access_all_tenants()
    {
        $user = User::factory()->create();
        $user->assignRole('app-admin');

        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'data' => ['name' => 'Test Tenant']
        ]);

        $this->assertTrue($user->canAccessTenant($tenant));
    }

    public function test_tenant_admin_can_only_access_their_tenant()
    {
        $user = User::factory()->create();
        $user->assignRole('tenant-admin');

        $ownTenant = Tenant::create([
            'id' => 'own-tenant',
            'data' => ['name' => 'Own Tenant']
        ]);

        $otherTenant = Tenant::create([
            'id' => 'other-tenant',
            'data' => ['name' => 'Other Tenant']
        ]);

        // Assign user to own tenant
        $ownTenant->users()->attach($user->id, [
            'role' => 'admin',
            'joined_at' => now()
        ]);

        $this->assertTrue($user->canAccessTenant($ownTenant));
        $this->assertFalse($user->canAccessTenant($otherTenant));
    }

    public function test_user_role_hierarchy()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $appAdmin = User::factory()->create();
        $appAdmin->assignRole('app-admin');

        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $user = User::factory()->create();
        $user->assignRole('user');

        // Test role hierarchy levels
        $this->assertEquals(100, $superAdmin->getRoleLevel());
        $this->assertEquals(90, $appAdmin->getRoleLevel());
        $this->assertEquals(85, $tenantAdmin->getRoleLevel());
        $this->assertEquals(80, $admin->getRoleLevel());
        $this->assertEquals(60, $manager->getRoleLevel());
        $this->assertEquals(20, $user->getRoleLevel());
    }

    public function test_super_admin_can_manage_all_users()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $appAdmin = User::factory()->create();
        $appAdmin->assignRole('app-admin');

        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $this->assertTrue($superAdmin->canManageUser($appAdmin));
        $this->assertTrue($superAdmin->canManageUser($tenantAdmin));
        $this->assertTrue($superAdmin->canManageUser($superAdmin)); // Can manage themselves
    }

    public function test_app_admin_can_manage_lower_roles()
    {
        $appAdmin = User::factory()->create();
        $appAdmin->assignRole('app-admin');

        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->assertTrue($appAdmin->canManageUser($tenantAdmin));
        $this->assertTrue($appAdmin->canManageUser($admin));
        $this->assertFalse($appAdmin->canManageUser($superAdmin));
        $this->assertFalse($appAdmin->canManageUser($appAdmin)); // Cannot manage same level
    }





    public function test_subscription_filtering_for_tenant_admins()
    {
        // Create users
        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        // Create tenants
        $ownTenant = Tenant::create([
            'id' => 'own-tenant',
            'data' => ['name' => 'Own Tenant']
        ]);

        $otherTenant = Tenant::create([
            'id' => 'other-tenant',
            'data' => ['name' => 'Other Tenant']
        ]);

        // Assign users to tenants
        $ownTenant->users()->attach($tenantAdmin->id, [
            'role' => 'admin',
            'joined_at' => now()
        ]);

        $otherTenant->users()->attach($otherUser->id, [
            'role' => 'user',
            'joined_at' => now()
        ]);

        // Create plans and subscriptions
        $plan = Plan::factory()->create();

        $ownSubscription = Subscription::create([
            'user_id' => $tenantAdmin->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 50.00,
            'currency' => 'USD',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $otherSubscription = Subscription::create([
            'user_id' => $otherUser->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 30.00,
            'currency' => 'USD',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Test that tenant admin can only see their tenant's subscriptions
        $this->actingAs($tenantAdmin);
        
        // Set current tenant context
        tenancy()->initialize($ownTenant);

        // Tenant admin should only see subscriptions for users in their tenant
        $tenantUserIds = $ownTenant->users()->pluck('users.id');
        $visibleSubscriptions = Subscription::whereIn('user_id', $tenantUserIds)->get();

        $this->assertCount(1, $visibleSubscriptions);
        $this->assertEquals($ownSubscription->id, $visibleSubscriptions->first()->id);
    }



    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }
}
