<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = Carbon::parse($this->faker->dateTimeBetween('-1 month', 'now'));
        $endTime = $this->faker->optional(0.8)->dateTimeBetween($startTime, '+1 day');
        $durationMinutes = $endTime ? $startTime->diffInMinutes(Carbon::parse($endTime)) : 0;
        $rate = $this->faker->randomFloat(2, 10, 100);
        $cost = $endTime ? ($durationMinutes / 60) * $rate : 0;

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $durationMinutes,
            'description' => $this->faker->optional()->sentence(),
            'rate' => $rate,
            'cost' => $cost,
            'metadata' => [
                'category' => $this->faker->word(),
                'notes' => $this->faker->optional()->paragraph(),
            ],
        ];
    }

    /**
     * Indicate that the time entry is active (running).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'end_time' => null,
            'duration_minutes' => 0,
            'cost' => 0,
        ]);
    }

    /**
     * Indicate that the time entry is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $this->faker->dateTimeBetween('-1 month', '-1 hour'),
            'end_time' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'duration_minutes' => $this->faker->numberBetween(15, 480), // 15 minutes to 8 hours
            'cost' => $this->faker->randomFloat(2, 5, 500),
        ]);
    }

    /**
     * Indicate that the time entry has a specific duration.
     */
    public function withDuration(int $minutes): static
    {
        $startTime = Carbon::parse($this->faker->dateTimeBetween('-1 month', 'now'));
        $endTime = $startTime->copy()->addMinutes($minutes);
        $rate = $this->faker->randomFloat(2, 10, 100);
        $cost = ($minutes / 60) * $rate;

        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $minutes,
            'cost' => $cost,
        ]);
    }

    /**
     * Indicate that the time entry has a specific rate.
     */
    public function withRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => $rate,
        ]);
    }

    /**
     * Indicate that the time entry has a description.
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }
}
