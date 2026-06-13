<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Usage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usage>
 */
class UsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'idempotency_key' => (string) Str::uuid(),
            'session' => 'ses_'.fake()->hexColor(),
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-5',
            'variant' => null,
            'tokens_input' => fake()->numberBetween(0, 5000),
            'tokens_output' => fake()->numberBetween(0, 5000),
            'tokens_reasoning' => 0,
            'tokens_cache_read' => 0,
            'tokens_cache_write' => 0,
            'cost_total' => null,
            'is_priced' => false,
        ];
    }
}
