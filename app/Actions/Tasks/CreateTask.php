<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class CreateTask
{
    /**
     * Create a new open task owned by the user and scoped to their Department.
     *
     * @param  array{name: string, content?: string|null, external_url?: string|null, external_provider?: string|null}  $attributes
     */
    public function handle(User $user, array $attributes): Task
    {
        $department = $user->currentTeam;

        return Task::create([
            'team_id' => $department && ! $department->is_personal ? $department->id : null,
            'user_id' => $user->id,
            'name' => $attributes['name'],
            'content' => $attributes['content'] ?? null,
            'status' => TaskStatus::Open,
            'external_url' => $attributes['external_url'] ?? null,
            'external_provider' => $attributes['external_provider'] ?? null,
        ]);
    }
}
