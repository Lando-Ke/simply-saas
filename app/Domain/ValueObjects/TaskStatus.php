<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class TaskStatus
{
    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function inProgress(): self
    {
        return new self(self::IN_PROGRESS);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->value === self::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function getDisplayName(): string
    {
        return match($this->value) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            default => ucfirst($this->value),
        };
    }

    public function getColor(): string
    {
        return match($this->value) {
            self::PENDING => 'secondary',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        $allowedTransitions = [
            self::PENDING => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED, self::CANCELLED],
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
        $validStatuses = [self::PENDING, self::IN_PROGRESS, self::COMPLETED, self::CANCELLED];
        
        if (!in_array($value, $validStatuses)) {
            throw new InvalidArgumentException("Invalid task status: {$value}");
        }
    }

    public static function getValidValues(): array
    {
        return [self::PENDING, self::IN_PROGRESS, self::COMPLETED, self::CANCELLED];
    }
}
