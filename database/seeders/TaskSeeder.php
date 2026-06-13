<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Seed a handful of open demo tasks for the test user so the CLI task
     * picker has something to choose from. Idempotent on (user, name).
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        // Scope to the user's Department (non-personal current team) when present.
        $team = $user->currentTeam;
        $teamId = $team && ! $team->is_personal ? $team->id : null;

        $names = [
            'Implement device login flow',
            'Fix payment webhook retries',
            'Refactor session store',
            'Add usage reporting to CLI',
            'Investigate slow dashboard queries',
        ];

        foreach ($names as $name) {
            Task::firstOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                [
                    'team_id' => $teamId,
                    'status' => TaskStatus::Open,
                ],
            );
        }
    }
}
