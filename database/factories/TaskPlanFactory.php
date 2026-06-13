<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskPlan>
 */
class TaskPlanFactory extends Factory
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
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'source' => 'cli',
            'version' => 1,
        ];
    }
}
