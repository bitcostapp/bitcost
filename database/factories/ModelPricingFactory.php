<?php

namespace Database\Factories;

use App\Models\ModelPricing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModelPricing>
 */
class ModelPricingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'anthropic',
            'model' => fake()->unique()->slug(2),
            'variant' => null,
            'input_price' => 3.0,
            'output_price' => 15.0,
            'cache_read_price' => 0.3,
            'cache_write_price' => 3.75,
            'reasoning_price' => 0.0,
            'currency' => 'USD',
            'is_subscription' => false,
            'effective_from' => null,
            'effective_until' => null,
        ];
    }

    /**
     * Indicate that the pricing is for a flat-fee subscription (notional cost).
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_subscription' => true,
        ]);
    }
}
