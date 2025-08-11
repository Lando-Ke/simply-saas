<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\BillingCycle;
use InvalidArgumentException;
use Tests\TestCase;

class BillingCycleTest extends TestCase
{
    public function test_billing_cycle_can_be_created_with_valid_cycle()
    {
        $cycle = new BillingCycle('monthly');
        
        $this->assertEquals('monthly', $cycle->getCycle());
        $this->assertEquals(30, $cycle->getDaysInCycle());
    }

    public function test_billing_cycle_throws_exception_for_invalid_cycle()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid billing cycle: invalid');
        
        new BillingCycle('invalid');
    }

    public function test_billing_cycle_can_be_monthly()
    {
        $cycle = new BillingCycle('monthly');
        
        $this->assertTrue($cycle->isMonthly());
        $this->assertFalse($cycle->isYearly());
        $this->assertFalse($cycle->isWeekly());
        $this->assertFalse($cycle->isDaily());
    }

    public function test_billing_cycle_can_be_yearly()
    {
        $cycle = new BillingCycle('yearly');
        
        $this->assertTrue($cycle->isYearly());
        $this->assertFalse($cycle->isMonthly());
        $this->assertFalse($cycle->isWeekly());
        $this->assertFalse($cycle->isDaily());
    }

    public function test_billing_cycle_can_be_weekly()
    {
        $cycle = new BillingCycle('weekly');
        
        $this->assertTrue($cycle->isWeekly());
        $this->assertFalse($cycle->isMonthly());
        $this->assertFalse($cycle->isYearly());
        $this->assertFalse($cycle->isDaily());
    }

    public function test_billing_cycle_can_be_daily()
    {
        $cycle = new BillingCycle('daily');
        
        $this->assertTrue($cycle->isDaily());
        $this->assertFalse($cycle->isMonthly());
        $this->assertFalse($cycle->isYearly());
        $this->assertFalse($cycle->isWeekly());
    }

    public function test_billing_cycle_returns_correct_days_in_cycle()
    {
        $this->assertEquals(1, (new BillingCycle('daily'))->getDaysInCycle());
        $this->assertEquals(7, (new BillingCycle('weekly'))->getDaysInCycle());
        $this->assertEquals(30, (new BillingCycle('monthly'))->getDaysInCycle());
        $this->assertEquals(365, (new BillingCycle('yearly'))->getDaysInCycle());
    }

    public function test_billing_cycle_returns_display_name()
    {
        $this->assertEquals('Daily', (new BillingCycle('daily'))->getDisplayName());
        $this->assertEquals('Weekly', (new BillingCycle('weekly'))->getDisplayName());
        $this->assertEquals('Monthly', (new BillingCycle('monthly'))->getDisplayName());
        $this->assertEquals('Yearly', (new BillingCycle('yearly'))->getDisplayName());
    }

    public function test_billing_cycle_returns_short_name()
    {
        $this->assertEquals('day', (new BillingCycle('daily'))->getShortName());
        $this->assertEquals('week', (new BillingCycle('weekly'))->getShortName());
        $this->assertEquals('month', (new BillingCycle('monthly'))->getShortName());
        $this->assertEquals('year', (new BillingCycle('yearly'))->getShortName());
    }

    public function test_billing_cycle_returns_plural_name()
    {
        $this->assertEquals('days', (new BillingCycle('daily'))->getPluralName());
        $this->assertEquals('weeks', (new BillingCycle('weekly'))->getPluralName());
        $this->assertEquals('months', (new BillingCycle('monthly'))->getPluralName());
        $this->assertEquals('years', (new BillingCycle('yearly'))->getPluralName());
    }

    public function test_billing_cycle_calculates_next_billing_date()
    {
        $startDate = new \DateTime('2024-01-01');
        
        $dailyCycle = new BillingCycle('daily');
        $nextDaily = $dailyCycle->calculateNextBillingDate($startDate);
        $this->assertEquals('2024-01-02', $nextDaily->format('Y-m-d'));
        
        $weeklyCycle = new BillingCycle('weekly');
        $nextWeekly = $weeklyCycle->calculateNextBillingDate($startDate);
        $this->assertEquals('2024-01-08', $nextWeekly->format('Y-m-d'));
        
        $monthlyCycle = new BillingCycle('monthly');
        $nextMonthly = $monthlyCycle->calculateNextBillingDate($startDate);
        $this->assertEquals('2024-02-01', $nextMonthly->format('Y-m-d'));
        
        $yearlyCycle = new BillingCycle('yearly');
        $nextYearly = $yearlyCycle->calculateNextBillingDate($startDate);
        $this->assertEquals('2025-01-01', $nextYearly->format('Y-m-d'));
    }

    public function test_billing_cycle_calculates_previous_billing_date()
    {
        $startDate = new \DateTime('2024-01-01');
        
        $dailyCycle = new BillingCycle('daily');
        $prevDaily = $dailyCycle->calculatePreviousBillingDate($startDate);
        $this->assertEquals('2023-12-31', $prevDaily->format('Y-m-d'));
        
        $weeklyCycle = new BillingCycle('weekly');
        $prevWeekly = $weeklyCycle->calculatePreviousBillingDate($startDate);
        $this->assertEquals('2023-12-25', $prevWeekly->format('Y-m-d'));
        
        $monthlyCycle = new BillingCycle('monthly');
        $prevMonthly = $monthlyCycle->calculatePreviousBillingDate($startDate);
        $this->assertEquals('2023-12-01', $prevMonthly->format('Y-m-d'));
        
        $yearlyCycle = new BillingCycle('yearly');
        $prevYearly = $yearlyCycle->calculatePreviousBillingDate($startDate);
        $this->assertEquals('2023-01-01', $prevYearly->format('Y-m-d'));
    }

    public function test_billing_cycle_returns_annual_multiplier()
    {
        $this->assertEquals(365, (new BillingCycle('daily'))->getAnnualMultiplier());
        $this->assertEquals(52, (new BillingCycle('weekly'))->getAnnualMultiplier());
        $this->assertEquals(12, (new BillingCycle('monthly'))->getAnnualMultiplier());
        $this->assertEquals(1, (new BillingCycle('yearly'))->getAnnualMultiplier());
    }

    public function test_billing_cycle_equals_other_cycle_with_same_cycle()
    {
        $cycle1 = new BillingCycle('monthly');
        $cycle2 = new BillingCycle('monthly');
        
        $this->assertTrue($cycle1->equals($cycle2));
    }

    public function test_billing_cycle_does_not_equal_other_cycle_with_different_cycle()
    {
        $cycle1 = new BillingCycle('monthly');
        $cycle2 = new BillingCycle('yearly');
        
        $this->assertFalse($cycle1->equals($cycle2));
    }

    public function test_billing_cycle_converts_to_string()
    {
        $cycle = new BillingCycle('monthly');
        
        $this->assertEquals('monthly', (string) $cycle);
    }

    public function test_billing_cycle_can_be_created_with_static_methods()
    {
        $this->assertTrue(BillingCycle::daily()->isDaily());
        $this->assertTrue(BillingCycle::weekly()->isWeekly());
        $this->assertTrue(BillingCycle::monthly()->isMonthly());
        $this->assertTrue(BillingCycle::yearly()->isYearly());
    }

    public function test_billing_cycle_handles_edge_cases_for_date_calculations()
    {
        // Test leap year
        $leapYearDate = new \DateTime('2024-02-29');
        $monthlyCycle = new BillingCycle('monthly');
        $nextBilling = $monthlyCycle->calculateNextBillingDate($leapYearDate);
        $this->assertEquals('2024-03-29', $nextBilling->format('Y-m-d'));
        
        // Test month end
        $monthEndDate = new \DateTime('2024-01-31');
        $nextBilling = $monthlyCycle->calculateNextBillingDate($monthEndDate);
        $this->assertEquals('2024-03-02', $nextBilling->format('Y-m-d')); // PHP's date arithmetic
    }
}
