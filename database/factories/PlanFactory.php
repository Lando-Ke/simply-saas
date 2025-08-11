<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plans = [
            'Free' => 0.00,
            'Basic' => 29.99,
            'Premium' => 79.99,
            'Enterprise' => 199.99,
        ];

        $planName = $this->faker->randomElement(array_keys($plans));
        $price = $plans[$planName];

        return [
            'name' => $planName,
            'description' => $this->faker->sentence(),
            'price' => $price,
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'features' => [
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence(),
            ],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the plan is free.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'price' => 0.00,
            'features' => ['Basic features', 'Email support'],
        ]);
    }

    /**
     * Indicate that the plan is basic.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic',
            'price' => 29.99,
            'features' => ['All Free features', 'Priority support', 'Basic analytics'],
        ]);
    }

    /**
     * Indicate that the plan is premium.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium',
            'price' => 79.99,
            'features' => ['All Basic features', 'Advanced analytics', 'API access'],
        ]);
    }

    /**
     * Indicate that the plan is enterprise.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Enterprise',
            'price' => 199.99,
            'features' => ['All Premium features', 'Custom integrations', 'Dedicated support'],
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
