<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\ProjectStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProjectStatusTest extends TestCase
{
    public function test_can_create_active_status()
    {
        $status = ProjectStatus::active();
        
        $this->assertEquals(ProjectStatus::ACTIVE, $status->getValue());
        $this->assertTrue($status->isActive());
        $this->assertFalse($status->isCompleted());
        $this->assertFalse($status->isOnHold());
        $this->assertFalse($status->isCancelled());
    }

    public function test_can_create_completed_status()
    {
        $status = ProjectStatus::completed();
        
        $this->assertEquals(ProjectStatus::COMPLETED, $status->getValue());
        $this->assertTrue($status->isCompleted());
        $this->assertFalse($status->isActive());
    }

    public function test_can_create_on_hold_status()
    {
        $status = ProjectStatus::onHold();
        
        $this->assertEquals(ProjectStatus::ON_HOLD, $status->getValue());
        $this->assertTrue($status->isOnHold());
    }

    public function test_can_create_cancelled_status()
    {
        $status = ProjectStatus::cancelled();
        
        $this->assertEquals(ProjectStatus::CANCELLED, $status->getValue());
        $this->assertTrue($status->isCancelled());
    }

    public function test_constructor_validates_status()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid project status: invalid_status');
        
        new ProjectStatus('invalid_status');
    }

    public function test_get_display_name()
    {
        $this->assertEquals('Active', ProjectStatus::active()->getDisplayName());
        $this->assertEquals('Completed', ProjectStatus::completed()->getDisplayName());
        $this->assertEquals('On Hold', ProjectStatus::onHold()->getDisplayName());
        $this->assertEquals('Cancelled', ProjectStatus::cancelled()->getDisplayName());
    }

    public function test_get_color()
    {
        $this->assertEquals('success', ProjectStatus::active()->getColor());
        $this->assertEquals('primary', ProjectStatus::completed()->getColor());
        $this->assertEquals('warning', ProjectStatus::onHold()->getColor());
        $this->assertEquals('danger', ProjectStatus::cancelled()->getColor());
    }

    public function test_status_transitions()
    {
        $active = ProjectStatus::active();
        $completed = ProjectStatus::completed();
        $onHold = ProjectStatus::onHold();
        $cancelled = ProjectStatus::cancelled();

        // Active can transition to completed, on_hold, or cancelled
        $this->assertTrue($active->canTransitionTo($completed));
        $this->assertTrue($active->canTransitionTo($onHold));
        $this->assertTrue($active->canTransitionTo($cancelled));

        // On hold can transition to active or cancelled
        $this->assertTrue($onHold->canTransitionTo($active));
        $this->assertTrue($onHold->canTransitionTo($cancelled));

        // Completed cannot transition to anything
        $this->assertFalse($completed->canTransitionTo($active));
        $this->assertFalse($completed->canTransitionTo($onHold));
        $this->assertFalse($completed->canTransitionTo($cancelled));

        // Cancelled cannot transition to anything
        $this->assertFalse($cancelled->canTransitionTo($active));
        $this->assertFalse($cancelled->canTransitionTo($onHold));
        $this->assertFalse($cancelled->canTransitionTo($completed));
    }

    public function test_equals()
    {
        $status1 = ProjectStatus::active();
        $status2 = ProjectStatus::active();
        $status3 = ProjectStatus::completed();

        $this->assertTrue($status1->equals($status2));
        $this->assertFalse($status1->equals($status3));
    }

    public function test_to_string()
    {
        $status = ProjectStatus::active();
        $this->assertEquals(ProjectStatus::ACTIVE, (string) $status);
    }

    public function test_get_valid_values()
    {
        $validValues = ProjectStatus::getValidValues();
        
        $this->assertContains(ProjectStatus::ACTIVE, $validValues);
        $this->assertContains(ProjectStatus::COMPLETED, $validValues);
        $this->assertContains(ProjectStatus::ON_HOLD, $validValues);
        $this->assertContains(ProjectStatus::CANCELLED, $validValues);
        $this->assertCount(4, $validValues);
    }
}
