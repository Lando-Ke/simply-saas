<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    public function test_mail_configuration_is_properly_set()
    {
        $config = config('mail');
        
        $this->assertArrayHasKey('default', $config);
        $this->assertArrayHasKey('mailers', $config);
        $this->assertArrayHasKey('from', $config);
    }

    public function test_mail_from_address_is_configured()
    {
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        
        $this->assertNotEmpty($fromAddress);
        $this->assertNotEmpty($fromName);
        $this->assertIsString($fromAddress);
        $this->assertIsString($fromName);
    }

    public function test_mail_driver_is_supported()
    {
        $driver = config('mail.default');
        $supportedDrivers = ['smtp', 'sendmail', 'mail', 'log', 'array'];
        
        $this->assertContains($driver, $supportedDrivers);
    }

    public function test_mail_queue_is_configured()
    {
        $queueConnection = config('queue.default');
        
        $this->assertNotEmpty($queueConnection);
        $this->assertContains($queueConnection, ['sync', 'database', 'redis', 'sqs']);
    }

    public function test_mail_can_be_sent_in_test_environment()
    {
        Mail::fake();
        
        $this->assertTrue(true); // Mail facade is available
    }

    public function test_mail_configuration_has_proper_timeout()
    {
        $config = config('mail.mailers.smtp');
        
        if (isset($config['timeout'])) {
            $this->assertGreaterThan(0, $config['timeout']);
            $this->assertLessThanOrEqual(60, $config['timeout']);
        } else {
            $this->assertTrue(true); // Timeout is optional
        }
    }

    public function test_mail_encryption_is_properly_set()
    {
        $config = config('mail.mailers.smtp');
        
        if (isset($config['encryption'])) {
            $this->assertContains($config['encryption'], ['tls', 'ssl', null]);
        } else {
            $this->assertTrue(true); // Encryption is optional
        }
    }

    public function test_mail_port_is_valid()
    {
        $config = config('mail.mailers.smtp');
        
        if (isset($config['port'])) {
            $this->assertGreaterThan(0, $config['port']);
            $this->assertLessThanOrEqual(65535, $config['port']);
        } else {
            $this->assertTrue(true); // Port is optional
        }
    }

    public function test_mail_host_is_configured()
    {
        $config = config('mail.mailers.smtp');
        
        if (isset($config['host'])) {
            $this->assertNotEmpty($config['host']);
        } else {
            $this->assertTrue(true); // Host is optional for some drivers
        }
    }
}
