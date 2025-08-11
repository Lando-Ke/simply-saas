<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class ProjectStatus
{
    public const ACTIVE = 'active';
    public const COMPLETED = 'completed';
    public const ON_HOLD = 'on_hold';
    public const CANCELLED = 'cancelled';

    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function onHold(): self
    {
        return new self(self::ON_HOLD);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isOnHold(): bool
    {
        return $this->value === self::ON_HOLD;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function getDisplayName(): string
    {
        return match($this->value) {
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::ON_HOLD => 'On Hold',
            self::CANCELLED => 'Cancelled',
            default => ucfirst($this->value),
        };
    }

    public function getColor(): string
    {
        return match($this->value) {
            self::ACTIVE => 'success',
            self::COMPLETED => 'primary',
            self::ON_HOLD => 'warning',
            self::CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        $allowedTransitions = [
            self::ACTIVE => [self::COMPLETED, self::ON_HOLD, self::CANCELLED],
            self::ON_HOLD => [self::ACTIVE, self::CANCELLED],
            self::COMPLETED => [], // Completed is final state
            self::CANCELLED => [], // Cancelled is final state
        ];

        return in_array($newStatus->getValue(), $allowedTransitions[$this->value] ?? []);
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
        $validStatuses = [self::ACTIVE, self::COMPLETED, self::ON_HOLD, self::CANCELLED];
        
        if (!in_array($value, $validStatuses)) {
            throw new InvalidArgumentException("Invalid project status: {$value}");
        }
    }

    public static function getValidValues(): array
    {
        return [self::ACTIVE, self::COMPLETED, self::ON_HOLD, self::CANCELLED];
    }
}
