<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDashboardSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Departments to seed, each with the number of users and the model mix
     * they tend to lean on. Heavier models drive higher cost per token.
     *
     * @var array<int, array{name: string, users: int}>
     */
    private const DEPARTMENTS = [
        ['name' => 'Engineering', 'users' => 6],
        ['name' => 'Data Science', 'users' => 4],
        ['name' => 'Marketing', 'users' => 5],
        ['name' => 'Sales', 'users' => 4],
        ['name' => 'Customer Support', 'users' => 5],
    ];

    /**
     * Per-million-token prices keyed by model, used to derive a realistic
     * cost for each seeded usage row.
     *
     * @var array<string, array{input: float, output: float}>
     */
    private const MODEL_PRICES = [
        'claude-opus-4-8' => ['input' => 15.0, 'output' => 75.0],
        'claude-sonnet-4-6' => ['input' => 3.0, 'output' => 15.0],
        'claude-haiku-4-5' => ['input' => 0.8, 'output' => 4.0],
        'openai/gpt-5.4' => ['input' => 5.0, 'output' => 20.0],
    ];

    /**
     * @var array<int, string>
     */
    private const TASK_NAMES = [
        'Draft Q3 launch announcement',
        'Summarise customer interviews',
        'Refactor billing service',
        'Triage inbound support tickets',
        'Build churn prediction model',
        'Write API integration guide',
        'Generate ad creative variants',
        'Investigate latency regression',
        'Qualify enterprise leads',
        'Migrate legacy auth flow',
        'Analyse funnel drop-off',
        'Compose weekly newsletter',
    ];

    /**
     * Seed several departments, each with multiple members, tasks, and
     * priced usage turns so every dashboard widget has rich data to show.
     */
    public function run(): void
    {
        foreach (self::DEPARTMENTS as $config) {
            $department = Team::firstOrCreate(
                ['slug' => Str::slug($config['name'])],
                ['name' => $config['name'], 'is_personal' => false],
            );

            for ($i = 0; $i < $config['users']; $i++) {
                $user = User::factory()->create();

                $department->members()->syncWithoutDetaching([
                    $user->id => ['role' => $i === 0 ? TeamRole::Owner->value : TeamRole::Member->value],
                ]);

                $user->update(['current_team_id' => $department->id]);

                $this->seedTasksFor($user, $department);
            }
        }
    }

    /**
     * Create a few tasks for the user, each with several priced usage turns.
     */
    private function seedTasksFor(User $user, Team $department): void
    {
        $taskCount = random_int(2, 4);

        for ($t = 0; $t < $taskCount; $t++) {
            $isCompleted = fake()->boolean(40);

            $task = Task::create([
                'team_id' => $department->id,
                'user_id' => $user->id,
                'name' => self::TASK_NAMES[array_rand(self::TASK_NAMES)].' #'.random_int(100, 999),
                'content' => fake()->paragraphs(random_int(1, 3), true),
                'status' => $isCompleted ? TaskStatus::Completed : TaskStatus::Open,
                'completed_at' => $isCompleted ? now()->subDays(random_int(0, 20)) : null,
            ]);

            $turns = random_int(3, 10);

            for ($u = 0; $u < $turns; $u++) {
                $this->seedUsageFor($task);
            }
        }
    }

    /**
     * Create one priced usage turn with realistic token and cost figures.
     */
    private function seedUsageFor(Task $task): void
    {
        $model = array_rand(self::MODEL_PRICES);
        $prices = self::MODEL_PRICES[$model];

        $tokensInput = random_int(800, 40000);
        $tokensOutput = random_int(200, 12000);
        $tokensCacheRead = random_int(0, 30000);
        $tokensReasoning = str_contains($model, 'opus') ? random_int(0, 8000) : 0;

        $cost = ($tokensInput + $tokensCacheRead) / 1_000_000 * $prices['input']
            + ($tokensOutput + $tokensReasoning) / 1_000_000 * $prices['output'];

        Usage::create([
            'task_id' => $task->id,
            'idempotency_key' => (string) Str::uuid(),
            'session' => 'ses_'.Str::lower(Str::random(8)),
            'provider' => str_contains($model, '/') ? 'openai' : 'anthropic',
            'model' => $model,
            'tokens_input' => $tokensInput,
            'tokens_output' => $tokensOutput,
            'tokens_reasoning' => $tokensReasoning,
            'tokens_cache_read' => $tokensCacheRead,
            'tokens_cache_write' => 0,
            'cost_total' => round($cost, 6),
            'currency' => 'USD',
            'is_priced' => true,
            'cost_source' => 'seed',
            'reported_at' => now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1440)),
        ]);
    }
}
