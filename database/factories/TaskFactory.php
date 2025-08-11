<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'project_id' => Project::factory(),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'completed_at' => null,
            'estimated_hours' => $this->faker->optional()->randomFloat(2, 0.5, 40),
            'actual_hours' => $this->faker->optional()->randomFloat(2, 0.5, 40),
            'cost' => $this->faker->optional()->randomFloat(2, 10, 1000),
            'tags' => $this->faker->optional()->randomElements(['bug', 'feature', 'urgent', 'documentation', 'testing'], $this->faker->numberBetween(0, 3)),
            'metadata' => [
                'category' => $this->faker->word(),
                'complexity' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
        ];
    }

    /**
     * Indicate that the task is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the task is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the task is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the task is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the task is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Indicate that the task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'status' => $this->faker->randomElement(['pending', 'in_progress']),
        ]);
    }

    /**
     * Indicate that the task has a cost.
     */
    public function withCost(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $amount ?? $this->faker->randomFloat(2, 10, 1000),
        ]);
    }

    /**
     * Indicate that the task has estimated hours.
     */
    public function withEstimatedHours(float $hours = null): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_hours' => $hours ?? $this->faker->randomFloat(2, 0.5, 40),
        ]);
    }

    /**
     * Indicate that the task has actual hours.
     */
    public function withActualHours(float $hours = null): static
    {
        return $this->state(fn (array $attributes) => [
            'actual_hours' => $hours ?? $this->faker->randomFloat(2, 0.5, 40),
        ]);
    }

    /**
     * Indicate that the task has specific tags.
     */
    public function withTags(array $tags): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => $tags,
        ]);
    }
}
