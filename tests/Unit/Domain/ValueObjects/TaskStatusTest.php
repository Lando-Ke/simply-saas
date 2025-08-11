<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\TaskStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_can_create_pending_status()
    {
        $status = TaskStatus::pending();
        
        $this->assertEquals(TaskStatus::PENDING, $status->getValue());
        $this->assertTrue($status->isPending());
        $this->assertFalse($status->isInProgress());
        $this->assertFalse($status->isCompleted());
        $this->assertFalse($status->isCancelled());
    }

    public function test_can_create_in_progress_status()
    {
        $status = TaskStatus::inProgress();
        
        $this->assertEquals(TaskStatus::IN_PROGRESS, $status->getValue());
        $this->assertTrue($status->isInProgress());
        $this->assertFalse($status->isPending());
    }

    public function test_can_create_completed_status()
    {
        $status = TaskStatus::completed();
        
        $this->assertEquals(TaskStatus::COMPLETED, $status->getValue());
        $this->assertTrue($status->isCompleted());
    }

    public function test_can_create_cancelled_status()
    {
        $status = TaskStatus::cancelled();
        
        $this->assertEquals(TaskStatus::CANCELLED, $status->getValue());
        $this->assertTrue($status->isCancelled());
    }

    public function test_constructor_validates_status()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid task status: invalid_status');
        
        new TaskStatus('invalid_status');
    }

    public function test_get_display_name()
    {
        $this->assertEquals('Pending', TaskStatus::pending()->getDisplayName());
        $this->assertEquals('In Progress', TaskStatus::inProgress()->getDisplayName());
        $this->assertEquals('Completed', TaskStatus::completed()->getDisplayName());
        $this->assertEquals('Cancelled', TaskStatus::cancelled()->getDisplayName());
    }

    public function test_get_color()
    {
        $this->assertEquals('secondary', TaskStatus::pending()->getColor());
        $this->assertEquals('info', TaskStatus::inProgress()->getColor());
        $this->assertEquals('success', TaskStatus::completed()->getColor());
        $this->assertEquals('danger', TaskStatus::cancelled()->getColor());
    }

    public function test_status_transitions()
    {
        $pending = TaskStatus::pending();
        $inProgress = TaskStatus::inProgress();
        $completed = TaskStatus::completed();
        $cancelled = TaskStatus::cancelled();

        // Pending can transition to in_progress or cancelled
        $this->assertTrue($pending->canTransitionTo($inProgress));
        $this->assertTrue($pending->canTransitionTo($cancelled));
        $this->assertFalse($pending->canTransitionTo($completed));

        // In progress can transition to completed or cancelled
        $this->assertTrue($inProgress->canTransitionTo($completed));
        $this->assertTrue($inProgress->canTransitionTo($cancelled));
        $this->assertFalse($inProgress->canTransitionTo($pending));

        // Completed cannot transition to anything
        $this->assertFalse($completed->canTransitionTo($pending));
        $this->assertFalse($completed->canTransitionTo($inProgress));
        $this->assertFalse($completed->canTransitionTo($cancelled));

        // Cancelled cannot transition to anything
        $this->assertFalse($cancelled->canTransitionTo($pending));
        $this->assertFalse($cancelled->canTransitionTo($inProgress));
        $this->assertFalse($cancelled->canTransitionTo($completed));
    }

    public function test_equals()
    {
        $status1 = TaskStatus::pending();
        $status2 = TaskStatus::pending();
        $status3 = TaskStatus::completed();

        $this->assertTrue($status1->equals($status2));
        $this->assertFalse($status1->equals($status3));
    }

    public function test_to_string()
    {
        $status = TaskStatus::pending();
        $this->assertEquals(TaskStatus::PENDING, (string) $status);
    }

    public function test_get_valid_values()
    {
        $validValues = TaskStatus::getValidValues();
        
        $this->assertContains(TaskStatus::PENDING, $validValues);
        $this->assertContains(TaskStatus::IN_PROGRESS, $validValues);
        $this->assertContains(TaskStatus::COMPLETED, $validValues);
        $this->assertContains(TaskStatus::CANCELLED, $validValues);
        $this->assertCount(4, $validValues);
    }
}
