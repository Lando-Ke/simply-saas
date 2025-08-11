<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\TaskPriority;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TaskPriorityTest extends TestCase
{
    public function test_can_create_low_priority()
    {
        $priority = TaskPriority::low();
        
        $this->assertEquals(TaskPriority::LOW, $priority->getValue());
        $this->assertTrue($priority->isLow());
        $this->assertFalse($priority->isMedium());
        $this->assertFalse($priority->isHigh());
        $this->assertFalse($priority->isUrgent());
    }

    public function test_can_create_medium_priority()
    {
        $priority = TaskPriority::medium();
        
        $this->assertEquals(TaskPriority::MEDIUM, $priority->getValue());
        $this->assertTrue($priority->isMedium());
        $this->assertFalse($priority->isLow());
    }

    public function test_can_create_high_priority()
    {
        $priority = TaskPriority::high();
        
        $this->assertEquals(TaskPriority::HIGH, $priority->getValue());
        $this->assertTrue($priority->isHigh());
    }

    public function test_can_create_urgent_priority()
    {
        $priority = TaskPriority::urgent();
        
        $this->assertEquals(TaskPriority::URGENT, $priority->getValue());
        $this->assertTrue($priority->isUrgent());
    }

    public function test_constructor_validates_priority()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid task priority: invalid_priority');
        
        new TaskPriority('invalid_priority');
    }

    public function test_get_display_name()
    {
        $this->assertEquals('Low', TaskPriority::low()->getDisplayName());
        $this->assertEquals('Medium', TaskPriority::medium()->getDisplayName());
        $this->assertEquals('High', TaskPriority::high()->getDisplayName());
        $this->assertEquals('Urgent', TaskPriority::urgent()->getDisplayName());
    }

    public function test_get_color()
    {
        $this->assertEquals('success', TaskPriority::low()->getColor());
        $this->assertEquals('info', TaskPriority::medium()->getColor());
        $this->assertEquals('warning', TaskPriority::high()->getColor());
        $this->assertEquals('danger', TaskPriority::urgent()->getColor());
    }

    public function test_get_weight()
    {
        $this->assertEquals(1, TaskPriority::low()->getWeight());
        $this->assertEquals(2, TaskPriority::medium()->getWeight());
        $this->assertEquals(3, TaskPriority::high()->getWeight());
        $this->assertEquals(4, TaskPriority::urgent()->getWeight());
    }

    public function test_priority_comparison()
    {
        $low = TaskPriority::low();
        $medium = TaskPriority::medium();
        $high = TaskPriority::high();
        $urgent = TaskPriority::urgent();

        // Test isHigherThan
        $this->assertTrue($medium->isHigherThan($low));
        $this->assertTrue($high->isHigherThan($medium));
        $this->assertTrue($urgent->isHigherThan($high));
        $this->assertFalse($low->isHigherThan($medium));

        // Test isLowerThan
        $this->assertTrue($low->isLowerThan($medium));
        $this->assertTrue($medium->isLowerThan($high));
        $this->assertTrue($high->isLowerThan($urgent));
        $this->assertFalse($urgent->isLowerThan($high));
    }

    public function test_equals()
    {
        $priority1 = TaskPriority::high();
        $priority2 = TaskPriority::high();
        $priority3 = TaskPriority::low();

        $this->assertTrue($priority1->equals($priority2));
        $this->assertFalse($priority1->equals($priority3));
    }

    public function test_to_string()
    {
        $priority = TaskPriority::high();
        $this->assertEquals(TaskPriority::HIGH, (string) $priority);
    }

    public function test_get_valid_values()
    {
        $validValues = TaskPriority::getValidValues();
        
        $this->assertContains(TaskPriority::LOW, $validValues);
        $this->assertContains(TaskPriority::MEDIUM, $validValues);
        $this->assertContains(TaskPriority::HIGH, $validValues);
        $this->assertContains(TaskPriority::URGENT, $validValues);
        $this->assertCount(4, $validValues);
    }
}
