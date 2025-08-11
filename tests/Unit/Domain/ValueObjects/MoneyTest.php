<?php

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\Money;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_money_can_be_created_with_valid_amount()
    {
        $money = new Money(100.50, 'USD');
        
        $this->assertEquals(100.50, $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency());
    }

    public function test_money_rounds_amount_to_two_decimal_places()
    {
        $money = new Money(100.567, 'USD');
        
        $this->assertEquals(100.57, $money->getAmount());
    }

    public function test_money_throws_exception_for_negative_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');
        
        new Money(-100.50, 'USD');
    }

    public function test_money_throws_exception_for_empty_currency()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency cannot be empty');
        
        new Money(100.50, '');
    }

    public function test_money_converts_currency_to_uppercase()
    {
        $money = new Money(100.50, 'usd');
        
        $this->assertEquals('USD', $money->getCurrency());
    }

    public function test_money_returns_amount_in_cents()
    {
        $money = new Money(100.50, 'USD');
        
        $this->assertEquals(10050, $money->getAmountInCents());
    }

    public function test_money_returns_display_amount()
    {
        $money = new Money(100.50, 'USD');
        
        $this->assertEquals('$100.50', $money->getDisplayAmount());
    }

    public function test_money_can_add_other_money()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(50.25, 'USD');
        
        $result = $money1->add($money2);
        
        $this->assertEquals(150.75, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_money_throws_exception_when_adding_different_currencies()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(50.25, 'EUR');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add money with different currencies');
        
        $money1->add($money2);
    }

    public function test_money_can_subtract_other_money()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(25.25, 'USD');
        
        $result = $money1->subtract($money2);
        
        $this->assertEquals(75.25, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_money_throws_exception_when_subtracting_different_currencies()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(25.25, 'EUR');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot subtract money with different currencies');
        
        $money1->subtract($money2);
    }

    public function test_money_throws_exception_when_subtraction_results_in_negative()
    {
        $money1 = new Money(50.00, 'USD');
        $money2 = new Money(100.00, 'USD');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Result cannot be negative');
        
        $money1->subtract($money2);
    }

    public function test_money_can_multiply_by_factor()
    {
        $money = new Money(100.50, 'USD');
        
        $result = $money->multiply(2.5);
        
        $this->assertEquals(251.25, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_money_throws_exception_when_multiplying_by_negative_factor()
    {
        $money = new Money(100.50, 'USD');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Factor cannot be negative');
        
        $money->multiply(-2.5);
    }

    public function test_money_can_divide_by_divisor()
    {
        $money = new Money(100.50, 'USD');
        
        $result = $money->divide(2);
        
        $this->assertEquals(50.25, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_money_throws_exception_when_dividing_by_zero()
    {
        $money = new Money(100.50, 'USD');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Divisor must be positive');
        
        $money->divide(0);
    }

    public function test_money_throws_exception_when_dividing_by_negative()
    {
        $money = new Money(100.50, 'USD');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Divisor must be positive');
        
        $money->divide(-2);
    }

    public function test_money_equals_other_money_with_same_amount_and_currency()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(100.50, 'USD');
        
        $this->assertTrue($money1->equals($money2));
    }

    public function test_money_does_not_equal_other_money_with_different_amount()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(100.51, 'USD');
        
        $this->assertFalse($money1->equals($money2));
    }

    public function test_money_does_not_equal_other_money_with_different_currency()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(100.50, 'EUR');
        
        $this->assertFalse($money1->equals($money2));
    }

    public function test_money_is_zero_when_amount_is_zero()
    {
        $money = new Money(0, 'USD');
        
        $this->assertTrue($money->isZero());
    }

    public function test_money_is_not_zero_when_amount_is_not_zero()
    {
        $money = new Money(100.50, 'USD');
        
        $this->assertFalse($money->isZero());
    }

    public function test_money_can_compare_greater_than()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(50.25, 'USD');
        
        $this->assertTrue($money1->isGreaterThan($money2));
        $this->assertFalse($money2->isGreaterThan($money1));
    }

    public function test_money_can_compare_less_than()
    {
        $money1 = new Money(50.25, 'USD');
        $money2 = new Money(100.50, 'USD');
        
        $this->assertTrue($money1->isLessThan($money2));
        $this->assertFalse($money2->isLessThan($money1));
    }

    public function test_money_throws_exception_when_comparing_different_currencies()
    {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(100.50, 'EUR');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot compare money with different currencies');
        
        $money1->isGreaterThan($money2);
    }

    public function test_money_converts_to_string()
    {
        $money = new Money(100.50, 'USD');
        
        $this->assertEquals('$100.50', (string) $money);
    }

    public function test_money_can_be_created_from_cents()
    {
        $money = Money::fromCents(10050, 'USD');
        
        $this->assertEquals(100.50, $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency());
    }

    public function test_money_can_be_created_as_zero()
    {
        $money = Money::zero('USD');
        
        $this->assertEquals(0, $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency());
        $this->assertTrue($money->isZero());
    }
}
