<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

class TimeDuration
{
    private int $minutes;

    public function __construct(int $minutes)
    {
        if ($minutes < 0) {
            throw new InvalidArgumentException("Duration cannot be negative");
        }
        $this->minutes = $minutes;
    }

    public static function fromMinutes(int $minutes): self
    {
        return new self($minutes);
    }

    public static function fromHours(float $hours): self
    {
        return new self((int) ($hours * 60));
    }

    public static function fromHoursAndMinutes(int $hours, int $minutes): self
    {
        return new self(($hours * 60) + $minutes);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function getMinutes(): int
    {
        return $this->minutes;
    }

    public function getHours(): float
    {
        return $this->minutes / 60;
    }

    public function getHoursAndMinutes(): array
    {
        $hours = floor($this->minutes / 60);
        $minutes = $this->minutes % 60;
        
        return [
            'hours' => $hours,
            'minutes' => $minutes,
        ];
    }

    public function getDisplayFormat(): string
    {
        $hoursAndMinutes = $this->getHoursAndMinutes();
        
        if ($hoursAndMinutes['hours'] > 0) {
            return sprintf('%dh %dm', $hoursAndMinutes['hours'], $hoursAndMinutes['minutes']);
        }
        
        return sprintf('%dm', $hoursAndMinutes['minutes']);
    }

    public function getDecimalHours(): float
    {
        return round($this->minutes / 60, 2);
    }

    public function add(self $duration): self
    {
        return new self($this->minutes + $duration->minutes);
    }

    public function subtract(self $duration): self
    {
        $result = $this->minutes - $duration->minutes;
        if ($result < 0) {
            throw new InvalidArgumentException("Cannot subtract larger duration from smaller duration");
        }
        return new self($result);
    }

    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException("Multiplication factor cannot be negative");
        }
        return new self((int) ($this->minutes * $factor));
    }

    public function divide(float $divisor): self
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException("Division divisor must be positive");
        }
        return new self((int) ($this->minutes / $divisor));
    }

    public function isZero(): bool
    {
        return $this->minutes === 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->minutes > $other->minutes;
    }

    public function isLessThan(self $other): bool
    {
        return $this->minutes < $other->minutes;
    }

    public function equals(self $other): bool
    {
        return $this->minutes === $other->minutes;
    }

    public function toArray(): array
    {
        return [
            'minutes' => $this->minutes,
            'hours' => $this->getHours(),
            'display' => $this->getDisplayFormat(),
        ];
    }

    public function __toString(): string
    {
        return $this->getDisplayFormat();
    }
}
