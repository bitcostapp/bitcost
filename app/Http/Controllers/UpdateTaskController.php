<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateTaskController extends Controller
{
    /**
     * Update a task's content. Content is metadata, so it stays editable even
     * after the task is completed (the lock only blocks new usage).
     */
    public function __invoke(Request $request, string $current_team, Task $task): RedirectResponse
    {
        $department = $request->user()->currentTeam;
        $isDepartment = $department && ! $department->is_personal;

        // A task only resolves within its own Department — you cannot edit
        // another Department's task by guessing its id.
        abort_unless($isDepartment && $task->team_id === $department->id, 404);

        $validated = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $task->update(['content' => $validated['content'] ?? null]);

        return back();
    }
}
