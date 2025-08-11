<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_super_admin_can_access_all_routes()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $routes = [
            '/dashboard',
            '/projects',
            '/tasks',
            '/billing',
            '/branding'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_admin_can_access_appropriate_routes()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Admin should access these
        $allowedRoutes = [
            '/dashboard',
            '/projects',
            '/tasks',
            '/billing',
            '/branding'
        ];

        foreach ($allowedRoutes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_manager_has_limited_access()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        // Manager should access these
        $allowedRoutes = [
            '/dashboard',
            '/projects',
            '/tasks'
        ];

        foreach ($allowedRoutes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(200);
        }

        // Manager should NOT access these
        $forbiddenRoutes = [
            '/branding'
        ];

        foreach ($forbiddenRoutes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(302); // Should redirect
        }
    }

    public function test_client_has_minimal_access()
    {
        $user = User::factory()->create();
        $user->assignRole('client');

        // Client should access these
        $allowedRoutes = [
            '/dashboard'
        ];

        foreach ($allowedRoutes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(200);
        }

        // Client should NOT access these
        $forbiddenRoutes = [
            '/billing',
            '/branding'
        ];

        foreach ($forbiddenRoutes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(302); // Should redirect
        }
    }

    public function test_user_role_hierarchy()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $user = User::factory()->create();
        $user->assignRole('user');

        $client = User::factory()->create();
        $client->assignRole('client');

        // Test role levels
        $this->assertEquals(100, $superAdmin->getRoleLevel());
        $this->assertEquals(80, $admin->getRoleLevel());
        $this->assertEquals(60, $manager->getRoleLevel());
        $this->assertEquals(20, $user->getRoleLevel());
        $this->assertEquals(10, $client->getRoleLevel());

        // Test user management permissions
        $this->assertTrue($superAdmin->canManageUser($admin));
        $this->assertTrue($admin->canManageUser($manager));
        $this->assertTrue($manager->canManageUser($user));
        $this->assertFalse($user->canManageUser($manager));
        $this->assertFalse($client->canManageUser($user));
    }

    public function test_permission_checking()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = User::factory()->create();
        $client->assignRole('client');

        // Admin should have admin permissions
        $this->assertTrue($admin->hasPermissionTo('manage branding'));
        $this->assertTrue($admin->hasPermissionTo('view projects'));
        $this->assertTrue($admin->hasPermissionTo('create projects'));

        // Client should have limited permissions
        $this->assertFalse($client->hasPermissionTo('manage branding'));
        $this->assertTrue($client->hasPermissionTo('view projects'));
        $this->assertFalse($client->hasPermissionTo('create projects'));
    }

    public function test_filament_admin_access()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $client = User::factory()->create();
        $client->assignRole('client');

        // Super admin should access admin panel
        $this->assertTrue($superAdmin->hasPermissionTo('access admin panel'));

        // Client should not access admin panel
        $this->assertFalse($client->hasPermissionTo('access admin panel'));
    }

    public function test_middleware_redirects_properly()
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        // Client trying to access branding should be redirected
        $response = $this->actingAs($client)->get('/branding');
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}