<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EnvironmentConfigurationTest extends TestCase
{
    public function test_application_key_is_set()
    {
        $this->assertNotEmpty(config('app.key'));
        $this->assertNotEquals('base64:', config('app.key'));
    }

    public function test_application_name_is_configured()
    {
        $this->assertNotEmpty(config('app.name'));
        $this->assertIsString(config('app.name'));
    }

    public function test_application_url_is_configured()
    {
        $this->assertNotEmpty(config('app.url'));
        $this->assertIsString(config('app.url'));
    }

    public function test_application_environment_is_set()
    {
        $env = config('app.env');
        $this->assertContains($env, ['local', 'testing', 'staging', 'production']);
    }

    public function test_debug_mode_is_properly_set()
    {
        $debug = config('app.debug');
        $this->assertIsBool($debug);
    }

    public function test_timezone_is_configured()
    {
        $timezone = config('app.timezone');
        $this->assertNotEmpty($timezone);
        $this->assertIsString($timezone);
    }

    public function test_locale_is_configured()
    {
        $locale = config('app.locale');
        $this->assertNotEmpty($locale);
        $this->assertIsString($locale);
    }

    public function test_fallback_locale_is_configured()
    {
        $fallbackLocale = config('app.fallback_locale');
        $this->assertNotEmpty($fallbackLocale);
        $this->assertIsString($fallbackLocale);
    }

    public function test_session_configuration_is_proper()
    {
        $this->assertNotEmpty(config('session.driver'));
        $this->assertGreaterThan(0, config('session.lifetime'));
    }

    public function test_cache_configuration_is_proper()
    {
        $this->assertNotEmpty(config('cache.default'));
        $this->assertContains(config('cache.default'), ['file', 'database', 'redis', 'memcached', 'array']);
    }

    public function test_queue_configuration_is_proper()
    {
        $this->assertNotEmpty(config('queue.default'));
        $this->assertContains(config('queue.default'), ['sync', 'database', 'redis', 'sqs']);
    }

    public function test_logging_configuration_is_proper()
    {
        $this->assertNotEmpty(config('logging.default'));
        $this->assertArrayHasKey('channels', config('logging'));
    }

    public function test_database_configuration_is_proper()
    {
        $this->assertNotEmpty(config('database.default'));
        $this->assertArrayHasKey('connections', config('database'));
    }

    public function test_redis_configuration_is_proper()
    {
        $this->assertArrayHasKey('client', config('database.redis'));
        $this->assertArrayHasKey('default', config('database.redis'));
    }

    public function test_filesystem_configuration_is_proper()
    {
        $this->assertNotEmpty(config('filesystems.default'));
        $this->assertArrayHasKey('disks', config('filesystems'));
    }

    public function test_broadcasting_configuration_is_proper()
    {
        $this->assertNotEmpty(config('broadcasting.default'));
        $this->assertArrayHasKey('connections', config('broadcasting'));
    }
}
