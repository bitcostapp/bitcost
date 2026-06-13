<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    /**
     * Determine whether the user can report usage against the task.
     */
    public function report(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    /**
     * Determine whether the user can complete the task.
     */
    public function complete(User $user, Task $task): bool
    {
        return $this->owns($user, $task);
    }

    /**
     * A user owns a task when they created it and it belongs either to no
     * Department or to a Department (Team) they are a member of.
     */
    private function owns(User $user, Task $task): bool
    {
        if ($task->user_id !== $user->id) {
            return false;
        }

        return $task->team_id === null || ($task->team !== null && $user->belongsToTeam($task->team));
    }
}
