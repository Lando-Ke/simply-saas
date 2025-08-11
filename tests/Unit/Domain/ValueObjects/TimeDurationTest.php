<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\TimeDuration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TimeDurationTest extends TestCase
{
    public function test_can_create_from_minutes()
    {
        $duration = TimeDuration::fromMinutes(90);
        
        $this->assertEquals(90, $duration->getMinutes());
        $this->assertEquals(1.5, $duration->getHours());
    }

    public function test_can_create_from_hours()
    {
        $duration = TimeDuration::fromHours(2.5);
        
        $this->assertEquals(150, $duration->getMinutes());
        $this->assertEquals(2.5, $duration->getHours());
    }

    public function test_can_create_from_hours_and_minutes()
    {
        $duration = TimeDuration::fromHoursAndMinutes(1, 30);
        
        $this->assertEquals(90, $duration->getMinutes());
        $this->assertEquals(1.5, $duration->getHours());
    }

    public function test_can_create_zero_duration()
    {
        $duration = TimeDuration::zero();
        
        $this->assertEquals(0, $duration->getMinutes());
        $this->assertEquals(0.0, $duration->getHours());
        $this->assertTrue($duration->isZero());
    }

    public function test_constructor_validates_negative_minutes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration cannot be negative');
        
        new TimeDuration(-10);
    }

    public function test_get_hours_and_minutes()
    {
        $duration = TimeDuration::fromMinutes(125);
        $hoursAndMinutes = $duration->getHoursAndMinutes();
        
        $this->assertEquals(2, $hoursAndMinutes['hours']);
        $this->assertEquals(5, $hoursAndMinutes['minutes']);
    }

    public function test_get_display_format()
    {
        $this->assertEquals('1h 30m', TimeDuration::fromMinutes(90)->getDisplayFormat());
        $this->assertEquals('45m', TimeDuration::fromMinutes(45)->getDisplayFormat());
        $this->assertEquals('2h 0m', TimeDuration::fromMinutes(120)->getDisplayFormat());
        $this->assertEquals('0m', TimeDuration::zero()->getDisplayFormat());
    }

    public function test_get_decimal_hours()
    {
        $this->assertEquals(1.5, TimeDuration::fromMinutes(90)->getDecimalHours());
        $this->assertEquals(2.25, TimeDuration::fromMinutes(135)->getDecimalHours());
        $this->assertEquals(0.0, TimeDuration::zero()->getDecimalHours());
    }

    public function test_add_durations()
    {
        $duration1 = TimeDuration::fromMinutes(60);
        $duration2 = TimeDuration::fromMinutes(30);
        $result = $duration1->add($duration2);
        
        $this->assertEquals(90, $result->getMinutes());
        $this->assertEquals(1.5, $result->getHours());
    }

    public function test_subtract_durations()
    {
        $duration1 = TimeDuration::fromMinutes(90);
        $duration2 = TimeDuration::fromMinutes(30);
        $result = $duration1->subtract($duration2);
        
        $this->assertEquals(60, $result->getMinutes());
        $this->assertEquals(1.0, $result->getHours());
    }

    public function test_subtract_larger_duration_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot subtract larger duration from smaller duration');
        
        $duration1 = TimeDuration::fromMinutes(30);
        $duration2 = TimeDuration::fromMinutes(60);
        $duration1->subtract($duration2);
    }

    public function test_multiply_duration()
    {
        $duration = TimeDuration::fromMinutes(60);
        $result = $duration->multiply(2.5);
        
        $this->assertEquals(150, $result->getMinutes());
        $this->assertEquals(2.5, $result->getHours());
    }

    public function test_multiply_negative_factor_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiplication factor cannot be negative');
        
        $duration = TimeDuration::fromMinutes(60);
        $duration->multiply(-1);
    }

    public function test_divide_duration()
    {
        $duration = TimeDuration::fromMinutes(120);
        $result = $duration->divide(2);
        
        $this->assertEquals(60, $result->getMinutes());
        $this->assertEquals(1.0, $result->getHours());
    }

    public function test_divide_by_zero_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Division divisor must be positive');
        
        $duration = TimeDuration::fromMinutes(60);
        $duration->divide(0);
    }

    public function test_divide_by_negative_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Division divisor must be positive');
        
        $duration = TimeDuration::fromMinutes(60);
        $duration->divide(-1);
    }

    public function test_comparison_methods()
    {
        $duration1 = TimeDuration::fromMinutes(60);
        $duration2 = TimeDuration::fromMinutes(90);
        $duration3 = TimeDuration::fromMinutes(60);
        
        $this->assertTrue($duration2->isGreaterThan($duration1));
        $this->assertTrue($duration1->isLessThan($duration2));
        $this->assertTrue($duration1->equals($duration3));
        $this->assertFalse($duration1->equals($duration2));
    }

    public function test_to_array()
    {
        $duration = TimeDuration::fromMinutes(90);
        $array = $duration->toArray();
        
        $this->assertEquals(90, $array['minutes']);
        $this->assertEquals(1.5, $array['hours']);
        $this->assertEquals('1h 30m', $array['display']);
    }

    public function test_to_string()
    {
        $duration = TimeDuration::fromMinutes(90);
        $this->assertEquals('1h 30m', (string) $duration);
    }
}
