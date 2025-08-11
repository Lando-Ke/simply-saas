<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class BillingCycle
{
    public const MONTHLY = 'monthly';
    public const YEARLY = 'yearly';
    public const WEEKLY = 'weekly';
    public const DAILY = 'daily';

    private string $cycle;
    private int $daysInCycle;

    public function __construct(string $cycle)
    {
        $this->validateCycle($cycle);
        $this->cycle = $cycle;
        $this->daysInCycle = $this->calculateDaysInCycle($cycle);
    }

    private function validateCycle(string $cycle): void
    {
        $validCycles = [self::MONTHLY, self::YEARLY, self::WEEKLY, self::DAILY];
        
        if (!in_array($cycle, $validCycles)) {
            throw new InvalidArgumentException("Invalid billing cycle: {$cycle}");
        }
    }

    private function calculateDaysInCycle(string $cycle): int
    {
        return match($cycle) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::MONTHLY => 30,
            self::YEARLY => 365,
            default => throw new InvalidArgumentException("Unknown billing cycle: {$cycle}")
        };
    }

    public function getCycle(): string
    {
        return $this->cycle;
    }

    public function getDaysInCycle(): int
    {
        return $this->daysInCycle;
    }

    public function isMonthly(): bool
    {
        return $this->cycle === self::MONTHLY;
    }

    public function isYearly(): bool
    {
        return $this->cycle === self::YEARLY;
    }

    public function isWeekly(): bool
    {
        return $this->cycle === self::WEEKLY;
    }

    public function isDaily(): bool
    {
        return $this->cycle === self::DAILY;
    }

    public function getDisplayName(): string
    {
        return match($this->cycle) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
            default => ucfirst($this->cycle)
        };
    }

    public function getShortName(): string
    {
        return match($this->cycle) {
            self::DAILY => 'day',
            self::WEEKLY => 'week',
            self::MONTHLY => 'month',
            self::YEARLY => 'year',
            default => $this->cycle
        };
    }

    public function getPluralName(): string
    {
        return match($this->cycle) {
            self::DAILY => 'days',
            self::WEEKLY => 'weeks',
            self::MONTHLY => 'months',
            self::YEARLY => 'years',
            default => $this->cycle . 's'
        };
    }

    public function calculateNextBillingDate(\DateTime $fromDate): \DateTime
    {
        $nextDate = clone $fromDate;
        
        return match($this->cycle) {
            self::DAILY => $nextDate->add(new \DateInterval('P1D')),
            self::WEEKLY => $nextDate->add(new \DateInterval('P1W')),
            self::MONTHLY => $nextDate->add(new \DateInterval('P1M')),
            self::YEARLY => $nextDate->add(new \DateInterval('P1Y')),
            default => throw new InvalidArgumentException("Unknown billing cycle: {$this->cycle}")
        };
    }

    public function calculatePreviousBillingDate(\DateTime $fromDate): \DateTime
    {
        $previousDate = clone $fromDate;
        
        return match($this->cycle) {
            self::DAILY => $previousDate->sub(new \DateInterval('P1D')),
            self::WEEKLY => $previousDate->sub(new \DateInterval('P1W')),
            self::MONTHLY => $previousDate->sub(new \DateInterval('P1M')),
            self::YEARLY => $previousDate->sub(new \DateInterval('P1Y')),
            default => throw new InvalidArgumentException("Unknown billing cycle: {$this->cycle}")
        };
    }

    public function getAnnualMultiplier(): float
    {
        return match($this->cycle) {
            self::DAILY => 365,
            self::WEEKLY => 52,
            self::MONTHLY => 12,
            self::YEARLY => 1,
            default => throw new InvalidArgumentException("Unknown billing cycle: {$this->cycle}")
        };
    }

    public function equals(BillingCycle $other): bool
    {
        return $this->cycle === $other->cycle;
    }

    public function __toString(): string
    {
        return $this->cycle;
    }

    public static function monthly(): self
    {
        return new self(self::MONTHLY);
    }

    public static function yearly(): self
    {
        return new self(self::YEARLY);
    }

    public static function weekly(): self
    {
        return new self(self::WEEKLY);
    }

    public static function daily(): self
    {
        return new self(self::DAILY);
    }
}
