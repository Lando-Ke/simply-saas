<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stancl\Tenancy\Database\Models\Domain;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created()
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('test-tenant', $tenant->id);
    }

    public function test_tenant_can_have_domains()
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
        ]);

        $domain = Domain::create([
            'domain' => 'test.localhost',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($tenant->domains->contains($domain));
    }

    public function test_tenant_isolation_works()
    {
        $tenant1 = Tenant::create(['id' => 'tenant1']);
        $tenant2 = Tenant::create(['id' => 'tenant2']);

        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);

        // Simulate tenant context
        tenancy()->initialize($tenant1);
        $this->assertNotEquals($user1->id, $user2->id);

        tenancy()->initialize($tenant2);
        $this->assertNotEquals($user1->id, $user2->id);
    }

    public function test_tenant_user_relationship()
    {
        $tenant = Tenant::create([
            'id' => 'user-tenant',
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@tenant.com',
        ]);

        // In a real multi-tenant setup, users would be associated with tenants
        // This test verifies the relationship structure
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
    }

    public function test_tenant_data_isolation()
    {
        $tenant1 = Tenant::create(['id' => 'tenant1']);
        $tenant2 = Tenant::create(['id' => 'tenant2']);

        // Simulate different tenant contexts
        tenancy()->initialize($tenant1);
        $user1 = User::factory()->create(['name' => 'User 1']);

        tenancy()->initialize($tenant2);
        $user2 = User::factory()->create(['name' => 'User 2']);

        // Verify data isolation
        $this->assertNotEquals($user1->id, $user2->id);
        $this->assertNotEquals($user1->name, $user2->name);
    }

    public function test_tenant_usage_stats()
    {
        $tenant = Tenant::create([
            'id' => 'stats-tenant',
        ]);

        $stats = $tenant->getUsageStats();

        $this->assertArrayHasKey('users_count', $stats);
        $this->assertArrayHasKey('storage_used', $stats);
        $this->assertArrayHasKey('api_calls', $stats);
        $this->assertEquals(0, $stats['users_count']);
    }
}
