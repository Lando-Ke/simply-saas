<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class TaskPriority
{
    public const LOW = 'low';
    public const MEDIUM = 'medium';
    public const HIGH = 'high';
    public const URGENT = 'urgent';

    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function low(): self
    {
        return new self(self::LOW);
    }

    public static function medium(): self
    {
        return new self(self::MEDIUM);
    }

    public static function high(): self
    {
        return new self(self::HIGH);
    }

    public static function urgent(): self
    {
        return new self(self::URGENT);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isLow(): bool
    {
        return $this->value === self::LOW;
    }

    public function isMedium(): bool
    {
        return $this->value === self::MEDIUM;
    }

    public function isHigh(): bool
    {
        return $this->value === self::HIGH;
    }

    public function isUrgent(): bool
    {
        return $this->value === self::URGENT;
    }

    public function getDisplayName(): string
    {
        return match($this->value) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
            default => ucfirst($this->value),
        };
    }

    public function getColor(): string
    {
        return match($this->value) {
            self::LOW => 'success',
            self::MEDIUM => 'info',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
            default => 'secondary',
        };
    }

    public function getWeight(): int
    {
        return match($this->value) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
            default => 0,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->getWeight() > $other->getWeight();
    }

    public function isLowerThan(self $other): bool
    {
        return $this->getWeight() < $other->getWeight();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(string $value): void
    {
        $validPriorities = [self::LOW, self::MEDIUM, self::HIGH, self::URGENT];
        
        if (!in_array($value, $validPriorities)) {
            throw new InvalidArgumentException("Invalid task priority: {$value}");
        }
    }

    public static function getValidValues(): array
    {
        return [self::LOW, self::MEDIUM, self::HIGH, self::URGENT];
    }
}
