<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_user_can_be_created()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_user_can_have_roles_assigned()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->isAdmin());
    }

    public function test_user_can_have_multiple_roles()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();

        $user->assignRole([$adminRole, $managerRole]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('manager'));
        $this->assertTrue($user->hasAnyRole(['admin', 'manager']));
    }

    public function test_user_can_have_permissions()
    {
        $user = User::factory()->create();
        $permission = Permission::where('name', 'view users')->first();

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('view users'));
        $this->assertContains('view users', $user->getPermissionsArray());
    }

    public function test_super_admin_has_all_permissions()
    {
        $user = User::factory()->create();
        $superAdminRole = Role::where('name', 'super-admin')->first();

        $user->assignRole($superAdminRole);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermissionTo('view users'));
        $this->assertTrue($user->hasPermissionTo('manage settings'));
        $this->assertTrue($user->hasPermissionTo('system maintenance'));
    }

    public function test_admin_role_has_correct_permissions()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();

        $user->assignRole($adminRole);

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasPermissionTo('view users'));
        $this->assertTrue($user->hasPermissionTo('create users'));
        $this->assertFalse($user->hasPermissionTo('system maintenance'));
    }

    public function test_user_role_has_limited_permissions()
    {
        $user = User::factory()->create();
        $userRole = Role::where('name', 'user')->first();

        $user->assignRole($userRole);

        $this->assertTrue($user->hasPermissionTo('view content'));
        $this->assertTrue($user->hasPermissionTo('create content'));
        $this->assertFalse($user->hasPermissionTo('view users'));
        $this->assertFalse($user->hasPermissionTo('manage settings'));
    }

    public function test_user_primary_role_is_correct()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();

        $user->assignRole($adminRole);

        $this->assertEquals('admin', $user->getPrimaryRole());
    }

    public function test_user_can_check_multiple_roles()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();

        $user->assignRole([$adminRole, $managerRole]);

        $this->assertTrue($user->hasAnyRole(['admin', 'manager']));
        $this->assertTrue($user->hasAllRoles(['admin', 'manager']));
    }

    public function test_user_permissions_are_cached()
    {
        $user = User::factory()->create();
        $permission = Permission::where('name', 'view users')->first();

        $user->givePermissionTo($permission);

        // Test that permissions are properly cached
        $this->assertTrue($user->hasPermissionTo('view users'));
        
        // Clear cache and test again
        $user->forgetCachedPermissions();
        $this->assertTrue($user->hasPermissionTo('view users'));
    }

    public function test_role_permissions_are_inherited()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();

        $user->assignRole($adminRole);

        // Admin role should have 'view users' permission
        $this->assertTrue($user->hasPermissionTo('view users'));
        $this->assertTrue($user->hasPermissionTo('create users'));
        $this->assertTrue($user->hasPermissionTo('edit users'));
    }

    public function test_user_can_be_removed_from_role()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();

        $user->assignRole($adminRole);
        $this->assertTrue($user->hasRole('admin'));

        $user->removeRole($adminRole);
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_user_can_have_permissions_revoked()
    {
        $user = User::factory()->create();
        $permission = Permission::where('name', 'view users')->first();

        $user->givePermissionTo($permission);
        $this->assertTrue($user->hasPermissionTo('view users'));

        $user->revokePermissionTo($permission);
        $this->assertFalse($user->hasPermissionTo('view users'));
    }
}
