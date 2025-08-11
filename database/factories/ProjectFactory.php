<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(3);
        
        return [
            'name' => $name,
            'description' => $this->faker->paragraph(),
            'slug' => Str::slug($name),
            'status' => $this->faker->randomElement(['active', 'completed', 'on_hold', 'cancelled']),
            'user_id' => User::factory(),
            'tenant_id' => null,
            'settings' => [
                'notifications_enabled' => $this->faker->boolean(),
                'auto_assign_tasks' => $this->faker->boolean(),
            ],
            'metadata' => [
                'category' => $this->faker->word(),
                'tags' => $this->faker->words(3),
            ],
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'budget' => $this->faker->optional()->randomFloat(2, 100, 10000),
            'is_public' => $this->faker->boolean(20),
            'is_featured' => $this->faker->boolean(10),
        ];
    }

    /**
     * Indicate that the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the project is on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_hold',
        ]);
    }

    /**
     * Indicate that the project is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the project is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the project has a budget.
     */
    public function withBudget(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'budget' => $amount ?? $this->faker->randomFloat(2, 100, 10000),
        ]);
    }

    /**
     * Indicate that the project has a timeline.
     */
    public function withTimeline(): static
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+6 months');
        
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
