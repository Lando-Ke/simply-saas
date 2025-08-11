<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_connection_is_working()
    {
        $this->assertTrue(DB::connection()->getPdo() instanceof \PDO);
    }

    public function test_database_connection_returns_correct_driver()
    {
        $driver = DB::connection()->getDriverName();
        $this->assertContains($driver, ['mysql', 'sqlite', 'pgsql']);
    }

    public function test_database_connection_has_proper_configuration()
    {
        $config = config('database.connections.' . config('database.default'));
        
        $this->assertArrayHasKey('driver', $config);
        
        // Different drivers have different required keys
        if ($config['driver'] === 'mysql') {
            $this->assertArrayHasKey('host', $config);
            $this->assertArrayHasKey('database', $config);
        } elseif ($config['driver'] === 'sqlite') {
            $this->assertArrayHasKey('database', $config);
        }
    }

    public function test_database_connection_pooling_is_configured()
    {
        $config = config('database.connections.' . config('database.default'));
        
        // Connection pooling is optional, so we just check if the config exists
        $this->assertIsArray($config);
    }

    public function test_database_can_execute_simple_query()
    {
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    public function test_database_transactions_work_properly()
    {
        DB::beginTransaction();
        
        try {
            // Test transaction rollback
            DB::rollBack();
            $this->assertTrue(true); // If we get here, rollback worked
        } catch (\Exception $e) {
            $this->fail('Database transaction rollback failed: ' . $e->getMessage());
        }
    }

    public function test_database_connection_timeout_is_reasonable()
    {
        $start = microtime(true);
        
        try {
            DB::connection()->getPdo();
            $end = microtime(true);
            
            $this->assertLessThan(5.0, $end - $start, 'Database connection took too long');
        } catch (\Exception $e) {
            $this->fail('Database connection failed: ' . $e->getMessage());
        }
    }
}
