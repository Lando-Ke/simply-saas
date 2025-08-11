<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Plan;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\SubscriptionTrendsChart;
use App\Filament\Widgets\RevenueChart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class FilamentWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_admin_stats_overview_for_super_admin()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        // Create test data
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'data' => ['name' => 'Test Tenant']
        ]);

        $plan = Plan::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $superAdmin->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 100.00,
            'currency' => 'USD',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($superAdmin);

        $component = Livewire::test(AdminStatsOverview::class);

        $component->assertOk();
        
        // The widget should show app admin stats
        $stats = $component->instance()->getStats();
        $this->assertCount(4, $stats);
        
        // Check that we have the expected stat titles
        $statTitles = collect($stats)->map(fn($stat) => $stat->getLabel())->toArray();
        $this->assertContains('Total Tenants', $statTitles);
        $this->assertContains('Total Users', $statTitles);
        $this->assertContains('Active Subscriptions', $statTitles);
        $this->assertContains('Revenue This Month', $statTitles);
    }

    public function test_admin_stats_overview_for_tenant_admin()
    {
        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'data' => ['name' => 'Test Tenant', 'status' => 'active']
        ]);

        $tenant->users()->attach($tenantAdmin->id, [
            'role' => 'admin',
            'joined_at' => now()
        ]);

        $this->actingAs($tenantAdmin);
        tenancy()->initialize($tenant);

        $component = Livewire::test(AdminStatsOverview::class);

        $component->assertOk();
        
        // The widget should show tenant admin stats
        $stats = $component->instance()->getStats();
        $this->assertCount(4, $stats);
        
        // Check that we have the expected stat titles
        $statTitles = collect($stats)->map(fn($stat) => $stat->getLabel())->toArray();
        $this->assertContains('Tenant Users', $statTitles);
        $this->assertContains('Active Subscriptions', $statTitles);
        $this->assertContains('Monthly Spend', $statTitles);
        $this->assertContains('Tenant Status', $statTitles);
    }

    public function test_admin_stats_overview_for_tenant_admin_without_tenant()
    {
        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $this->actingAs($tenantAdmin);

        $component = Livewire::test(AdminStatsOverview::class);

        $component->assertOk();
        
        // The widget should show "no tenant selected" message
        $stats = $component->instance()->getStats();
        $this->assertCount(1, $stats);
        $this->assertEquals('No Tenant Selected', $stats[0]->getLabel());
    }

    public function test_subscription_trends_chart_for_super_admin()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        // Create test subscriptions
        $plan = Plan::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Subscription::create([
            'user_id' => $user1->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 50.00,
            'currency' => 'USD',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addMonth(),
        ]);

        Subscription::create([
            'user_id' => $user2->id,
            'plan_id' => $plan->id,
            'status' => 'canceled',
            'amount' => 30.00,
            'currency' => 'USD',
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDays(1),
        ]);

        $this->actingAs($superAdmin);

        $component = Livewire::test(SubscriptionTrendsChart::class);

        $component->assertOk();
        
        $data = $component->instance()->getData();
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(2, $data['datasets']); // Active and Canceled datasets
    }

    public function test_subscription_trends_chart_for_tenant_admin()
    {
        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'data' => ['name' => 'Test Tenant']
        ]);

        $tenant->users()->attach($tenantAdmin->id, [
            'role' => 'admin',
            'joined_at' => now()
        ]);

        // Create test subscriptions for tenant users
        $plan = Plan::factory()->create();
        Subscription::create([
            'user_id' => $tenantAdmin->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 75.00,
            'currency' => 'USD',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($tenantAdmin);
        tenancy()->initialize($tenant);

        $component = Livewire::test(SubscriptionTrendsChart::class);

        $component->assertOk();
        
        $data = $component->instance()->getData();
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(2, $data['datasets']);
    }

    public function test_revenue_chart_for_super_admin()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        // Create test data
        $plan = Plan::factory()->create();
        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 99.99,
            'currency' => 'USD',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($superAdmin);

        $component = Livewire::test(RevenueChart::class);

        $component->assertOk();
        
        $data = $component->instance()->getData();
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(2, $data['datasets']); // Revenue and Users datasets

        // Check dataset labels
        $datasetLabels = collect($data['datasets'])->pluck('label')->toArray();
        $this->assertContains('Revenue ($)', $datasetLabels);
        $this->assertContains('New Users', $datasetLabels);
    }

    public function test_revenue_chart_for_tenant_admin()
    {
        $tenantAdmin = User::factory()->create();
        $tenantAdmin->assignRole('tenant-admin');

        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'data' => ['name' => 'Test Tenant']
        ]);

        $tenant->users()->attach($tenantAdmin->id, [
            'role' => 'admin',
            'joined_at' => now()
        ]);

        // Create test subscription for tenant user
        $plan = Plan::factory()->create();
        Subscription::create([
            'user_id' => $tenantAdmin->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 199.99,
            'currency' => 'USD',
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($tenantAdmin);
        tenancy()->initialize($tenant);

        $component = Livewire::test(RevenueChart::class);

        $component->assertOk();
        
        $data = $component->instance()->getData();
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(2, $data['datasets']);

        // Check dataset labels for tenant admin (should show "spending" instead of "revenue")
        $datasetLabels = collect($data['datasets'])->pluck('label')->toArray();
        $this->assertContains('Spending ($)', $datasetLabels);
        $this->assertContains('New Users', $datasetLabels);
    }

    public function test_chart_filters_work_correctly()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin);

        $component = Livewire::test(SubscriptionTrendsChart::class);

        // Test different filter options
        $component->set('filter', '7days');
        $component->assertOk();

        $component->set('filter', '30days');
        $component->assertOk();

        $component->set('filter', '3months');
        $component->assertOk();

        $component->set('filter', '6months');
        $component->assertOk();

        $component->set('filter', '1year');
        $component->assertOk();
    }

    public function test_widgets_handle_empty_data_gracefully()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin);

        // Test with no data
        $statsComponent = Livewire::test(AdminStatsOverview::class);
        $statsComponent->assertOk();

        $trendsComponent = Livewire::test(SubscriptionTrendsChart::class);
        $trendsComponent->assertOk();

        $revenueComponent = Livewire::test(RevenueChart::class);
        $revenueComponent->assertOk();

        // All should handle empty data gracefully without errors
        $statsData = $statsComponent->instance()->getStats();
        $this->assertIsArray($statsData);

        $trendsData = $trendsComponent->instance()->getData();
        $this->assertArrayHasKey('datasets', $trendsData);
        $this->assertArrayHasKey('labels', $trendsData);

        $revenueData = $revenueComponent->instance()->getData();
        $this->assertArrayHasKey('datasets', $revenueData);
        $this->assertArrayHasKey('labels', $revenueData);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }
}
